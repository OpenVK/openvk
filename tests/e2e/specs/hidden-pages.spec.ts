import { execSync } from 'child_process';
import { test, expect } from '../fixtures.js';
import { loginAsAdmin, loginAsCharlie } from '../helpers.js';

const dbHost = process.env.DB_HOST || 'mariadb-primary';
const dbUser = process.env.DB_USER || 'openvk';
const dbPass = process.env.DB_PASSWORD || 'openvk';
const dbName = process.env.DB_NAME || 'db';
const mysql = `mysql -h ${dbHost} -u ${dbUser} ${dbName} --ssl-mode=DISABLED --default-character-set=utf8mb4`;
const mysqlValue = `${mysql} -N -B`;

function sql(query: string): void {
  execSync(mysql, { input: query, env: { ...process.env, MYSQL_PWD: dbPass } });
}

function sqlValue(query: string): string {
  return execSync(mysqlValue, { input: query, env: { ...process.env, MYSQL_PWD: dbPass } }).toString().trim();
}

const ALICE_ID = 2;
const BOB_ID = 3;

function banAlicePermanently(): void {
  sql(`INSERT INTO bans (id, user, initiator, iat, exp, time, reason, removed_manually, removed_by) VALUES
       (2, ${ALICE_ID}, 1, UNIX_TIMESTAMP(), 0, 0, 'Spamming', 0, 0);
       UPDATE profiles SET block_reason = 2 WHERE id = ${ALICE_ID}`);
}

test.describe('Hidden pages', () => {
  test('admin sees a closed profile', async ({ page }) => {
    sql(`UPDATE profiles SET profile_type = 1 WHERE id = ${ALICE_ID}`);

    await loginAsAdmin(page);
    await page.goto('/id2');

    await expect(page.locator('.moderation-banner')).toContainText('Hidden page preview');
    await expect(page.locator('.moderation-banner')).toContainText('This profile is closed');
    await expect(page.locator('.moderation-banner')).toContainText('friend request');
    await expect(page.locator('.profileName h2')).toContainText('Alice');
    await expect(page.locator('a[href="/im?sel=2"]')).toHaveCount(0);

    const addFriend = page.locator('form[action="/setSub/user"] input[type="submit"]');
    await expect(addFriend).toHaveValue('Add to friends');
    await addFriend.click();
    await expect(page.locator('form[action="/setSub/user"] input[type="submit"]')).toHaveValue('Cancel request');
  });

  test('shows the closed profile moderation preview', async ({ page }) => {
    sql(`UPDATE profiles SET profile_type = 1 WHERE id = ${ALICE_ID}`);

    await loginAsAdmin(page);
    await page.goto('/id2');

    await expect(page.locator('.page_body')).toHaveScreenshot('hidden-pages-closed-admin.png', {
      maxDiffPixels: 200,
      mask: [
        page.locator('section.footer-body').filter({ hasText: /Altair/ }),
        page.locator('.fictional_profile_section').filter({ hasText: /Audios/ }),
        page.locator('#basicInfo tr').filter({ hasText: /Birth date/ }),
      ],
    });
  });

  test('regular user sees the closed profile stub', async ({ page }) => {
    sql(`UPDATE profiles SET profile_type = 1 WHERE id = ${ALICE_ID}`);

    await loginAsCharlie(page);
    await page.goto('/id2');

    await expect(page.locator('.moderation-banner')).toHaveCount(0);
    await expect(page.getByText(/limited access to/)).toBeVisible();
    await expect(page.locator('form[action="/setSub/user"]')).toBeVisible();
  });

  test('admin sees a banned profile and its unban action', async ({ page }) => {
    banAlicePermanently();

    await loginAsAdmin(page);
    await page.goto('/id2');

    await expect(page.locator('.moderation-banner')).toContainText('This user is banned');
    await expect(page.locator('.profileName h2')).toContainText('Alice');
    await expect(page.locator('#unbanHiddenProfile')).toBeVisible();
    await expect(page.locator('#restoreHiddenProfile')).toHaveCount(0);
    await expect(page.locator('form[action="/setSub/user"]')).toHaveCount(0);
  });

  test('shows the banned profile moderation preview', async ({ page }) => {
    banAlicePermanently();

    await loginAsAdmin(page);
    await page.goto('/id2');

    await expect(page.locator('.page_body')).toHaveScreenshot('hidden-pages-banned-admin.png', {
      maxDiffPixels: 200,
      mask: [
        page.locator('section.footer-body').filter({ hasText: /Altair/ }),
        page.locator('.fictional_profile_section').filter({ hasText: /Audios/ }),
        page.locator('#basicInfo tr').filter({ hasText: /Birth date/ }),
      ],
    });
  });

  test('admin sees a deleted profile without restore action', async ({ page }) => {
    sql(`UPDATE profiles SET deleted = 1, deact_date = 0 WHERE id = ${ALICE_ID}`);

    await loginAsAdmin(page);
    await page.goto('/id2');

    await expect(page.locator('.moderation-banner')).toContainText('This page is deleted');
    await expect(page.locator('.profileName h2')).toContainText('DELETED');
    await expect(page.getByText('Hello world! This is my very first post')).toBeVisible();
    await expect(page.locator('#restoreHiddenProfile')).toHaveCount(0);
    await expect(page.locator('form[action="/setSub/user"]')).toHaveCount(0);
  });

  test('regular user sees the deleted profile stub', async ({ page }) => {
    sql(`UPDATE profiles SET deleted = 1, deact_date = 0 WHERE id = ${ALICE_ID}`);

    await loginAsCharlie(page);
    await page.goto('/id2');

    await expect(page.locator('.moderation-banner')).toHaveCount(0);
    await expect(page.getByText(/Either this profile has been deleted/)).toBeVisible();
  });

  test('admin restores a deactivated profile', async ({ page }) => {
    sql(`UPDATE profiles SET deleted = 1, deact_date = UNIX_TIMESTAMP() + 86400 WHERE id = ${ALICE_ID}`);

    await loginAsAdmin(page);
    await page.goto('/id2');

    await expect(page.locator('.moderation-banner')).toContainText('This page is deactivated');
    await expect(page.locator('#restoreHiddenProfile')).toBeVisible();

    await page.locator('#restoreHiddenProfile').click();
    await page.locator('.ovk-diag-action button', { hasText: 'OK' }).click();

    await expect(page.locator('.moderation-banner')).toHaveCount(0);
    await expect(page.locator('.profileName h2')).toContainText('Alice');
  });

  test('admin sees a blacklisted open profile as read-only', async ({ page }) => {
    sql(`INSERT INTO blacklist_relations (author, target, created) VALUES (${ALICE_ID}, 1, UNIX_TIMESTAMP())`);

    await loginAsAdmin(page);
    await page.goto('/id2');

    await expect(page.locator('.moderation-banner')).toContainText('This user has restricted access to their page (blacklist).');
    await expect(page.locator('.profileName h2')).toContainText('Alice');
    await expect(page.locator('a[href="/im?sel=2"]')).toHaveCount(0);
    await expect(page.locator('form[action="/setSub/user"]')).toHaveCount(0);
  });

  test('admin sees the correct banner when they blacklisted the user', async ({ page }) => {
    sql(`INSERT INTO blacklist_relations (author, target, created) VALUES (1, ${ALICE_ID}, UNIX_TIMESTAMP())`);

    await loginAsAdmin(page);
    await page.goto('/id2');

    await expect(page.locator('.moderation-banner')).toContainText('You have blacklisted this user.');
  });

  test('admin user list shows deleted users only with the filter', async ({ page }) => {
    sql(`UPDATE profiles SET deleted = 1, deact_date = 0 WHERE id = ${ALICE_ID}`);

    await loginAsAdmin(page);
    await page.goto('/admin/users');

    await expect(page.locator('a[href="/alice"]')).toHaveCount(0);

    await page.goto('/admin/users?deleted=1');

    const row = page.locator('tr', { has: page.locator('a[href="/alice"]') });
    await expect(row).toBeVisible();
    await expect(row.getByText('Deleted', { exact: true })).toBeVisible();
  });

  test('admin who is a mutual friend of a closed profile keeps normal access', async ({ page }) => {
    sql(`UPDATE profiles SET profile_type = 1 WHERE id = ${ALICE_ID};
         INSERT INTO subscriptions (follower, model, target) VALUES
         (1, 'openvk\\\\Web\\\\Models\\\\Entities\\\\User', 2),
         (2, 'openvk\\\\Web\\\\Models\\\\Entities\\\\User', 1)`);

    await loginAsAdmin(page);
    await page.goto('/id2');

    await expect(page.locator('.moderation-banner')).toHaveCount(0);
    await expect(page.locator('.profileName h2')).toContainText('Alice');
    await expect(page.locator('a[href="/im?sel=2"]')).toBeVisible();
    await expect(page.locator('form[action="/setSub/user"]')).toBeVisible();
  });

  test('admin sees photos and videos of a deleted profile', async ({ page }) => {
    sql(`UPDATE profiles SET deleted = 1, deact_date = 0 WHERE id = ${ALICE_ID}`);

    await loginAsAdmin(page);
    await page.goto('/photo2_1');
    await expect(page.getByText('Sunset at the beach')).toBeVisible();
    await expect(page.locator('.post-like-button')).toHaveCount(0);

    await page.goto('/video2_1');
    await expect(page.locator('.ovk-vid-details b', { hasText: 'Welcome Video' })).toBeVisible();
    await expect(page.locator('.post-like-button')).toHaveCount(0);
  });

  test('admin sees notes of a deleted profile', async ({ page }) => {
    sql(`UPDATE profiles SET deleted = 1, deact_date = 0 WHERE id = ${ALICE_ID}`);

    await loginAsAdmin(page);
    await page.goto('/note2_1');

    await expect(page.getByText('This is the content of my first note on OpenVK.')).toBeVisible();
  });

  test('polls on a hidden profile are read-only', async ({ page }) => {
    sql(`UPDATE profiles SET deleted = 1, deact_date = 0 WHERE id = ${ALICE_ID}`);
    sql("INSERT INTO attachments (attachable_type, attachable_id, target_type, target_id, `index`) VALUES ('openvk\\\\Web\\\\Models\\\\Entities\\\\Poll', 1, 'openvk\\\\Web\\\\Models\\\\Entities\\\\Post', 1, 0)");

    await loginAsAdmin(page);
    await page.goto('/id2');

    await expect(page.getByText('What is your favorite programming language?')).toBeVisible();
    await expect(page.locator('.poll form')).toHaveCount(0);
    await expect(page.locator('.poll-results')).toBeVisible();
  });

  test('viewHidden bypasses info privacy on an open profile', async ({ page }) => {
    sql(`UPDATE profiles SET privacy = 7493989779936116736 WHERE id = ${ALICE_ID}`);

    await loginAsAdmin(page);
    await page.goto('/id2');

    await expect(page.locator('.moderation-banner')).toHaveCount(0);
    await expect(page.locator('.profileName h2')).toContainText('Alice');
    await expect(page.locator('#basicInfo')).toBeVisible();
  });

  test('regular user cannot bypass info privacy on an open profile', async ({ page }) => {
    sql(`UPDATE profiles SET privacy = 7493989779936116736 WHERE id = ${ALICE_ID}`);

    await loginAsCharlie(page);
    await page.goto('/id2');

    await expect(page.locator('#basicInfo')).toHaveCount(0);
  });

  test('viewHidden bypasses photos privacy on an open profile', async ({ page }) => {
    sql(`UPDATE profiles SET privacy = 9115285645789495296 WHERE id = ${ALICE_ID}`);

    await loginAsAdmin(page);
    await page.goto('/photo2_1');

    await expect(page.getByText('Sunset at the beach')).toBeVisible();
  });

  test('viewHidden does not bypass write privacy on a normal profile', async ({ page }) => {
    sql(`UPDATE profiles SET privacy = 9223345648567320576 WHERE id = ${ALICE_ID}`);

    await loginAsAdmin(page);
    await page.goto('/id2');

    await expect(page.locator('.profileName h2')).toContainText('Alice');
    await expect(page.locator('a[href="/im?sel=2"]')).toHaveCount(0);
  });

  test('cannot subscribe to a hidden user without an explicit add', async ({ page }) => {
    sql(`UPDATE profiles SET deleted = 1, deact_date = 0 WHERE id = ${BOB_ID}`);

    await loginAsCharlie(page);

    await page.request.post('/setSub/user', { form: { id: String(BOB_ID) } });
    expect(sqlValue(`SELECT COUNT(*) FROM subscriptions WHERE follower = 4 AND target = ${BOB_ID}`)).toBe('0');

    await page.request.post('/setSub/user', { form: { id: String(BOB_ID), act: 'add' } });
    expect(sqlValue(`SELECT COUNT(*) FROM subscriptions WHERE follower = 4 AND target = ${BOB_ID}`)).toBe('0');
  });

  test('hidden subscriptions do not leak posts into the local feed', async ({ page }) => {
    sql(`UPDATE profiles SET deleted = 1, deact_date = 0 WHERE id = ${BOB_ID}`);
    sql(`INSERT INTO subscriptions (follower, model, target) VALUES (4, 'openvk\\\\Web\\\\Models\\\\Entities\\\\User', ${BOB_ID})`);

    await loginAsCharlie(page);
    await page.goto('/feed');

    await expect(page.getByText('Hey everyone! Bob here. Just joined this cool network.')).toHaveCount(0);
  });

  test('admin feed does not include hidden subscriptions', async ({ page }) => {
    sql(`UPDATE profiles SET deleted = 1, deact_date = 0 WHERE id = ${BOB_ID}`);
    sql(`INSERT INTO subscriptions (follower, model, target) VALUES (1, 'openvk\\\\Web\\\\Models\\\\Entities\\\\User', ${BOB_ID})`);

    await loginAsAdmin(page);
    await page.goto('/feed');

    await expect(page.getByText('Hey everyone! Bob here. Just joined this cool network.')).toHaveCount(0);
  });

  test('audio controls are read-only on a hidden profile', async ({ page }) => {
    sql(`UPDATE profiles SET deleted = 1, deact_date = 0 WHERE id = ${ALICE_ID}`);

    await loginAsAdmin(page);
    await page.goto('/audios2');

    await expect(page.locator('.audioEmbed[data-readonly="1"]').first()).toBeVisible();
    await expect(page.locator('.add-icon')).toHaveCount(0);
    await expect(page.locator('.download-icon')).toHaveCount(0);

    const token = await page.locator('meta[name="csrf"]').getAttribute('value');
    const response = await page.request.post('/audio1/action?act=add', { form: { hash: token ?? '' } });
    const body = await response.json();

    expect(body.flash.message).toBe('Access denied');
  });
});

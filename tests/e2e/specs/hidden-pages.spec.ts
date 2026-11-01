import { execSync } from 'child_process';
import { test, expect } from '../fixtures.js';
import { loginAsAdmin, loginAsCharlie } from '../helpers.js';

const dbHost = process.env.DB_HOST || 'mariadb-primary';
const dbUser = process.env.DB_USER || 'openvk';
const dbPass = process.env.DB_PASSWORD || 'openvk';
const dbName = process.env.DB_NAME || 'db';
const mysql = `mysql -h ${dbHost} -u ${dbUser} ${dbName} --ssl-mode=DISABLED --default-character-set=utf8mb4`;

function sql(query: string): void {
  execSync(mysql, { input: query, env: { ...process.env, MYSQL_PWD: dbPass } });
}

const ALICE_ID = 2;

test.describe('Hidden pages', () => {
  test('admin sees a closed profile', async ({ page }) => {
    sql(`UPDATE profiles SET profile_type = 1 WHERE id = ${ALICE_ID}`);

    await loginAsAdmin(page);
    await page.goto('/id2');

    await expect(page.locator('.moderation-banner')).toContainText('Hidden page preview');
    await expect(page.locator('.moderation-banner')).toContainText('This profile is closed');
    await expect(page.locator('.profileName h2')).toContainText('Alice');
    await expect(page.locator('a[href="/im?sel=2"]')).toHaveCount(0);
  });

  test('regular user sees the closed profile stub', async ({ page }) => {
    sql(`UPDATE profiles SET profile_type = 1 WHERE id = ${ALICE_ID}`);

    await loginAsCharlie(page);
    await page.goto('/id2');

    await expect(page.locator('.moderation-banner')).toHaveCount(0);
    await expect(page.getByText(/limited access to/)).toBeVisible();
  });

  test('admin sees a banned profile and its unban action', async ({ page }) => {
    sql(`UPDATE profiles SET block_reason = 1 WHERE id = ${ALICE_ID}`);

    await loginAsAdmin(page);
    await page.goto('/id2');

    await expect(page.locator('.moderation-banner')).toContainText('This user is banned');
    await expect(page.locator('.profileName h2')).toContainText('Alice');
    await expect(page.locator('#unbanHiddenProfile')).toBeVisible();
    await expect(page.locator('#restoreHiddenProfile')).toHaveCount(0);
  });

  test('admin sees a deleted profile without restore action', async ({ page }) => {
    sql(`UPDATE profiles SET deleted = 1, deact_date = 0 WHERE id = ${ALICE_ID}`);

    await loginAsAdmin(page);
    await page.goto('/id2');

    await expect(page.locator('.moderation-banner')).toContainText('This page is deleted');
    await expect(page.locator('.profileName h2')).toContainText('DELETED');
    await expect(page.getByText('Hello world! This is my very first post')).toBeVisible();
    await expect(page.locator('#restoreHiddenProfile')).toHaveCount(0);
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

    await expect(page.locator('.moderation-banner')).toContainText('blacklist');
    await expect(page.locator('.profileName h2')).toContainText('Alice');
    await expect(page.locator('a[href="/im?sel=2"]')).toHaveCount(0);
    await expect(page.locator('form[action="/setSub/user"]')).toHaveCount(0);
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
});

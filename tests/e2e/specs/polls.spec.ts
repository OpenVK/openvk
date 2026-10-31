import { test, expect } from '../fixtures.js';
import { loginAsAlice } from '../helpers.js';
import { execFileSync } from 'child_process';

test.describe('Polls', () => {
  test.beforeEach(async ({ page }) => {
    await loginAsAlice(page);
  });

  test('shows active poll view', async ({ page }) => {
    await page.goto('/poll1');
    await expect(page.locator('.poll')).toHaveScreenshot('poll-active.png', { maxDiffPixels: 200 });
  });

  test('shows ended poll view', async ({ page }) => {
    await page.goto('/poll2');
    await expect(page.locator('.poll')).toHaveScreenshot('poll-ended.png', { maxDiffPixels: 200 });
  });

  test('ignores deleted parent posts and works again after restoration', async ({ page }) => {
    const postModel = Buffer.from('openvk\\Web\\Models\\Entities\\Post').toString('hex');
    const pollModel = Buffer.from('openvk\\Web\\Models\\Entities\\Poll').toString('hex');
    const values = [2, 3].map(id => `(UNHEX('${postModel}'), ${id}, UNHEX('${pollModel}'), 1)`).join(',');
    execFileSync('mysql', [
      '-h', process.env.DB_HOST || 'mariadb-primary',
      '-u', process.env.DB_USER || 'openvk',
      '--ssl-mode=DISABLED',
      process.env.DB_NAME || 'db',
      '-e', `INSERT INTO attachments (target_type, target_id, attachable_type, attachable_id) VALUES ${values}`,
    ], { env: { ...process.env, MYSQL_PWD: process.env.DB_PASSWORD || 'openvk' } });

    const pollHasOptions = async () => (await (await page.request.get('/poll1')).text()).includes('class="poll-options"');
    await page.goto('/wall2');
    expect(await pollHasOptions()).toBe(true);

    await page.locator('.post_microblog[data-id="2_2"] a[href="/wall2_2/delete"]').click();
    await expect(page.locator('.post-deleted')).toHaveCount(1);
    expect(await pollHasOptions()).toBe(true);

    await page.locator('.post_microblog[data-id="2_3"] a[href="/wall2_3/delete"]').click();
    await expect(page.locator('.post-deleted')).toHaveCount(2);
    expect(await pollHasOptions()).toBe(false);

    await page.locator('.post-deleted').last().getByText('Restore').click();
    await expect(page.locator('.post-deleted')).toHaveCount(1);
    expect(await pollHasOptions()).toBe(true);
  });
});

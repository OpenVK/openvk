import { test, expect } from '../fixtures.js';
import { loginAsAlice } from '../helpers.js';

test.describe('Wall', () => {
  test.beforeEach(async ({ page }) => {
    await loginAsAlice(page);
  });

  test('shows global feed', async ({ page }) => {
    await page.goto('/feed/all');
    await expect(page.locator('.page_body')).toHaveScreenshot('feed-all.png', { maxDiffPixels: 200 });
  });

  test('feed shows posts from friends', async ({ page }) => {
    await page.goto('/feed');
    await expect(page.locator('text=Hello world! This is my very first post on OpenVK')).toBeVisible();
    await expect(page.locator('.page_body')).toHaveScreenshot('feed-friends.png', { maxDiffPixels: 200 });
  });

  test('user wall shows all posts', async ({ page }) => {
    await page.goto('/wall2');
    await expect(page.locator('text=Pinned announcement: I am now accepting friend requests!')).toBeVisible();
    await expect(page.locator('.page_body')).toHaveScreenshot('wall-posts.png', { maxDiffPixels: 200 });
  });

  test('wall owner can archive and restore a post', async ({ page }) => {
    const postText = 'Hello world! This is my very first post on OpenVK';

    await page.goto('/wall2');
    await page.locator('table.post[data-id="2_1"] .archive').click();
    await page.waitForURL('/wall2?type=archived');
    await expect(page.getByText(postText, { exact: false })).toBeVisible();

    await page.goto('/wall2');
    await expect(page.getByText(postText, { exact: false })).toHaveCount(0);

    await page.goto('/wall2?type=archived');
    await page.locator('table.post[data-id="2_1"] .archive.restore').click();
    await page.waitForURL('/wall2');
    await expect(page.getByText(postText, { exact: false })).toBeVisible();
  });
});

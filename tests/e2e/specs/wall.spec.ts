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
});

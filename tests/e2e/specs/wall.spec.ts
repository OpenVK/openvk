import { test, expect } from '@playwright/test';
import { loginAsAlice } from '../helpers.js';

test.describe('Wall', () => {
  test.beforeEach(async ({ page }) => {
    await loginAsAlice(page);
  });

  test('feed shows posts from friends', async ({ page }) => {
    await page.goto('/feed');
    await expect(page.locator('text=Hello world! This is my very first post on OpenVK')).toBeVisible();
    await expect(page.locator('.page_body')).toHaveScreenshot('feed-friends.png');
  });

  test('user wall shows all posts', async ({ page }) => {
    await page.goto('/wall2');
    await expect(page.locator('text=Pinned announcement: I am now accepting friend requests!')).toBeVisible();
    await expect(page.locator('.page_body')).toHaveScreenshot('wall-posts.png');
  });
});

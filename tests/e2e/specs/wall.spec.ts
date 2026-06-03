import { test, expect } from '@playwright/test';
import { acceptCookies } from '../helpers.js';

test.describe('Wall', () => {
  test.beforeEach(async ({ page }) => {
    await acceptCookies(page);
    await page.goto('/login');
    await page.fill('#fastLogin input[name="login"]', 'alice@test.local');
    await page.fill('#fastLogin input[name="password"]', 'test123');
    await page.click('#fastLogin input[type="submit"]');
    await page.waitForURL(/\/alice/);
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

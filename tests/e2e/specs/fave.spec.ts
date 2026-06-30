import { test, expect } from '../fixtures.js';
import { loginAsAlice } from '../helpers.js';

test.describe('Favorites', () => {
  test.beforeEach(async ({ page }) => {
    await loginAsAlice(page);
  });

  test('shows favorites page', async ({ page }) => {
    await page.goto('/fave');
    await expect(page.locator('.page_body')).toHaveScreenshot('fave.png');
  });

  test('shows favorite comments', async ({ page }) => {
    await page.goto('/fave?section=comments');
    await expect(page.locator('.page_body')).toHaveScreenshot('fave-comments.png');
  });

  test('shows favorite photos', async ({ page }) => {
    await page.goto('/fave?section=photos');
    await expect(page.locator('.page_body')).toHaveScreenshot('fave-photos.png');
  });

  test('shows favorite videos', async ({ page }) => {
    await page.goto('/fave?section=videos');
    await expect(page.locator('.page_body')).toHaveScreenshot('fave-videos.png');
  });
});

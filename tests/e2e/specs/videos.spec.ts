import { test, expect } from '../fixtures.js';
import { loginAsAlice } from '../helpers.js';

test.describe('Videos', () => {
  test.beforeEach(async ({ page }) => {
    await loginAsAlice(page);
  });

  test('shows videos page', async ({ page }) => {
    await page.goto('/videos2');
    await expect(page.locator('.page_body')).toHaveScreenshot('videos2.png', { maxDiffPixels: 200 });
  });

  test('shows video view', async ({ page }) => {
    await page.goto('/video2_1');
    await expect(page.locator('.page_body')).toHaveScreenshot('video2_1.png', {
      maxDiffPixels: 200,
      mask: [page.locator('.page_footer p').filter({ hasText: /Altair/ })],
    });
  });

  test('shows upload video form', async ({ page }) => {
    await page.goto('/videos/upload');
    await expect(page.locator('.page_body')).toHaveScreenshot('video-upload.png', { maxDiffPixels: 200 });
  });

  test('shows edit video page', async ({ page }) => {
    await page.goto('/video2_1/edit');
    await expect(page.locator('.page_body')).toHaveScreenshot('video2_1-edit.png', { maxDiffPixels: 200 });
  });
});

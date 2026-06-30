import { test, expect } from '../fixtures.js';
import { loginAsAlice } from '../helpers.js';

test.describe('Photos', () => {
  test.beforeEach(async ({ page }) => {
    await loginAsAlice(page);
  });

  test('shows photo albums', async ({ page }) => {
    await page.goto('/albums2');
    await page.evaluate(() => {
      for (const el of document.querySelectorAll('span[style*="color: grey"]')) {
        if (el.textContent?.match(/Обновлено|Updated|Aktualizacja/i))
          el.textContent = 'Обновлено [УДАЛЕНО]';
      }
    });
    await expect(page.locator('.page_body')).toHaveScreenshot('albums2.png');
  });

  test('shows album view', async ({ page }) => {
    await page.goto('/album2_1');
    await expect(page.locator('.page_body')).toHaveScreenshot('album2_1.png');
  });

  test('shows edit album page', async ({ page }) => {
    await page.goto('/album2_1/edit');
    await expect(page.locator('.page_body')).toHaveScreenshot('album2_1-edit.png');
  });

  test('shows photo view', async ({ page }) => {
    await page.goto('/photo2_1');
    await expect(page.locator('.page_body')).toHaveScreenshot('photo2_1.png', {
      mask: [page.locator('.page_footer p').filter({ hasText: /Altair/ })],
    });
  });

  test('shows edit photo page', async ({ page }) => {
    await page.goto('/photo2_1/edit');
    await expect(page.locator('.page_body')).toHaveScreenshot('photo-edit.png');
  });

  test('shows upload photo form', async ({ page }) => {
    await page.goto('/photos/upload?album=2_1');
    await expect(page.locator('.page_body')).toHaveScreenshot('photo-upload.png');
  });

  test('shows create album form', async ({ page }) => {
    await page.goto('/albums/create');
    await expect(page.locator('.page_body')).toHaveScreenshot('album-create.png');
  });
});

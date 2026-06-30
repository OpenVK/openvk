import { test, expect } from '../fixtures.js';
import { loginAsAlice } from '../helpers.js';

test.describe('Topics', () => {
  test.beforeEach(async ({ page }) => {
    await loginAsAlice(page);
  });

  test('shows board view', async ({ page }) => {
    await page.goto('/board1');
    await expect(page.locator('.page_body')).toHaveScreenshot('board1.png', { maxDiffPixels: 200 });
  });

  test('shows topic view', async ({ page }) => {
    await page.goto('/topic1_1');
    await expect(page.locator('.page_body')).toHaveScreenshot('topic1_1.png', {
      maxDiffPixels: 200,
      mask: [page.locator('.page_footer p').filter({ hasText: /Altair/ })],
    });
  });

  test('shows create topic form', async ({ page }) => {
    await page.goto('/board1/create');
    await expect(page.locator('.page_body')).toHaveScreenshot('board-create-topic.png', { maxDiffPixels: 200 });
  });
});

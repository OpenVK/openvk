import { test, expect } from '../fixtures.js';
import { acceptCookies } from '../helpers.js';

test.describe('Auth', () => {
  test.beforeEach(async ({ page }) => {
    await acceptCookies(page);
  });

  test('shows password restore form', async ({ page }) => {
    await page.goto('/restore');
    await expect(page.locator('.page_body')).toHaveScreenshot('restore.png', { maxDiffPixels: 200 });
  });
});

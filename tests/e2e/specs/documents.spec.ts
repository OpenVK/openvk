import { test, expect } from '../fixtures.js';
import { loginAsAlice } from '../helpers.js';

test.describe('Documents', () => {
  test.beforeEach(async ({ page }) => {
    await loginAsAlice(page);
  });

  test('shows documents page', async ({ page }) => {
    await page.goto('/docs');
    await expect(page.locator('.page_body')).toHaveScreenshot('docs.png', { maxDiffPixels: 200 });
  });
});

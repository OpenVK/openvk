import { test, expect } from '../fixtures.js';
import { loginAsAdmin } from '../helpers.js';

test.describe('NoSpam admin tool', () => {
  test.beforeEach(async ({ page }) => {
    await loginAsAdmin(page);
  });

  test('shows noSpam form', async ({ page }) => {
    await page.goto('/noSpam');
    await expect(page.locator('.page_body')).toHaveScreenshot('nospam-form.png', { maxDiffPixels: 200 });
  });
});

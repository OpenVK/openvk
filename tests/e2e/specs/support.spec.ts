import { test, expect } from '../fixtures.js';
import { loginAsAlice } from '../helpers.js';

test.describe('Support', () => {
  test.beforeEach(async ({ page }) => {
    await loginAsAlice(page);
  });

  test('shows support index with faq', async ({ page }) => {
    await page.goto('/support');
    await expect(page.locator('.page_body')).toHaveScreenshot('support-index.png');
  });
});

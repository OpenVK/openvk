import { test, expect } from '../fixtures.js';
import { loginAsAlice } from '../helpers.js';

test.describe('Messenger', () => {
  test.beforeEach(async ({ page }) => {
    await loginAsAlice(page);
  });

  test('shows messages page', async ({ page }) => {
    await page.goto('/im');
    await expect(page.locator('.page_body')).toHaveScreenshot('im.png', { maxDiffPixels: 200 });
  });
});

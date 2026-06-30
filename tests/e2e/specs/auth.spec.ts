import { test, expect } from '../fixtures.js';
import { acceptCookies } from '../helpers.js';

// Password restore is disabled in test config (disablePasswordRestoring: true).
// The route returns 404 when disabled, so there's nothing to screenshot.
// If the config changes, un-skip and test /restore.
test.describe('Auth', () => {
  test.beforeEach(async ({ page }) => {
    await acceptCookies(page);
  });

  test('shows password restore form', async ({ page }) => {
    await page.goto('/restore');
    await expect(page.locator('.page_body')).toHaveScreenshot('restore.png');
  });
});

import { test, expect } from '../fixtures.js';
import { acceptCookies } from '../helpers.js';

test.describe('Away / Banned link warning', () => {
  test.beforeEach(async ({ page }) => {
    await acceptCookies(page);
  });

  test('shows banned link warning for blocked domain', async ({ page }) => {
    await page.goto('/away.php/1?to=https://example.com');
    await expect(page.locator('.page_body')).toHaveScreenshot('banned-link-warning.png');
  });
});

import { test, expect } from '../fixtures.js';
import { acceptCookies } from '../helpers.js';

test.describe('Maintenance pages', () => {
  test.beforeEach(async ({ page }) => {
    await acceptCookies(page);
  });

  test('shows global maintenance page', async ({ page }) => {
    await page.goto('/maintenances/');
    await expect(page.locator('.page_body')).toHaveScreenshot('maintenance-all.png');
  });
});

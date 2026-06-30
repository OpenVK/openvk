import { test, expect } from '../fixtures.js';
import { loginAsAlice } from '../helpers.js';

test.describe('Search', () => {
  test.beforeEach(async ({ page }) => {
    await loginAsAlice(page);
  });

  test('shows search page', async ({ page }) => {
    await page.goto('/search');
    await expect(page.locator('.page_body')).toHaveScreenshot('search.png');
  });
});

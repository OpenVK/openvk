import { test, expect } from '../fixtures.js';
import { loginAsAlice } from '../helpers.js';

test.describe('Apps', () => {
  test.beforeEach(async ({ page }) => {
    await loginAsAlice(page);
  });

  test('shows apps page', async ({ page }) => {
    await page.goto('/apps');
    await expect(page.locator('.page_body')).toHaveScreenshot('apps.png');
  });
});

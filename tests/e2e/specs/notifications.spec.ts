import { test, expect } from '../fixtures.js';
import { loginAsAlice } from '../helpers.js';

test.describe('Notifications', () => {
  test.beforeEach(async ({ page }) => {
    await loginAsAlice(page);
  });

  test('shows notifications', async ({ page }) => {
    await page.goto('/notifications');
    await expect(page.locator('.page_body')).toHaveScreenshot('notifications.png');
  });
});

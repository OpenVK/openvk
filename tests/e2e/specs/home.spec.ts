import { test, expect } from '../fixtures.js';
import { acceptCookies } from '../helpers.js';

test.describe('Home page', () => {
  test('shows about page for unauthenticated users', async ({ page }) => {
    await acceptCookies(page);
    await page.goto('/');
    await expect(page).toHaveScreenshot('home-layout.png', {
      fullPage: true,
      mask: [page.locator('.page_footer p').filter({ hasText: /Altair/ })],
    });
  });

  test('robots.txt is accessible', async ({ page }) => {
    const response = await page.goto('/robots.txt');
    expect(response?.status()).toBe(200);
  });
});

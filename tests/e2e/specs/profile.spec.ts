import { test, expect } from '@playwright/test';
import { acceptCookies } from '../helpers.js';

test.describe('User profile', () => {
  test.beforeEach(async ({ page }) => {
    await acceptCookies(page);
    await page.goto('/login');
    await page.fill('#fastLogin input[name="login"]', 'alice@test.local');
    await page.fill('#fastLogin input[name="password"]', 'test123');
    await page.click('#fastLogin input[type="submit"]');
    await page.waitForURL(/\/alice/);
  });

  test('displays own profile correctly', async ({ page }) => {
    await page.goto('/id2');
    await expect(page.locator('h2').filter({ hasText: 'Alice Testova' })).toBeVisible();
    await expect(page).toHaveScreenshot('profile-alice-layout.png', {
      fullPage: true,
      maxDiffPixels: 200,
      mask: [
        page.locator('.page_footer p').filter({ hasText: /Altair/ }),
        page.locator('#basicInfo tr').filter({ hasText: 'День рождения' }),
      ],
    });
  });

  test('shows profile wall posts', async ({ page }) => {
    await page.goto('/id2');
    await expect(page.locator('text=Hello world! This is my very first post on OpenVK')).toBeVisible();
  });
});

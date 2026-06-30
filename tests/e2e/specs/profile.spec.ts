import { test, expect } from '../fixtures.js';
import { loginAsAlice } from '../helpers.js';

test.describe('User profile', () => {
  test.beforeEach(async ({ page }) => {
    await loginAsAlice(page);
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
        page.locator('.mediaInfo'),
        page.locator('.mini_timer'),
      ],
    });
  });

  test('shows profile wall posts', async ({ page }) => {
    await page.goto('/id2');
    await expect(page.locator('text=Hello world! This is my very first post on OpenVK')).toBeVisible();
  });
});

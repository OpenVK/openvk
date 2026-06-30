import { test, expect } from '../fixtures.js';
import { loginAsAlice } from '../helpers.js';

test.describe('Settings', () => {
  test.beforeEach(async ({ page }) => {
    await loginAsAlice(page);
  });

  test('shows settings page', async ({ page }) => {
    await page.goto('/settings');
    await expect(page.locator('.page_body')).toHaveScreenshot('settings.png');
  });

  test('shows 2FA settings', async ({ page }) => {
    await page.goto('/settings/2fa');
    await expect(page.locator('.page_body')).toHaveScreenshot('settings-2fa.png', {
      mask: [
        page.locator('img[width="225"]'),
        page.locator('p:has(b)'),
      ],
    });
  });

  test('shows settings security tab', async ({ page }) => {
    await page.goto('/settings?act=security');
    await expect(page.locator('.page_body')).toHaveScreenshot('settings-security.png');
  });

  test('shows settings privacy tab', async ({ page }) => {
    await page.goto('/settings?act=privacy');
    await expect(page.locator('.page_body')).toHaveScreenshot('settings-privacy.png');
  });

  test('shows settings blacklist tab', async ({ page }) => {
    await page.goto('/settings?act=blacklist');
    await expect(page.locator('.page_body')).toHaveScreenshot('settings-blacklist.png');
  });

  test('shows settings interface tab', async ({ page }) => {
    await page.goto('/settings?act=interface');
    await expect(page.locator('.page_body')).toHaveScreenshot('settings-interface.png');
  });
});

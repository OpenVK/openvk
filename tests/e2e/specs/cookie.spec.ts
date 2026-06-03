import { test, expect } from '@playwright/test';

declare function agreeWithCookies(): void;

test.describe('Cookie consent', () => {
  test('shows cookie banner and allows accepting', async ({ page }) => {
    await page.goto('/');
    await expect(page.locator('.cookies-popup')).toBeVisible();
    await expect(page.locator('.cookies-popup')).toHaveScreenshot('cookie-banner.png');

    await page.evaluate(() => agreeWithCookies());
    await expect(page.locator('.cookies-popup')).not.toBeVisible();
  });
});

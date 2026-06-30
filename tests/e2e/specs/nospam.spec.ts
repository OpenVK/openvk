import { test, expect } from '../fixtures.js';
import { acceptCookies } from '../helpers.js';

async function loginAsAdmin(page: import('@playwright/test').Page): Promise<void> {
  await acceptCookies(page);
  await page.goto('/login');
  await page.fill('#fastLogin input[name="login"]', 'admin@test.local');
  await page.fill('#fastLogin input[name="password"]', 'test123');
  await page.click('#fastLogin input[type="submit"]');
  await page.waitForURL(/\/sysop|\/id1/);
}

test.describe('NoSpam admin tool', () => {
  test.beforeEach(async ({ page }) => {
    await loginAsAdmin(page);
  });

  test('shows noSpam form', async ({ page }) => {
    await page.goto('/noSpam');
    await expect(page.locator('.page_body')).toHaveScreenshot('nospam-form.png');
  });
});

import { test, expect } from '../fixtures.js';
import { acceptCookies } from '../helpers.js';

test.describe('Login page', () => {
  test('shows login form for unauthenticated users', async ({ page }) => {
    await acceptCookies(page);
    await page.goto('/login');
    await expect(page.locator('#fastLogin')).toHaveScreenshot('login-form.png', { maxDiffPixels: 200 });
    await expect(page.locator('#fastLogin input[name="login"]')).toBeVisible();
    await expect(page.locator('#fastLogin input[name="password"]')).toBeVisible();
    await expect(page.locator('#fastLogin input[type="submit"]')).toBeVisible();
  });

  test('redirects to user page after successful login', async ({ page }) => {
    await page.goto('/login');
    await page.fill('#fastLogin input[name="login"]', 'alice@test.local');
    await page.fill('#fastLogin input[name="password"]', 'test123');
    await page.click('#fastLogin input[type="submit"]');
    await expect(page).toHaveURL(/\/alice/);
  });

  test('shows error with wrong credentials', async ({ page }) => {
    await page.goto('/login');
    await page.fill('#fastLogin input[name="login"]', 'alice@test.local');
    await page.fill('#fastLogin input[name="password"]', 'wrongpassword');
    await page.click('#fastLogin input[type="submit"]');
    await expect(page).toHaveURL(/\/login/);
    await expect(page.locator('.msg_err')).toHaveScreenshot('login-error.png', { maxDiffPixels: 200 });
    await expect(page.locator('.msg_err')).toContainText('Login failed');
  });
});

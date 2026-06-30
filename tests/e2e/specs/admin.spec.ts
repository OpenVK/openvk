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

test.describe('Admin panel', () => {
  // TODO: mark these as test.skip if no admin@test.local exists in seed data
  test.beforeEach(async ({ page }) => {
    await loginAsAdmin(page);
  });

  test('shows admin dashboard', async ({ page }) => {
    await page.goto('/admin');
    await expect(page).toHaveScreenshot('admin-dashboard.png', {
      mask: [page.locator('section.footer-body').filter({ hasText: /Altair/ })],
    });
  });

  test('shows admin users list', async ({ page }) => {
    await page.goto('/admin/users');
    await expect(page).toHaveScreenshot('admin-users.png', {
      mask: [page.locator('section.footer-body').filter({ hasText: /Altair/ })],
    });
  });

  test('shows admin clubs list', async ({ page }) => {
    await page.goto('/admin/clubs');
    await expect(page).toHaveScreenshot('admin-clubs.png', {
      mask: [page.locator('section.footer-body').filter({ hasText: /Altair/ })],
    });
  });

  test('shows admin banned links list', async ({ page }) => {
    await page.goto('/admin/bannedLinks');
    await expect(page).toHaveScreenshot('admin-banned-links.png', {
      mask: [page.locator('section.footer-body').filter({ hasText: /Altair/ })],
    });
  });

  test('shows admin banned link detail', async ({ page }) => {
    await page.goto('/admin/bannedLink/id1');
    await expect(page).toHaveScreenshot('admin-banned-link-detail.png', {
      mask: [page.locator('section.footer-body').filter({ hasText: /Altair/ })],
    });
  });
});

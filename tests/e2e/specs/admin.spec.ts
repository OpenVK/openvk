import { test, expect } from '../fixtures.js';
import { acceptCookies } from '../helpers.js';

// Admin tests require an admin user (admin@test.local). These tests may fail
// if the test database does not have the admin@test.local account configured.
// In that scenario, provide a custom loginAsAdmin helper or use the admin
// seed data from install/sqls/.
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
});

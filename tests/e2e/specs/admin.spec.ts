import { test, expect } from '../fixtures.js';
import { loginAsAdmin } from '../helpers.js';

test.describe('Admin panel', () => {
  // TODO: mark these as test.skip if no admin@test.local exists in seed data
  test.beforeEach(async ({ page }) => {
    await loginAsAdmin(page);
  });

  test('shows admin dashboard', async ({ page }) => {
    await page.goto('/admin');
    await expect(page).toHaveScreenshot('admin-dashboard.png', {
      maxDiffPixels: 200,
      mask: [page.locator('section.footer-body').filter({ hasText: /Altair/ })],
    });
  });

  test('shows admin users list', async ({ page }) => {
    await page.goto('/admin/users');
    await expect(page).toHaveScreenshot('admin-users.png', {
      maxDiffPixels: 200,
      mask: [page.locator('section.footer-body').filter({ hasText: /Altair/ })],
    });
  });

  test('shows admin clubs list', async ({ page }) => {
    await page.goto('/admin/clubs');
    await expect(page).toHaveScreenshot('admin-clubs.png', {
      maxDiffPixels: 200,
      mask: [page.locator('section.footer-body').filter({ hasText: /Altair/ })],
    });
  });

  test('shows admin banned links list', async ({ page }) => {
    await page.goto('/admin/bannedLinks');
    await expect(page).toHaveScreenshot('admin-banned-links.png', {
      maxDiffPixels: 200,
      mask: [page.locator('section.footer-body').filter({ hasText: /Altair/ })],
    });
  });

  test('shows admin banned link detail', async ({ page }) => {
    await page.goto('/admin/bannedLink/id1');
    await expect(page).toHaveScreenshot('admin-banned-link-detail.png', {
      maxDiffPixels: 200,
      mask: [page.locator('section.footer-body').filter({ hasText: /Altair/ })],
    });
  });
});

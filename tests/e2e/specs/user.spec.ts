import { test, expect } from '../fixtures.js';
import { loginAsAlice } from '../helpers.js';

test.describe('Friends', () => {
  test.beforeEach(async ({ page }) => {
    await loginAsAlice(page);
  });

  test('shows friends list', async ({ page }) => {
    await page.goto('/friends2');
    await expect(page.locator('.page_body')).toHaveScreenshot('friends2.png', { maxDiffPixels: 200 });
  });
});

test.describe('User groups', () => {
  test.beforeEach(async ({ page }) => {
    await loginAsAlice(page);
  });

  test('shows user groups list', async ({ page }) => {
    await page.goto('/groups2');
    await expect(page.locator('.page_body')).toHaveScreenshot('groups2.png', { maxDiffPixels: 200 });
  });
});

test.describe('User profile additional', () => {
  test.beforeEach(async ({ page }) => {
    await loginAsAlice(page);
  });

  test('shows own profile at /id2', async ({ page }) => {
    await page.goto('/id2');
    await expect(page.locator('h2').filter({ hasText: 'Alice Testova' })).toBeVisible();
    await expect(page).toHaveScreenshot('user-profile-id2.png', {
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
});

test.describe('Edit profile', () => {
  test.beforeEach(async ({ page }) => {
    await loginAsAlice(page);
  });

  test('shows edit profile page', async ({ page }) => {
    await page.goto('/edit');
    await expect(page.locator('.page_body')).toHaveScreenshot('edit.png', { maxDiffPixels: 200 });
  });
});

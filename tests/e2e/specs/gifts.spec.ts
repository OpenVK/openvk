import { test, expect } from '../fixtures.js';
import { loginAsAlice } from '../helpers.js';

test.describe('Gifts', () => {
  test.beforeEach(async ({ page }) => {
    await loginAsAlice(page);
  });

  test('shows received gifts on user gifts page', async ({ page }) => {
    await page.goto('/gifts2');
    await expect(page.locator('.page_body')).toHaveScreenshot('gifts-user.png', { maxDiffPixels: 200 });
  });

  test('shows gift category picker', async ({ page }) => {
    await page.goto('/gifts?act=pick&user=3');
    await expect(page.locator('.page_body')).toHaveScreenshot('gifts-pick.png', { maxDiffPixels: 200 });
  });

  test('shows gift list in a category', async ({ page }) => {
    await page.goto('/gifts?act=menu&user=3&pack=1');
    await expect(page.locator('.page_body')).toHaveScreenshot('gifts-category.png', { maxDiffPixels: 200 });
  });

  test('shows gift confirmation form', async ({ page }) => {
    await page.goto('/gifts?act=confirm&user=3&pack=1&elid=1');
    await expect(page.locator('.page_body')).toHaveScreenshot('gifts-confirm.png', { maxDiffPixels: 200 });
  });
});

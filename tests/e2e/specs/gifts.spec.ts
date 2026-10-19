import { test, expect } from '../fixtures.js';
import { loginAsAdmin, loginAsAlice } from '../helpers.js';

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

  test('blocks getting delisted gifts and sets by direct link', async ({ page }) => {
    await page.goto('/gifts?act=menu&user=3&pack=3');
    await expect(page.locator('.msg.msg_err')).toHaveScreenshot('gifts-blocked-set.png');

    await page.goto('/gifts?act=confirm&user=3&pack=1&elid=3');
    await expect(page.locator('.msg.msg_err')).toHaveScreenshot('gifts-blocked-gift.png');
  });
});

test.describe('Gifts administration', () => {
  test.beforeEach(async ({ page }) => {
    await loginAsAdmin(page);
  });

  test('shows delisted sets in the admin sets list', async ({ page }) => {
    await page.goto('/admin/gifts');
    await expect(page).toHaveScreenshot('gifts-admin-sets.png', {
      maxDiffPixels: 200,
      mask: [page.locator('section.footer-body')],
    });
  });

  test('shows delisted gifts in the admin gift list', async ({ page }) => {
    await page.goto('/admin/gifts/x.1/');
    await expect(page).toHaveScreenshot('gifts-admin-category.png', {
      maxDiffPixels: 200,
      mask: [page.locator('section.footer-body')],
    });
  });

  test('shows the unlisted checkbox on the gift edit page', async ({ page }) => {
    await page.goto('/admin/gifts/id5');
    await expect(page).toHaveScreenshot('gifts-admin-gift-edit.png', {
      maxDiffPixels: 200,
      fullPage: true,
      mask: [page.locator('section.footer-body')],
    });
  });

  test('shows the unlisted checkbox on the set edit page', async ({ page }) => {
    await page.goto('/admin/gifts/x.3.meta');
    await expect(page).toHaveScreenshot('gifts-admin-set-edit.png', {
      maxDiffPixels: 200,
      fullPage: true,
      mask: [page.locator('section.footer-body')],
    });
  });

  test('relisting a gift from the admin area returns it to the catalog', async ({ page }) => {
    await page.goto('/admin/gifts/x.1/');
    await page.locator('a[href^="/admin/gifts/id5?act=toggle"]').click();

    await page.goto('/gifts?act=menu&user=3&pack=1');
    await expect(page.locator('.page_body')).toHaveScreenshot('gifts-category-relisted.png', { maxDiffPixels: 200 });
  });

  test('relisting a set from the admin area returns it to the picker', async ({ page }) => {
    await page.goto('/admin/gifts');
    await page.locator('a[href*=".3.meta?act=toggle"]').click();

    await page.goto('/gifts?act=pick&user=3');
    await expect(page.locator('.page_body')).toHaveScreenshot('gifts-pick-relisted.png', { maxDiffPixels: 200 });
  });
});

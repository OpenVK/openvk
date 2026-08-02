import { test, expect } from '../fixtures.js';
import { loginAsAlice, loginAsCharlie } from '../helpers.js';

test.describe('Group wiki pages', () => {
  test.beforeEach(async ({ page }) => {
    await loginAsAlice(page);
  });

  test('shows pages list', async ({ page }) => {
    await page.goto('/pages-1');
    await expect(page.locator('.page_body')).toHaveScreenshot('pages-list.png', { maxDiffPixels: 200 });
  });

  test('shows page view', async ({ page }) => {
    await page.goto('/page-1_1');
    await expect(page.locator('.page_body')).toHaveScreenshot('page-1_1.png', { maxDiffPixels: 200 });
  });

  test('shows page edit form', async ({ page }) => {
    await page.goto('/page-1_1/edit');
    await expect(page.locator('.page_body')).toHaveScreenshot('page-1_1-edit.png', { maxDiffPixels: 200 });
  });

  test('shows create page form', async ({ page }) => {
    await page.goto('/pages-1/create');
    await expect(page.locator('.page_body')).toHaveScreenshot('page-create.png', { maxDiffPixels: 200 });
  });

  test('shows page history', async ({ page }) => {
    await page.goto('/page-1_1/history');
    await expect(page.locator('.page_body')).toHaveScreenshot('page-1_1-history.png', { maxDiffPixels: 200 });
  });

  test('shows markup help', async ({ page }) => {
    await page.goto('/pages-1/help');
    await expect(page.locator('.page_body')).toHaveScreenshot('pages-help.png', { maxDiffPixels: 200 });
  });

  test('creates a new page', async ({ page }) => {
    await page.goto('/pages-1/create');
    await page.fill('input[name="title"]', 'Rules');
    await page.fill('textarea[name="source"]', '## Community rules\n\nBe nice.');
    await page.click('input[type="submit"]');
    await page.waitForURL(/\/page-1_\d+/);
    await expect(page.locator('.wiki_page_title')).toHaveText('Rules');
    await expect(page.locator('.wiki_page_body')).toContainText('Be nice');
  });

  test('shows edit pages link on club page', async ({ page }) => {
    await page.goto('/club1');
    await expect(page.locator('#profile_links a', { hasText: /Edit community pages|Редактировать страницы/ })).toBeVisible();
    await expect(page.locator('.wiki_page_body, .content_title_expanded').filter({ hasText: /Welcome Page|Community main page|Главная/ }).first()).toBeVisible();
  });
});

test.describe('Group wiki pages permissions', () => {
  test('visitor can view open page but not edit', async ({ page }) => {
    await loginAsCharlie(page);
    await page.goto('/page-1_1');
    await expect(page.locator('.wiki_page_title')).toHaveText('Welcome Page');
    await expect(page.locator('.pages_editor_tab', { hasText: /Edit|Редактирование/ })).toHaveCount(0);

    await page.goto('/page-1_1/edit');
    await expect(page.locator('.page_body')).toContainText(/access|прав|forbidden|denied/i);
  });
});

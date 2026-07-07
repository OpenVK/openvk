import { test, expect } from '../fixtures.js';
import { loginAsAlice } from '../helpers.js';

test.describe('Groups', () => {
  test.beforeEach(async ({ page }) => {
    await loginAsAlice(page);
  });

  test('shows group page', async ({ page }) => {
    await page.goto('/club1');
    await expect(page.locator('.page_body')).toHaveScreenshot('club1.png', { maxDiffPixels: 200 });
  });

  test('shows group followers', async ({ page }) => {
    await page.goto('/club1/followers');
    await expect(page.locator('.page_body')).toHaveScreenshot('club1-followers.png', { maxDiffPixels: 200 });
  });

  test('shows create group form', async ({ page }) => {
    await page.goto('/groups_create');
    await expect(page.locator('.page_body')).toHaveScreenshot('groups-create.png', { maxDiffPixels: 200 });
  });

  test('shows group admin page', async ({ page }) => {
    await page.goto('/club1/followers/1');
    await expect(page.locator('.page_body')).toHaveScreenshot('club1-admin.png', { maxDiffPixels: 200 });
  });

  test('shows edit group form', async ({ page }) => {
    await page.goto('/club1/edit');
    await expect(page.locator('.page_body')).toHaveScreenshot('club1-edit.png', { maxDiffPixels: 200 });
  });
});

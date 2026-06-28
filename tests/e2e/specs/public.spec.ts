import { test, expect } from '@playwright/test';
import { acceptCookies } from '../helpers.js';

test.describe('Public pages', () => {
  test.beforeEach(async ({ page }) => {
    await acceptCookies(page);
  });

  test('shows about instance page', async ({ page }) => {
    await page.goto('/about');
    await expect(page.locator('.page_body')).toHaveScreenshot('about.png');
  });

  test('shows terms page', async ({ page }) => {
    await page.goto('/terms');
    await expect(page.locator('.page_body')).toHaveScreenshot('terms.png');
  });

  test('shows privacy page', async ({ page }) => {
    await page.goto('/privacy');
    await expect(page.locator('.page_body')).toHaveScreenshot('privacy.png');
  });

  test('shows tour page', async ({ page }) => {
    await page.goto('/tour');
    await expect(page.locator('.page_body')).toHaveScreenshot('tour.png');
  });

  test('shows donate page', async ({ page }) => {
    await page.goto('/donate');
    await expect(page.locator('.page_body')).toHaveScreenshot('donate.png');
  });

  test('shows registration page', async ({ page }) => {
    await page.goto('/reg');
    await expect(page.locator('.page_body')).toHaveScreenshot('reg.png');
  });


});

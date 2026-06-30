import { test, expect } from '../fixtures.js';
import { acceptCookies, loginAsAlice } from '../helpers.js';

test.describe('Public pages', () => {
  test.beforeEach(async ({ page }) => {
    await acceptCookies(page);
  });

  test('shows about instance page', async ({ page }) => {
    await page.goto('/about');
    await expect(page.locator('.page_body')).toHaveScreenshot('about.png', { maxDiffPixels: 200 });
  });

  test('shows terms page', async ({ page }) => {
    await page.goto('/terms');
    await expect(page.locator('.page_body')).toHaveScreenshot('terms.png', { maxDiffPixels: 200 });
  });

  test('shows privacy page', async ({ page }) => {
    await page.goto('/privacy');
    await expect(page.locator('.page_body')).toHaveScreenshot('privacy.png', { maxDiffPixels: 200 });
  });

  test('shows tour page', async ({ page }) => {
    await page.goto('/tour');
    await expect(page.locator('.page_body')).toHaveScreenshot('tour.png', { maxDiffPixels: 200 });
  });

  test('shows donate page', async ({ page }) => {
    await page.goto('/donate');
    await expect(page.locator('.page_body')).toHaveScreenshot('donate.png', { maxDiffPixels: 200 });
  });

  test('shows registration page', async ({ page }) => {
    await page.goto('/reg');
    await expect(page.locator('.page_body')).toHaveScreenshot('reg.png', { maxDiffPixels: 200 });
  });

  test('shows language page', async ({ page }) => {
    await page.goto('/language');
    await expect(page.locator('.page_body')).toHaveScreenshot('language.png', { maxDiffPixels: 200 });
  });

  // WerySkok: Skipped due to strange formatting differences and constantly updated info here 
  // (including commit hashes)
  test.skip('shows version page', async ({ page }) => {
    await page.goto('/about:openvk');
    await expect(page.locator('.page_body')).toHaveScreenshot('version.png', {
      maxDiffPixels: 200,
      mask: [
        page.locator('table .v').filter({ hasText: /Altair/ }),
        page.locator('h1.p').filter({ hasText: /Altair/ })
      ],
    });
  });

  test('shows badbrowser page', async ({ page }) => {
    await page.goto('/badbrowser.php');
    await expect(page.locator('.page_body')).toHaveScreenshot('badbrowser.png', { maxDiffPixels: 200 });
  });
});

test.describe('Invite', () => {
  test.beforeEach(async ({ page }) => {
    await loginAsAlice(page);
  });

  test('shows invite page', async ({ page }) => {
    await page.goto('/invite');
    await expect(page.locator('.page_body')).toHaveScreenshot('invite.png', { maxDiffPixels: 200 });
  });
});

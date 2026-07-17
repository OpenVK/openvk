import { test, expect } from '../fixtures.js';
import { loginAsAlice } from '../helpers.js';

test.describe('Polls', () => {
  test.beforeEach(async ({ page }) => {
    await loginAsAlice(page);
  });

  test('shows active poll view', async ({ page }) => {
    await page.goto('/poll1');
    await expect(page.locator('.poll')).toHaveScreenshot('poll-active.png', { maxDiffPixels: 200 });
  });

  test('shows ended poll view', async ({ page }) => {
    await page.goto('/poll2');
    await expect(page.locator('.poll')).toHaveScreenshot('poll-ended.png', { maxDiffPixels: 200 });
  });
});

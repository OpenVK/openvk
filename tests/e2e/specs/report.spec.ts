import { test, expect } from '../fixtures.js';
import { loginAsAdmin } from '../helpers.js';

test.describe('Reports list', () => {
  test.beforeEach(async ({ page }) => {
    await loginAsAdmin(page);
  });

  test('shows /scumfeed with report entries', async ({ page }) => {
    await page.goto('/scumfeed');
    await expect(page.locator('.page_body')).toHaveScreenshot('report-list.png', {
      maxDiffPixels: 200,
      mask: [page.locator('section.footer-body').filter({ hasText: /Altair/ })],
    });
  });
});

import { test, expect } from '../fixtures.js';
import { loginAsAlice } from '../helpers.js';

test.describe('Notes', () => {
  test.beforeEach(async ({ page }) => {
    await loginAsAlice(page);
  });

  test('shows notes page', async ({ page }) => {
    await page.goto('/notes2');
    await expect(page.locator('.page_body')).toHaveScreenshot('notes2.png', { maxDiffPixels: 200 });
  });

  test('shows note view', async ({ page }) => {
    await page.goto('/note2_1');
    await expect(page.locator('.page_body')).toHaveScreenshot('note2_1.png', { maxDiffPixels: 200 });
  });

  test('shows create note form', async ({ page }) => {
    await page.goto('/notes/create');
    await expect(page.locator('.page_body')).toHaveScreenshot('note-create.png', {
      maxDiffPixels: 200,
      mask: [page.locator('.monaco-editor .scrollbar')],
    });
  });

  test('shows edit note page', async ({ page }) => {
    await page.goto('/note2_1/edit');
    await expect(page.locator('.page_body')).toHaveScreenshot('note2_1-edit.png', {
      maxDiffPixels: 200,
      mask: [page.locator('.monaco-editor .scrollbar')],
    });
  });
});

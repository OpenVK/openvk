import { test, expect } from '../fixtures.js';
import { loginAsAlice, loginAsCharlie } from '../helpers.js';

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

test.describe('Group wall permissions', () => {
  test('visitor cannot archive their post on a group wall', async ({ page }) => {
    await loginAsCharlie(page);
    await page.goto('/club1');

    const postText = 'A visitor post on the group wall';
    const form = page.locator('form[action="/wall-1/makePost"]');
    await form.locator('textarea[name="text"]').fill(postText);
    await form.locator('input[type="submit"]').click();

    const post = page.locator('.post').filter({ hasText: postText });
    await expect(post).toBeVisible();
    await expect(post.locator('.archive_post, .oldPostArchive')).toHaveCount(0);
  });
});

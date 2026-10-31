import { test, expect } from '../fixtures.js';
import { loginAsAlice, loginAsCharlie } from '../helpers.js';

test.describe('Wall', () => {
  test.beforeEach(async ({ page }) => {
    await loginAsAlice(page);
  });

  test('shows global feed', async ({ page }) => {
    await page.goto('/feed/all');
    await expect(page.locator('.page_body')).toHaveScreenshot('feed-all.png', { maxDiffPixels: 200 });
  });

  test('feed shows posts from friends', async ({ page }) => {
    await page.goto('/feed');
    await expect(page.locator('text=Hello world! This is my very first post on OpenVK')).toBeVisible();
    await expect(page.locator('.page_body')).toHaveScreenshot('feed-friends.png', { maxDiffPixels: 200 });
  });

  test('user wall shows all posts', async ({ page }) => {
    await page.goto('/wall2');
    await expect(page.locator('text=Pinned announcement: I am now accepting friend requests!')).toBeVisible();
    await expect(page.locator('.page_body')).toHaveScreenshot('wall-posts.png', { maxDiffPixels: 200 });
  });

  test('deletes and restores a microblog post until the page is reloaded', async ({ page }) => {
    await page.goto('/wall2');
    const post = page.locator('.post_microblog[data-id="2_2"]');
    const text = 'Just had a great cup of coffee and watched the sunrise.';

    for (let i = 0; i < 2; i++) {
      await post.locator('a[href="/wall2_2/delete"]').click();
      const deleted = page.locator('.post-deleted').filter({ hasText: 'Post was removed.' });
      await expect(deleted).toBeVisible();
      if (i === 0) {
        await expect(deleted).toHaveScreenshot('deleted-post.png');
      }
      await deleted.getByText('Restore').click();
      await expect(post).toContainText(text);
    }

    await page.reload();
    await expect(post).toContainText(text);
    await expect(page.locator('.post-deleted')).toHaveCount(0);

    await post.locator('a[href="/wall2_2/delete"]').click();
    await expect(page.locator('.post-deleted').filter({ hasText: 'Post was removed.' })).toBeVisible();
    await page.reload();
    await expect(post).toHaveCount(0);
    await expect(page.locator('.post-deleted')).toHaveCount(0);
  });

  test('restores a comment and post on the post page', async ({ page }) => {
    await page.goto('/wall2_3');
    const comment = page.locator('#_comment3');
    await comment.locator('a[href="/comment3/delete"]').click();

    const deleted = page.locator('.post-deleted').filter({ hasText: 'Comment was removed.' });
    await expect(deleted).toBeVisible();
    await expect(deleted).toHaveScreenshot('deleted-comment.png');
    await expect(page.locator('#post_page_main .post')).toBeVisible();
    await deleted.getByText('Restore').click();
    await expect(comment).toContainText('Thanks guys!');
    await page.reload();
    await expect(comment).toContainText('Thanks guys!');

    await page.locator('#_ajaxDelete').click();
    const deletedPost = page.locator('#post_page_main .post-deleted');
    await expect(deletedPost).toContainText('Post was removed.');
    await expect(page.locator('#post_page_meta')).toBeHidden();
    await deletedPost.getByText('Restore').click();
    await expect(page.locator('#post_page_main .post')).toContainText('Check out this cool photo');
    await expect(page.locator('#post_page_meta')).not.toHaveCSS('display', 'none');
    await expect(page.locator('#_comment3')).toBeVisible();
  });

  test('restores a comment and post inside the post viewer', async ({ page }) => {
    await page.goto('/wall2');
    await page.locator('.post_microblog[data-id="2_3"] .post-menu > a.date[href="/wall2_3"]').click();
    const viewer = page.locator('.ovk-msg-all.ovk-post-viewer-dimmer');
    const comment = viewer.locator('#_comment3');
    await comment.locator('a[href="/comment3/delete"]').click();
    const deleted = viewer.locator('.post-deleted').filter({ hasText: 'Comment was removed.' });
    await expect(deleted).toBeVisible();
    await expect(viewer.locator('.post_microblog[data-id="2_3"]')).toBeVisible();
    await deleted.getByText('Restore').click();
    await expect(comment).toContainText('Thanks guys!');

    await viewer.locator('#_ajaxDelete').click();
    const deletedPost = viewer.locator('#itemContent .post-deleted');
    await expect(deletedPost).toContainText('Post was removed.');
    await expect(deletedPost).toHaveScreenshot('deleted-viewer-post.png');
    await expect(viewer).toHaveClass(/viewer-deleted/);
    await deletedPost.getByText('Restore').click();
    await expect(viewer.locator('#itemContent > .post')).toContainText('Check out this cool photo');
    await expect(viewer).not.toHaveClass(/viewer-deleted/);
  });

  test('hides deleted reposts and restores their attachment links', async ({ page }) => {
    await page.goto('/wall2');
    const original = 'Just had a great cup of coffee and watched the sunrise. Life is good.';
    const shareStatus = await page.evaluate(async () => {
      const body = new FormData();
      body.append('hash', (window as any).router.csrf);
      body.append('text', 'Repost test marker');
      const response = await fetch('/wall2_2/repost', { method: 'POST', body });
      return response.status;
    });
    expect(shareStatus).toBe(200);
    await page.reload();
    const repost = page.locator('.post_microblog.common-post').filter({ hasText: 'Repost test marker' });
    await expect(repost).toContainText(original);

    await page.locator('.post_microblog.common-post[data-id="2_2"] .postAction.delete').click();
    const deleted = page.locator('.post-deleted').filter({ hasText: 'Post was removed.' });
    await expect(deleted).toBeVisible();
    const wallWhileDeleted = await (await page.request.get('/wall2')).text();
    expect(wallWhileDeleted).toContain('Repost test marker');
    expect(wallWhileDeleted).not.toContain(original);

    await deleted.getByText('Restore').click();
    await page.reload();
    await expect(repost).toContainText(original);
  });

  test('keeps the restore control when the request fails', async ({ page }) => {
    await page.goto('/wall2');
    const post = page.locator('.post_microblog[data-id="2_2"]');
    await post.locator('a[href="/wall2_2/delete"]').click();
    const deleted = page.locator('.post-deleted').filter({ hasText: 'Post was removed.' });
    await expect(deleted).toBeVisible();

    await page.route('**/wall2_2/restore', route => route.fulfill({
      status: 200,
      contentType: 'application/json',
      body: JSON.stringify({ success: false, flash: { message: 'Restore failed' } }),
    }));
    await deleted.getByText('Restore').click();
    await expect(page.locator('.ovk-diag-body').filter({ hasText: 'Restore failed' })).toBeVisible();
    await expect(deleted.getByText('Restore')).toBeVisible();
    await page.unroute('**/wall2_2/restore');
    await page.locator('.ovk-diag-action button').first().click();
    await deleted.getByText('Restore').click();
    await expect(post).toBeVisible();
  });

  test('requires a valid restore token', async ({ page }) => {
    await page.goto('/wall2');
    const result = await page.evaluate(async () => {
      const csrf = (window as any).router.csrf;
      const send = async (url: string, token?: string) => {
        const body = new FormData();
        body.append('hash', csrf);
        body.append('ajax', '1');
        if (token) body.append('restore_token', token);
        return fetch(url, { method: 'POST', body });
      };
      const deleted = await (await send('/wall2_2/delete')).json();
      const missing = await send('/wall2_2/restore');
      const wrong = await send('/wall2_2/restore', 'invalid');
      const restored = await send('/wall2_2/restore', deleted.restore_token);
      const deletedAgain = await (await send('/wall2_2/delete')).json();
      const stale = await send('/wall2_2/restore', deleted.restore_token);
      const restoredAgain = await send('/wall2_2/restore', deletedAgain.restore_token);
      return [missing.status, wrong.status, restored.status, stale.status, restoredAgain.status];
    });
    expect(result).toEqual([403, 403, 200, 403, 200]);
  });

  test('requires CSRF for AJAX comment deletion', async ({ page }) => {
    await page.goto('/wall2_3');
    const status = await page.evaluate(async () => {
      const body = new FormData();
      body.append('ajax', '1');
      return (await fetch('/comment3/delete', { method: 'POST', body })).status;
    });
    expect(status).toBe(400);
    await page.reload();
    await expect(page.locator('#_comment3')).toBeVisible();
  });
});

test('restores an old-style post and shows its pinned state in the viewer', async ({ page }) => {
  await loginAsCharlie(page);
  await page.goto('/wall4');
  const post = page.locator('.post_oldschool[data-id="4_2"]');
  const togglePin = async () => {
    await post.locator('a[href*="/wall4_2/pin"]').click();
    await page.locator('.ovk-diag-action button').first().click();
  };
  if (await post.evaluate(node => node.classList.contains('is-pinned'))) {
    await togglePin();
    await expect(post).not.toHaveClass(/is-pinned/);
  }
  await post.locator('a[href="/wall4_2/delete"]').click();
  const deleted = page.locator('.post-deleted').filter({ hasText: 'Post was removed.' });
  await expect(deleted).toBeVisible();
  await deleted.getByText('Restore').click();
  await expect(post).toContainText('Great article about photography techniques');

  await post.locator('.post-author .date').click();
  const viewer = page.locator('.ovk-post-viewer-dimmer');
  await expect(viewer.locator('.itemPostTime')).not.toContainText('pinned');
  await viewer.locator('#ovk-photo-close').click();
  await expect(viewer).toHaveCount(0);

  await togglePin();
  await expect(post).toHaveClass(/is-pinned/);
  await post.locator('.post-author .date').click();
  await expect(viewer.locator('.itemPostTime')).toContainText('pinned');
  await viewer.locator('#ovk-photo-close').click();
  await togglePin();
  await expect(post).not.toHaveClass(/is-pinned/);
});

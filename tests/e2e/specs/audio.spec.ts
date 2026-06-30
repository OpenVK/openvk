import { test, expect } from '../fixtures.js';
import { loginAsAlice } from '../helpers.js';

test.describe('Audio', () => {
  test.beforeEach(async ({ page }) => {
    await loginAsAlice(page);
  });

  test('shows audio page', async ({ page }) => {
    await page.goto('/audios2');
    await expect(page.locator('.page_body')).toHaveScreenshot('audios2.png', {
      mask: [page.locator('.friendsAudiosList')],
    });
  });

  test('shows playlists page', async ({ page }) => {
    await page.goto('/playlists2');
    await expect(page.locator('.page_body')).toHaveScreenshot('playlists2.png');
  });

  test('shows new playlist form', async ({ page }) => {
    await page.goto('/audios/newPlaylist');
    await expect(page.locator('.page_body')).toHaveScreenshot('audio-new-playlist.png');
  });

  test('shows alone audio view', async ({ page }) => {
    await page.goto('/audio2_1');
    await expect(page.locator('.page_body')).toHaveScreenshot('audio2_1.png', {
      mask: [
        page.locator('audio'),
        page.locator('.page_footer p'),
      ],
    });
  });
});

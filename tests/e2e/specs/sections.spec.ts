import { test, expect } from '../fixtures.js';
import { loginAsAlice } from '../helpers.js';

test.describe('Friends', () => {
  test.beforeEach(async ({ page }) => {
    await loginAsAlice(page);
  });

  test('shows friends list', async ({ page }) => {
    await page.goto('/friends2');
    await expect(page.locator('.page_body')).toHaveScreenshot('friends2.png');
  });
});

test.describe('Groups', () => {
  test.beforeEach(async ({ page }) => {
    await loginAsAlice(page);
  });

  test('shows user groups list', async ({ page }) => {
    await page.goto('/groups2');
    await expect(page.locator('.page_body')).toHaveScreenshot('groups2.png');
  });
});

test.describe('Photos', () => {
  test.beforeEach(async ({ page }) => {
    await loginAsAlice(page);
  });

  test('shows photo albums', async ({ page }) => {
    await page.goto('/albums2');
    await page.evaluate(() => {
      for (const el of document.querySelectorAll('span[style*="color: grey"]')) {
        if (el.textContent?.match(/Обновлено|Updated|Aktualizacja/i))
          el.textContent = 'Обновлено [УДАЛЕНО]';
      }
    });
    await expect(page.locator('.page_body')).toHaveScreenshot('albums2.png');
  });

});

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
});

test.describe('Videos', () => {
  test.beforeEach(async ({ page }) => {
    await loginAsAlice(page);
  });

  test('shows videos page', async ({ page }) => {
    await page.goto('/videos2');
    await expect(page.locator('.page_body')).toHaveScreenshot('videos2.png');
  });
});

test.describe('Messages', () => {
  test.beforeEach(async ({ page }) => {
    await loginAsAlice(page);
  });

  test('shows messages page', async ({ page }) => {
    await page.goto('/im');
    await expect(page.locator('.page_body')).toHaveScreenshot('im.png');
  });
});

test.describe('Global feed', () => {
  test.beforeEach(async ({ page }) => {
    await loginAsAlice(page);
  });

  test('shows global feed', async ({ page }) => {
    await page.goto('/feed/all');
    await expect(page.locator('.page_body')).toHaveScreenshot('feed-all.png');
  });
});

test.describe('Favorites', () => {
  test.beforeEach(async ({ page }) => {
    await loginAsAlice(page);
  });

  test('shows favorites page', async ({ page }) => {
    await page.goto('/fave');
    await expect(page.locator('.page_body')).toHaveScreenshot('fave.png');
  });
});

test.describe('Notifications', () => {
  test.beforeEach(async ({ page }) => {
    await loginAsAlice(page);
  });

  test('shows notifications', async ({ page }) => {
    await page.goto('/notifications');
    await expect(page.locator('.page_body')).toHaveScreenshot('notifications.png');
  });
});

test.describe('Settings', () => {
  test.beforeEach(async ({ page }) => {
    await loginAsAlice(page);
  });

  test('shows settings page', async ({ page }) => {
    await page.goto('/settings');
    await expect(page.locator('.page_body')).toHaveScreenshot('settings.png');
  });

  test('shows 2FA settings', async ({ page }) => {
    await page.goto('/settings/2fa');
    await expect(page.locator('.page_body')).toHaveScreenshot('settings-2fa.png', {
      mask: [
        page.locator('img[width="225"]'),
        page.locator('p:has(b)'),
      ],
    });
  });
});

test.describe('Search', () => {
  test.beforeEach(async ({ page }) => {
    await loginAsAlice(page);
  });

  test('shows search page', async ({ page }) => {
    await page.goto('/search');
    await expect(page.locator('.page_body')).toHaveScreenshot('search.png');
  });
});

test.describe('Edit profile', () => {
  test.beforeEach(async ({ page }) => {
    await loginAsAlice(page);
  });

  test('shows edit profile page', async ({ page }) => {
    await page.goto('/edit');
    await expect(page.locator('.page_body')).toHaveScreenshot('edit.png');
  });
});

test.describe('Group page', () => {
  test.beforeEach(async ({ page }) => {
    await loginAsAlice(page);
  });

  test('shows group page', async ({ page }) => {
    await page.goto('/club1');
    await expect(page.locator('.page_body')).toHaveScreenshot('club1.png');
  });

  test('shows group followers', async ({ page }) => {
    await page.goto('/club1/followers');
    await expect(page.locator('.page_body')).toHaveScreenshot('club1-followers.png');
  });
});

test.describe('Notes', () => {
  test.beforeEach(async ({ page }) => {
    await loginAsAlice(page);
  });

  test('shows notes page', async ({ page }) => {
    await page.goto('/notes2');
    await expect(page.locator('.page_body')).toHaveScreenshot('notes2.png');
  });
});

test.describe('Apps', () => {
  test.beforeEach(async ({ page }) => {
    await loginAsAlice(page);
  });

  test('shows apps page', async ({ page }) => {
    await page.goto('/apps');
    await expect(page.locator('.page_body')).toHaveScreenshot('apps.png');
  });
});

test.describe('Documents', () => {
  test.beforeEach(async ({ page }) => {
    await loginAsAlice(page);
  });

  test('shows documents page', async ({ page }) => {
    await page.goto('/docs');
    await expect(page.locator('.page_body')).toHaveScreenshot('docs.png');
  });
});

test.describe('Invite', () => {
  test.beforeEach(async ({ page }) => {
    await loginAsAlice(page);
  });

  test('shows invite page', async ({ page }) => {
    await page.goto('/invite');
    await expect(page.locator('.page_body')).toHaveScreenshot('invite.png');
  });
});

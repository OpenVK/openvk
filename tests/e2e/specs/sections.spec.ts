import { test, expect } from '@playwright/test';
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
    await expect(page.locator('.page_body')).toHaveScreenshot('albums2.png', {
      mask: [page.locator('span').filter({ hasText: /Обновлено|Updated/i })],
    });
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

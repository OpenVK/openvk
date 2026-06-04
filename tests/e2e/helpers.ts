import { Page } from '@playwright/test';

export async function acceptCookies(page: Page): Promise<void> {
  await page.context().addCookies([{
    name: 'cookiesAgreed',
    value: 'true',
    url: process.env.BASE_URL || 'http://openvk:80',
  }]);
}

export async function loginAsAlice(page: Page): Promise<void> {
  await acceptCookies(page);
  await page.goto('/login');
  await page.fill('#fastLogin input[name="login"]', 'alice@test.local');
  await page.fill('#fastLogin input[name="password"]', 'test123');
  await page.click('#fastLogin input[type="submit"]');
  await page.waitForURL(/\/alice/);
}

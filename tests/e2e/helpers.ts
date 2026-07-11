import { Page } from '@playwright/test';

export async function acceptCookies(page: Page): Promise<void> {
  await page.context().addCookies([{
    name: 'cookiesAgreed',
    value: 'true',
    url: process.env.BASE_URL || 'http://localhost:8080',
  }]);
}

export async function loginAsAdmin(page: Page): Promise<void> {
  await acceptCookies(page);
  await page.goto('/login');
  await page.fill('#fastLogin input[name="login"]', 'admin@test.local');
  await page.fill('#fastLogin input[name="password"]', 'test123');
  await page.click('#fastLogin input[type="submit"]');
  await page.waitForURL(/\/sysop|\/id1/);
}

export async function loginAsAlice(page: Page): Promise<void> {
  await acceptCookies(page);
  await page.goto('/login');
  await page.fill('#fastLogin input[name="login"]', 'alice@test.local');
  await page.fill('#fastLogin input[name="password"]', 'test123');
  await page.click('#fastLogin input[type="submit"]');
  await page.waitForURL(/\/alice/);
}

export async function loginAsBob(page: Page): Promise<void> {
  await acceptCookies(page);
  await page.goto('/login');
  await page.fill('#fastLogin input[name="login"]', 'bob@test.local');
  await page.fill('#fastLogin input[name="password"]', 'test123');
  await page.click('#fastLogin input[type="submit"]');
  await page.waitForURL(/\/bob/);
}

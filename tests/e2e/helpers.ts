import { Page } from '@playwright/test';

export async function acceptCookies(page: Page): Promise<void> {
  await page.context().addCookies([{
    name: 'cookiesAgreed',
    value: 'true',
    url: process.env.BASE_URL || 'http://openvk:80',
  }]);
}

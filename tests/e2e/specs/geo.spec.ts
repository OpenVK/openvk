import { test, expect } from '../fixtures.js';
import { loginAsAdmin } from '../helpers.js';

async function blockTarget(page, target: string): Promise<void> {
  await page.goto(`/admin/geo?target=${encodeURIComponent(target)}`);

  const form = page.locator('form:has(input[name="action"][value="block"])');
  await form.locator('input[name="reason"]').fill('test demand');
  await form.locator('button[type="submit"]').click();
  await page.waitForURL('**/admin/geo');
  await page.locator('table.aui tbody tr').first().waitFor({ state: 'visible' });
}

async function countryPage(browser, country: string) {
  const context = await browser.newContext({
    locale: 'ru-RU',
    extraHTTPHeaders: { 'X-Geo-Country': country },
  });

  return context.newPage();
}

test.describe('Geo blocks', () => {
  test.beforeEach(async ({ page }) => {
    await loginAsAdmin(page);
  });

  test('blocks a post for RU but not for US', async ({ page, browser }) => {
    await blockTarget(page, '/wall2_1');

    const ruPage = await countryPage(browser, 'RU');
    const ruResponse = await ruPage.goto('/wall2_1');
    expect(ruResponse?.status()).toBe(451);
    await expect(ruPage.locator('text=Материал недоступен в вашем регионе').first()).toBeVisible();

    await ruPage.goto('/wall2');
    await expect(ruPage.locator('text=Hello world! This is my very first post on OpenVK')).toHaveCount(0);

    const usPage = await countryPage(browser, 'US');
    const usResponse = await usPage.goto('/wall2_1');
    expect(usResponse?.status()).toBe(200);
    await expect(usPage.locator('text=Hello world! This is my very first post on OpenVK')).toBeVisible();
  });

  test('blocks a profile and all of its content', async ({ page, browser }) => {
    await blockTarget(page, '/id3');

    const ruPage = await countryPage(browser, 'RU');
    const profileResponse = await ruPage.goto('/id3');
    expect(profileResponse?.status()).toBe(451);

    const wallResponse = await ruPage.goto('/wall3');
    expect(wallResponse?.status()).toBe(451);

    const usPage = await countryPage(browser, 'US');
    const usResponse = await usPage.goto('/id3');
    expect(usResponse?.status()).toBe(200);
  });

  test('blocks a pinned post', async ({ page, browser }) => {
    await blockTarget(page, '/wall2_5');

    const ruPage = await countryPage(browser, 'RU');
    expect((await ruPage.goto('/wall2_5'))?.status()).toBe(451);
    await ruPage.goto('/wall2');
    await expect(ruPage.locator('text=Pinned announcement: I am now accepting friend requests!')).toHaveCount(0);

    const usPage = await countryPage(browser, 'US');
    expect((await usPage.goto('/wall2_5'))?.status()).toBe(200);
  });

  test('blocks derived blob paths for a photo', async ({ page, browser }) => {
    await blockTarget(page, '/photo2_1');

    const hash = '0'.repeat(128);
    const derived = `/blob_00/${hash}_cropped/normal.jpeg`;

    const ruPage = await countryPage(browser, 'RU');
    expect((await ruPage.goto(derived))?.status()).toBe(451);

    const usPage = await countryPage(browser, 'US');
    expect((await usPage.goto(derived))?.status()).not.toBe(451);
  });

  test('unblocks a post', async ({ page, browser }) => {
    await blockTarget(page, '/wall2_1');

    const ruPage = await countryPage(browser, 'RU');
    expect((await ruPage.goto('/wall2_1'))?.status()).toBe(451);

    await page.goto('/admin/geo');
    const row = page.locator('tr', {
      has: page.locator('td', { hasText: /^post:1$/ }),
    }).first();
    await row.locator('input[name="reason"]').fill('demand withdrawn');
    await row.locator('button').click();
    await page.waitForURL('**/admin/geo');

    expect((await ruPage.goto('/wall2_1'))?.status()).toBe(200);
  });
});

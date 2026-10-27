import { Page, expect, test } from '@playwright/test';
import { setAPIKey } from './utils';

test.describe.configure({ mode: 'serial' });

let page: Page;

test.describe('onboarding', () => {
  test.beforeAll(async ({ browser }) => {
    page = await browser.newPage();

    await setAPIKey(page, '');
  });

  test('show the account of a connected api key', async () => {
    await page.goto('/wp-admin/options-general.php?page=tiny-onboarding&step=1');
    await page.locator('#tinypng_api_key_name').fill('John');
    await page.locator('#tinypng_api_key_email').fill('john@example.com');
    await page.getByRole('button', { name: 'Create account' }).click();
    await page.waitForURL('**step=2**');

    await page.goto('/wp-admin/options-general.php?page=tiny-onboarding&step=1');
    await page.locator('#tinypng_api_key').fill('LIMIT123');
    await page.getByRole('button', { name: 'Connect', exact: true }).click();
    await page.waitForURL('**step=3**');

    await expect(page.getByRole('heading', { name: 'Connected as test@example.com' })).toBeVisible();
    await expect(page.getByText('Free plan, 500 credits a month.')).toBeVisible();
  });
});

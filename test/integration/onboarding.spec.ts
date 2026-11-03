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

  test('show the free credits of a paid account', async () => {
    await setAPIKey(page, '');
    await page.goto('/wp-admin/options-general.php?page=tiny-onboarding&step=1');
    await page.locator('#tinypng_api_key').fill('PAID123');
    await page.getByRole('button', { name: 'Connect', exact: true }).click();
    await page.waitForURL('**step=3**');

    await expect(page.getByText('Pay per compression, after 500 free credits a month.')).toBeVisible();
  });

  test('change the email address before confirming it', async () => {
    await setAPIKey(page, '');
    await page.goto('/wp-admin/options-general.php?page=tiny-onboarding&step=1');
    await page.locator('#tinypng_api_key_name').fill('John');
    await page.locator('#tinypng_api_key_email').fill('john@example.com');
    await page.getByRole('button', { name: 'Create account' }).click();
    await page.waitForURL('**step=2**');

    await page.getByRole('link', { name: 'Change email address' }).click();
    await page.locator('#tinypng_api_key_name').fill('Jane');
    await page.locator('#tinypng_api_key_email').fill('jane@example.com');
    await page.getByRole('button', { name: 'Create account' }).click();
    await page.waitForURL('**step=2**');

    await expect(page.getByText('Click the link we sent to jane@example.com.')).toBeVisible();
  });
});

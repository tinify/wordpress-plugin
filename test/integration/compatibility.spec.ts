import { Page, expect, test } from '@playwright/test';
import { activatePlugin, enableCompressionSizes, setAPIKey, setCompressionTiming } from './utils';
import { onBucketSavedResponse, onKeySavedResponse, onStateCheck } from './data/as3cf';

test.describe.configure({ mode: 'serial' });

test.describe('as3cf', () => {
  let page: Page;

  test.beforeAll(async ({ browser }) => {
    page = await browser.newPage();

    await activatePlugin(page, 'amazon-s3-and-cloudfront');

    await page.goto('/wp-admin/options-general.php?page=amazon-s3-and-cloudfront#/storage/provider');

    await page.getByText('I understand the risks').click();

    await page.getByLabel('Access Key ID').fill('test_key_id');
    await page.getByLabel('Secret Access Key').fill('test_secret_access_key');

    // await page.route('*/**/index.php?rest_route=/wp-offload-media/v1/settings/', async (route) => {
    //   await route.fulfill({ json: onKeySavedResponse });
    // });
    await page.getByRole('button', { name: 'Save & Continue' }).click();

    await page.waitForLoadState('networkidle');

    await page.getByText('Enter bucket name').click();

    await page.getByPlaceholder('Enter bucket name…').fill('tinifytest');

    // await page.route('*/**/index.php?rest_route=/wp-offload-media/v1/settings/', async (route) => {
    //   await route.fulfill({ json: onBucketSavedResponse });
    // });

    // await page.route('*/**/index.php?rest_route=/wp-offload-media/v1/state/', async (route) => {
    //   await route.fulfill({ json: onStateCheck });
    // });

    await page.getByRole('button', { name: 'Save Bucket Settings' }).click();
    await page.waitForLoadState('networkidle');

    await page.reload();

    await setAPIKey(page, 'JPG123');
    await enableCompressionSizes(page, [], true);
  });

  test('does not show notification when local media is preserved', async () => {
    await page.goto('/wp-admin/options-general.php?page=tinify');

    await expect(page.getByText(' configure WP Offload')).not.toBeVisible();
  });

  test('does not show notification when local media is not preserved but timing is auto', async () => {
    await page.goto('/wp-admin/options-general.php?page=amazon-s3-and-cloudfront');
    await page.locator('#remove-local-file').check({ force: true });

    await page.getByRole('button', { name: 'Save Changes' }).click();

    await page.goto('/wp-admin/options-general.php?page=tinify');

    await expect(page.getByText(' configure WP Offload')).not.toBeVisible();
  });

  test('shows notification when local media is not preserved and timing is on background', async () => {
    await setCompressionTiming(page, 'background');

    await page.goto('/wp-admin/options-general.php?page=amazon-s3-and-cloudfront');
    await page.locator('#remove-local-file').check({ force: true });

    await page.getByRole('button', { name: 'Save Changes' }).click();

    await page.goto('/wp-admin/options-general.php?page=tinify');

    await expect(page.getByText('For background compression to work you will need to configure WP Offload S3 to keep a copy of the images on the server.')).toBeVisible();
  });
});

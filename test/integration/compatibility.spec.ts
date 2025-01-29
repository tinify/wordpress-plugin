import { Page, expect, test } from '@playwright/test';
import { activatePlugin, enableCompressionSizes, setAPIKey, setCompressionTiming, uploadMedia } from './utils';

test.describe.configure({ mode: 'serial' });

test.describe('as3cf', () => {
  let page: Page;

  test.beforeAll(async ({ browser }) => {
    page = await browser.newPage();

    await activatePlugin(page, 'amazon-s3-and-cloudfront');

    await page.goto('/wp-admin/options-general.php?page=amazon-s3-and-cloudfront');

    const isConfigured = await page.getByText('Storage provider is successfully connected and ready to offload new media.').isVisible();
    if (!isConfigured) {
      await page.getByText('Enter bucket name').click();
      await page.getByPlaceholder('Enter bucket name…').fill('tinytest');
      await page.getByRole('button', { name: 'Save Bucket Settings' }).click();
      await page.waitForLoadState('networkidle');
    }

    const cfgRemoveLocal = await page.locator('label').filter({ hasText: 'Remove Local Media' }).isChecked();
    if (cfgRemoveLocal) {
      // start with remove local media disabled
      await  page.locator('label').filter({ hasText: 'Remove Local Media' }).uncheck({ force: true });
      await page.getByRole('button', { name: 'Save Changes' }).click();
    }

    await setAPIKey(page, 'JPG123');
    await enableCompressionSizes(page, [], true);
  });

  test('does not show notification when local media is preserved', async () => {
    await page.goto('/wp-admin/options-general.php?page=tinify');

    await expect(page.getByText(' configure WP Offload')).not.toBeVisible();
  });

  test('does not show notification when local media is not preserved but timing is auto', async () => {
    await page.goto('/wp-admin/options-general.php?page=amazon-s3-and-cloudfront');
    await page.getByLabel('Remove Local Media').check({ force: true });

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

  test('compress image before offloading', async ({ page }) => {
    await setCompressionTiming(page, 'auto');

    await uploadMedia(page, 'input-example.jpg');
  });

  test('compress image asynchronously', async ({ page }) => {
    await setCompressionTiming(page, 'background');

    await uploadMedia(page, 'input-example.jpg');
  });
});

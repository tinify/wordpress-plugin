import { Page, expect, test } from '@playwright/test';
import { activatePlugin, clearMediaLibrary, deactivatePlugin, enableCompressionSizes, setAPIKey, setCompressionTiming, uploadMedia } from './utils';

test.describe.configure({ mode: 'serial' });

const TEST_BUCKETNAME = 'tinytest';

async function setRemoveLocalMedia(page: Page, enabled: boolean) {
  await page.goto('/wp-admin/options-general.php?page=amazon-s3-and-cloudfront');

  const cfgRemoveLocal = await page.getByLabel('Remove Local Media').isChecked();
  if (cfgRemoveLocal && enabled) {
    // should enable but is already enabled
    return;
  }
  if (!cfgRemoveLocal && !enabled) {
    // should disable but is already disabled
    return;
  }

  await page.locator('label[for="remove-local-file"]').click();
  await page.getByRole('button', { name: 'Save Changes' }).click({ force: true });
  await page.waitForLoadState('networkidle'); //async save
}

test.describe('as3cf', () => {
  let page: Page;

  test.beforeAll(async ({ browser }) => {
    page = await browser.newPage();

    await activatePlugin(page, 'amazon-s3-and-cloudfront');

    await page.goto('/wp-admin/options-general.php?page=amazon-s3-and-cloudfront');

    const isConfigured = await page.getByText('Storage provider is successfully connected and ready to offload new media.').isVisible();
    if (!isConfigured) {
      await page.getByText('Enter bucket name').click();
      await page.getByPlaceholder('Enter bucket name…').fill(TEST_BUCKETNAME);
      await page.getByRole('button', { name: 'Save Bucket Settings' }).click();
      await page.waitForLoadState('networkidle');
    }

    await setRemoveLocalMedia(page, false);

    await setAPIKey(page, 'JPG123');
    await enableCompressionSizes(page, [], true);
  });

  test.afterAll(async () => {
    await deactivatePlugin(page, 'amazon-s3-and-cloudfront');
    await page.close();
  });

  test('does not show notification when local media is preserved', async () => {
    await setRemoveLocalMedia(page, false);

    await page.goto('/wp-admin/options-general.php?page=tinify');

    await expect(page.getByText(' configure WP Offload')).not.toBeVisible();
  });

  test('shows notification when local media is not preserved', async () => {
    await setRemoveLocalMedia(page, true);

    await page.goto('/wp-admin/options-general.php?page=tinify');

    await expect(page.getByText(' configure WP Offload')).toBeVisible();
  });

  test('compress image before offloading', async ({ page }) => {
    await setRemoveLocalMedia(page, false);
    await clearMediaLibrary(page);
    await setCompressionTiming(page, 'auto');

    await uploadMedia(page, 'input-example.jpg');

    await page.goto('/wp-admin/upload.php?mode=list');
    await page.getByLabel('“input-example” (Edit)').click();

    await expect(page.locator('#attachment_url')).toContainText(TEST_BUCKETNAME);
    await expect(page.getByText('5 sizes compressed')).toBeVisible();
  });

  test('compress image asynchronously', async ({ page }) => {
    await setRemoveLocalMedia(page, false);
    await clearMediaLibrary(page);
    await setCompressionTiming(page, 'background');

    await uploadMedia(page, 'input-example.jpg');

    await page.goto('/wp-admin/upload.php?mode=list');
    await page.getByLabel('“input-example” (Edit)').click();

    expect(page.locator('#attachment_url')).toContainText(TEST_BUCKETNAME);
    expect(page.getByText('5 sizes compressed')).toBeVisible();
  });
});

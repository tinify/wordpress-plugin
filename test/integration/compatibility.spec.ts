import { Page, expect, test } from '@playwright/test';
import { activatePlugin, clearMediaLibrary, deactivatePlugin, enableCompressionSizes, getWPVersion, setAPIKey, setCompressionTiming, uploadMedia } from './utils';

test.describe.configure({ mode: 'serial' });

const TEST_BUCKETNAME = 'tinytest';
let WPVersion = 0;

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

  await Promise.all([
    page.waitForResponse((resp) => resp.url().includes('/wp-offload-media/v1/state/') && resp.status() === 200),
    page.locator('label[for="remove-local-file"]').click(),
  ]);

  await Promise.all([
    page.waitForResponse((resp) => resp.url().includes('/wp-offload-media/v1/settings/') && resp.status() === 200),
    page.getByRole('button', { name: 'Save Changes' }).click({ force: true })
  ]);
}

test.describe('as3cf', () => {
  let page: Page;

  test.beforeAll(async ({ browser }) => {
    page = await browser.newPage();
    WPVersion = await getWPVersion(page);

    if (WPVersion < 5.5) {
      // Skipping test as it WP Offload does not support WordPress < 5.5
      test.skip();
      return;
    }

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
    await setCompressionTiming(page, 'auto');
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
    await setCompressionTiming(page, 'background');
    await setRemoveLocalMedia(page, true);

    await page.goto('/wp-admin/options-general.php?page=tinify');

    await expect(page.getByText(' configure WP Offload')).toBeVisible();
  });

  test('compress image before offloading', async ({ page }) => {
    await setRemoveLocalMedia(page, false);
    await clearMediaLibrary(page);
    await setCompressionTiming(page, 'auto');
    await enableCompressionSizes(page, ['0'], false);

    await uploadMedia(page, 'input-example.jpg');

    await page.goto('/wp-admin/upload.php?mode=list');
    await page.getByLabel('“input-example” (Edit)').click();

    const imageURL = await page.locator('#attachment_url').inputValue();
    await expect(imageURL).toContain(TEST_BUCKETNAME);
    await expect(page.getByText('1 size compressed')).toBeVisible();
  });

  test('compress image asynchronously', async ({ page }) => {
    await setRemoveLocalMedia(page, false);
    await clearMediaLibrary(page);
    await setCompressionTiming(page, 'background');
    await enableCompressionSizes(page, ['0'], false);

    await uploadMedia(page, 'input-example.jpg');

    await page.goto('/wp-admin/upload.php?mode=list');
    await page.getByLabel('“input-example” (Edit)').click();

    const imageURL = await page.locator('#attachment_url').inputValue();
    await expect(imageURL).toContain(TEST_BUCKETNAME);
    await expect(page.getByText('1 size compressed')).toBeVisible();
  });

  test('compress images manually', async ({ page }) => {
    await setRemoveLocalMedia(page, false);
    await clearMediaLibrary(page);
    await setCompressionTiming(page, 'manual');
    await enableCompressionSizes(page, ['0'], false);

    await uploadMedia(page, 'input-example.jpg');

    await page.goto('/wp-admin/upload.php?mode=list');
    await page.getByLabel('“input-example” (Edit)').click();

    const imageURL = await page.locator('#attachment_url').inputValue();
    await expect(imageURL).toContain(TEST_BUCKETNAME);
    await expect(page.getByText('1 size to be compressed')).toBeVisible();
  });

  test('does not show compression button if image is not available locally', async ({ page }) => {
    // Currently, we cannot support compression of images that are not available locally.
    await setRemoveLocalMedia(page, true);
    await clearMediaLibrary(page);
    await setCompressionTiming(page, 'manual');

    await uploadMedia(page, 'input-example.jpg');

    await page.goto('/wp-admin/upload.php?mode=list');

    await expect(page.getByRole('button', { name: 'Compress' })).not.toBeVisible();
  });
});

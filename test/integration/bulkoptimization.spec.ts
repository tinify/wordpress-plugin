import { Page, expect, test } from '@playwright/test';
import { BASE_URL, clearMediaLibrary, enableCompressionSizes, getWPVersion, setAPIKey, setCompressionTiming, setConversionSettings, uploadImages, uploadMedia } from './utils';

test.describe.configure({ mode: 'serial' });

let page: Page;
let WPVersion = 0;

/** Server side behaviour, faked with cookies read by test/fixtures/class-tiny-config.php. */
interface BulkBehaviour {
  /** Whether the site can send requests to itself. Defaults to false. */
  loopbackSupported?: boolean;
  /** Seconds it takes to optimize one image. Defaults to 0.1. */
  compressionDuration?: number;
}

test.describe('bulkoptimization', () => {
  test.beforeAll(async ({ browser }) => {
    page = await browser.newPage();
    WPVersion = await getWPVersion(page);
    await setConversionSettings(page, {
      convert: false,
    });
  });

  test.beforeEach(async () => {
    await clearMediaLibrary(page);
    // Cookies read by test/fixtures/class-tiny-config.php.
    await page.context().clearCookies({ name: /^tiny_test_/ });
  });

  /**
   * loopbackSupported sets a cookie which redirects to the local admin url so that loopbacks work on docker
   * compressionDuration decides how long the mock should sleep for compression
   * 
   * @see test/fixtures/class-tiny-config.php
   */
  async function setBulkBehaviour(currentPage: Page, { loopbackSupported = false, compressionDuration = 0.1 }: BulkBehaviour) {
    await currentPage.context().addCookies([{
      name: 'tiny_test_slow_compression',
      value: compressionDuration.toString(),
      url: BASE_URL,
    }, {
      name: 'tiny_test_no_loopback',
      value: loopbackSupported ? '0' : '1',
      url: BASE_URL,
    }]);
  }

  test('offer an upgrade next to a smaller run for an account with insufficient credits', async () => {
    await setAPIKey(page, 'INSUFFICIENTCREDITS123');
    await setCompressionTiming(page, 'manual');
    await enableCompressionSizes(page, ['0']);
    await uploadImages(page, 'input-example.jpg', 2, { generateSizes: false });

    await page.goto('/wp-admin/upload.php?page=tiny-bulk-optimization');

    await expect(page.getByText('Not enough credits')).toBeVisible();
    await expect(page.getByRole('link', { name: 'Upgrade' })).toBeVisible();
    await expect(page.getByRole('button', { name: 'Compress 1 image' })).toBeVisible();
  });

  test('only offer an upgrade for an account without credits', async () => {
    await setAPIKey(page, 'NOCREDITS123');
    await setCompressionTiming(page, 'manual');
    await enableCompressionSizes(page, ['0']);
    await uploadImages(page, 'input-example.jpg', 1, { generateSizes: false });

    await page.goto('/wp-admin/upload.php?page=tiny-bulk-optimization');

    await expect(page.getByText('Out of credits')).toBeVisible();
    await expect(page.getByRole('link', { name: 'Upgrade' })).toBeVisible();
    await expect(page.getByRole('button', { name: /^Compress/ })).toHaveCount(0);
  });

  test('not offer an upgrade to paid accounts', async () => {
    await setAPIKey(page, 'PAID123');
    await setCompressionTiming(page, 'manual');
    await enableCompressionSizes(page, ['0']);
    await uploadImages(page, 'input-example.jpg', 1, { generateSizes: false });

    await page.goto('/wp-admin/upload.php?page=tiny-bulk-optimization');

    await expect(page.getByText('Ready to compress')).toBeVisible();
    await expect(page.getByRole('link', { name: 'Upgrade' })).toHaveCount(0);
  });

  test('show the empty library', async () => {
    await enableCompressionSizes(page, ['0', 'thumbnail', 'medium']);

    await page.goto('/wp-admin/upload.php?page=tiny-bulk-optimization');

    await expect(page.getByRole('heading', { name: 'No images yet' })).toBeVisible();
    await expect(page.getByRole('link', { name: 'Upload images' })).toBeVisible();
  });

  test('show that no image sizes are selected', async () => {
    await setAPIKey(page, 'JPG123');
    await setCompressionTiming(page, 'manual');
    await uploadImages(page, 'input-example.jpg', 1, { generateSizes: false });
    await enableCompressionSizes(page, []);

    await page.goto('/wp-admin/upload.php?page=tiny-bulk-optimization');

    await expect(page.getByRole('heading', { name: 'No image sizes selected' })).toBeVisible();
    await expect(page.getByRole('link', { name: 'Change settings' }).first()).toBeVisible();
  });

  test('bulk optimize webp images', async () => {
    // https://make.wordpress.org/core/2021/06/07/wordpress-5-8-adds-webp-support/
    if (WPVersion < 5.8) return;

    await setAPIKey(page, 'JPG123');
    await setCompressionTiming(page, 'auto');
    await setBulkBehaviour(page, { loopbackSupported: true });

    await enableCompressionSizes(page, []);
    await uploadMedia(page, 'input-example.jpg');

    await enableCompressionSizes(page, ['0']);
    await uploadMedia(page, 'input-example.webp');

    await enableCompressionSizes(page, ['0', 'thumbnail', 'medium']);
    await uploadMedia(page, 'input-example.jpg');

    await page.goto('/wp-admin/upload.php?page=tiny-bulk-optimization');

    await expect(page.getByRole('heading', { name: '2 images to compress' })).toBeVisible();
    await expect(page.getByText('1 of 3 compressed')).toBeVisible();
  });

  test('summary should display correct values', async () => {
    await setAPIKey(page, 'JPG123');
    await setCompressionTiming(page, 'auto');

    await enableCompressionSizes(page, []);
    await uploadImages(page, 'input-example.jpg');

    await enableCompressionSizes(page, ['0']);
    await uploadImages(page, 'input-example.jpg');

    await enableCompressionSizes(page, ['0', 'thumbnail', 'medium']);
    await uploadImages(page, 'input-example.jpg');

    await page.goto('/wp-admin/upload.php?page=tiny-bulk-optimization');

    await expect(page.getByRole('heading', { name: '2 images to compress' })).toBeVisible();
    await expect(page.getByText('1 of 3 compressed')).toBeVisible();
    await expect(page.getByText('2 to compress')).toBeVisible();
    await expect(page.getByTestId('bulk-item-status-0')).toHaveText('Waiting');
    await expect(page.getByTestId('bulk-item-status-1')).toHaveText('Waiting');
    await expect(page.getByText('2 waiting')).toBeVisible();
  });

  test('start bulk optimization should optimize remaining images', async () => {
    await setAPIKey(page, 'JPG123');
    await setCompressionTiming(page, 'auto');

    await enableCompressionSizes(page, []);
    await uploadImages(page, 'input-example.jpg');

    await enableCompressionSizes(page, ['0']);
    await uploadImages(page, 'input-example.jpg');

    await enableCompressionSizes(page, ['0', 'thumbnail', 'medium']);
    await uploadImages(page, 'input-example.jpg');
    await setBulkBehaviour(page, { loopbackSupported: true });

    await page.goto('/wp-admin/upload.php?page=tiny-bulk-optimization');
    await page.getByRole('button', { name: 'Compress 2 images' }).click();

    // A short run can be done before the notice is checked, so it may already be hidden.
    await expect(page.locator('[data-tiny-bulk="run-notice"]')).toContainText('continues in the background');
    await expect(page.getByRole('heading', { name: 'All 3 images are compressed' })).toBeVisible({ timeout: 15000 });
    await expect(page.getByRole('heading', { name: 'Recently compressed' })).toBeVisible();
    await expect(page.getByTestId('bulk-item-status-0')).toContainText('smaller');
  });

  test('start bulk optimization without loopback should optimize remaining images in the page', async () => {
    await setAPIKey(page, 'JPG123');
    await setCompressionTiming(page, 'auto');

    await enableCompressionSizes(page, []);
    await uploadImages(page, 'input-example.jpg');

    await enableCompressionSizes(page, ['0']);
    await uploadImages(page, 'input-example.jpg');

    await enableCompressionSizes(page, ['0', 'thumbnail', 'medium']);
    await uploadImages(page, 'input-example.jpg');
    await setBulkBehaviour(page, { loopbackSupported: false });

    await page.goto('/wp-admin/upload.php?page=tiny-bulk-optimization');
    await page.getByRole('button', { name: 'Compress 2 images' }).click();

    await expect(page.locator('[data-tiny-bulk="run-notice"]')).toContainText('keep this page open');
    await expect(page.getByRole('heading', { name: 'All 3 images are compressed' })).toBeVisible({ timeout: 15000 });
  });

  test('background optimization continues when page is refreshed', async () => {
    await setAPIKey(page, 'JPG123');
    await setCompressionTiming(page, 'manual');
    await enableCompressionSizes(page, ['0']);
    await uploadImages(page, 'input-example.jpg', 1, { generateSizes: false });
    await setBulkBehaviour(page, { loopbackSupported: true, compressionDuration: 10 });

    await page.goto('/wp-admin/upload.php?page=tiny-bulk-optimization');
    await page.getByRole('button', { name: 'Compress 1 image' }).click();
    await expect(page.getByText('continues in the background')).toBeVisible();

    await page.reload();

    await expect(page.getByRole('heading', { name: 'Compressing 0 of 1 image' })).toBeVisible();
    await expect(page.getByText('continues in the background')).toBeVisible();
    await expect(page.getByRole('heading', { name: 'All 1 image is compressed' })).toBeVisible({ timeout: 15000 });
  });

  test('pause returns to the images that are left', async () => {
    await setAPIKey(page, 'JPG123');
    await setCompressionTiming(page, 'manual');
    await enableCompressionSizes(page, ['0']);
    await uploadImages(page, 'input-example.jpg', 6, { generateSizes: false });
    await setBulkBehaviour(page, { loopbackSupported: false, compressionDuration: 5 });

    await page.goto('/wp-admin/upload.php?page=tiny-bulk-optimization');
    await page.getByRole('button', { name: 'Compress 6 images' }).click();
    await expect(page.getByText('keep this page open')).toBeVisible();

    await page.getByRole('button', { name: 'Pause' }).click();
    await expect(page.getByRole('button', { name: 'Pausing...' })).toBeDisabled();

    // Images that were being compressed finish, then the page shows what is left.
    await expect(page.getByText('Ready to compress')).toBeVisible({ timeout: 20000 });
    await expect(page.getByRole('heading', { name: /^\d images? to compress$/ })).toBeVisible();
    await expect(page.getByTestId('bulk-item-status-0')).toHaveText('Waiting');
  });

  test('stop when the account runs out of credits', async () => {
    await setAPIKey(page, 'LIMIT123');
    await setCompressionTiming(page, 'manual');
    await enableCompressionSizes(page, ['0']);
    await uploadImages(page, 'input-example.jpg', 6, { generateSizes: false });

    await page.goto('/wp-admin/upload.php?page=tiny-bulk-optimization');
    await page.getByRole('button', { name: 'Compress 6 images' }).click();
    await expect(page.getByText('Compressing', { exact: true })).toBeVisible();

    // The first image to hit the limit takes the rest off the queue, and the page shows what is left.
    await expect(page.getByRole('heading', { name: '6 images to compress' })).toBeVisible({ timeout: 30000 });
  });

  test('will only apply a conversion when images are already compressed but not converted', async ({ page }) => {
    await setAPIKey(page, 'JPG123');
    await enableCompressionSizes(page, ['0']);

    // upload 1 image and automaticly compress it, but not convert it yet
    await setCompressionTiming(page, 'auto');
    await setConversionSettings(page, { convert: false });
    await uploadImages(page, 'input-example.jpg', 1, { generateSizes: false });

    // turn on convert and set to manual compression, upload another image
    await setCompressionTiming(page, 'manual');
    await setConversionSettings(page, { convert: true });
    await uploadImages(page, 'input-example.jpg', 1, { generateSizes: false });

    // apply bulk optimization
    await page.goto('/wp-admin/upload.php?page=tiny-bulk-optimization');
    await page.getByRole('button', { name: 'Compress 2 images' }).click();

    // both images end up optimized: one only converted, the other compressed and converted
    await expect(page.getByRole('heading', { name: 'All 2 images are compressed' })).toBeVisible({ timeout: 15000 });
    await expect(page.locator('.tiny-bulk-item[data-status="compressed"]')).toHaveCount(2);
  });
});

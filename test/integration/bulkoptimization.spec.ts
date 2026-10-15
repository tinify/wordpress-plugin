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

  test('display upgrade button for account with insufficient credits', async () => {
    await setAPIKey(page, 'INSUFFICIENTCREDITS123');
    await setCompressionTiming(page, 'auto');
    await enableCompressionSizes(page, ['0', 'thumbnail', 'medium']);

    await uploadImages(page, 'input-example.jpg');

    await page.goto('/wp-admin/upload.php?page=tiny-bulk-optimization');

    await expect(page.getByText('Upgrade account')).toBeVisible();
    await expect(page.getByRole('link', { name: 'No thanks, continue anyway' })).toBeVisible();
  });

  test('not display dismiss link for no credits', async () => {
    await setAPIKey(page, 'NOCREDITS123');
    await setCompressionTiming(page, 'auto');
    await enableCompressionSizes(page, ['0', 'thumbnail', 'medium']);

    await uploadImages(page, 'input-example.jpg');

    await page.goto('/wp-admin/upload.php?page=tiny-bulk-optimization');

    await expect(page.getByText('Upgrade account')).toBeVisible();
    await expect(page.getByRole('link', { name: 'No thanks, continue anyway' })).not.toBeVisible();
  });

  test('show bulk optimization button after dismissing notice', async () => {
    await setAPIKey(page, 'INSUFFICIENTCREDITS123');
    await setCompressionTiming(page, 'auto');
    await enableCompressionSizes(page, ['0', 'thumbnail', 'medium']);

    await uploadImages(page, 'input-example.jpg');

    await page.goto('/wp-admin/upload.php?page=tiny-bulk-optimization');

    await page.getByRole('link', { name: 'No thanks, continue anyway' }).click();

    await expect(page.getByRole('button', { name: 'Start Bulk Optimization' })).toBeVisible();
  });

  test('show notice after dismissing notice and refreshing page', async () => {
    await setAPIKey(page, 'INSUFFICIENTCREDITS123');
    await setCompressionTiming(page, 'auto');
    await enableCompressionSizes(page, ['0', 'thumbnail', 'medium']);

    await uploadImages(page, 'input-example.jpg');

    await page.goto('/wp-admin/upload.php?page=tiny-bulk-optimization');

    await page.getByRole('link', { name: 'No thanks, continue anyway' }).click();

    await page.reload();

    await expect(page.getByRole('button', { name: 'Start Bulk Optimization' })).not.toBeVisible();
    await expect(page.getByRole('link', { name: 'Upgrade account' })).toBeVisible();
    await expect(page.getByRole('link', { name: 'No thanks, continue anyway' })).toBeVisible();
  });

  test('not display upgrade button for paid accounts', async () => {
    await setAPIKey(page, 'PAID123');

    await page.goto('/wp-admin/upload.php?page=tiny-bulk-optimization');

    await expect(page.getByText('Upgrade account')).not.toBeVisible();
  });

  test('summary should display correct values for empty library', async () => {
    await enableCompressionSizes(page, ['0', 'thumbnail', 'medium']);

    await page.goto('/wp-admin/upload.php?page=tiny-bulk-optimization');

    await expect(page.locator('#uploaded-images')).toHaveText('0');
    await expect(page.locator('#optimizable-image-sizes')).toHaveText('0');
    await expect(page.locator('#estimated-cost')).toHaveText('$ 0.00');
    await expect(page.locator('#optimized-image-sizes')).toHaveText('0');
    await expect(page.locator('#unoptimized-library-size')).toHaveText('-');
    await expect(page.locator('#optimized-library-size')).toHaveText('-');
    await expect(page.locator('#savings-percentage')).toHaveText('0%');
    await expect(page.locator('#compression-progress-bar')).toHaveText('0 / 0 (100%)');
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

    await expect(page.locator('#uploaded-images')).toHaveText('3');
    await expect(page.locator('#optimizable-image-sizes')).toHaveText('5');
    await expect(page.locator('#optimized-image-sizes')).toHaveText('4');
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

    // We uploaded 3 images
    await expect(page.locator('#uploaded-images')).toHaveText('3');
    await expect(page.locator('#optimizable-image-sizes')).toHaveText('5');
    await expect(page.locator('#optimized-image-sizes')).toHaveText('4');

    // Comparing byte sizes is unreliable at the moment. We need to figure out
    // why there are differences between environments and versions.
    // await expect(page.locator('#unoptimized-library-size')).toHaveText('3.03 MB');
    // await expect(page.locator('#optimized-library-size')).toHaveText('2.36 MB');
    // await expect(page.locator('#savings-percentage')).toHaveText('22.2%');

    await expect(page.locator('#compression-progress-bar')).toHaveText('4 / 9 (44%)');
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

    await expect(page.locator('#optimizable-image-sizes')).toHaveText('5');
    await expect(page.locator('#compression-progress-bar')).toHaveText('4 / 9 (44%)');

    await page.getByRole('button', { name: 'Start Bulk Optimization' }).click();

    await expect(page.locator('.optimization-buttons_notice')).toContainText('continues in the background');
    await expect(page.locator('#compression-progress-bar')).toHaveText('9 / 9 (100%)');
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

    await expect(page.locator('#optimizable-image-sizes')).toHaveText('5');
    await expect(page.locator('#compression-progress-bar')).toHaveText('4 / 9 (44%)');

    await page.getByRole('button', { name: 'Start Bulk Optimization' }).click();

    await expect(page.locator('.optimization-buttons_notice')).toContainText('keep this page open');
    await expect(page.locator('#compression-progress-bar')).toHaveText('9 / 9 (100%)');
  });

  test('background optimization continues when page is refreshed', async () => {
    await setAPIKey(page, 'JPG123');
    await setCompressionTiming(page, 'manual');
    await enableCompressionSizes(page, ['0']);
    await uploadImages(page, 'input-example.jpg', 1, { generateSizes: false });
    await setBulkBehaviour(page, { loopbackSupported: true, compressionDuration: 10 });

    await page.goto('/wp-admin/upload.php?page=tiny-bulk-optimization');
    await page.getByRole('button', { name: 'Start Bulk Optimization' }).click();
    await expect(page.locator('.optimization-buttons_notice')).toContainText('continues in the background');

    await page.reload();

    await expect(page.locator('.optimization-buttons_notice')).toContainText('continues in the background');
    await expect(page.locator('#compression-progress-bar')).toHaveText('1 / 1 (100%)', { timeout: 15000 });
  });

  test('cancel will clear the queue', async () => {
    await setAPIKey(page, 'JPG123');
    await setCompressionTiming(page, 'manual');
    await enableCompressionSizes(page, ['0']);
    await uploadImages(page, 'input-example.jpg', 6, { generateSizes: false });
    await setBulkBehaviour(page, { loopbackSupported: false, compressionDuration: 5 });

    await page.goto('/wp-admin/upload.php?page=tiny-bulk-optimization');
    await page.getByRole('button', { name: 'Start Bulk Optimization' }).click();
    await expect(page.locator('.optimization-buttons_notice')).toContainText('keep this page open');

    // The cursor still rests where Start was clicked, so Optimizing can already have turned into Cancel.
    await page.locator('#id-optimizing, #id-cancel').filter({ visible: true }).hover();
    await page.locator('#id-cancel').click();

    // Images that were being compressed finish, the others are cancelled.
    await expect(page.locator('#bulk-optimization-actions')).toBeHidden({ timeout: 15000 });
    await expect(page.locator('[data-status="cancelled"]').first()).toHaveText('Cancelled');
    await expect(page.getByText('All images are processed')).not.toBeVisible();
  });

  test('stop when the account runs out of credits', async () => {
    await setAPIKey(page, 'LIMIT123');
    await setCompressionTiming(page, 'manual');
    await enableCompressionSizes(page, ['0']);
    await uploadImages(page, 'input-example.jpg', 6, { generateSizes: false });

    await page.goto('/wp-admin/upload.php?page=tiny-bulk-optimization');
    await page.getByRole('button', { name: 'Start Bulk Optimization' }).click();

    // The first image to hit the limit takes the rest off the queue.
    await expect(page.getByText('Optimization stopped early.')).toBeVisible({ timeout: 15000 });
    await expect(page.locator('[data-status="cancelled"]').first()).toHaveText('Stopped');
  });

  test('should display tooltips', async () => {
    await page.goto('/wp-admin/upload.php?page=tiny-bulk-optimization');
    const tooltips = await page.locator('div.tip').all();
    await expect(tooltips.length).toEqual(1);
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
    await Promise.all([
      page.waitForResponse(res => 
        res.url().includes('admin-ajax.php') && res.status() === 200
      ),
      page.locator('#id-start').click(),
    ]);
    
    // we are expecting 2 unoptimized images sizes = 2 conversion and 1 compression
    // expect two rows in the optimization items table
    const optimizationRows = page.locator('#optimization-items tbody tr');
    await expect(optimizationRows).toHaveCount(2);
    
    // first row should have "1 compressed and 1 converted"
    // second row should have "1 converted"
    await expect(page.getByTestId(`bulk-item-status-1`)).toContainText('1 compressed 1 converted');
    await expect(page.getByTestId(`bulk-item-status-0`)).toContainText('1 converted');

    // this should have cost us 3 credits
  });
});

import { Page, expect, test } from '@playwright/test';
import { clearMediaLibrary, enableCompressionSizes, getWPVersion, setAPIKey, setCompressionTiming, setOriginalImage, uploadMedia } from './utils';

test.describe.configure({ mode: 'serial' });

let page: Page;
let WPVersion = 0;

test.describe('bulkoptimization', () => {
  test.beforeAll(async ({ browser }) => {
    page = await browser.newPage();
    WPVersion = await getWPVersion(page);
  });

  test.beforeEach(async () => {
    await clearMediaLibrary(page);
  });

  test('display upgrade button for account with insufficient credits', async () => {
    await setAPIKey(page, 'INSUFFICIENTCREDITS123');
    await setCompressionTiming(page, 'auto');
    await enableCompressionSizes(page, ['0', 'thumbnail', 'medium']);

    await uploadMedia(page, 'input-example.jpg');

    await page.goto('/wp-admin/upload.php?page=tiny-bulk-optimization');

    await expect(page.getByText('Upgrade account')).toBeVisible();
    await expect(page.getByRole('link', { name: 'No thanks, continue anyway' })).toBeVisible();
  });

  test('not display dismiss link for no credits', async () => {
    await setAPIKey(page, 'NOCREDITS123');
    await setCompressionTiming(page, 'auto');
    await enableCompressionSizes(page, ['0', 'thumbnail', 'medium']);

    await uploadMedia(page, 'input-example.jpg');

    await page.goto('/wp-admin/upload.php?page=tiny-bulk-optimization');

    await expect(page.getByText('Upgrade account')).toBeVisible();
    await expect(page.getByRole('link', { name: 'No thanks, continue anyway' })).not.toBeVisible();
  });

  test('show bulk optimization button after dismissing notice', async () => {
    await setAPIKey(page, 'INSUFFICIENTCREDITS123');
    await setCompressionTiming(page, 'auto');
    await enableCompressionSizes(page, ['0', 'thumbnail', 'medium']);

    await uploadMedia(page, 'input-example.jpg');

    await page.goto('/wp-admin/upload.php?page=tiny-bulk-optimization');

    await page.getByRole('link', { name: 'No thanks, continue anyway' }).click();

    await expect(page.getByRole('button', { name: 'Start Bulk Optimization' })).toBeVisible();
  });

  test('show notice after dismissing notice and refreshing page', async () => {
    await setAPIKey(page, 'INSUFFICIENTCREDITS123');
    await setCompressionTiming(page, 'auto');
    await enableCompressionSizes(page, ['0', 'thumbnail', 'medium']);

    await uploadMedia(page, 'input-example.jpg');

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
    await uploadMedia(page, 'input-example.jpg');

    await enableCompressionSizes(page, ['0']);
    await uploadMedia(page, 'input-example.jpg');

    await enableCompressionSizes(page, ['0', 'thumbnail', 'medium']);
    await uploadMedia(page, 'input-example.jpg');

    await page.goto('/wp-admin/upload.php?page=tiny-bulk-optimization');

    await expect(page.locator('#uploaded-images')).toHaveText('3');
    await expect(page.locator('#optimizable-image-sizes')).toHaveText('5');
    await expect(page.locator('#optimized-image-sizes')).toHaveText('4');

    if (WPVersion < 5.7) {
      await expect(page.locator('#unoptimized-library-size')).toHaveText('3.03 MB');
      await expect(page.locator('#optimized-library-size')).toHaveText('2.36 MB');
      await expect(page.locator('#savings-percentage')).toHaveText('22.2%');
    } else if (WPVersion < 6.0) {
      await expect(page.locator('#unoptimized-library-size')).toHaveText('3.57 MB');
      await expect(page.locator('#optimized-library-size')).toHaveText('2.90 MB');
      await expect(page.locator('#savings-percentage')).toHaveText('18.9%');
    } else {
      await expect(page.locator('#unoptimized-library-size')).toHaveText('2.84 MB');
      await expect(page.locator('#optimized-library-size')).toHaveText('2.16 MB');
      await expect(page.locator('#savings-percentage')).toHaveText('23.8%');
    }
    await expect(page.locator('#compression-progress-bar')).toHaveText('4 / 9 (44%)');
  });

  test('start bulk optimization should optimize remaining images', async () => {
    await setAPIKey(page, 'JPG123');
    await setCompressionTiming(page, 'auto');

    await enableCompressionSizes(page, []);
    await uploadMedia(page, 'input-example.jpg');

    await enableCompressionSizes(page, ['0']);
    await uploadMedia(page, 'input-example.jpg');

    await enableCompressionSizes(page, ['0', 'thumbnail', 'medium']);
    await uploadMedia(page, 'input-example.jpg');

    await page.goto('/wp-admin/upload.php?page=tiny-bulk-optimization');

    await expect(page.locator('#optimizable-image-sizes')).toHaveText('5');
    await expect(page.locator('#compression-progress-bar')).toHaveText('4 / 9 (44%)');

    await page.getByRole('button', { name: 'Start Bulk Optimization' }).click();

    await expect(page.locator('#compression-progress-bar')).toHaveText('9 / 9 (100%)');
  });

  test('should display tooltips', async () => {
    await page.goto('/wp-admin/upload.php?page=tiny-bulk-optimization');
    const tooltips = await page.locator('div.tip').all();
    await expect(tooltips.length).toEqual(1);
  });
});

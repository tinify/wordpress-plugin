import { Page, expect, test } from '@playwright/test';
import { clearMediaLibrary, setAPIKey, setCompressionTiming, uploadImages, uploadMedia, enableCompressionSizes } from './utils';

test.describe.configure({ mode: 'serial' });

let page: Page;

const widget = () => page.locator('#tinypng_dashboard_widget');

// The settings page fetches the account details over ajax after saving,
// so wait for that before the dashboard reads them.
async function setAccount(key: string) {
  await setAPIKey(page, key);
  await page.waitForURL(/settings-updated=true/);
  await page.waitForLoadState('networkidle');
}

test.describe('dashboardwidget', () => {
  test.beforeAll(async ({ browser }) => {
    page = await browser.newPage({ viewport: { width: 1440, height: 900 } });
    await setAPIKey(page, '');
    await setCompressionTiming(page, 'auto');
    await enableCompressionSizes(page, [], true); // enable all sizes
  });

  test.beforeEach(async () => {
    await clearMediaLibrary(page);
    await page.context().clearCookies({ name: /^tiny_test_/ });
  });

  test('not connected without api key', async () => {
    await setAPIKey(page, '');
    await uploadMedia(page, 'input-example.png');

    await page.goto('/wp-admin/index.php');

    await expect(widget().getByText('Not connected')).toBeVisible();
    await expect(widget().getByText('0%')).toBeVisible();
    await expect(widget().getByText('1 to compress')).toBeVisible();
    await expect(widget().getByRole('link', { name: 'Connect Tinify' })).toHaveAttribute('href', /page=tinify/);
  });

  test('no images yet', async () => {
    await setAPIKey(page, 'JPG123');

    await page.goto('/wp-admin/index.php');

    await expect(widget().getByText('No images yet')).toBeVisible();
    await expect(widget().getByRole('link', { name: 'Upload images' })).toHaveAttribute('href', /media-new\.php/);
  });

  test('ready to compress', async () => {
    await setAccount('JPG123');
    await setCompressionTiming(page, 'manual');
    await uploadImages(page, 'input-example.jpg');

    await page.goto('/wp-admin/index.php');

    await expect(widget().getByText('Ready to compress')).toBeVisible();
    await expect(widget().getByRole('link', { name: 'Compress 1 image' })).toHaveAttribute('href', /page=tiny-bulk-optimization&start=1$/);
  });

  test('all done', async () => {
    await setAPIKey(page, 'JPG123');
    await setCompressionTiming(page, 'auto');
    await uploadMedia(page, 'input-example.jpg');

    await page.goto('/wp-admin/index.php');

    await expect(widget().getByText('All done')).toBeVisible();
    await expect(widget().getByText('100%')).toBeVisible();
    await expect(widget().getByText('1 compressed')).toBeVisible();
  });

  test('start flag starts the bulk optimization', async () => {
    await setAPIKey(page, 'JPG123');
    await setCompressionTiming(page, 'manual');
    await uploadImages(page, 'input-example.jpg', 1, { generateSizes: false });

    await page.goto('/wp-admin/upload.php?page=tiny-bulk-optimization&start=1');

    await expect(page).toHaveURL(/page=tiny-bulk-optimization$/);
    await expect(page.locator('#compression-progress-bar')).toHaveText('1 / 1 (100%)');
  });

  test('upgrade shows how to get more credits', async () => {
    await setAccount('INSUFFICIENTCREDITS123');
    await setCompressionTiming(page, 'auto');
    await uploadImages(page, 'input-example.jpg');

    await page.goto('/wp-admin/index.php');

    await expect(widget().getByText('Not enough credits')).toBeVisible();
    await widget().getByRole('button', { name: 'Upgrade' }).click();

    await expect(widget().getByText('Get more credits')).toBeVisible();
    await expect(widget().getByText('Not enough credits')).not.toBeVisible();
    await expect(widget().getByRole('link', { name: 'Buy a credit bundle' })).toHaveAttribute('href', 'https://tinify.com/checkout/credits?utm_source=wordpress-plugin&utm_medium=referral&utm_campaign=upgrade&utm_content=dashboard-widget-credits');
    await expect(widget().getByRole('link', { name: 'Switch to pay per compression' })).toHaveAttribute('href', 'https://tinify.com/checkout/api?utm_source=wordpress-plugin&utm_medium=referral&utm_campaign=upgrade&utm_content=dashboard-widget-api');

    await widget().getByRole('button', { name: 'Back' }).click();

    await expect(widget().getByText('Not enough credits')).toBeVisible();
    await expect(widget().getByText('Get more credits')).not.toBeVisible();
  });
});

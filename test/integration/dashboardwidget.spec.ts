import { Page, expect, test } from '@playwright/test';
import { clearMediaLibrary, setAPIKey, setCompressionTiming, uploadMedia, enableCompressionSizes } from './utils';

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
    // The widget hides its notices when narrower than 560px, which it is
    // in the two column dashboard at the default 1280px viewport.
    page = await browser.newPage({ viewport: { width: 1440, height: 900 } });
    await setAPIKey(page, '');
    await setCompressionTiming(page, 'auto');
    await enableCompressionSizes(page, [], true); // enable all sizes
  });

  test.beforeEach(async () => {
    await clearMediaLibrary(page);
  });

  test('show widget without images', async () => {
    await setAPIKey(page, 'JPG123');

    await page.goto('/wp-admin/index.php');

    await expect(widget().getByText('George is hungry')).toBeVisible();
    await expect(widget().getByText('There are no images uploaded yet.')).toBeVisible();
  });

  test('show notice without api key', async () => {
    await setAPIKey(page, '');

    await page.goto('/wp-admin/index.php');

    await expect(widget().getByText('Please register or provide an API key to start compressing images.')).toBeVisible();
  });

  test('show widget with images to optimize', async () => {
    // It won't compress images without an API Key
    await setAPIKey(page, '');
    await uploadMedia(page, 'input-example.png');

    await page.goto('/wp-admin/index.php');

    await expect(widget().getByText('1 image needs optimization')).toBeVisible();
    await expect(widget().getByText('Please register or provide an API key to start compressing images.')).toBeVisible();
    await expect(widget().getByRole('link', { name: 'Bulk Optimizer' })).toBeVisible();
  });

  test('show widget with all images optimized', async () => {
    await setAPIKey(page, 'JPG123');
    await uploadMedia(page, 'input-example.jpg');

    await page.goto('/wp-admin/index.php');

    await expect(widget().getByText('All images are optimized')).toBeVisible();
    await expect(widget().getByText('100%')).toBeVisible();
    await expect(widget().getByRole('link', { name: 'Bulk Optimizer' })).not.toBeVisible();
  });

  test('show notice with low credits on free plan', async () => {
    await setAccount('INSUFFICIENTCREDITS123');

    await page.goto('/wp-admin/index.php');

    await expect(widget().getByText('You are on a free plan with 1 compression left.')).toBeVisible();
  });

  test('not show credits on paid plan', async () => {
    await setAccount('JPG123');

    await page.goto('/wp-admin/index.php');

    await expect(widget().getByText('George is hungry')).toBeVisible();
    await expect(widget().getByText('compressions left')).not.toBeVisible();
  });
});

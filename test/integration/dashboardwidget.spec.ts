import { Page, expect, test } from '@playwright/test';
import { clearMediaLibrary, setAPIKey, setCompressionTiming, uploadMedia } from './utils';

test.describe.configure({ mode: 'serial' });

let page: Page;

test.describe('dashboardwidget', () => {
  test.beforeAll(async ({ browser }) => {
    page = await browser.newPage();
    await setAPIKey(page, '');
  });

  test.beforeEach(async () => {
    await clearMediaLibrary(page);
  });

  test('show widget without images', async () => {
    await page.goto('/wp-admin/index.php');
    await expect(page.getByText('You do not seem to have uploaded any JPEG, PNG or WebP images yet.')).toBeVisible();
  });

  test('show widget without optimized images', async () => {
    // It won't compress images without an API Key
    await setAPIKey(page, '');
    await setCompressionTiming(page, 'auto');

    await uploadMedia(page, 'input-example.png');

    await page.goto('/wp-admin/index.php');
    await expect(
      page.getByText('Hi Admin, you haven’t compressed any images in your media library. If you like you can to optimize your whole library in one go with the bulk optimization page.')
    ).toBeVisible();
  });

  test('show widget with some images optimized', async () => {
    await setAPIKey(page, '');

    await setCompressionTiming(page, 'auto');
  
    await uploadMedia(page, 'input-example.jpg');

    await setAPIKey(page, 'JPG123');

    await uploadMedia(page, 'input-example.jpg');

    await page.goto('/wp-admin/index.php');

    await expect(page.getByText('Admin, you are doing good. With your current settings you can still optimize')).toBeVisible();
  });

  test('show widget with all images optimized', async () => {
    await page.goto('/wp-admin/index.php');

    await uploadMedia(page, 'input-example.jpg');

    await page.goto('/wp-admin/index.php');

    await expect(page.getByText('Admin, this is great! Your entire library is optimized!')).toBeVisible();
  });
});

import { Page, expect, test } from '@playwright/test';
import { setAPIKey, uploadMedia } from './utils';

test.describe.configure({ mode: 'serial' });

let page: Page;

test.describe('dashboardwidget', () => {
  test.beforeAll(async ({ browser }) => {
    page = await browser.newPage();
    await setAPIKey(page, 'PNG123')
  });
  test('show widget without images', async () => {
    await page.goto('/wp-admin/index.php');
    await expect(page.getByText('You do not seem to have uploaded any JPEG, PNG or WebP images yet.')).toBeVisible();
  });

  test('show widget without optimized images', async () => {
    await page.goto('/wp-admin/options-general.php?page=tinify');
    await page.locator('#tinypng_compression_timing_auto').check();
    await page.locator('#submit').click();

    // upload something
    await uploadMedia(page, 'input-example.png');

    await page.goto('/wp-admin/index.php');
    await expect(
      page.getByText('Hi Admin, you haven’t compressed any images in your media library. If you like you can to optimize your whole library in one go with the bulk optimization page.')
    ).toBeVisible();
  });

  test('show widget with some images optimized', async () => {
    // set compression on auto
    // upload media here
    // set api key
    // upload something
    // validate if halve optimized

    await page.goto('/wp-admin/index.php');
    await expect(page.getByText('Admin, you are doing good. With your current settings you can still optimize')).toBeVisible();
  });

  test('show widget with all images optimized', async () => {
    // set compression on auto
    // set api key
    // upload something

    await page.goto('/wp-admin/index.php');
    await expect(page.getByText('Admin, this is great! Your entire library is optimized!')).toBeVisible();
  });
});

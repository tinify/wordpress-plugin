import { APIRequestContext, Page, expect, request, test } from '@playwright/test';
import { clearMediaLibrary, enableCompressionSizes, getWPVersion, newPost, setAPIKey, setCompressionTiming, setConversionSettings, uploadMedia } from './utils';

test.describe.configure({ mode: 'serial' });

let WPVersion = 0;

test.describe('conversion', () => {
  let page: Page;

  test.beforeAll(async ({ browser }) => {
    page = await browser.newPage();
    WPVersion = await getWPVersion(page);

    await setAPIKey(page, 'JPG123');
    await enableCompressionSizes(page, ['0'], false);
    await setCompressionTiming(page, 'auto');
  });

  test.beforeEach(async () => {
    await clearMediaLibrary(page);
  });

  test.afterAll(async () => {
    await page.close();
  });

  test('will add the optimized format to the original image', async () => {
    // JPG123 will ensure mock service returns a jpeg on /shrink
    await setConversionSettings(page, {
      convert: true,
    });
    await uploadMedia(page, 'input-example.jpg');

    await page.goto('/wp-admin/upload.php');

    await expect(page.getByText('1 size compressed')).toBeVisible();

    // thickbox is used to show modal window so wait until it is loaded
    await page.waitForLoadState('networkidle');
    await page.getByRole('link', { name: 'Details' }).click();

    const tableRows = await page.locator('.tiny-compression-details tr').all();
    const originalRow = tableRows[1];
    const cells = await originalRow.locator('td').all();
    expect(cells[4]).toContainText('image/avif (99.2');
  });

  test('will display the optimized image on a page', async () => {
    const media = await uploadMedia(page, 'input-example.jpg');
    const postURL = await newPost(page, {
      title: 'test',
      content: `<figure class="wp-block-image size-large" id="tinytest"><img src="${media}" alt="" class="wp-image-209"/></figure>`,
    }, WPVersion);

    await page.goto(postURL);
    
    const img = await page.locator('[srcset*="input-example.avif"]');
    await expect(img).toBeVisible();
  });
});

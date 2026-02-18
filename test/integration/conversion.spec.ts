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
      output: 'smallest',
      delivery: 'picture',
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
    await setConversionSettings(page, {
      convert: true,
      output: 'smallest',
      delivery: 'picture',
    });
    const media = await uploadMedia(page, 'input-example.jpg');
    const postID = await newPost(
      page,
      {
        title: 'test',
        content: `<figure class="wp-block-image size-large" id="tinytest"><img src="${media}" alt="" class="wp-image-209"/></figure>`,
      },
      WPVersion
    );

    await page.goto(`/?p=${postID}`);

    const picture = await page.locator('picture:has(source[srcset*="input-example.avif"][type="image/avif"])');
    await expect(picture).toBeVisible();
  });

  test('will serve optimized image when server side rules are configured', async () => {
    await setConversionSettings(page, {
      convert: true,
      output: 'smallest',
      delivery: 'htaccess',
    });
    const media = await uploadMedia(page, 'input-example.jpg');
    const postID = await newPost(
      page,
      {
        title: 'test',
        content: `<figure class="wp-block-image size-large" id="tinytest"><img src="${media}" alt="" class="wp-image-209"/></figure>`,
      },
      WPVersion
    );

    const imageResponsePromise = page.waitForResponse((response) => response.url().includes('input-example.jpg'), { timeout: 10000 });

    await page.goto(`/?p=${postID}`);

    await imageResponsePromise;

    const response = await page.request.get(media, {
      headers: {
        Accept: 'image/avif,image/webp,*/*', // browser automatically add this
      },
    });
    const buffer = await response.body();
    const signature = buffer.toString('ascii', 0, 16);

    expect(signature).toContain('ftypavif');
  });
});

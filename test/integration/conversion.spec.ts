import { Page, expect, test } from '@playwright/test';
import { activatePlugin, clearMediaLibrary, deactivatePlugin, enableCompressionSizes, getWPVersion, setAPIKey, setCompressionTiming, setConversionSettings, uploadMedia } from './utils';

test.describe.configure({ mode: 'serial' });

let WPVersion = 0;

test.describe('conversion', () => {
  let page: Page;

  test.beforeAll(async ({ browser }) => {
    page = await browser.newPage();
    WPVersion = await getWPVersion(page);

    await setAPIKey(page, 'AVIF123');
    await enableCompressionSizes(page, [], true);
    await setCompressionTiming(page, 'auto');
    await setConversionSettings(page, {
      replace: false,
      convert: true,
    })
  });

  test.afterAll(async () => {
    await page.close();
  });

  test('will add the optimized format to the original image', async () => {});

  test('will replace the original image with the optimized image', async () => {
    await uploadMedia(page, 'input-example.jpg');

  });

  test('will display the optimized image on a page', async () => {});

  test('will delete the optimized image when the original is deleted', async () => {});
});

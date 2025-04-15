import { Page, expect, test } from '@playwright/test';
import { activatePlugin, clearMediaLibrary, deactivatePlugin, enableCompressionSizes, getWPVersion, setAPIKey, setCompressionTiming, setConversionSettings, uploadMedia } from './utils';

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
      replace: false,
      convert: true,
    });
    await uploadMedia(page, 'input-example.jpg');

    await page.goto('/wp-admin/upload.php');

    await expect(page.getByText('1 size compressed')).toBeVisible();

    // thickbox is used to show modal window so wait until it is loaded
    await page.waitForLoadState('networkidle');
    await page.getByRole('link', { name: 'Details' }).click();

    const tableRows = await page.locator('.tiny-compression-details tr').all();
    const origialRow = tableRows[1];
    const cells = await origialRow.locator('td').all();
    expect(cells[4]).toContainText('image/avif (99.2 KB)');
  });

  test('will display the optimized image on a page', async () => {
    await uploadMedia(page, 'input-example.jpg');
    
    await page.goto('/wp-admin/post-new.php');
    await page.locator('iframe[name="editor-canvas"]').contentFrame().getByLabel('Add block').click();
    await page.getByRole('option', { name: 'Image' }).click();
    await page.locator('iframe[name="editor-canvas"]').contentFrame().getByRole('button', { name: 'Media Library' }).click();
    await page.getByLabel('input-example').click();
    await page.getByRole('button', { name: 'Select', exact: true }).click();
    await page.getByRole('button', { name: 'Publish', exact: true }).click();
    await page.getByLabel('Editor publish').getByRole('button', { name: 'Publish', exact: true }).click();
    await page.getByTestId('snackbar').getByRole('link', { name: 'View Post' }).click();
    await page.locator('img').click();
  });

  test('will delete the optimized image when the original is deleted', async () => {});
});

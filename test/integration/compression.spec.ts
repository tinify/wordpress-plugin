import { Page, expect, test } from '@playwright/test';
import { clearMediaLibrary, enableCompressionSizes, setAPIKey, setCompressionTiming, uploadMedia } from './utils';

test.describe.configure({ mode: 'serial' });

let page: Page;

test.describe('compression', () => {
    test.beforeAll(async ({ browser }) => {
      page = await browser.newPage();
      await setAPIKey(page, '');
    });

    test.beforeEach(async () => {
        await clearMediaLibrary(page);
    });

    test('upload without key should show error', async () => {
        await uploadMedia(page, 'input-example.jpg');
        
        await page.goto('/wp-admin/upload.php');

        await expect(page.getByText('Latest error: Register an account or provide an API key first')).toBeVisible();
    });

    test('upload with invalid key should show error', async () => {
        await setAPIKey(page, '1234');
        await setCompressionTiming(page, 'auto');

        await uploadMedia(page, 'input-example.jpg');
        
        await page.goto('/wp-admin/upload.php');

        await expect(page.getByText('Latest error: Credentials are invalid')).toBeVisible();
    });

    test('upload with limited key should show error', async () => {
        await setAPIKey(page, 'LIMIT123');
        await setCompressionTiming(page, 'auto');
        
        await uploadMedia(page, 'input-example.jpg');
        
        await page.goto('/wp-admin/upload.php');

        await expect(page.getByText('Latest error: Your monthly limit has been exceeded')).toBeVisible();
    });

    test('upload with valid key should show sizes compressed', async () => {
        await setAPIKey(page, 'JPG123');
        await setCompressionTiming(page, 'auto');
        
        await uploadMedia(page, 'input-example.jpg');
        
        await page.goto('/wp-admin/upload.php');

        await expect(page.getByText('1 size compressed')).toBeVisible();
    });

    test('upload with gateway timeout should show error', async () => {
        await setAPIKey(page, 'GATEWAYTIMEOUT');
        await setCompressionTiming(page, 'auto');
        await enableCompressionSizes(page, ['medium']);
        
        await uploadMedia(page, 'input-example.jpg');
        
        await page.goto('/wp-admin/upload.php');

        await expect(page.getByText('Error while parsing response')).toBeVisible();
    });

    test('upload with incorrect metadata should show error', async () => {
        await setAPIKey(page, 'PNG123 INVALID');
        await setCompressionTiming(page, 'auto');
        await enableCompressionSizes(page, ['0', 'medium']);
        await page.goto('options-general.php?page=tinify');
        await page.locator('#tinypng_preserve_data_copyright').check({ force: true });
        await page.locator('#submit').click();
        
        await uploadMedia(page, 'input-example.jpg');
        
        await page.goto('/wp-admin/upload.php');

        await expect(page.getByText(`Metadata key 'author' not supported`)).toBeVisible();
    });

    test('show details in edit screen', async () => {
        await setAPIKey(page, 'JPG123');
        await setCompressionTiming(page, 'auto');
        await enableCompressionSizes(page, [], false);

        await uploadMedia(page, 'input-example.jpg');

        await enableCompressionSizes(page, ['medium', 'large']);

        await page.goto('/wp-admin/upload.php');
        await page.getByLabel('“input-example” (Edit)').click();
        await expect(page.locator('2 sizes to be compressed')).toBeVisible();
    });

    test('show compression details in edit screen popup', async () => {
        await setAPIKey(page, 'JPG123');
        await setCompressionTiming(page, 'auto');
        await enableCompressionSizes(page, ['medium', 'large']);

        await uploadMedia(page, 'input-example.jpg');

        await page.getByLabel('“input-example” (Edit)').click();

        // thickbox is used to show modal window so wait until it is loaded
        await page.waitForLoadState('networkidle'); 

        await page.getByRole('link', { name: 'Details' }).click({ force: true });

        await page.waitForSelector('#TB_overlay');

        const expectedText = [
            ['Original', 'Not configured to be compressed'],
            ['Large', '147.5 KB'],
            ['Medium', '147.5 KB'],
            ['Medium_large', 'Not configured to be compressed'],
            ['Thumbnail', 'Not configured to be compressed'],
            ['1536x1536', 'Not present'],
            ['2048x2048', 'Not present']
        ];
        const tableRows = await page.locator('.tiny-compression-details tr').all();
        for (let i = 0; i < tableRows.length - 1; i++) {
            if (i === 0) {
                // skip header
                continue;
            };
            const row = tableRows[i];
            const cells = await row.locator('td').all(); 
            await expect(cells[0]).toHaveText(expectedText[i - 1][0]);
            await expect(cells[2]).toHaveText(expectedText[i - 1][1]);
        }
    });


});
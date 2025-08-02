import { Page, expect, test } from '@playwright/test';
import fs from 'fs/promises';
import path from 'path';
import { BASE_URL, clearMediaLibrary, enableCompressionSizes, getWPVersion, setAPIKey, setCompressionTiming, setOriginalImage, uploadMedia } from './utils';

test.describe.configure({ mode: 'serial' });

let page: Page;
let WPVersion = 0;

function viewImage(page: Page, file: string) {
  page.getByLabel(`“${file}” (Edit)`).click();
}

test.describe('compression', () => {
  test.beforeAll(async ({ browser }) => {
    page = await browser.newPage();
    WPVersion = await getWPVersion(page);
  });

  test.beforeEach(async () => {
    await clearMediaLibrary(page);
  });

  test('upload without key should show error', async () => {
    await setAPIKey(page, '');
    await setCompressionTiming(page, 'auto');
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
    await enableCompressionSizes(page, ['medium']);

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

    await page.goto('/wp-admin/options-general.php?page=tinify');
    await page.locator('#tinypng_preserve_data_copyright').check();
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
    await expect(page.getByText('2 sizes to be compressed')).toBeVisible();
  });

  test('show compression details in edit screen popup', async () => {
    await setAPIKey(page, 'JPG123');
    await setCompressionTiming(page, 'auto');
    await enableCompressionSizes(page, ['medium', 'large']);

    await uploadMedia(page, 'input-example.jpg');

    await viewImage(page, 'input-example');

    // thickbox is used to show modal window so wait until it is loaded
    await page.waitForLoadState('networkidle');

    await page.getByRole('link', { name: 'Details' }).click({ force: true });

    await page.waitForSelector('#TB_overlay');

    const expectedSizes: Record<string, string> = {
      Original: 'Not configured to be compressed',
      Large: '147.5',
      Medium: '147.5',
      Thumbnail: 'Not configured to be compressed',
    };
    const tableRows = await page.locator('.tiny-compression-details tr').all();
    for (let i = 1; i < tableRows.length; i++) {
      const row = tableRows[i];
      const cells = await row.locator('td').all();
      const sizeName = await cells[0].textContent();
      if (sizeName && expectedSizes[sizeName]) {
        await expect(cells[2]).toContainText(expectedSizes[sizeName]);
      }
    }
  });

  test('button in edit screen should compress images', async () => {
    await setAPIKey(page, '');
    await setCompressionTiming(page, 'manual');
    await enableCompressionSizes(page, ['medium', 'large']);
    await setAPIKey(page, 'JPG123');
    
    await uploadMedia(page, 'input-example.jpg');

    await page.goto('/wp-admin/upload.php');

    await page.getByRole('button', { name: 'Compress', exact: true }).click();
    await expect(page.getByText('2 sizes compressed')).toBeVisible();
  });

  test('compress button in edit screen should compress webp images', async () => {
    // https://make.wordpress.org/core/2021/06/07/wordpress-5-8-adds-webp-support/
    if (WPVersion < 5.8) return;

    await setAPIKey(page, '');
    await setCompressionTiming(page, 'auto');
    await uploadMedia(page, 'input-example.webp');
    await setAPIKey(page, 'JPG123');
    await enableCompressionSizes(page, ['medium', 'large', 'thumbnail']);

    await page.goto('/wp-admin/upload.php');

    await page.getByRole('button', { name: 'Compress', exact: true }).click();
    await expect(page.getByText('3 sizes compressed')).toBeVisible();
  });

  test('compress button should compress uncompressed sizes', async () => {
    await setAPIKey(page, 'JPG123');
    await setCompressionTiming(page, 'auto');
    await enableCompressionSizes(page, ['medium']);
    await uploadMedia(page, 'input-example.jpg');
    await enableCompressionSizes(page, ['medium', 'thumbnail']);

    await page.goto('/wp-admin/upload.php');
    await viewImage(page, 'input-example');

    await expect(page.getByText('1 size compressed')).toBeVisible();
    await expect(page.getByText('1 size to be compressed')).toBeVisible();

    await page.waitForLoadState('networkidle');

    await page.getByRole('button', { name: 'Compress', exact: true }).click({ force: true });

    await expect(page.getByText('2 sizes compressed')).toBeVisible();
  });

  test('button should show error for incorrect json', async () => {
    await setAPIKey(page, '');
    await setCompressionTiming(page, 'auto');
    await enableCompressionSizes(page, []);
    await uploadMedia(page, 'input-example.jpg');
    await enableCompressionSizes(page, ['medium', 'large']);

    await setAPIKey(page, 'JSON1234');
    await page.goto('/wp-admin/upload.php');

    await page.getByRole('button', { name: 'Compress', exact: true }).click();
    await expect(page.getByText('Error while parsing response')).toBeVisible();
  });

  test('limit reached dismiss button should remove error', async () => {
    await setAPIKey(page, 'LIMIT123');
    await setCompressionTiming(page, 'auto');
    await enableCompressionSizes(page, [], true);

    await uploadMedia(page, 'input-example.jpg');

    await page.goto('/wp-admin/upload.php');

    await expect(page.getByText('You have reached your free limit this month')).toBeVisible();

    // Since 4.2 it is no longer a link but a button.
    const isButton = WPVersion >= 4.2;
    if (isButton) {
      await page.getByRole('button', { name: 'Dismiss this notice.' }).click();
    } else {
      await page.getByRole('link', { name: 'Dismiss' }).click();
    }

    await page.goto('/wp-admin/options-general.php?page=tinify');

    await expect(page.getByText('Your monthly limit has been exceeded')).not.toBeVisible();
  });

  test('resize fit should display resized text in library', async () => {
    await setAPIKey(page, 'JPG123');
    await setCompressionTiming(page, 'auto');
    await enableCompressionSizes(page, [], true);
    await setOriginalImage(page, {
      resize: true,
      width: 300,
      height: 200,
      preserveDate: false,
      preserveCopyright: false,
      preserveGPS: false,
    });
    await uploadMedia(page, 'input-example.jpg');

    await page.goto('/wp-admin/upload.php');

    await page.waitForLoadState('networkidle');

    await page.getByRole('link', { name: 'Details' }).click({ force: true });

    await expect(page.getByText('Original (resized to 300x200)')).toBeVisible();
  });

  test('resize fit should display resized text in edit screen', async () => {
    await setAPIKey(page, 'JPG123');
    await setCompressionTiming(page, 'auto');
    await enableCompressionSizes(page, [], true);
    await setOriginalImage(page, {
      resize: true,
      width: 300,
      height: 200,
      preserveDate: false,
      preserveCopyright: false,
      preserveGPS: false,
    });

    await uploadMedia(page, 'input-example.jpg');

    await page.goto('/wp-admin/upload.php');
    await viewImage(page, 'input-example');

    const dimensionText = await page.locator('.misc-pub-section.misc-pub-dimensions').textContent();
    const shouldMatch = /.*300\s*(x|×|by)\s*200.*/;
    await expect(dimensionText?.trim()).toMatch(shouldMatch);
  });

  test('resize scale should display resized text in library', async () => {
    await setAPIKey(page, 'JPG123');
    await setCompressionTiming(page, 'auto');
    await enableCompressionSizes(page, [], true);
    await setOriginalImage(page, {
      resize: true,
      height: 200,
      preserveDate: false,
      preserveCopyright: false,
      preserveGPS: false,
    });

    await uploadMedia(page, 'input-example.jpg');

    await page.goto('/wp-admin/upload.php');

    await page.waitForLoadState('networkidle');

    await page.getByRole('link', { name: 'Details' }).click({ force: true });

    await expect(page.getByText('resized to 300x200')).toBeVisible();
  });

  test('resize scale should display resized text in edit screen', async () => {
    await setAPIKey(page, 'JPG123');
    await setCompressionTiming(page, 'auto');
    await enableCompressionSizes(page, [], true);
    await setOriginalImage(page, {
      resize: true,
      height: 200,
      preserveDate: false,
      preserveCopyright: false,
      preserveGPS: false,
    });

    await uploadMedia(page, 'input-example.jpg');

    await page.goto('/wp-admin/upload.php');
    await viewImage(page, 'input-example');

    const dimensionText = await page.locator('.misc-pub-section.misc-pub-dimensions').textContent();
    const shouldMatch = /.*300\s*(x|×|by)\s*200.*/;
    await expect(dimensionText?.trim()).toMatch(shouldMatch);
  });

  test('superfluous resize should not display resized text in library', async () => {
    await setAPIKey(page, 'JPG123');
    await setCompressionTiming(page, 'auto');
    await enableCompressionSizes(page, [], true);
    await setOriginalImage(page, {
      resize: true,
      width: 15000,
      height: 15000,
      preserveDate: false,
      preserveCopyright: false,
      preserveGPS: false,
    });

    await uploadMedia(page, 'input-example.jpg');

    await page.goto('/wp-admin/upload.php');

    await page.waitForLoadState('networkidle');

    await page.getByRole('link', { name: 'Details' }).click({ force: true });

    await expect(page.getByText('resized')).not.toBeVisible();
  });

  test('superfluous resize should display original dimension in edit screen', async () => {
    await setAPIKey(page, 'JPG123');
    await setCompressionTiming(page, 'auto');
    await enableCompressionSizes(page, [], true);
    await setOriginalImage(page, {
      resize: true,
      width: 15000,
      height: 15000,
      preserveDate: false,
      preserveCopyright: false,
      preserveGPS: false,
    });

    await uploadMedia(page, 'input-example.jpg');

    await page.goto('/wp-admin/upload.php');
    await viewImage(page, 'input-example');

    const dimensionText = await page.locator('.misc-pub-section.misc-pub-dimensions').textContent();
    const shouldMatch = /.*1080\s*(x|×|by)\s*720.*/;
    await expect(dimensionText?.trim()).toMatch(shouldMatch);
  });

  test('resize disabled should not display resized text in library', async () => {
    await setAPIKey(page, 'JPG123');
    await setCompressionTiming(page, 'auto');
    await enableCompressionSizes(page, [], true);
    await setOriginalImage(page, {
      resize: false,
      preserveDate: false,
      preserveCopyright: false,
      preserveGPS: false,
    });

    await uploadMedia(page, 'input-example.jpg');

    await page.goto('/wp-admin/upload.php');

    await expect(page.getByText('resized')).not.toBeVisible();
  });

  test('resize disabled should display original dimension in edit screen', async () => {
    await setAPIKey(page, 'JPG123');
    await setCompressionTiming(page, 'auto');
    await enableCompressionSizes(page, [], true);
    await setOriginalImage(page, {
      resize: false,
      preserveDate: false,
      preserveCopyright: false,
      preserveGPS: false,
    });
    await uploadMedia(page, 'input-example.jpg');
    await page.goto('/wp-admin/upload.php');
    await viewImage(page, 'input-example');

    const dimensionText = await page.locator('.misc-pub-section.misc-pub-dimensions').textContent();
    const shouldMatch = /.*1080\s*(x|×|by)\s*720.*/;
    await expect(dimensionText?.trim()).toMatch(shouldMatch);
  });

  test('preserve copyright should not display modification in library', async () => {
    await setAPIKey(page, 'PRESERVEJPG123');
    await setCompressionTiming(page, 'auto');
    await enableCompressionSizes(page, [], true);
    await setOriginalImage(page, {
      resize: false,
      preserveDate: false,
      preserveCopyright: true,
      preserveGPS: false,
    });

    await uploadMedia(page, 'input-copyright.jpg');

    await page.goto('/wp-admin/upload.php');

    await expect(page.getByText('files modified after compression')).not.toBeVisible();
  });

  test('unsupported format should not show compress info in library', async () => {
    await setCompressionTiming(page, 'auto');
    await enableCompressionSizes(page, [], true);
    await setOriginalImage(page, {
      resize: false,
      preserveDate: false,
      preserveCopyright: false,
      preserveGPS: false,
    });

    await uploadMedia(page, 'input-example.gif');

    await page.goto('/wp-admin/upload.php');

    await expect(page.getByRole('button', { name: 'Compress', exact: true })).not.toBeVisible();
  });

  test('non image file should not show compress info in library', async () => {
    await setCompressionTiming(page, 'auto');
    await enableCompressionSizes(page, [], true);
    await setOriginalImage(page, {
      resize: false,
      preserveDate: false,
      preserveCopyright: false,
      preserveGPS: false,
    });

    await uploadMedia(page, 'input-example.pdf');

    await page.goto('/wp-admin/upload.php');

    await expect(page.getByRole('button', { name: 'Compress', exact: true })).not.toBeVisible();
  });

  test('compresses images upload via JSON API', async () => {
    if (WPVersion < 4.7) {
      // Content REST API was introduced in 4.7
      return;
    }

    await setAPIKey(page, 'JPG123');
    await setCompressionTiming(page, 'auto');
    await enableCompressionSizes(page, ['0', 'medium']);

    const file = await fs.readFile(path.join(__dirname, '../fixtures/input-example.jpg'));
    const response = await page.evaluate(
      async (params) => {
        const authResult = await fetch(`${params.baseURL}/wp-admin/admin-ajax.php?action=rest-nonce`);
        const nonce = await authResult.text();

        const blob = new Blob([new Uint8Array(params.file)], { type: 'image/jpeg' });

        const mediaResponse = await fetch(`${params.baseURL}?rest_route=/wp/v2/media`, {
          method: 'POST',
          headers: {
            'X-WP-Nonce': nonce,
            'Content-Disposition': 'attachment; filename="input-example.jpg"',
          },
          body: blob,
        });
        const jsonResponse = await mediaResponse.json();
        return jsonResponse;
      },
      {
        file: Array.from(file),
        baseURL: BASE_URL,
      }
    );

    await page.goto(`/wp-admin/post.php?post=${response.id}&action=edit`);
    await expect(page.getByText('2 sizes compressed')).toBeVisible();
  });

  test('will mark a single attachment as compressed', async () => {
    await setAPIKey(page, 'JPG123');
    await setCompressionTiming(page, 'manual');
    await enableCompressionSizes(page, ['0', 'medium']);
    await uploadMedia(page, 'input-example.jpg');
    
    await page.goto('/wp-admin/upload.php');

    await page.getByRole('button', { name: 'Mark as Compressed' }).click();
    await expect(page.getByText('2 sizes compressed')).toBeVisible();
    await expect(page.getByText('2 sizes converted')).toBeVisible();
  });
  
  test('will mark multiple attachments as compressed', async () => {
    await setAPIKey(page, 'JPG123');
    await setCompressionTiming(page, 'manual');
    await enableCompressionSizes(page, ['0', 'medium']);
    
    await uploadMedia(page, 'input-example.jpg');
    await uploadMedia(page, 'input-example.png');

    await page.goto('/wp-admin/upload.php');

    await page.locator('#cb-select-all-1').check();
    await page.locator('#bulk-action-selector-top').selectOption('tiny_bulk_mark_compressed');
    await page.locator('#doaction').click();
    await expect(page.getByText('2 sizes compressed')).toBeVisible();
    await expect(page.getByText('2 sizes converted')).toBeVisible();
  });
});

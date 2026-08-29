import { Page, expect, test } from '@playwright/test';
import fs from 'fs/promises';
import path from 'path';
import { enableCompressionSizes, setAPIKey, setBackupEnabled, setCompressionTiming, setConversionSettings, setOriginalImage, uploadMedia } from './utils';

test.describe.configure({ mode: 'serial' });

let page: Page;

function viewImage(page: Page, attachmentID: string) {
  return page.goto(`/wp-admin/post.php?post=${attachmentID}&action=edit`);
}

test.describe('backup and restore', () => {
  test.beforeAll(async ({ browser }) => {
    page = await browser.newPage();
  });

  test.beforeEach(async () => {
    await setAPIKey(page, 'JPG123');
    await setCompressionTiming(page, 'auto');
    await enableCompressionSizes(page, ['0']);
    await setBackupEnabled(page, true);
    // Resizing or converting makes the mock return a different image, which
    // would break the comparisons against output-example.jpg below.
    await setOriginalImage(page, { resize: false, preserveDate: false, preserveCopyright: false, preserveGPS: false });
    await setConversionSettings(page, { convert: false });
  });

  test('enabling backup and compressing creates a backup of the original image', async () => {
    const original = await fs.readFile(path.join(__dirname, '../fixtures/input-example.jpg'));

    const { attachmentID } = await uploadMedia(page, 'input-example.jpg');

    await viewImage(page, attachmentID);
    await page.waitForLoadState('networkidle');
    await expect(page.getByText('1 size compressed')).toBeVisible();

    await page.getByRole('link', { name: 'Details' }).click({ force: true });
    await page.waitForSelector('#TB_overlay');

    const backupLink = page.getByRole('link', { name: 'View uncompressed file' });
    await expect(backupLink).toBeVisible();

    const backupURL = await backupLink.getAttribute('href');
    if (!backupURL) {
      throw new Error('backup link has no href');
    }

    const response = await page.request.get(backupURL);
    expect(response.ok()).toBeTruthy();

    const backupContent = await response.body();
    expect(backupContent.equals(original)).toBeTruthy();
  });

  test('restoring the backup replaces the compressed files with the original', async () => {
    const uncompressed = await fs.readFile(path.join(__dirname, '../fixtures/input-example.jpg'));
    const compressed = await fs.readFile(path.join(__dirname, '../mock-tinypng-webservice/output-example.jpg'));

    const { attachmentID, imageURL } = await uploadMedia(page, 'input-example.jpg');

    await viewImage(page, attachmentID);
    await page.waitForLoadState('networkidle');
    await expect(page.getByText('1 size compressed')).toBeVisible();

    // Sanity check: compression should have replaced the file on disk with a
    // different (compressed) version, otherwise the restore assertion below
    // would be meaningless.
    const compressedContent = await (await page.request.get(imageURL)).body();
    expect(compressedContent.equals(compressed)).toBeTruthy();

    await page.getByRole('link', { name: 'Details' }).click({ force: true });
    await page.waitForSelector('#TB_overlay');

    await page.getByRole('link', { name: 'Restore Backup' }).click();
    await expect(page.locator('dialog.tiny-dialog[open]')).toBeVisible();
    await page.getByRole('button', { name: 'Restore' }).click();

    await expect(page.getByText('1 size to be compressed')).toBeVisible();

    await page.reload();

    const restoredContent = await (await page.request.get(imageURL)).body();
    expect(restoredContent.equals(uncompressed)).toBeTruthy();
  });
});

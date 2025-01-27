import path from 'path';
import { Page } from '@playwright/test';

export async function uploadMedia(page: Page, file: string) {
  await page.goto('/wp-admin/media-new.php');
  const fileChooserPromise = page.waitForEvent('filechooser');
  await page.locator('#async-upload').click();
  const fileChooser = await fileChooserPromise;
  await fileChooser.setFiles(path.join(__dirname, `../fixtures/${file}`));
  await page.locator('#html-upload').click();
}

export async function clearMediaLibrary(page: Page) {
  await page.goto('/wp-admin/upload.php?mode=list');
  const hasNoFiles = await page.getByText('No media files found.').isVisible();
  if (hasNoFiles) {
    return;
  }
  await page.locator('#cb-select-all-1').check({ force: true });
  await page.locator('#bulk-action-selector-top').selectOption('delete');
  page.once('dialog', dialog => dialog.accept());
  await page.locator('#doaction').click();
}

export async function setAPIKey(page: Page, key = '') {
  await page.goto('/wp-admin/options-general.php?page=tinify');

  await page.waitForLoadState('networkidle');
  const changeAPIKey = await page.getByText('Change API key');
  const isVisible = await changeAPIKey.isVisible();
  if (isVisible) {
    await changeAPIKey.click();
    
  }
  await page.locator('#tinypng_api_key').fill(key);

  await page.locator('#submit').click();
}

/** Sets the compression timing to the preferred value
 * background: will upload asynchronously through ajax
 * auto: will compress before storing the file on drive
 * manual: when user clicks compress
 * @param  {Page} page the page context
 * @param  {'background'|'auto'|'manual'} timing the timing setting
 */
export async function setCompressionTiming(page: Page, timing: 'background' | 'auto' | 'manual') {
  await page.goto('/wp-admin/options-general.php?page=tinify');
  await page.locator(`#tinypng_compression_timing_${timing}`).check({ force: true });
  await page.locator('#submit').click();
}
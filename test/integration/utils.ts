import path from 'path';
import { Page } from '@playwright/test';

export async function uploadMedia(page: Page, file: string) {
  await page.goto('/wp-admin/media-new.php');
  const fileChooserPromise = page.waitForEvent('filechooser');
  await page.locator('#async-upload').click();
  const fileChooser = await fileChooserPromise;
  await fileChooser.setFiles(path.join(__dirname, `../fixtures/${file}`));
}

export async function setAPIKey(page: Page, key = '') {
  await page.goto('/wp-admin/options-general.php?page=tinify');

  // Clear API Key
  await page.locator('#tinypng_api_key').fill(key);

  await page.locator('#submit').click();
}

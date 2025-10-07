import path from 'path';
import { Page } from '@playwright/test';

export const BASE_URL = `http://localhost:${process.env.WORDPRESS_PORT}`;

export async function uploadMedia(page: Page, file: string): Promise<string> {
  await page.goto('/wp-admin/media-new.php?browser-uploader');
  const fileChooserPromise = page.waitForEvent('filechooser');
  await page.getByLabel('Upload').click();
  const fileChooser = await fileChooserPromise;
  await fileChooser.setFiles(path.join(__dirname, `../fixtures/${file}`));
  await Promise.all([
    page.waitForURL('**/wp-admin/upload.php**', { waitUntil: 'load' }),
    page.locator('#html-upload').click(),
  ]);
  
  await page.goto('/wp-admin/upload.php?mode=list');

  const row = await page.locator('table.wp-list-table tbody > tr').first();
  if (!row) {
    throw Error('Could not find recently uploaded file');
  }

  const rowID = await row.getAttribute('id');
  const attachmentID = rowID?.split('-')[1];
  await Promise.all([
    page.waitForURL(new RegExp(`/wp-admin/post\\.php\\?post=${attachmentID}&action=edit$`), { waitUntil: 'load' }),
    page.goto(`/wp-admin/post.php?post=${attachmentID}&action=edit`),
  ]);

  return page.locator('input[name="attachment_url"]').inputValue();
}

export async function clearMediaLibrary(page: Page) {
  await page.request.post('/wp-admin/admin-ajax.php', {
    form: {
      action: 'clear_media_library',
    },
  });
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

type DefaultSizes = '0' | 'thumbnail' | 'medium' | 'medium_large' | 'large' | '1536x1536' | '2048x2048';
/**
 * @param  {Page} page the page context
 * @param  {DefaultSizes[]} sizes the sizes to enable
 * @param  {boolean=false} enableOtherSizes the state of other sizes not in the size list
 */
export async function enableCompressionSizes(page: Page, sizes: DefaultSizes[], enableOtherSizes: boolean = false) {
  await page.goto('/wp-admin/options-general.php?page=tinify');

  const allSizes = await page.locator('.sizes input[type="checkbox"]').all();
  for (const size of allSizes) {
    const sizeID = await size.getAttribute('id');
    if (!sizeID) continue;

    const sizeName = sizeID.split('tinypng_sizes_').pop();
    if (!sizeName) continue;

    const shouldBeChecked = enableOtherSizes || sizes.includes(sizeName as DefaultSizes);
    const isChecked = await size.isChecked();

    if (shouldBeChecked && !isChecked) {
      await size.check();
    } else if (!shouldBeChecked && isChecked) {
      await size.uncheck();
    }
  }

  await page.locator('#submit').click();
}

type OriginalImageSettings = {
  resize: boolean;
  width?: number;
  height?: number;
  preserveDate: boolean;
  preserveCopyright: boolean;
  preserveGPS: boolean;
};
export async function setOriginalImage(page: Page, settings: OriginalImageSettings) {
  await page.goto('/wp-admin/options-general.php?page=tinify');

  if (settings.resize) {
    await page.locator('#tinypng_resize_original_enabled').check({ force: true });
    await page.fill('#tinypng_resize_original_width', settings.width?.toString() || '');
    await page.fill('#tinypng_resize_original_height', settings.height?.toString() || '');
  } else {
    await page.locator('#tinypng_resize_original_enabled').uncheck({ force: true });
  }

  await page.waitForSelector('#tinypng_preserve_data_creation');
  if (settings.preserveDate) {
    await page.locator('#tinypng_preserve_data_creation').check({ force: true });
  } else {
    await page.locator('#tinypng_preserve_data_creation').uncheck({ force: true });
  }

  await page.waitForSelector('#tinypng_preserve_data_copyright');
  if (settings.preserveCopyright) {
    await page.locator('#tinypng_preserve_data_copyright').check({ force: true });
  } else {
    await page.locator('#tinypng_preserve_data_copyright').uncheck({ force: true });
  }

  await page.waitForSelector('#tinypng_preserve_data_location');
  if (settings.preserveGPS) {
    await page.locator('#tinypng_preserve_data_location').check({ force: true });
  } else {
    await page.locator('#tinypng_preserve_data_location').uncheck({ force: true });
  }

  await page.locator('#submit').click();
}

/**
 * @param  {Page} page context
 * @returns {number} retrieves the current WordPress version
 */
export async function getWPVersion(page: Page): Promise<number> {
  await page.goto('/wp-admin/index.php');

  let wpVersionElement;

  let isModernWP = await page.locator('#wp-version').isVisible();
  if (isModernWP) {
    wpVersionElement = await page.locator('#wp-version');
  } else {
    wpVersionElement = await page.locator('#wp-version-message');
  }

  const versionText = await wpVersionElement.textContent();
  if (!versionText) throw Error('Could not find version text');

  const match = versionText.match(/\d+(\.\d+)?/);
  const parsedText = match ? parseFloat(match[0]) : null;

  if (!parsedText) throw Error('Could not find version number');

  return parsedText;
}

/**
 * @param  {Page} page context
 * @param  {string} pluginSlug slug of the plugin, ex 'tiny-compress-images'
 */
export async function activatePlugin(page: Page, pluginSlug: string) {
  await page.goto('/wp-admin/plugins.php');

  const plugin = await page.locator('tr[data-slug="' + pluginSlug + '"]');
  if (!plugin) {
    throw Error(`Plug-in ${pluginSlug} not found. Are you sure it is installed?`);
  }

  const className = await plugin.getAttribute('class');
  if (className === 'active') {
    return;
  }

  await plugin.getByLabel('Activate').click();
}

/**
 * @param  {Page} page context
 * @param  {string} pluginSlug slug of the plugin, ex 'tiny-compress-images'
 */
export async function deactivatePlugin(page: Page, pluginSlug: string) {
  await page.goto('/wp-admin/plugins.php');

  const pluginInstalled = await page.isVisible('tr[data-slug="' + pluginSlug + '"]');
  if (!pluginInstalled) {
    return;
  }

  const plugin = await page.locator('tr[data-slug="' + pluginSlug + '"]');
  const className = await plugin.getAttribute('class');
  const pluginActivated = className === 'active';
  if (!pluginActivated) {
    return;
  }

  await plugin.getByLabel('Deactivate').click();
}

export async function setConversionSettings(page: Page, settings: { convert: boolean; output?: 'smallest' | 'webp' | 'avif' }) {
  await page.goto('/wp-admin/options-general.php?page=tinify');

  if (settings.convert) {
    await page.locator('#tinypng_conversion_convert').check();

    switch (settings.output) {
      case 'webp':
        await page.locator('#tinypng_convert_convert_to_webp').check();
        break;
      case 'avif':
        await page.locator('#tinypng_convert_convert_to_avif').check();
        break;
      case 'smallest':
      default:
        await page.locator('#tinypng_convert_convert_to_smallest').check();
    }
  } else {
    await page.locator('#tinypng_conversion_convert').uncheck();
  }

  await page.locator('#submit').click();
}

interface NewPostOptions {
  title?: string;
  content: string;
  excerpt?: string;
}
export async function newPost(page: Page, options: NewPostOptions, WPVersion: number): Promise<string> {
  const query = new URLSearchParams();
  const { title, content, excerpt } = options;

  if (title) {
    query.set('post_title', title);
  }
  if (excerpt) {
    query.set('excerpt', excerpt);
  }

  await page.goto('/wp-admin/post-new.php?' + query.toString() + '#content-html');
  if (WPVersion > 5) {
    const welcomeGuideExists = await page.getByLabel('Close', { exact: true }).isVisible();
    if (welcomeGuideExists) {
      await page.getByLabel('Close', { exact: true }).click();
    }
    await page.evaluate((contentHtml) => {
      wp.data.dispatch('core/editor').resetBlocks([]);
      wp.data.dispatch('core/editor').insertBlocks(wp.blocks.parse(contentHtml));
    }, content);
    await page.getByRole('button', { name: 'Publish', exact: true }).click();
    await page.getByLabel('Editor publish').getByRole('button', { name: 'Publish', exact: true }).click();
    await page.getByLabel('Editor publish').getByRole('link', { name: 'View Post' }).click();
  } else {
    await page.locator('#content-html').click();
    await page.locator('#content').fill(content);
    await page.locator('#publish').click();
    await page.getByRole('link', { name: 'View Post' }).first().click();
  }

  return page.url();
}

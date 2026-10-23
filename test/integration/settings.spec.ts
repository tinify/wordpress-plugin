import { Page, expect, test } from '@playwright/test';
import { enableCompressionSizes, setAPIKey, setConversionSettings } from './utils';

test.describe.configure({ mode: 'serial' });

let page: Page;

test.describe('settings', () => {
  test.beforeAll(async ({ browser }) => {
    page = await browser.newPage();

    await setAPIKey(page, '');

    // Resize on background
    await page.locator('#tinypng_resize_original_enabled').uncheck();

    // Enable all sizes
    await enableCompressionSizes(page, [], true);

    await setConversionSettings(page, {
      convert: false,
    });

    await page.locator('#submit').click();
  });

  test('can load settings page', async () => {
    await expect(page.getByRole('heading', { name: 'Tinify', exact: true })).toBeVisible();
  });

  test('show notice if key is missing', async () => {
    await expect(page.getByText('Please register or provide an API key to start compressing images.')).toBeVisible();
  });

  test('show account connect if key is missing', async () => {
    await expect(page.getByRole('heading', { name: 'Create a free account' })).toBeVisible();
    await expect(page.getByRole('heading', { name: 'Already have an account?' })).toBeVisible();
  });

  test('invalid keys should not be stored', async () => {
    await page.locator('#tinypng_api_key').fill('INVALID123');
    await page.getByRole('button', { name: 'Connect', exact: true }).click();

    await expect(page.getByText('This API key isn’t valid.')).toBeVisible();

    await page.reload();

    await expect(page.getByRole('heading', { name: 'Already have an account?' })).toBeVisible();
  });

  test('connect with an api key', async () => {
    await page.locator('#tinypng_api_key').fill('PNG123');
    await page.getByRole('button', { name: 'Connect', exact: true }).click();

    await expect(page.getByText('test@example.com')).toBeVisible();
    await expect(page.getByRole('heading', { name: 'Already have an account?' })).not.toBeVisible();
  });

  test('store valid api key', async () => {
    await setAPIKey(page, 'PNG123');

    await expect(page.getByText('test@example.com')).toBeVisible();
    await expect(page.getByText('Pay per compression', { exact: true })).toBeVisible();
  });

  test('not show notice if key is set', async () => {
    await expect(page.getByText('Please register or provide an API key to start compressing images.')).not.toBeVisible();
  });

  test('keep the api key when saving settings', async () => {
    await page.locator('#submit').click();

    await expect(page.getByText('test@example.com')).toBeVisible();
  });

  test('show account connect to change the account', async () => {
    await page.getByRole('button', { name: 'Change account' }).click();

    await expect(page.getByRole('heading', { name: 'Already have an account?' })).toBeVisible();
    await expect(page.locator('#tinypng_api_key')).toBeFocused();
  });

  test('show credits for free accounts', async () => {
    await setAPIKey(page, 'LIMIT123');

    await expect(page.getByText('500 free credits')).toBeVisible();
    await expect(page.getByText('Get more credits')).toBeVisible();
  });

  test('not show credits for paid accounts', async () => {
    await setAPIKey(page, 'PAID123');

    await expect(page.getByText('test@example.com')).toBeVisible();
    await expect(page.getByText('Get more credits')).not.toBeVisible();
  });

  test('have prefilled registration form', async () => {
    await setAPIKey(page, '');

    await expect(page.locator('#tinypng_api_key_name')).toHaveValue('');
    await expect(page.locator('#tinypng_api_key_email')).toHaveValue('wordpress@example.com');
  });

  test('should not send registration without name', async () => {
    await page.getByRole('button', { name: 'Create account' }).click();

    await expect(page.getByText('Please enter your name')).toBeVisible();
  });

  test('should not send registration without email', async () => {
    await page.locator('#tinypng_api_key_email').fill('');
    await page.locator('#tinypng_api_key_name').fill('John');
    await page.getByRole('button', { name: 'Create account' }).click();

    await expect(page.getByText('Please enter your email address')).toBeVisible();
  });

  test('store registration key', async () => {
    await page.locator('#tinypng_api_key_name').fill('John');
    await page.locator('#tinypng_api_key_email').fill('john@example.com');
    await page.getByRole('button', { name: 'Create account' }).click();

    await expect(page.getByText('Your API key is not confirmed yet. Click the link we sent to john@example.com to activate it.')).toBeVisible();
  });

  test('allow key reset', async () => {
    await setAPIKey(page, '');

    await expect(page.getByRole('heading', { name: 'Already have an account?' })).toBeVisible();
  });

  test('store compression timing', async () => {
    await page.locator('#tinypng_compression_timing_auto').check();
    await page.locator('#submit').click();

    await expect(page.locator('#tinypng_compression_timing_auto')).toBeChecked();
  });

  test('have all sizes enabled by default', async () => {
    const sizes = await page.locator('.tiny-sizes input[type=checkbox]').all();
    await Promise.all(
      sizes.map(async (size) => {
        await expect(size).toBeChecked();
      })
    );
  });

  test('store size settings', async () => {
    await enableCompressionSizes(page, []); // disable all sizes

    await page.locator('#submit').click();

    const sizesDisabled = await page.locator('.tiny-sizes input[type=checkbox]').all();
    await Promise.all(
      sizesDisabled.map(async (size) => {
        await expect(size).not.toBeChecked();
      })
    );
  });

  test('show credits per image', async () => {
    await enableCompressionSizes(page, ['0', 'thumbnail', 'medium', 'large']);

    await expect(page.getByText('4 sizes selected. Each new image uses 4 credits.')).toBeVisible();
    await expect(page.getByText('With your settings, each new image uses 4 credits.')).toBeVisible();
  });

  test('update credits per image', async () => {
    await page.locator('#tinypng_sizes_medium').uncheck();

    await expect(page.getByText('3 sizes selected. Each new image uses 3 credits.')).toBeVisible();
    await expect(page.getByText('With your settings, each new image uses 3 credits.')).toBeVisible();
  });

  test('count modern formats in credits per image', async () => {
    await page.locator('#tiny-convert').check();

    await expect(page.getByText('3 sizes selected. Each new image uses 6 credits.')).toBeVisible();

    await page.locator('#tiny-convert').uncheck();
  });

  test('show no compressions', async () => {
    await enableCompressionSizes(page, []); // disable all sizes

    await expect(page.getByText('With these settings no images will be compressed.')).toBeVisible();
  });

  test('not show resizing when original is disabled', async () => {
    await expect(page.getByText('Enable compression of the original image size for more options.')).toBeVisible();
    await expect(page.getByRole('heading', { name: 'Resize large originals' })).not.toBeVisible();
  });

  test('show resizing options when original is enabled', async () => {
    await page.locator('#tinypng_sizes_0').check();

    await expect(page.getByRole('heading', { name: 'Resize large originals' })).toBeVisible();
    await expect(page.getByText('Enable compression of the original image size for more options.')).not.toBeVisible();
  });

  test('store resizing settings', async () => {
    await page.locator('#tinypng_resize_original_enabled').check();
    await page.locator('#tinypng_resize_original_width').fill('234');
    await page.locator('#tinypng_resize_original_height').fill('345');
    await page.locator('#submit').click();

    await expect(page.locator('#tinypng_resize_original_enabled')).toBeChecked();
    await expect(page.locator('#tinypng_resize_original_width')).toHaveValue('234');
    await expect(page.locator('#tinypng_resize_original_height')).toHaveValue('345');
  });

  test('will not convert by default', async () => {
    await expect(page.locator('#tiny-convert')).not.toBeChecked();
  });

  test('will store conversion settings', async () => {
    await setConversionSettings(page, {
      convert: true,
    });

    await expect(page.locator('#tiny-convert')).toBeChecked();
  });
});

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
    const showsPageTitle = await page.getByRole('heading', { name: 'TinyPNG - JPEG, PNG & WebP image compression' }).isVisible();
    expect(showsPageTitle).toBe(true);
  });

  test('show notice if key is missing', async () => {
    await expect(page.getByText('Please register or provide an API key to start compressing images.')).toBeVisible();
  });

  test('invalid keys should not be stored', async () => {
    await page.locator('#tinypng_api_key').click();
    await page.locator('#tinypng_api_key').fill('INVALID123');
    await page.getByRole('button', { name: 'Save', exact: true }).click();

    await expect(page.getByText('The key that you have entered is not valid')).toBeVisible();

    await page.reload();

    await expect(page.getByText('Register new account')).toBeVisible();
  });

  test('store valid api key', async () => {
    await page.locator('#tinypng_api_key').click();
    await page.locator('#tinypng_api_key').fill('PNG123');
    await page.getByRole('button', { name: 'Save', exact: true }).click();

    await expect(page.getByText('Your account is connected')).toBeVisible();

    await page.reload();

    await expect(page.getByText('Your account is connected')).toBeVisible();
  });

  test('not show notice if key is set', async () => {
    await expect(page.getByText('Please register or provide an API key to start compressing images.')).not.toBeVisible();
  });

  test('allow changing api key', async () => {
    await page.getByText('Change API key').click();

    await page.locator('#tinypng_api_key').click();
    await page.locator('#tinypng_api_key').fill('JPG123');
    await page.getByRole('button', { name: 'Save', exact: true }).click();

    await expect(page.getByText('Your account is connected')).toBeVisible();
  });

  test('show upgrade notice', async () => {
    await page.getByText('Change API key').click();

    await page.locator('#tinypng_api_key').click();
    await page.locator('#tinypng_api_key').fill('LIMIT123');
    await page.getByRole('button', { name: 'Save', exact: true }).click();

    await expect(page.getByText('Upgrade account')).toBeVisible();
  });

  test('not show upgrade notice for paid users', async () => {
    await page.getByText('Change API key').click();

    await page.locator('#tinypng_api_key').click();
    await page.locator('#tinypng_api_key').fill('PAID123');
    await page.getByRole('button', { name: 'Save', exact: true }).click();

    await expect(page.getByText('Upgrade account')).not.toBeVisible();
    await page.getByText('Change API key').click();
    await page.locator('#tinypng_api_key').fill('');
    await page.getByRole('button', { name: 'Save', exact: true }).click();
  });

  test('have prefilled registration form', async () => {
    await expect(page.locator('#tinypng_api_key_name')).toHaveValue('');
    await expect(page.locator('#tinypng_api_key_email')).toHaveValue('wordpress@example.com');
  });

  test('should not send registration without name', async () => {
    await expect(page.locator('#tinypng_api_key_name')).toHaveValue('');
    await expect(page.locator('#tinypng_api_key_email')).toHaveValue('wordpress@example.com');
    await page.getByText('Register account').click();

    await expect(page.getByText('Please enter your name')).toBeVisible();

    await page.reload();

    await page.waitForLoadState('networkidle');

    await expect(page.getByText('Register new account')).toBeVisible();
  });

  test('should not send registration without email', async () => {
    await page.locator('#tinypng_api_key_email').fill('');
    await page.locator('#tinypng_api_key_name').fill('John');
    await page.getByText('Register account').click();

    await expect(page.getByText('Please enter your email address')).toBeVisible();

    await page.reload();

    await expect(page.getByText('Register new account')).toBeVisible();
  });

  test('store registration key', async () => {
    await page.locator('#tinypng_api_key_name').fill('John');
    await page.locator('#tinypng_api_key_email').fill('john@example.com');
    await page.getByText('Register account').click();

    await expect(page.getByText('An email has been sent to activate your account')).toBeVisible();

    await page.reload();

    await page.waitForLoadState('networkidle');

    await expect(page.getByText('An email has been sent to activate your account')).toBeVisible();
  });

  test('allow key reset', async () => {
    await page.getByText('Change API key').click();
    await page.locator('#tinypng_api_key').fill('');
    await page.locator('.update .button').click();

    await expect(page.getByText('Register new account')).toBeVisible();
  });

  test('store compression timing', async () => {
    await page.locator('#tinypng_compression_timing_auto').check();
    await page.locator('#submit').click();

    await expect(page.locator('#tinypng_compression_timing_auto')).toBeChecked();
  });

  test('have all sizes enabled by default', async () => {
    const sizes = await page.locator('.sizes input[type=checkbox]').all();
    await Promise.all(
      sizes.map(async (size) => {
        await expect(size).toBeChecked();
      })
    );
  });

  test('store size settings', async () => {
    await enableCompressionSizes(page, []); // disable all sizes

    await page.locator('#submit').click();

    const sizesDisabled = await page.locator('.sizes input[type=checkbox]').all();
    await Promise.all(
      sizesDisabled.map(async (size) => {
        await expect(size).not.toBeChecked();
      })
    );
  });

  test('show free compressions', async () => {
    await enableCompressionSizes(page, ['0', 'thumbnail', 'medium', 'large']);

    await page.locator('#submit').click();

    await expect(page.getByText('With these settings you can compress at least 125 images for free each month.')).toBeVisible();
  });

  test('update free compressions', async () => {
    await page.locator('#tinypng_sizes_medium').uncheck();

    await expect(page.getByText('With these settings you can compress at least 166 images for free each month.')).toBeVisible();
  });

  test('show no compressions', async () => {
    await enableCompressionSizes(page, []); // disable all sizes
    await page.locator('#submit').click();

    await expect(page.getByText('With these settings no images will be compressed.')).toBeVisible();
  });

  test('not show resizing when original is disabled', async () => {
    await expect(page.getByText('Enable compression of the original image size for more options.')).toBeVisible();
  });

  test('show resizing options when original is enabled', async () => {
    await page.locator('#tinypng_sizes_0').check();

    await expect(page.getByText('Resize the original image')).toBeVisible();
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
    await expect(page.locator('#tinypng_conversion_convert')).not.toBeChecked();
  });

  test('will store conversion settings', async () => {
    await setConversionSettings(page, {
      convert: true,
    });

    await expect(page.locator('#tinypng_conversion_convert')).toBeChecked();
  });
});

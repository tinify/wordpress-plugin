import { expect, test } from '@playwright/test';

test.describe.configure({ mode: 'serial' });

test.describe('settings', () => {
  test('can load settings page', async ({ page }) => {
    await page.goto('/wp-admin/options-general.php?page=tinify');

    const showsPageTitle = await page.getByRole('heading', { name: 'TinyPNG - JPEG, PNG & WebP image compression' }).isVisible();
    expect(showsPageTitle).toBe(true);
  });

  test('show notice if key is missing', async ({ page }) => {
    await page.goto('/wp-admin/options-general.php?page=tinify');

    await expect(page.getByText('Please register or provide an API key to start compressing images.')).toBeVisible();
  });

  test('invalid keys should not be stored', async ({ page }) => {
    await page.goto('/wp-admin/options-general.php?page=tinify');

    await page.locator('#tinypng_api_key').click();
    await page.locator('#tinypng_api_key').fill('INVALID123');
    await page.getByRole('button', { name: 'Save', exact: true }).click();

    await expect(page.getByText('The key that you have entered is not valid')).toBeVisible();

    await page.reload();

    await expect(page.getByText('Register new account')).toBeVisible();
  });

  test('store valid api key', async ({ page }) => {
    await page.goto('/wp-admin/options-general.php?page=tinify');

    await page.locator('#tinypng_api_key').click();
    await page.locator('#tinypng_api_key').fill('PNG123');
    await page.getByRole('button', { name: 'Save', exact: true }).click();

    await expect(page.getByText('Your account is connected')).toBeVisible();

    await page.reload();

    await expect(page.getByText('Your account is connected')).toBeVisible();
  });

  test('not show notice if key is set', async ({ page }) => {
    await page.goto('/wp-admin/options-general.php?page=tinify');

    await expect(page.getByText('Please register or provide an API key to start compressing images.')).not.toBeVisible();
  });

  test('allow changing api key', async ({ page }) => {
    await page.goto('/wp-admin/options-general.php?page=tinify');

    await page.getByText('Change API key').click();

    await page.locator('#tinypng_api_key').click();
    await page.locator('#tinypng_api_key').fill('JPG123');
    await page.getByRole('button', { name: 'Save', exact: true }).click();

    await expect(page.getByText('Your account is connected')).toBeVisible();
  });

  test('show upgrade notice', async ({ page }) => {
    await page.goto('/wp-admin/options-general.php?page=tinify');

    await page.getByText('Change API key').click();

    await page.locator('#tinypng_api_key').click();
    await page.locator('#tinypng_api_key').fill('LIMIT123');
    await page.getByRole('button', { name: 'Save', exact: true }).click();

    await expect(page.getByText('Upgrade account')).toBeVisible();
  });

  test('not show upgrade notice for paid users', async ({ page }) => {
    await page.goto('/wp-admin/options-general.php?page=tinify');

    await page.getByText('Change API key').click();

    await page.locator('#tinypng_api_key').click();
    await page.locator('#tinypng_api_key').fill('PAID123');
    await page.getByRole('button', { name: 'Save', exact: true }).click();

    
    await expect(page.getByText('Upgrade account')).not.toBeVisible();
    
    await page.getByText('Change API key').click();
    await page.locator('#tinypng_api_key').fill('');
    await page.getByRole('button', { name: 'Save', exact: true }).click();
  });

  test('have prefilled registration form', async ({ page }) => {
    await page.goto('/wp-admin/options-general.php?page=tinify');

    await expect(page.locator('#tinypng_api_key_name')).toHaveValue('');
    await expect(page.locator('#tinypng_api_key_email')).toHaveValue('wordpress@example.com');
  });

  test('should not send registration without name', async ({ page }) => {
    await page.goto('/wp-admin/options-general.php?page=tinify');

    await expect(page.locator('#tinypng_api_key_name')).toHaveValue('');
    await expect(page.locator('#tinypng_api_key_email')).toHaveValue('wordpress@example.com');
    await page.getByText('Register account').click();

    await expect(page.getByText('Please enter your name')).toBeVisible();

    await page.reload();

    await expect(page.getByText('Register new account')).toBeVisible();
  });

  test('should not send registration without email', async ({ page }) => {
    await page.goto('/wp-admin/options-general.php?page=tinify');

    await page.locator('#tinypng_api_key_email').fill('');
    await page.locator('#tinypng_api_key_name').fill('John');
    await page.getByText('Register account').click();

    await expect(page.getByText('Please enter your email address')).toBeVisible();

    await page.reload();

    await expect(page.getByText('Register new account')).toBeVisible();
  });

  test('should store registration key', async ({ page }) => {
    await page.goto('/wp-admin/options-general.php?page=tinify');

    await page.locator('#tinypng_api_key_name').fill('John');
    await page.locator('#tinypng_api_key_email').fill('john@example.com');
    await page.getByText('Register account').click();

    await expect(page.getByText('An email has been sent to activate your account')).toBeVisible();

    await page.reload();

    await expect(page.getByText('An email has been sent to activate your account')).toBeVisible();
  });
});

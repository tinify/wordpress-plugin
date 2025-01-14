import { expect, test } from '@playwright/test';

test.describe('settings', () => {
  test('can load settings page', async ({ page }) => {
    await page.goto('/wp-admin/options-general.php?page=tinify');

    const showsPageTitle = await page.getByRole('heading', { name: 'TinyPNG - JPEG, PNG & WebP image compression' }).isVisible();
    expect(showsPageTitle).toBeTruthy();
  });

  test('show notice if key is missing', async ({ page }) => {
    await page.goto('/wp-admin/options-general.php?page=tinify');

    const hasRegistrationUrl = await page.getByText('Please register or provide an API key to start compressing images.').isVisible();
    expect(hasRegistrationUrl).toBeTruthy();
  });

  test('not show notice if key is set', async ({ page }) => {
    await page.goto('/wp-admin/options-general.php?page=tinify');

    await page.locator('#tinypng_api_key').click();
    await page.locator('#tinypng_api_key').fill('PNG123');
    await page.getByRole('button', { name: 'Save', exact: true }).click();
  });
});

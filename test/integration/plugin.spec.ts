import { expect, test } from '@playwright/test';

test.describe('plugin', () => {
  test('should have title', async ({ page }) => {
    await page.goto('/wp-admin/plugins.php');
    await expect(page.getByText('TinyPNG - JPEG, PNG & WebP image compression', { exact: true })).toBeVisible();
  });

  test('includes settings link', async ({ page }) => {
    await page.goto('/wp-admin/plugins.php');
    await expect(page.getByRole('link', { name: 'Settings', exact: true })).toBeVisible();
  });
  test('includes bulk optimization link', async ({ page }) => {
    await page.goto('/wp-admin/plugins.php');
    await expect(page.getByLabel('Main content').getByRole('link', { name: 'Bulk Optimization', exact: true })).toBeVisible();
  });
});

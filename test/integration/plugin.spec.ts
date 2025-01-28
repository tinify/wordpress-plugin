import { expect, test } from '@playwright/test';

test.describe('plugin', () => {
  test('should have title', async ({ page }) => {
    await page.goto('/wp-admin/plugins.php');
    await expect(page.getByText('TinyPNG - JPEG, PNG & WebP image compression')).toBeVisible();
  });

  test('includes settings link', async ({ page }) => {
    await page.goto('/wp-admin/plugins.php');
    const link = await page.locator('tr[data-slug="tiny-compress-images"] span.settings a').first();
    await expect(link).toHaveAttribute('href', 'options-general.php?page=tinify');
  });

  test('includes bulk optimization link', async ({ page }) => {
    await page.goto('/wp-admin/plugins.php');
    const link = await page.locator('tr[data-slug="tiny-compress-images"] span.bulk a').first();
    await expect(link).toHaveAttribute('href', 'upload.php?page=tiny-bulk-optimization');
  });
});

import { expect, test } from '@playwright/test';

test.describe('plugin', () => {
  test('should have title', async ({ page }) => {
    await page.goto('/wp-admin/plugins.php');
    await expect(page.locator('tr[data-slug=tiny-compress-images] td.plugin-title strong')).toHaveText('TinyPNG - JPEG, PNG & WebP image compression');
  });

  test('includes settings link', async ({ page }) => {
    await page.goto('/wp-admin/plugins.php');
    await expect(page.locator('tr[data-slug=tiny-compress-images] span.settings a')).toHaveAttribute('href', 'options-general.php?page=tinify');
  });
});

import { Page, expect, test } from '@playwright/test';

let page: Page;

test.describe('plugin', () => {
  test.beforeAll(async ({ browser }) => {
    page = await browser.newPage();
    await page.goto('/wp-admin/plugins.php');
  });

  test('should have title', async () => {
    await expect(page.getByText('TinyPNG - JPEG, PNG & WebP image compression', { exact: true })).toBeVisible();
  });

  test('includes settings link', async () => {
    await expect(page.locator('#the-list').getByRole('link', { name: 'Settings' })).toBeVisible();
  });

  test('includes bulk optimization link', async () => {
    await expect(page.locator('#the-list').getByRole('link', { name: 'Bulk Optimization' })).toBeVisible();
  });
});

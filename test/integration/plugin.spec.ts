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
    await expect(page.getByRole('link', { name: 'Settings', exact: true })).toBeVisible();
  });

  test('includes bulk optimization link', async () => {
    await expect(page.getByLabel('Main content').getByRole('link', { name: 'Bulk Optimization', exact: true })).toBeVisible();
  });
});

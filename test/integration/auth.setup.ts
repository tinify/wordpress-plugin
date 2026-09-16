import { test as setup, expect } from '@playwright/test';
import path from 'path';

const authFile = path.join(__dirname, './.auth/user.json');

setup.describe('setup', () => {
  setup('authenticate', async ({ page }) => {
    await page.goto('/wp-login.php');
    await expect(page.locator('#user_login')).toBeFocused();
    await page.fill('#user_login', 'admin');
    await page.fill('#user_pass', 'password');
    await expect(page.locator('#user_login')).toHaveValue('admin');
    await expect(page.locator('#user_pass')).toHaveValue('password');

    await Promise.all([
      page.waitForURL('**/wp-admin/**', { timeout: 15000 }),
      page.getByRole('button', { name: 'Log In' }).click(),
    ]);

    await page.context().storageState({ path: authFile });
  });
});

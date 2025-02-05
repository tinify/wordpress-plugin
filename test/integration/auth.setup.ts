import { test as setup } from '@playwright/test';
import path from 'path';

const authFile = path.join(__dirname, './.auth/user.json');

setup.describe('setup', () => {
  setup('authenticate', async ({ page }) => {
    await page.goto('/wp-login.php');
    await page.fill('#user_login', 'admin');
    await page.fill('#user_pass', 'password');

    await page.getByRole('button', { name: 'Log In' }).click();

    await page.context().storageState({ path: authFile });
  });
});

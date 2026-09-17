import { test, expect } from '@playwright/test';

// Same seeded accounts tests/Support/ApiClient.php uses for the PHPUnit
// suites - one dedicated password for all three, from
// tests/fixtures/seed_test_data.sql.
const PASSWORD = 'TestPass123!';

const ROLES: Record<string, { username: string; heading: string }> = {
  superadmin: { username: 'test_superadmin', heading: 'Dashboard' },
  admin: { username: 'test_admin', heading: 'Dashboard Admin' },
  finance: { username: 'test_finance', heading: 'Dashboard Finance' },
};

for (const [role, { username, heading }] of Object.entries(ROLES)) {
  test(`${role} can log in and reach its dashboard`, async ({ page }) => {
    await page.goto('Login');
    await page.fill('#username', username);
    await page.fill('#password', PASSWORD);
    await page.click('#form_login button[type="submit"]');

    // 'domcontentloaded' rather than the default 'load': the superadmin
    // dashboard pulls in enough extra chart libraries/assets that waiting
    // for every last one under the throwaway single-threaded `php -S`
    // server isn't reliable, and the assertions below only need the DOM.
    await page.waitForURL(/\/Dashboard/, { waitUntil: 'domcontentloaded' });
    await expect(page.locator('h3', { hasText: heading })).toBeVisible();

    // A PHP fatal/parse error renders as plain text in the response body
    // instead of inside the app's own layout - catch that early too.
    await expect(page.locator('body')).not.toContainText('Fatal error');
  });
}

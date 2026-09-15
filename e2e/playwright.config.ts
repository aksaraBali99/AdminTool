import { defineConfig } from '@playwright/test';
import path from 'path';

const PORT = process.env.ADMINTOOL_E2E_PORT || '8090';
const baseURL = `http://127.0.0.1:${PORT}/index.php/`;

export default defineConfig({
  testDir: './tests',
  retries: 0,
  reporter: 'list',
  use: {
    baseURL,
    trace: 'retain-on-failure',
  },
  webServer: {
    // Runs from the repo root, not e2e/, so its relative paths
    // (tests/fixtures/..., tests/router.php) resolve correctly.
    command: 'bash tests/serve-test-app.sh',
    cwd: path.resolve(__dirname, '..'),
    url: baseURL + 'Login',
    reuseExistingServer: !process.env.CI,
    timeout: 30_000,
  },
});

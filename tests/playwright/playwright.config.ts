import { defineConfig, devices } from '@playwright/test';

export default defineConfig({
  testDir: './specs',
  fullyParallel: true,
  forbidOnly: !!process.env.CI,
  retries: process.env.CI ? 1 : 0,
  // Single worker under CI. Beyond the login contention seen in other suites, this plugin
  // needs it: [ux_cv_api4_get] caches rendered output in a site-wide WordPress transient, so
  // two workers hitting the same shortcode page as different users would race over one cache
  // entry. Serial execution plus the purge helper in fixtures/cache.ts keeps that deterministic.
  workers: process.env.CI ? 1 : undefined,
  timeout: process.env.CI ? 60000 : undefined,
  reporter: process.env.CI ? [['html', { open: 'never' }], ['list']] : 'list',
  use: {
    baseURL: process.env.BASE_URL || 'http://localhost:8080',
    trace: 'retain-on-failure',
    screenshot: 'only-on-failure',
  },
  projects: [
    {
      name: 'chromium',
      use: { ...devices['Desktop Chrome'] },
    },
  ],
});

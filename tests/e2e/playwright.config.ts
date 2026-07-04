import { defineConfig } from '@playwright/test';

export default defineConfig({
  testDir: './specs',
  snapshotDir: './screenshots',
  outputDir: './test-results',
  retries: 0,
  workers: 1,
  timeout: 60000,
  use: {
    baseURL: process.env.BASE_URL || 'http://localhost:8080',
    viewport: { width: 1024, height: 768 },
    locale: 'en-US',
    screenshot: 'off',
    trace: 'retain-on-failure',
  },
  globalSetup: './global-setup.ts',
});

import { defineConfig, devices } from "@playwright/test";

/**
 * Playwright configuration.
 * - `chromium` project: the functional smoke/a11y journeys in ./e2e (excludes ./e2e/visual).
 * - `visual` project: deterministic visual-regression suite in ./e2e/visual (Part 16).
 *
 * Base URL: set PLAYWRIGHT_BASE_URL to test an already-running app; otherwise Playwright
 * builds and starts the Next app locally via the webServer block below.
 *
 * The visual suite requires a running dev server + installed browsers (CI). Baselines are
 * generated with `npm run test:visual -- --update-snapshots`. See ./e2e/visual/README.md.
 */
const baseURL = process.env.PLAYWRIGHT_BASE_URL ?? "http://localhost:3000";

export default defineConfig({
  testDir: "./e2e",
  fullyParallel: true,
  forbidOnly: !!process.env.CI,
  retries: process.env.CI ? 1 : 0,
  workers: process.env.CI ? 1 : undefined,
  reporter: process.env.CI
    ? [["github"], ["html", { open: "never" }]]
    : [["list"], ["html", { open: "never" }]],
  use: {
    baseURL,
    headless: true,
    trace: "retain-on-failure",
    screenshot: "only-on-failure",
    video: "on-first-retry",
  },
  // Deterministic screenshot comparison defaults for the visual suite.
  expect: {
    toHaveScreenshot: {
      animations: "disabled",
      caret: "hide",
      scale: "css",
      maxDiffPixelRatio: 0.02,
    },
  },
  projects: [
    {
      name: "chromium",
      testIgnore: /visual[\\/].*/,
      use: { ...devices["Desktop Chrome"] },
    },
    {
      name: "visual",
      testMatch: /visual[\\/].*\.spec\.ts/,
      use: {
        ...devices["Desktop Chrome"],
        viewport: { width: 1280, height: 800 },
        deviceScaleFactor: 1,
        // Deterministic rendering: freeze motion + a stable color scheme baseline.
        colorScheme: "light",
        contextOptions: { reducedMotion: "reduce" },
      },
    },
  ],
  // Only manage a local server when a base URL was not provided externally.
  webServer: process.env.PLAYWRIGHT_BASE_URL
    ? undefined
    : {
        command: "npm run build && npm run start",
        url: "http://localhost:3000",
        timeout: 120_000,
        reuseExistingServer: !process.env.CI,
      },
});

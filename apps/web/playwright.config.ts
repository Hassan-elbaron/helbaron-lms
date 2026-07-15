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
  // Only manage local servers when a base URL was not provided externally. Two servers:
  //  1) a deterministic mock of the public API (e2e/support/mock-api.mjs) so the SSR shell's
  //     branding/feature-flags/homepage fetches resolve INSTANTLY instead of stalling on the 20s
  //     apiFetch timeout when no real API is running;
  //  2) the Next app, built without standalone output (so `next start` can serve it) and pointed
  //     at the mock for both server-side (NEXT_PUBLIC_API_BASE_URL) and BFF (API_INTERNAL_URL) fetches.
  // Set PLAYWRIGHT_BASE_URL to run the full authenticated journeys against a real API+web instead.
  webServer: process.env.PLAYWRIGHT_BASE_URL
    ? undefined
    : [
        {
          command: "node e2e/support/mock-api.mjs",
          url: "http://127.0.0.1:8787/api/v1/health",
          timeout: 30_000,
          reuseExistingServer: !process.env.CI,
          env: { MOCK_API_PORT: "8787", MOCK_API_HOST: "127.0.0.1" },
        },
        {
          command: "npm run build && npm run start",
          url: "http://localhost:3000",
          timeout: 180_000,
          reuseExistingServer: !process.env.CI,
          // `next start` cannot serve a standalone build, so build without it; point SSR + the BFF
          // proxy at the mock API. (Playwright merges env over process.env, so PATH etc. survive.)
          env: {
            NEXT_DISABLE_STANDALONE: "1",
            NEXT_PUBLIC_API_BASE_URL: "http://127.0.0.1:8787/api/v1",
            API_INTERNAL_URL: "http://127.0.0.1:8787/api/v1",
          },
        },
      ],
});

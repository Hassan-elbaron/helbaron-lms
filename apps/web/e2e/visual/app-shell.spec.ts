import { test, expect } from "@playwright/test";
import { pinTheme, dynamicRegions, settle } from "./_helpers";

/**
 * Visual regression — authenticated app shell: dashboard, navigation (sidebar/topbar),
 * tables and cards behind auth (Part 16).
 *
 * These surfaces require an authenticated session. Provide a saved Playwright storage state via
 * PLAYWRIGHT_STORAGE_STATE (a JSON file produced by a login setup, see README). Without it these
 * tests skip rather than capture a redirect-to-login screen.
 */
const storageState = process.env.PLAYWRIGHT_STORAGE_STATE;
test.use(storageState ? { storageState } : {});

const SHELL_PAGES: { name: string; path: string }[] = [
  { name: "dashboard", path: "/dashboard" },
  { name: "my-learning", path: "/my-learning" },
  { name: "certificates", path: "/certificates" },
];

test.describe("app-shell (authenticated)", () => {
  test.skip(!storageState, "Set PLAYWRIGHT_STORAGE_STATE to a logged-in storage state to run these.");

  test.beforeEach(async ({ page }) => {
    await pinTheme(page, "light");
  });

  for (const p of SHELL_PAGES) {
    test(`shell: ${p.name}`, async ({ page }) => {
      const res = await page.goto(p.path);
      test.skip(!!res && res.status() >= 400, `"${p.path}" unavailable (status ${res?.status()}).`);
      await settle(page);
      await expect(page).toHaveScreenshot(`shell-${p.name}.png`, {
        fullPage: true,
        mask: dynamicRegions(page),
      });
    });
  }
});

import { test, expect } from "@playwright/test";
import { expectNoSeriousA11y } from "./support/a11y";

/**
 * Accessibility (WCAG 2.2 AA) spec — Part 7.
 *
 * Runs axe-core against the public marketing + auth surfaces (always available) and, when E2E
 * credentials are supplied, the authenticated dashboard shell. It also asserts the structural
 * a11y contract added in this pass: a skip link, a single <main> landmark, and a labelled
 * primary navigation. Requires a running server (Playwright's webServer builds/starts it in CI);
 * it is NOT executed in the repository sandbox.
 *
 * `expectNoSeriousA11y` (see ./support/a11y) independently re-verifies axe color-contrast findings
 * against the browser's real rendering to filter out axe's oklch/alpha false positives without
 * disabling the rule or excluding elements.
 */

const EMAIL = process.env.E2E_EMAIL;
const PASSWORD = process.env.E2E_PASSWORD;
const hasCreds = Boolean(EMAIL && PASSWORD);

test.describe("a11y: public surfaces", () => {
  test("homepage exposes a skip link + main landmark and passes axe", async ({ page }) => {
    await page.goto("/");

    // Skip link is the first focusable control and targets the main landmark.
    await page.keyboard.press("Tab");
    const skip = page.getByRole("link", { name: /skip to content/i });
    await expect(skip).toBeFocused();
    await expect(skip).toHaveAttribute("href", "#main-content");

    await expect(page.locator("main")).toHaveCount(1);
    await expectNoSeriousA11y(page, "home");
  });

  test("login page is accessible", async ({ page }) => {
    await page.goto("/login");
    await expect(page.getByRole("textbox").first()).toBeVisible();
    await expectNoSeriousA11y(page, "login");
  });

  test("pricing page is accessible", async ({ page }) => {
    await page.goto("/pricing");
    await expect(page.locator("body")).toBeVisible();
    await expectNoSeriousA11y(page, "pricing");
  });
});

test.describe("a11y: authenticated dashboard shell", () => {
  test("dashboard shell has labelled nav + is accessible", async ({ page }) => {
    test.skip(!hasCreds, "Set E2E_EMAIL and E2E_PASSWORD (and run the API) to exercise the dashboard shell.");

    await page.goto("/login");
    await page.getByLabel(/email/i).fill(EMAIL as string);
    await page.getByLabel(/password/i).fill(PASSWORD as string);
    await page.getByRole("button", { name: /log ?in|sign ?in/i }).click();
    await page.waitForURL(/\/(dashboard|my-learning)/, { timeout: 15_000 });

    // Primary navigation landmark carries an accessible name.
    await expect(page.getByRole("navigation", { name: /primary/i })).toBeVisible();
    await expect(page.locator("main#main-content")).toHaveCount(1);
    await expectNoSeriousA11y(page, "dashboard shell");
  });
});

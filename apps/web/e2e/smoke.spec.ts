import { test, expect } from "@playwright/test";
import { expectNoSeriousA11y } from "./support/a11y";

/**
 * Sprint 0 / A1-S03 smoke journey: Home -> Login -> Dashboard -> Logout.
 *
 * The unauthenticated legs (Home, Login) always run and are accessibility-checked with axe.
 * The authenticated legs (Dashboard, Logout) run only when E2E credentials are provided via
 * env (E2E_EMAIL + E2E_PASSWORD) AND the API backend is reachable; otherwise they are skipped
 * so the smoke test stays green in a backend-less CI while remaining a full journey when wired.
 */

const EMAIL = process.env.E2E_EMAIL;
const PASSWORD = process.env.E2E_PASSWORD;
const hasCreds = Boolean(EMAIL && PASSWORD);

const SMOKE_TAGS = ["wcag2a", "wcag2aa"];

test.describe("smoke: home -> login -> dashboard -> logout", () => {
  test("home renders and is accessible", async ({ page }) => {
    await page.goto("/");
    await expect(page).toHaveTitle(/.+/);
    await expect(page.locator("body")).toBeVisible();
    await expectNoSeriousA11y(page, "home", SMOKE_TAGS);
  });

  test("login page renders and is accessible", async ({ page }) => {
    await page.goto("/login");
    // A login form (email + password) should be present.
    await expect(
      page.getByRole("textbox").first(),
    ).toBeVisible();
    await expectNoSeriousA11y(page, "login", SMOKE_TAGS);
  });

  test("authenticated journey: login -> dashboard -> logout", async ({ page }) => {
    test.skip(
      !hasCreds,
      "Set E2E_EMAIL and E2E_PASSWORD (and run the API) to exercise the authenticated journey.",
    );

    await page.goto("/login");
    await page.getByLabel(/email/i).fill(EMAIL as string);
    await page.getByLabel(/password/i).fill(PASSWORD as string);
    await page.getByRole("button", { name: /log ?in|sign ?in/i }).click();

    // Land on the authenticated dashboard.
    await page.waitForURL(/\/(dashboard|my-learning)/, { timeout: 15_000 });
    await expect(page.locator("body")).toBeVisible();
    await expectNoSeriousA11y(page, "dashboard", SMOKE_TAGS);

    // Log out and return to a public/login surface.
    await page.getByRole("button", { name: /log ?out|sign ?out/i }).click();
    await page.waitForURL(/\/(login|$)/, { timeout: 15_000 });
  });
});

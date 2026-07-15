import { test, expect } from "@playwright/test";
import { pinTheme, dynamicRegions, settle } from "./_helpers";

/**
 * Visual regression — internal Design-System Showcase (Part 16).
 *
 * The showcase is the single most efficient surface for component coverage: it renders every
 * primitive, state, chart, table, form and homepage block on one page. It is gated, so in a
 * production build the runner must set NEXT_PUBLIC_ENABLE_DESIGN_SHOWCASE=true (dev needs nothing).
 *
 * Per-section captures (rather than one giant full-page shot) keep baselines small and make a
 * diff point at the exact component that regressed.
 */
const SECTIONS = [
  "colors", "typography", "spacing", "radius", "icons", "buttons", "badges",
  "inputs", "cards", "disclosure", "overlays", "navigation", "states",
  "charts", "tables", "forms", "blocks",
] as const;

test.describe("design-showcase", () => {
  test.beforeEach(async ({ page }) => {
    await pinTheme(page, "light");
  });

  for (const id of SECTIONS) {
    test(`section: ${id}`, async ({ page }) => {
      const res = await page.goto("/design-system");
      // If the route is gated off (prod without the flag), it 404s — skip rather than fail.
      test.skip(!!res && res.status() === 404, "Showcase gated off (set NEXT_PUBLIC_ENABLE_DESIGN_SHOWCASE=true).");
      await settle(page);
      const section = page.locator(`#${id}`);
      await section.scrollIntoViewIfNeeded();
      await expect(section).toHaveScreenshot(`showcase-${id}.png`, { mask: dynamicRegions(page) });
    });
  }

  test("overlay: dialog open", async ({ page }) => {
    const res = await page.goto("/design-system");
    test.skip(!!res && res.status() === 404, "Showcase gated off.");
    await settle(page);
    await page.getByRole("button", { name: "Open dialog" }).click();
    const dialog = page.getByRole("dialog");
    await expect(dialog).toBeVisible();
    await expect(dialog).toHaveScreenshot("showcase-dialog-open.png");
  });

  test("theme: dark full page", async ({ page }) => {
    await pinTheme(page, "dark");
    const res = await page.goto("/design-system");
    test.skip(!!res && res.status() === 404, "Showcase gated off.");
    await settle(page);
    await expect(page).toHaveScreenshot("showcase-dark-full.png", {
      fullPage: true,
      mask: dynamicRegions(page),
    });
  });
});

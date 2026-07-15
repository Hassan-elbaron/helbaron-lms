import type { Page } from "@playwright/test";

/**
 * Shared determinism helpers for the visual-regression suite (Part 16).
 *
 * Screenshots are only stable when motion, theme and dynamic regions are pinned. The Playwright
 * `visual` project already forces `reducedMotion: "reduce"` + `colorScheme: "light"`; here we also
 * pin next-themes to a fixed theme (via its localStorage key) before the app boots so the class on
 * <html> is deterministic regardless of the OS/system preference.
 */
export async function pinTheme(page: Page, theme: "light" | "dark" = "light"): Promise<void> {
  await page.addInitScript((value) => {
    try {
      window.localStorage.setItem("theme", value);
    } catch {
      /* storage unavailable — ignore */
    }
  }, theme);
}

/**
 * Selectors for regions that are inherently non-deterministic (embedded video iframes, live
 * relative dates, etc.). Passed to `toHaveScreenshot({ mask })` so diffs ignore them.
 */
export function dynamicRegions(page: Page) {
  return [
    page.locator("iframe"),
    page.locator("[data-visual-mask]"),
  ];
}

/** Wait for fonts + network idle so text metrics and layout have settled before capture. */
export async function settle(page: Page): Promise<void> {
  await page.waitForLoadState("networkidle").catch(() => {});
  await page.evaluate(() => document.fonts?.ready).catch(() => {});
}

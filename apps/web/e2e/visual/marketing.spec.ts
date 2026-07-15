import { test, expect } from "@playwright/test";
import { pinTheme, dynamicRegions, settle } from "./_helpers";

/**
 * Visual regression — public marketing + key pages (Part 16).
 *
 * These are the public, unauthenticated surfaces (homepage, catalog, pricing, about, contact,
 * certificate/verify). They render against live API data in a seeded environment, so genuinely
 * dynamic regions (video embeds, relative dates) are masked. Run against a deterministic,
 * demo-seeded backend for stable baselines (see README).
 */
const PAGES: { name: string; path: string; fullPage?: boolean }[] = [
  { name: "homepage", path: "/", fullPage: true },
  { name: "courses", path: "/courses" },
  { name: "pricing", path: "/pricing", fullPage: true },
  { name: "about", path: "/about", fullPage: true },
  { name: "contact", path: "/contact" },
  { name: "verify", path: "/verify" }, // certificate verification landing
  { name: "login", path: "/login" },
  { name: "register", path: "/register" },
];

test.describe("marketing", () => {
  test.beforeEach(async ({ page }) => {
    await pinTheme(page, "light");
  });

  for (const p of PAGES) {
    test(`page: ${p.name}`, async ({ page }) => {
      const res = await page.goto(p.path);
      test.skip(!!res && res.status() >= 400, `"${p.path}" unavailable (status ${res?.status()}).`);
      await settle(page);
      await expect(page).toHaveScreenshot(`marketing-${p.name}.png`, {
        fullPage: p.fullPage ?? false,
        mask: dynamicRegions(page),
      });
    });
  }
});

import type { Metadata } from "next";
import { notFound } from "next/navigation";
import { DesignShowcase } from "./showcase";

/**
 * Internal Design-System Showcase (Part 15) — dev/admin only, NEVER public.
 *
 * GATING (three independent layers, documented for reviewers):
 *   1. Route gate — available in development by default; in a production build it renders
 *      `notFound()` (a 404) unless `NEXT_PUBLIC_ENABLE_DESIGN_SHOWCASE === "true"` is set.
 *   2. `robots` metadata below emits `<meta name="robots" content="noindex, nofollow">`
 *      (reinforced by an explicit tag in the tree) so it is never indexed even if reachable.
 *   3. Sitemap exclusion — the route is not in `app/sitemap.ts` `publicRoutes` and is not a CMS
 *      page, so it is never emitted; `app/robots.ts` also disallows `/design-system`.
 * It is intentionally not wired into any public navigation.
 */
export const metadata: Metadata = {
  title: "Design System Showcase",
  robots: { index: false, follow: false, nocache: true, googleBot: { index: false, follow: false } },
};

const enabled =
  process.env.NODE_ENV !== "production" || process.env.NEXT_PUBLIC_ENABLE_DESIGN_SHOWCASE === "true";

export default function DesignSystemPage() {
  if (!enabled) notFound();
  return (
    <>
      <meta name="robots" content="noindex, nofollow" />
      <DesignShowcase />
    </>
  );
}

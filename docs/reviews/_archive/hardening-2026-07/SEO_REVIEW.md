# SEO Review (Audit)

**Date:** 2026-07-16 · **Scope:** `apps/web` (Next.js 15 App Router). Evidence-based; no changes applied (audit phase).

## Current state (strong foundation)
- **Metadata**: `generateMetadata` used site-wide. Root layout (`app/layout.tsx:34-70`) sets `metadataBase`, title default+template, description, canonical `/`, icons, OG, Twitter, `robots{index,follow}` — branded from admin Branding with `siteConfig` fallback. All existing routes export per-page title/description.
- **Per-page SEO manager**: clean single-mapper architecture — `lib/seo/api.ts` (`getSeo`, fail-safe→null), `lib/seo/metadata.ts` `buildMetadata()` merges managed override over fallback, wired for course + event detail, homepage, and marketing lists; CMS pages via `lib/pages/metadata.ts`.
- **robots** (`app/robots.ts`): allow `/` + disallow private surfaces + sitemap link. Per-page index/follow control via SEO manager. ✓
- **Sitemap** (`app/sitemap.ts`): 17 static routes + CMS pages (`/p/{slug}`) + managed SEO entities with priority/changefreq, deduped, fail-safe. ✓
- **JSON-LD present**: `EducationalOrganization` (root), `Event` (event detail), `WebPage` (CMS pages). ✓

## Problems found (prioritized, evidence)
| # | Severity | Finding | Evidence |
|---|---|---|---|
| SEO-1 | High | **No default/dynamic OG image** — `og:image` absent unless a per-entity `og_image` is set; no `opengraph-image.*` files exist | `app/**` (no opengraph-image), `lib/seo/metadata.ts:43` |
| SEO-2 | High | **Course detail emits no `Course` JSON-LD** (event detail has Event schema; course has none) | `courses/[public_id]/course-details-client.tsx` (no ld+json) |
| SEO-3 | Med | **No `BreadcrumbList` JSON-LD and breadcrumbs unused on real pages** — `components/ui/breadcrumb.tsx` referenced only by the design-system showcase | grep: breadcrumb only in `(dev)/design-system` |
| SEO-4 | Med | **hreflang effectively missing** — cookie-based locale (no `/en`,`/ar` paths); `alternates.languages` only if managed `seo.hreflang` present; root/CMS emit none | `app/layout.tsx`, `pages/metadata.ts:34` |
| SEO-5 | Med | **Course detail + list fallbacks lack `alternates.canonical`** — depends on a managed override existing | `courses/[public_id]/page.tsx:17-20` |
| SEO-6 | Low | **Sitemap omits dynamic detail URLs** (courses/events/trainers/categories) unless the managed `/seo/sitemap` API returns them | `app/sitemap.ts:17` |

## Changes applied
None (audit). All items queued in the consolidated backlog.

## Remaining opportunities (backlog seeds)
Add a default OG image (static `opengraph-image` + optional dynamic route); add `Course` + `BreadcrumbList` JSON-LD; wire real breadcrumbs on detail pages; add default per-path canonicals in route fallbacks; enumerate catalog detail URLs in the sitemap from the catalog API. All are additive and behavior-preserving.

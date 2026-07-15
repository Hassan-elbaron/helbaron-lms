// Pure, side-effect-free response contract for the E2E mock API. Kept separate from the HTTP
// server (mock-api.mjs) so the contract can be unit-tested without opening a socket
// (see tests/seo/sitemap-mock-contract.test.ts).
//
// Shapes are the SINGLE source of truth mirror of the real Laravel API + the frontend TS types:
//   GET /api/v1/seo/sitemap  -> ApiResponse::success(['entries' => [...]])  (SeoController@sitemap)
//                               entry: { url, priority, changefreq, updated_at }  (lib/seo/api.ts)
//   GET /api/v1/pages        -> { data: { pages: [...] } }                  (PageController@index)
//                               page:  StaticPageSummary                    (lib/pages/api.ts)
//   GET /api/v1/branding     -> { data: {} }        (mergeBranding({}) === defaultBranding)
//   GET /api/v1/feature-flags-> { data: { flags: {} } }
//   GET /api/v1/homepage     -> { data: { sections: [], seo: null } }       (falls back to built-in)
//   GET /api/v1/health       -> { status: "ok" }                            (readiness probe)
// A generic empty envelope is returned ONLY for endpoints where an empty response is contract-valid.

/** Deterministic timestamp for every fixture so builds/screenshots are reproducible. */
const ISO = "2026-07-01T00:00:00+00:00";

/**
 * Representative managed-SEO sitemap rows: one course, one category, one trainer, one event.
 * (Homepage + static marketing routes are emitted by app/sitemap.ts's own static list; one CMS
 * page is provided via /pages below.) Every row carries all fields app/sitemap.ts consumes.
 */
export const MOCK_SEO_SITEMAP_ENTRIES = [
  { url: "/courses/intro-to-project-management", priority: 0.8, changefreq: "weekly", updated_at: ISO },
  { url: "/categories/business-ai", priority: 0.7, changefreq: "weekly", updated_at: ISO },
  { url: "/trainers/yara-adel", priority: 0.6, changefreq: "monthly", updated_at: ISO },
  { url: "/events/leadership-live-2026", priority: 0.6, changefreq: "daily", updated_at: ISO },
];

/**
 * One published CMS page (StaticPageSummary). The slug deliberately avoids app/sitemap.ts's
 * OWN_ROUTE_SLUGS (about/contact/privacy/terms) so it emits a /p/{slug} sitemap entry.
 */
export const MOCK_PUBLISHED_PAGES = [
  {
    id: "pg_getting_started",
    slug: "getting-started",
    template: "standard",
    title: { en: "Getting Started", ar: "البداية" },
    excerpt: null,
    show_in_nav: true,
    position: 1,
    status: "published",
    published_at: ISO,
    updated_at: ISO,
  },
];

/** Exact-path handlers. Everything else is fail-open (see resolveMock). */
const ROUTES = new Map([
  ["/api/v1/health", () => ({ status: "ok" })],
  ["/api/v1/branding", () => ({ data: {} })],
  ["/api/v1/feature-flags", () => ({ data: { flags: {} } })],
  ["/api/v1/homepage", () => ({ data: { sections: [], seo: null } })],
  ["/api/v1/homepage/preview", () => ({ data: { sections: [], seo: null } })],
  ["/api/v1/seo/sitemap", () => ({ data: { entries: MOCK_SEO_SITEMAP_ENTRIES } })],
  ["/api/v1/pages", () => ({ data: { pages: MOCK_PUBLISHED_PAGES } })],
]);

/**
 * Resolve a request path/method to a deterministic { status, body }.
 * @param {string} pathname
 * @param {string} [method]
 * @returns {{ status: number, body: unknown }}
 */
export function resolveMock(pathname, method = "GET") {
  const path = pathname.replace(/\/+$/, "") || "/";
  const handler = ROUTES.get(path);
  if (handler) return { status: 200, body: handler() };
  // Fail-open only where empty is contract-valid: client lists render empty (never error/hang).
  if (method === "GET" || method === "HEAD") return { status: 200, body: { data: [] } };
  return { status: 200, body: { data: {} } };
}

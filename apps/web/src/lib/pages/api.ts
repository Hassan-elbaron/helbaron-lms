import { api } from "@/lib/api/client";

/**
 * Public Static Pages CMS client. Structured page records (about / privacy / custom pages …) are
 * managed in the admin CMS and served published-only via GET /api/v1/pages*. This module fetches a
 * page by slug and ALWAYS fails safe: it returns null on any error or 404 so callers can fall back
 * to their built-in hardcoded content (existing page URLs never break). All content is bilingual.
 */

export type Localized = { en: string; ar: string };

export type PageTemplate = "standard" | "legal" | "faq" | "contact";

export type StaticPageSeo = {
  meta_title?: Localized | null;
  meta_description?: Localized | null;
  keywords?: string | null;
  canonical?: string | null;
  robots_index?: boolean;
  robots_follow?: boolean;
  og_title?: Localized | string | null;
  og_description?: Localized | string | null;
  og_image?: string | null;
  twitter_card?: string | null;
  json_ld?: string | null;
};

/** Full page payload (GET /api/v1/pages/{slug}). */
export type StaticPage = {
  id: string;
  slug: string;
  template: PageTemplate;
  title: Localized;
  excerpt: Localized | null;
  body: Localized;
  hero_image: string | null;
  show_in_nav: boolean;
  position: number;
  status: string;
  published_at: string | null;
  updated_at: string | null;
  seo: StaticPageSeo;
};

/** Summary payload (GET /api/v1/pages). */
export type StaticPageSummary = {
  id: string;
  slug: string;
  template: PageTemplate;
  title: Localized;
  excerpt: Localized | null;
  show_in_nav: boolean;
  position: number;
  status: string;
  published_at: string | null;
  updated_at: string | null;
};

/** Narrow an unknown payload to a StaticPage: a plain object carrying a non-empty string `slug`. */
function isStaticPage(data: unknown): data is StaticPage {
  return (
    typeof data === "object" &&
    data !== null &&
    !Array.isArray(data) &&
    typeof (data as { slug?: unknown }).slug === "string" &&
    (data as { slug: string }).slug.length > 0
  );
}

/**
 * Fetch a published page by slug. Returns null on any failure OR when the page is not live, so the
 * caller can render its hardcoded fallback. `preview` requests the admin draft (best-effort; needs
 * an authenticated admin session).
 *
 * Defensive: only a well-formed page object resolves to a page; any other payload (null, an array,
 * or a malformed object) resolves to null so callers hit the `if (!page)` fallback instead of
 * dereferencing missing localized fields (which threw `Cannot read properties of undefined (en)`).
 */
export async function getStaticPage(slug: string, preview = false): Promise<StaticPage | null> {
  try {
    const path = preview ? `pages/${slug}/preview` : `pages/${slug}`;
    const data = await api.data<StaticPage | null>(path, { auth: preview, cache: "no-store" });
    return isStaticPage(data) ? data : null;
  } catch {
    return null;
  }
}

/**
 * Fetch the list of published pages (for the sitemap). Returns [] on any failure.
 * Defensive: only accepts a real `pages` array and drops rows missing a `slug`, so a malformed
 * payload can never make the sitemap build crash. A genuine API error still surfaces as [] via the
 * catch (the documented fail-safe).
 */
export async function listPublishedPages(): Promise<StaticPageSummary[]> {
  try {
    const payload = await api.data<{ pages?: StaticPageSummary[] } | null>("pages", { auth: false });
    const pages = payload && Array.isArray(payload.pages) ? payload.pages : [];
    return pages.filter((p): p is StaticPageSummary => Boolean(p) && typeof p.slug === "string" && p.slug.length > 0);
  } catch {
    return [];
  }
}

/** Pick a localized value from either a Localized bag or a plain string (SEO og_* fields). */
export function pickSeoText(value: Localized | string | null | undefined, locale: "en" | "ar"): string | undefined {
  if (value == null) return undefined;
  if (typeof value === "string") return value || undefined;
  return value[locale] || value.en || undefined;
}

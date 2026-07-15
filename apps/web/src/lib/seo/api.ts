import { api } from "@/lib/api/client";
import { type Locale } from "@/lib/i18n/config";

/**
 * Client for the centralized SEO Manager (GET /api/v1/seo/{entityType}/{key}). This is the SINGLE
 * source the frontend consults for managed per-page SEO overrides; it ALWAYS fails safe (returns null
 * on any error/404) so every caller can fall back to its existing hardcoded/derived metadata. The
 * frontend never re-derives SEO logic — it maps this resolved payload straight into Next.js Metadata
 * via the shared buildMetadata() helper.
 */

export type SeoLocalized = { en: string; ar: string };

export type SeoEntityType =
  | "homepage"
  | "static_page"
  | "course"
  | "category"
  | "trainer"
  | "event"
  | "marketing_page"
  | "certificate_verify"
  | "organization";

/** The fully-resolved SEO payload (overrides merged over entity + branding defaults). */
export type ResolvedSeo = {
  entity_type: string;
  entity_key: string;
  meta_title: SeoLocalized | null;
  meta_description: SeoLocalized | null;
  keywords: string | null;
  canonical: string;
  robots_index: boolean;
  robots_follow: boolean;
  og_title: SeoLocalized | null;
  og_description: SeoLocalized | null;
  og_image: string | null;
  twitter_title: SeoLocalized | null;
  twitter_description: SeoLocalized | null;
  twitter_image: string | null;
  twitter_card: string;
  json_ld: Record<string, unknown> | unknown[] | null;
  breadcrumb: unknown[] | null;
  hreflang: Record<string, string> | null;
  sitemap_enabled: boolean;
  sitemap_priority: number | null;
  sitemap_changefreq: string | null;
};

export type SeoSitemapEntry = {
  url: string;
  priority: number | null;
  changefreq: string | null;
  updated_at: string | null;
};

/**
 * Fetch the resolved managed SEO for an entity. Returns null on ANY failure (network / 404 / parse)
 * so the caller falls back to its own metadata — a missing override must never break a page.
 */
export async function getSeo(entityType: SeoEntityType, key: string): Promise<ResolvedSeo | null> {
  try {
    return await api.data<ResolvedSeo>(`seo/${entityType}/${encodeURIComponent(key)}`, {
      auth: false,
      cache: "no-store",
    });
  } catch {
    return null;
  }
}

/**
 * Fetch the managed sitemap entries (GET /api/v1/seo/sitemap). Returns [] on any failure.
 * Defensive: only accepts a real `entries` array and drops rows missing a `url`, so a malformed
 * payload (e.g. an array, which would otherwise expose `Array.prototype.entries`) can never make
 * the sitemap build crash with a non-iterable value. A genuine API error still surfaces as [] via
 * the catch (the documented fail-safe) — this does not silently swallow errors it can act on.
 */
export async function getSeoSitemap(): Promise<SeoSitemapEntry[]> {
  try {
    const payload = await api.data<{ entries?: SeoSitemapEntry[] } | null>("seo/sitemap", { auth: false });
    const entries = payload && Array.isArray(payload.entries) ? payload.entries : [];
    return entries.filter((e): e is SeoSitemapEntry => Boolean(e) && typeof e.url === "string" && e.url.length > 0);
  } catch {
    return [];
  }
}

/** Pick a localized SEO string, falling back to English then undefined. */
export function pickLocalized(value: SeoLocalized | null | undefined, locale: Locale): string | undefined {
  if (!value) return undefined;
  return value[locale] || value.en || undefined;
}

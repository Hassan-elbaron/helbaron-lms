import type { Metadata } from "next";
import { siteConfig } from "@/config/site";
import { type Localized, type StaticPage, type StaticPageSeo } from "@/lib/pages/api";
import type { ResolvedSeo, SeoLocalized } from "@/lib/seo/api";
import { buildMetadata } from "@/lib/seo/metadata";

/** Normalize a CMS SEO value (Localized bag OR plain string OR null) into a { en, ar } bag. */
function toBag(value: Localized | string | null | undefined): SeoLocalized | null {
  if (value == null) return null;
  if (typeof value === "string") return value ? { en: value, ar: value } : null;
  return { en: value.en ?? "", ar: value.ar ?? value.en ?? "" };
}

/** Adapt a CMS page's stored SEO bag into the shared ResolvedSeo shape (defaults from the page). */
function pageToResolvedSeo(page: StaticPage, fallbackPath: string): ResolvedSeo {
  const seo: StaticPageSeo = page.seo ?? {};

  return {
    entity_type: "static_page",
    entity_key: page.slug,
    meta_title: toBag(seo.meta_title) ?? page.title,
    meta_description: toBag(seo.meta_description) ?? page.excerpt,
    keywords: seo.keywords ?? null,
    canonical: seo.canonical || fallbackPath,
    robots_index: seo.robots_index ?? true,
    robots_follow: seo.robots_follow ?? true,
    og_title: toBag(seo.og_title) ?? toBag(seo.meta_title) ?? page.title,
    og_description: toBag(seo.og_description) ?? toBag(seo.meta_description) ?? page.excerpt,
    og_image: seo.og_image ?? page.hero_image,
    twitter_title: null,
    twitter_description: null,
    twitter_image: null,
    twitter_card: seo.twitter_card ?? "summary_large_image",
    json_ld: null,
    breadcrumb: null,
    hreflang: null,
    sitemap_enabled: true,
    sitemap_priority: null,
    sitemap_changefreq: null,
  };
}

/**
 * Build Next.js Metadata from a CMS page's resolved SEO block. Metadata is emitted in English (the
 * canonical crawl locale), mirroring the existing hardcoded pages. Delegates to the SINGLE shared
 * buildMetadata() mapper — no metadata logic is duplicated here — passing a title/description
 * fallback derived from the page so a page always has sensible values.
 */
export function buildPageMetadata(page: StaticPage, fallbackPath: string): Metadata {
  const fallback: Metadata = {
    title: page.title.en,
    description: page.excerpt?.en ?? undefined,
  };

  return buildMetadata(pageToResolvedSeo(page, fallbackPath), fallback, "en");
}

/**
 * Resolve the JSON-LD for a page: the admin-supplied raw JSON-LD when valid, otherwise a default
 * WebPage document. Returns null when there is nothing sensible to emit.
 */
export function pageJsonLd(page: StaticPage): Record<string, unknown> | null {
  const raw = page.seo?.json_ld;
  if (raw) {
    try {
      const parsed = JSON.parse(raw) as Record<string, unknown>;
      if (parsed && typeof parsed === "object") return parsed;
    } catch {
      // fall through to the default document
    }
  }

  return {
    "@context": "https://schema.org",
    "@type": "WebPage",
    name: page.title.en,
    ...(page.excerpt?.en ? { description: page.excerpt.en } : {}),
    url: `${siteConfig.url}${page.seo?.canonical || `/${page.slug}`}`,
    inLanguage: ["en", "ar"],
    isPartOf: { "@type": "WebSite", name: siteConfig.name, url: siteConfig.url },
  };
}

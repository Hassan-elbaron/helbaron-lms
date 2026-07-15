import type { Metadata } from "next";
import { defaultLocale, type Locale } from "@/lib/i18n/config";
import { pickLocalized, type ResolvedSeo } from "@/lib/seo/api";

/**
 * The ONE mapper from a resolved SEO payload into a Next.js Metadata object. Every page that honours a
 * managed SEO override calls this — the metadata-generation logic lives here and NOWHERE else. When
 * `seo` is null (no managed override) the caller's `fallback` is returned untouched, so existing
 * hardcoded/derived metadata always keeps working. Bilingual fields are resolved for the given locale.
 */
export function buildMetadata(
  seo: ResolvedSeo | null,
  fallback: Metadata,
  locale: Locale = defaultLocale,
): Metadata {
  if (!seo) return fallback;

  const title = pickLocalized(seo.meta_title, locale) ?? (fallback.title as string | undefined);
  const description = pickLocalized(seo.meta_description, locale) ?? (fallback.description as string | undefined);
  const canonical = seo.canonical;
  const ogTitle = pickLocalized(seo.og_title, locale) ?? title;
  const ogDescription = pickLocalized(seo.og_description, locale) ?? description;
  const twitterTitle = pickLocalized(seo.twitter_title, locale) ?? ogTitle;
  const twitterDescription = pickLocalized(seo.twitter_description, locale) ?? ogDescription;
  const twitterCard = (seo.twitter_card as "summary" | "summary_large_image") || "summary_large_image";

  return {
    ...fallback,
    title,
    description,
    // Exactly one canonical per page — the resolver guarantees a single valid value.
    alternates: {
      canonical,
      ...(seo.hreflang ? { languages: seo.hreflang } : {}),
    },
    robots: { index: seo.robots_index, follow: seo.robots_follow },
    openGraph: {
      ...(fallback.openGraph ?? {}),
      title: ogTitle,
      description: ogDescription,
      url: canonical,
      type: "website",
      ...(seo.og_image ? { images: [{ url: seo.og_image }] } : {}),
    },
    twitter: {
      ...(fallback.twitter ?? {}),
      card: twitterCard,
      title: twitterTitle,
      description: twitterDescription,
      ...(seo.twitter_image ? { images: [seo.twitter_image] } : {}),
    },
    ...(seo.keywords ? { keywords: seo.keywords } : {}),
  };
}

/**
 * The JSON-LD document to embed for a resolved SEO payload, or null when there is none. The resolver
 * only ever emits VALID structured data, so this is a straight pass-through (no re-validation here).
 */
export function seoJsonLd(seo: ResolvedSeo | null): Record<string, unknown> | unknown[] | null {
  return seo?.json_ld ?? null;
}

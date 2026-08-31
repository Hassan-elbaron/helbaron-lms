import type { Metadata } from "next";
import { defaultLocale, type Locale } from "@/lib/i18n/config";
import { pickLocalized, type ResolvedSeo } from "@/lib/seo/api";
import { withBrand } from "@/lib/branding/metadata";
import { resolveBrandName } from "@/lib/branding/metadata";

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

/**
 * The brand-aware entry point pages call.
 *
 * Page metadata is written with a `{brand}` token so one bundle can serve any academy, and this is
 * the ONE place that resolves it — a page added later inherits the behaviour by calling this instead
 * of remembering to interpolate.
 *
 * Async because the brand arrives from the branding API at request time. `buildMetadata` above stays
 * SYNC and pure: it is the mapper under test, and making it async would have forced every existing
 * caller and assertion to change for no gain.
 */
export async function buildBrandedMetadata(
  seo: ResolvedSeo | null,
  fallback: Metadata,
  locale: Locale = defaultLocale,
): Promise<Metadata> {
  return withBrand(buildMetadata(seo, fallback, locale), await resolveBrandName());
}

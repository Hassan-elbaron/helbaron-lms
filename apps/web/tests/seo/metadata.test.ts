import { describe, expect, it } from "vitest";
import type { ResolvedSeo } from "@/lib/seo/api";
import { buildMetadata, seoJsonLd } from "@/lib/seo/metadata";

function resolved(overrides: Partial<ResolvedSeo> = {}): ResolvedSeo {
  return {
    entity_type: "course",
    entity_key: "abc",
    meta_title: { en: "EN Title", ar: "AR عنوان" },
    meta_description: { en: "EN desc", ar: "AR وصف" },
    keywords: "a,b",
    canonical: "/courses/abc",
    robots_index: true,
    robots_follow: true,
    og_title: null,
    og_description: null,
    og_image: "https://cdn.test/x.png",
    twitter_title: null,
    twitter_description: null,
    twitter_image: null,
    twitter_card: "summary_large_image",
    json_ld: null,
    breadcrumb: null,
    hreflang: null,
    sitemap_enabled: true,
    sitemap_priority: null,
    sitemap_changefreq: null,
    ...overrides,
  };
}

describe("buildMetadata", () => {
  it("returns the fallback untouched when there is no managed override", () => {
    const fallback = { title: "Fallback", description: "Fallback desc" };
    expect(buildMetadata(null, fallback)).toBe(fallback);
  });

  it("maps a resolved payload into Next.js Metadata with exactly one canonical", () => {
    const meta = buildMetadata(resolved(), { title: "Fallback" }, "en");

    expect(meta.title).toBe("EN Title");
    expect(meta.description).toBe("EN desc");
    expect(meta.alternates?.canonical).toBe("/courses/abc");
    expect(meta.robots).toEqual({ index: true, follow: true });
    // og title falls back to the meta title; the image is carried through.
    expect(meta.openGraph?.title).toBe("EN Title");
    expect(meta.keywords).toBe("a,b");
  });

  it("resolves bilingual fields for the requested locale", () => {
    const meta = buildMetadata(resolved(), { title: "Fallback" }, "ar");
    expect(meta.title).toBe("AR عنوان");
    expect(meta.description).toBe("AR وصف");
  });

  it("honours a noindex override", () => {
    const meta = buildMetadata(resolved({ robots_index: false, robots_follow: false }), { title: "F" });
    expect(meta.robots).toEqual({ index: false, follow: false });
  });

  it("emits hreflang alternates when present", () => {
    const meta = buildMetadata(resolved({ hreflang: { en: "/en/x", ar: "/ar/x" } }), { title: "F" });
    expect(meta.alternates?.languages).toEqual({ en: "/en/x", ar: "/ar/x" });
  });
});

describe("seoJsonLd", () => {
  it("passes through the resolved json_ld or null", () => {
    expect(seoJsonLd(null)).toBeNull();
    expect(seoJsonLd(resolved())).toBeNull();
    const doc = { "@type": "Course" };
    expect(seoJsonLd(resolved({ json_ld: doc }))).toBe(doc);
  });
});

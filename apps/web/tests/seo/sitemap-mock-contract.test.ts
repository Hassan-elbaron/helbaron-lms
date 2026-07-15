import { describe, it, expect } from "vitest";
import { resolveMock, MOCK_SEO_SITEMAP_ENTRIES, MOCK_PUBLISHED_PAGES } from "../../e2e/support/mock-routes.mjs";

/**
 * Guards the E2E mock API's sitemap contract so `/sitemap.xml` can always be prerendered during the
 * Playwright build. The regression this locks down: a generic `{ data: [] }` fallback made
 * getSeoSitemap() destructure `entries` off an array (yielding Array.prototype.entries, a function)
 * which crashed the sitemap build with "e is not iterable". The mock now serves the real
 * `{ data: { entries: [...] } }` / `{ data: { pages: [...] } }` shapes; this test proves it.
 */

const CHANGEFREQS = new Set(["always", "hourly", "daily", "weekly", "monthly", "yearly", "never"]);

// Mirror of app/sitemap.ts toAbsolute() for URL-validity assertions.
function toAbsolute(url: string, baseUrl = "https://example.test"): string {
  if (/^https?:\/\//i.test(url)) return url;
  return url === "/" ? `${baseUrl}/` : `${baseUrl}${url.startsWith("/") ? url : `/${url}`}`;
}

describe("E2E mock API — sitemap contract", () => {
  it("GET /api/v1/seo/sitemap returns { data: { entries: [...] } } with valid, iterable rows", () => {
    const { status, body } = resolveMock("/api/v1/seo/sitemap");
    expect(status).toBe(200);
    const entries = (body as { data: { entries: unknown } }).data.entries;
    expect(Array.isArray(entries)).toBe(true);
    expect((entries as unknown[]).length).toBeGreaterThan(0);

    // Must be iterable in exactly the shape app/sitemap.ts consumes (for..of over managed rows).
    for (const e of entries as Array<Record<string, unknown>>) {
      expect(typeof e.url).toBe("string");
      expect((e.url as string).length).toBeGreaterThan(0);
      expect(e.priority === null || typeof e.priority === "number").toBe(true);
      expect(
        e.changefreq === null || (typeof e.changefreq === "string" && CHANGEFREQS.has(e.changefreq as string)),
      ).toBe(true);
      expect(e.updated_at === null || typeof e.updated_at === "string").toBe(true);
    }
  });

  it("GET /api/v1/pages returns { data: { pages: [...] } } with valid published-page summaries", () => {
    const { status, body } = resolveMock("/api/v1/pages");
    expect(status).toBe(200);
    const pages = (body as { data: { pages: unknown } }).data.pages;
    expect(Array.isArray(pages)).toBe(true);
    expect((pages as unknown[]).length).toBeGreaterThan(0);
    for (const p of pages as Array<Record<string, unknown>>) {
      expect(typeof p.slug).toBe("string");
      expect((p.slug as string).length).toBeGreaterThan(0);
      expect(p.updated_at === null || typeof p.updated_at === "string").toBe(true);
    }
  });

  it("every managed/CMS sitemap URL normalizes to a valid absolute URL with no undefined", () => {
    for (const e of MOCK_SEO_SITEMAP_ENTRIES) {
      const abs = toAbsolute(e.url);
      expect(() => new URL(abs)).not.toThrow();
      expect(abs).not.toContain("undefined");
    }
    for (const p of MOCK_PUBLISHED_PAGES) {
      const abs = toAbsolute(`/p/${p.slug}`);
      expect(() => new URL(abs)).not.toThrow();
      expect(abs).not.toContain("undefined");
    }
  });

  it("fail-open fallback stays a valid empty envelope for unknown GET paths", () => {
    const { status, body } = resolveMock("/api/v1/some/unknown/list");
    expect(status).toBe(200);
    expect(body).toEqual({ data: [] });
  });
});

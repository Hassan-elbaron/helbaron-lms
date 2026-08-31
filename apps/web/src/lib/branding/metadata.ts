import { getBranding } from "@/lib/branding/api";
import { interpolate } from "@/lib/i18n/interpolate";

/**
 * Resolve `{brand}` tokens in page metadata, server-side, at request time.
 *
 * WHY A HELPER RATHER THAN A CONSTANT. Page titles and descriptions were written with the vendor's
 * name baked in — `"About HElbaron"`, `"Compare HElbaron"`, `organizer.name` in event JSON-LD. Those
 * are `export const metadata` values, evaluated at BUILD time, so they are frozen into the bundle
 * and identical on every deployment. In a product shipped once per academy that means every
 * customer's `<title>`, Open Graph card and structured data advertise somebody else's brand — and
 * unlike body copy, this is the text that search engines index and social platforms cache.
 *
 * Pages therefore write `{brand}` and call this from an async `generateMetadata()`.
 *
 * Never throws: `getBranding()` already falls back to the built-in defaults on any failure, so a
 * branding outage degrades to the generic word rather than an unrenderable page.
 */
export async function resolveBrandName(): Promise<string> {
  const branding = await getBranding();

  return branding.identity.brand_name.en || "Academy";
}

/**
 * Interpolate `{brand}` through a whole metadata-ish object (strings, nested objects, arrays).
 *
 * Deliberately generic so a page can hand it `title`, `description`, `openGraph` and JSON-LD in one
 * call instead of remembering every field that might carry the token.
 */
export function withBrand<T>(value: T, brand: string): T {
  if (typeof value === "string") {
    return interpolate(value, { brand }) as unknown as T;
  }

  if (Array.isArray(value)) {
    return value.map((entry) => withBrand(entry, brand)) as unknown as T;
  }

  if (value && typeof value === "object") {
    const out: Record<string, unknown> = {};
    for (const [key, entry] of Object.entries(value as Record<string, unknown>)) {
      out[key] = withBrand(entry, brand);
    }
    return out as unknown as T;
  }

  return value;
}

/** Convenience for the common case: resolve the brand and interpolate one object. */
export async function brandedMetadata<T>(metadata: T): Promise<T> {
  return withBrand(metadata, await resolveBrandName());
}

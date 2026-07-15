import { cookies } from "next/headers";
import { defaultLocale, isLocale, localeCookieName, type Locale } from "@/lib/i18n/config";

/**
 * Resolve the current request locale from the locale cookie (defaults to the site default). Kept in
 * its own module so the pure SEO helpers (api.ts / metadata.ts) stay free of next/headers and remain
 * unit-testable; only Server Components (generateMetadata) import this.
 */
export async function resolveLocale(): Promise<Locale> {
  const store = await cookies();
  const value = store.get(localeCookieName)?.value;
  return isLocale(value) ? value : defaultLocale;
}

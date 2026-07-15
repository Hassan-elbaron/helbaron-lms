export const locales = ["en", "ar"] as const;
export type Locale = (typeof locales)[number];

/** Type guard for anything claiming to be a locale (env vars, cookies, …). */
export function isLocale(value: unknown): value is Locale {
  return typeof value === "string" && (locales as readonly string[]).includes(value);
}

const envDefault = process.env.NEXT_PUBLIC_DEFAULT_LOCALE;
export const defaultLocale: Locale = isLocale(envDefault) ? envDefault : "en";

/** Name of the cookie that persists the user's locale choice. */
export const localeCookieName = "locale";

/** Text direction per locale. */
export const localeDirection: Record<Locale, "ltr" | "rtl"> = {
  en: "ltr",
  ar: "rtl",
};

export function isRtl(locale: Locale): boolean {
  return localeDirection[locale] === "rtl";
}

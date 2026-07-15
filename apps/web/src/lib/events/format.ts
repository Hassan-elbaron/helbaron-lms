import type { Locale } from "@/lib/i18n/config";

/**
 * Format an ISO datetime in the given locale and event timezone. Falls back gracefully when the
 * value is missing or the timezone is unrecognized (renders in the browser/host zone).
 */
export function formatEventDateTime(iso: string | null, timezone: string | null, locale: Locale): string {
  if (!iso) return "";
  const date = new Date(iso);
  if (Number.isNaN(date.getTime())) return "";
  try {
    return new Intl.DateTimeFormat(locale === "ar" ? "ar" : "en", {
      dateStyle: "medium",
      timeStyle: "short",
      timeZone: timezone ?? undefined,
    }).format(date);
  } catch {
    return new Intl.DateTimeFormat(locale === "ar" ? "ar" : "en", {
      dateStyle: "medium",
      timeStyle: "short",
    }).format(date);
  }
}

/** Short date only (no time), used for compact cards. */
export function formatEventDate(iso: string | null, timezone: string | null, locale: Locale): string {
  if (!iso) return "";
  const date = new Date(iso);
  if (Number.isNaN(date.getTime())) return "";
  try {
    return new Intl.DateTimeFormat(locale === "ar" ? "ar" : "en", {
      dateStyle: "medium",
      timeZone: timezone ?? undefined,
    }).format(date);
  } catch {
    return new Intl.DateTimeFormat(locale === "ar" ? "ar" : "en", { dateStyle: "medium" }).format(date);
  }
}

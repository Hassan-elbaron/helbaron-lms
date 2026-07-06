/** Format a minor-unit integer (e.g. cents) as a localized currency string. */
export function formatMoney(minor: number | null | undefined, currency: string, locale = "en"): string {
  const value = (minor ?? 0) / 100;
  try {
    return new Intl.NumberFormat(locale === "ar" ? "ar" : "en", { style: "currency", currency }).format(value);
  } catch {
    return `${value.toFixed(2)} ${currency}`;
  }
}

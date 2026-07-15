import { api } from "@/lib/api/client";

/**
 * Public branding / white-label client. The whole site is white-labelable from the admin Branding
 * editor (GET /api/v1/branding). This module fetches the branding payload server-side and, crucially,
 * NEVER lets the site break: on any failure it returns the built-in defaults (which mirror the
 * Editorial Academy design in globals.css). All localized strings are bilingual ({ en, ar }).
 */

export type Localized = { en: string; ar: string };

export type BrandSocialLinks = {
  twitter?: string;
  linkedin?: string;
  facebook?: string;
  instagram?: string;
  youtube?: string;
};

export type BrandIdentity = {
  brand_name: Localized;
  short_name: string;
  company_name: string;
  copyright: Localized;
  address: Localized;
  support_email: string;
  support_phone: string;
  social_links: BrandSocialLinks;
  default_language: string;
  timezone: string;
  currency: string;
  date_format: string;
  time_format: string;
};

export type BrandLogos = {
  logo_light: string;
  logo_dark: string;
  favicon: string;
  apple_icon: string;
  pwa_icon: string;
  email_logo: string;
  certificate_logo: string;
  loader: string;
  login_background: string;
};

/** The 12 themeable colour slots (see globals.css var mapping in css.ts). */
export type BrandColors = {
  primary: string;
  secondary: string;
  accent: string;
  success: string;
  warning: string;
  danger: string;
  info: string;
  background: string;
  surface: string;
  sidebar: string;
  header: string;
  footer: string;
};

export type BrandTheme = {
  colors: BrandColors;
  radius: string;
  container_width: string;
  shadow_preset: string;
  font_body: string;
  font_heading: string;
  google_font: string;
  spacing_scale: string;
  dark: BrandColors;
  preset: string;
};

export type BrandEmail = {
  header: Localized;
  footer: Localized;
  colors: { background: string; text: string; button: string };
  signature: Localized;
  social_links: BrandSocialLinks;
};

export type BrandCertificate = {
  background: string;
  logo: string;
  signature: string;
  stamp: string;
  qr_position: string;
  font: string;
  colors: { text: string; accent: string };
  margins: { top: number; right: number; bottom: number; left: number };
};

export type Branding = {
  identity: BrandIdentity;
  logos: BrandLogos;
  theme: BrandTheme;
  email: BrandEmail;
  certificate: BrandCertificate;
};

/**
 * Built-in branding defaults — the Editorial Academy identity. Colours mirror apps/web globals.css
 * (light + `.dark`) so injecting them is a visual no-op; the site looks identical when branding is
 * empty or unreachable. This is the fallback that guarantees the site never breaks.
 */
export const defaultBranding: Branding = {
  identity: {
    brand_name: { en: "HElbaron", ar: "إلبارون" },
    short_name: "HElbaron",
    company_name: "HElbaron Academy",
    copyright: { en: "All rights reserved.", ar: "جميع الحقوق محفوظة." },
    address: { en: "Cairo · Dubai · Riyadh", ar: "القاهرة · دبي · الرياض" },
    support_email: "support@helbaron.com",
    support_phone: "",
    social_links: { twitter: "", linkedin: "", facebook: "", instagram: "", youtube: "" },
    default_language: "en",
    timezone: "Asia/Riyadh",
    currency: "SAR",
    date_format: "d M Y",
    time_format: "H:i",
  },
  logos: {
    logo_light: "",
    logo_dark: "",
    favicon: "",
    apple_icon: "",
    pwa_icon: "",
    email_logo: "",
    certificate_logo: "",
    loader: "",
    login_background: "",
  },
  theme: {
    colors: {
      primary: "oklch(0.36 0.045 185)",
      secondary: "oklch(0.91 0.03 86)",
      accent: "oklch(0.90 0.035 70)",
      success: "oklch(0.55 0.11 165)",
      warning: "oklch(0.74 0.12 82)",
      danger: "oklch(0.55 0.19 30)",
      info: "oklch(0.60 0.11 240)",
      background: "oklch(0.962 0.017 88)",
      surface: "oklch(0.99 0.008 88)",
      sidebar: "oklch(0.36 0.045 185)",
      header: "oklch(0.962 0.017 88)",
      footer: "oklch(0.36 0.045 185)",
    },
    radius: "0.75rem",
    container_width: "72rem",
    shadow_preset: "soft",
    font_body: "Inter",
    font_heading: "Fraunces",
    google_font: "",
    spacing_scale: "default",
    dark: {
      primary: "oklch(0.62 0.07 183)",
      secondary: "oklch(0.30 0.03 190)",
      accent: "oklch(0.33 0.035 60)",
      success: "oklch(0.68 0.12 165)",
      warning: "oklch(0.80 0.13 84)",
      danger: "oklch(0.62 0.18 28)",
      info: "oklch(0.66 0.11 240)",
      background: "oklch(0.21 0.022 190)",
      surface: "oklch(0.25 0.026 190)",
      sidebar: "oklch(0.25 0.026 190)",
      header: "oklch(0.21 0.022 190)",
      footer: "oklch(0.25 0.026 190)",
    },
    preset: "helbaron",
  },
  email: {
    header: { en: "", ar: "" },
    footer: {
      en: "HElbaron Academy — Master the core. Lead the future.",
      ar: "أكاديمية إلبارون — أتقن الأساس. قُد المستقبل.",
    },
    colors: { background: "#F7F1E3", text: "#21302E", button: "#134E4A" },
    signature: { en: "The HElbaron Team", ar: "فريق إلبارون" },
    social_links: { twitter: "", linkedin: "", facebook: "", instagram: "", youtube: "" },
  },
  certificate: {
    background: "",
    logo: "",
    signature: "",
    stamp: "",
    qr_position: "bottom-right",
    font: "Fraunces",
    colors: { text: "#21302E", accent: "#134E4A" },
    margins: { top: 48, right: 48, bottom: 48, left: 48 },
  },
};

/**
 * Fetch branding server-side. Mirrors getHomepage: unauthenticated, per-request. Returns the
 * built-in defaults on ANY failure so the site is never broken by a missing/unreachable API.
 */
export async function getBranding(): Promise<Branding> {
  try {
    const data = await api.data<Partial<Branding>>("branding", { auth: false, cache: "no-store" });
    return mergeBranding(data);
  } catch {
    return defaultBranding;
  }
}

/** Deep-merge a (possibly partial) API payload over the built-in defaults so consumers get a full set. */
function mergeBranding(data: Partial<Branding> | null | undefined): Branding {
  if (!data) return defaultBranding;
  return {
    identity: { ...defaultBranding.identity, ...data.identity, social_links: { ...defaultBranding.identity.social_links, ...data.identity?.social_links } },
    logos: { ...defaultBranding.logos, ...data.logos },
    theme: {
      ...defaultBranding.theme,
      ...data.theme,
      colors: { ...defaultBranding.theme.colors, ...data.theme?.colors },
      dark: { ...defaultBranding.theme.dark, ...data.theme?.dark },
    },
    email: { ...defaultBranding.email, ...data.email },
    certificate: { ...defaultBranding.certificate, ...data.certificate },
  };
}

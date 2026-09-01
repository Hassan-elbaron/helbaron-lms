export const siteConfig = {
  /**
   * GENERIC FALLBACKS ONLY. This is a build-time constant, so anything written here is frozen into
   * the bundle and cannot vary per instance — and this product ships one deployment per academy.
   * The real name and description come from the branding API at request time; these values are what
   * renders if that call fails.
   *
   * `{brand}` in the description is interpolated against the resolved brand name at render.
   */
  name: "Academy",
  description:
    "{brand} is a bilingual (Arabic/English) academy for professional courses, live cohorts, and workshops — learn from expert instructors and earn verifiable certificates.",
  /** Public site origin, used for canonical URLs, sitemap, and social cards. */
  url: (process.env.NEXT_PUBLIC_SITE_URL ?? "http://localhost:3000").replace(/\/+$/, ""),
  apiBaseUrl: process.env.NEXT_PUBLIC_API_BASE_URL ?? "http://localhost:8000/api/v1",
} as const;

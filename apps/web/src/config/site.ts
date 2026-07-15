export const siteConfig = {
  name: "HElbaron",
  description:
    "HElbaron is a bilingual (Arabic/English) academy for professional courses, live cohorts, and workshops — learn from expert instructors and earn verifiable certificates.",
  /** Public site origin, used for canonical URLs, sitemap, and social cards. */
  url: (process.env.NEXT_PUBLIC_SITE_URL ?? "http://localhost:3000").replace(/\/+$/, ""),
  apiBaseUrl: process.env.NEXT_PUBLIC_API_BASE_URL ?? "http://localhost:8000/api/v1",
} as const;

import type { MetadataRoute } from "next";

const baseUrl = (process.env.NEXT_PUBLIC_SITE_URL ?? "http://localhost:3000").replace(/\/+$/, "");

export default function robots(): MetadataRoute.Robots {
  return {
    rules: [
      {
        userAgent: "*",
        allow: "/",
        // Authenticated/private surfaces (real URLs — the (account) group adds no path segment).
        disallow: [
          "/profile", "/notifications", "/settings",
          "/dashboard", "/my-learning", "/continue-learning", "/certificates",
          "/learn", "/lessons",
          "/cart", "/checkout", "/orders", "/contracts",
          "/crm", "/teach", "/org",
          "/analytics", "/reports", "/dashboards",
          // Internal, dev-only design-system showcase (never indexed).
          "/design-system",
        ],
      },
    ],
    sitemap: `${baseUrl}/sitemap.xml`,
  };
}

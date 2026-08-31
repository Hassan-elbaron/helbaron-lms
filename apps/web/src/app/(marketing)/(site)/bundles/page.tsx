import { brandedMetadata } from "@/lib/branding/metadata";
import type { Metadata } from "next";
import { BundlesPageClient } from "./bundles-page-client";

const TITLE = "Course bundles — {brand}";
const DESCRIPTION =
  "Buy several {brand} courses in one purchase — for yourself or for your team, with seats your organization can assign.";

export async function generateMetadata(): Promise<Metadata> {
  // `{brand}` resolves here, at request time, from the branding API. As a build-time
  // constant this title/description was frozen into the bundle and identical on every
  // instance — and it is the text search engines index.
  return brandedMetadata({
  title: TITLE,
  description: DESCRIPTION,
  alternates: { canonical: "/bundles" },
  openGraph: { title: TITLE, description: DESCRIPTION, url: "/bundles", type: "website" },
  twitter: { card: "summary_large_image", title: TITLE, description: DESCRIPTION },
});
}

export default function BundlesPage() {
  return <BundlesPageClient />;
}

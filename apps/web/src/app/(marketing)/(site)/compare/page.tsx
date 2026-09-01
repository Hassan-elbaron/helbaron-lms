import { brandedMetadata } from "@/lib/branding/metadata";
import type { Metadata } from "next";
import { siteConfig } from "@/config/site";
import { jsonLdScript } from "@/lib/seo/json-ld";
import { competitorSlugs } from "@/config/comparison";
import { ComparisonIndex } from "@/components/marketing/comparison-page";

const TITLE = "Compare {brand}";
const DESCRIPTION =
  "Honest, category-level comparisons of {brand} against other learning platforms — operating models and capabilities, not unverified prices.";

export async function generateMetadata(): Promise<Metadata> {
  // `{brand}` resolves here, at request time, from the branding API. As a build-time
  // constant this title/description was frozen into the bundle and identical on every
  // instance — and it is the text search engines index.
  return brandedMetadata({
  title: TITLE,
  description: DESCRIPTION,
  alternates: { canonical: "/compare" },
  openGraph: { title: TITLE, description: DESCRIPTION, url: "/compare", type: "website" },
  twitter: { card: "summary_large_image", title: TITLE, description: DESCRIPTION },
});
}

const breadcrumb = {
  "@context": "https://schema.org",
  "@type": "BreadcrumbList",
  itemListElement: [
    { "@type": "ListItem", position: 1, name: "Home", item: `${siteConfig.url}/` },
    { "@type": "ListItem", position: 2, name: "Compare", item: `${siteConfig.url}/compare` },
  ],
};

export default function ComparePage() {
  return (
    <>
      <script type="application/ld+json" dangerouslySetInnerHTML={{ __html: jsonLdScript(breadcrumb) }} />
      <ComparisonIndex />
      {/* competitorSlugs referenced to keep the data contract explicit for future static params. */}
      <span hidden data-compare-count={competitorSlugs.length} />
    </>
  );
}

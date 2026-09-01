import { brandedMetadata } from "@/lib/branding/metadata";
import type { Metadata } from "next";
import { siteConfig } from "@/config/site";
import { jsonLdScript } from "@/lib/seo/json-ld";
import { SolutionsIndex } from "@/components/marketing/solutions-index";

const TITLE = "Solutions — {brand}";
const DESCRIPTION =
  "{brand} solutions for companies and enterprise L&D, training academies, independent instructors, and public-sector programs — one Arabic-first learning platform.";

export async function generateMetadata(): Promise<Metadata> {
  // `{brand}` resolves here, at request time, from the branding API. As a build-time
  // constant this title/description was frozen into the bundle and identical on every
  // instance — and it is the text search engines index.
  return brandedMetadata({
  title: TITLE,
  description: DESCRIPTION,
  alternates: { canonical: "/solutions" },
  openGraph: { title: TITLE, description: DESCRIPTION, url: "/solutions", type: "website" },
  twitter: { card: "summary_large_image", title: TITLE, description: DESCRIPTION },
});
}

const breadcrumb = {
  "@context": "https://schema.org",
  "@type": "BreadcrumbList",
  itemListElement: [
    { "@type": "ListItem", position: 1, name: "Home", item: `${siteConfig.url}/` },
    { "@type": "ListItem", position: 2, name: "Solutions", item: `${siteConfig.url}/solutions` },
  ],
};

export default function SolutionsPage() {
  return (
    <>
      <script type="application/ld+json" dangerouslySetInnerHTML={{ __html: jsonLdScript(breadcrumb) }} />
      <SolutionsIndex />
    </>
  );
}

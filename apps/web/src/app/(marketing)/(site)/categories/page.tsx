import type { Metadata } from "next";
import { getSeo } from "@/lib/seo/api";
import { resolveLocale } from "@/lib/seo/locale";
import { buildBrandedMetadata } from "@/lib/seo/metadata";
import { CategoriesPageClient } from "./categories-page-client";

/** Static metadata is the fallback; a managed SEO override (marketing_page/categories) wins. */
export async function generateMetadata(): Promise<Metadata> {
  const fallback: Metadata = {
    title: "Categories",
    description: "Explore {brand} course categories and find learning paths across every subject area.",
  };

  const [seo, locale] = await Promise.all([getSeo("marketing_page", "categories"), resolveLocale()]);
  return buildBrandedMetadata(seo, fallback, locale);
}

export default function CategoriesPage() {
  return <CategoriesPageClient />;
}

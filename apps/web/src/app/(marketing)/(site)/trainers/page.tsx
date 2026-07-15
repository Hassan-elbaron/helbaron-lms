import type { Metadata } from "next";
import { getSeo } from "@/lib/seo/api";
import { resolveLocale } from "@/lib/seo/locale";
import { buildMetadata } from "@/lib/seo/metadata";
import { TrainersPageClient } from "./trainers-page-client";

/** Static metadata is the fallback; a managed SEO override (marketing_page/trainers) wins. */
export async function generateMetadata(): Promise<Metadata> {
  const fallback: Metadata = {
    title: "Trainers",
    description: "Meet the HElbaron trainers — experienced instructors behind our courses, cohorts and workshops.",
  };

  const [seo, locale] = await Promise.all([getSeo("marketing_page", "trainers"), resolveLocale()]);
  return buildMetadata(seo, fallback, locale);
}

export default function TrainersPage() {
  return <TrainersPageClient />;
}

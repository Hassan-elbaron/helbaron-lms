import type { Metadata } from "next";
import { getSeo } from "@/lib/seo/api";
import { resolveLocale } from "@/lib/seo/locale";
import { buildMetadata } from "@/lib/seo/metadata";
import { CourseDetailsClient } from "./course-details-client";

type Params = { params: Promise<{ public_id: string }> };

/**
 * Static template metadata is the FALLBACK; a managed SEO override for this course (keyed by its
 * public_id) wins via the shared buildMetadata() helper. Course-specific details still load
 * client-side, so no course API dependency is introduced at build time beyond the optional override.
 */
export async function generateMetadata({ params }: Params): Promise<Metadata> {
  const { public_id } = await params;

  const fallback: Metadata = {
    title: "Course details",
    description: "View course details, curriculum, trainers and enrollment options on HElbaron.",
  };

  const [seo, locale] = await Promise.all([getSeo("course", public_id), resolveLocale()]);
  return buildMetadata(seo, fallback, locale);
}

export default function CourseDetailsPage() {
  return <CourseDetailsClient />;
}

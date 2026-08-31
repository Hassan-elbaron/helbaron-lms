import { cache } from "react";
import type { Metadata } from "next";
import { notFound, permanentRedirect } from "next/navigation";
import { getCourse } from "@/lib/catalog/api";
import { getSeo } from "@/lib/seo/api";
import { notFoundMetadata } from "@/lib/seo/not-found";
import { resolveLocale } from "@/lib/seo/locale";
import { buildBrandedMetadata } from "@/lib/seo/metadata";
import { CourseDetailsClient } from "./course-details-client";

/**
 * A missing course renders the site's 404 page and is marked noindex. The HTTP status stays
 * 200 for a reason that is not this route's doing — see notFoundMetadata.
 */
/**
 * Does this course exist and is it public? Cached per render, so the existence check and the
 * metadata share one request. Only a genuine 404 from the API counts — an outage or a timeout must
 * not be laundered into a permanent-looking 404 that a crawler would act on.
 */
const loadCourse = cache(async (publicId: string) => {
  try {
    return { found: true as const, course: await getCourse(publicId) };
  } catch (error) {
    // Only a 404 from the API means the course is not there. Anything else — the API down, a
    // timeout — must not be laundered into a permanent-looking 404 that a crawler would believe.
    const status = (error as { status?: number } | null)?.status;
    return { found: status !== 404, course: null };
  }
});

type Params = { params: Promise<{ public_id: string }> };

/**
 * Static template metadata is the FALLBACK; a managed SEO override for this course (keyed by its
 * public_id) wins via the shared buildMetadata() helper. Course-specific details still load
 * client-side, so no course API dependency is introduced at build time beyond the optional override.
 */
export async function generateMetadata({ params }: Params): Promise<Metadata> {
  const { public_id } = await params;

  // A course that is not there is marked noindex rather than 404'd — see notFoundMetadata for why
  // the status cannot be set from here. The body still renders the 404 page.
  const { found } = await loadCourse(public_id);
  if (!found) return notFoundMetadata("Course not found");

  const fallback: Metadata = {
    title: "Course details",
    description: "View course details, curriculum, trainers and enrollment options on {brand}.",
  };

  const [seo, locale] = await Promise.all([getSeo("course", public_id), resolveLocale()]);
  return buildBrandedMetadata(seo, fallback, locale);
}

/** Backstop: metadata already 404s a missing course, but a body that renders it anyway would lie. */
export default async function CourseDetailsPage({ params }: Params) {
  const { public_id } = await params;
  const { found, course } = await loadCourse(public_id);
  if (!found) notFound();

  // A renamed course keeps serving its old URLs: the API resolves a retired slug out of
  // slug_history, and this sends the browser and the crawler to the canonical one. Without the
  // redirect the old URL would render fine forever and split the page's search ranking across two
  // addresses; with a 404 instead, every inbound link and share would simply break.
  //
  // permanentRedirect, not redirect: a crawler must record the move rather than re-check it. Guarded
  // on a non-empty slug and a genuine difference, so a UUID-addressed request — the instructor and
  // admin deep links — is never rewritten.
  const canonical = course?.slug;
  if (canonical && canonical !== public_id && !isUuid(public_id)) {
    permanentRedirect(`/courses/${canonical}`);
  }

  return <CourseDetailsClient />;
}

/** The route accepts a public_id OR a slug; only the slug form has a canonical to redirect to. */
function isUuid(value: string): boolean {
  return /^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i.test(value);
}

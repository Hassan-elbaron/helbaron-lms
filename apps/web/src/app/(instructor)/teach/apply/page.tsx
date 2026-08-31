import { brandedMetadata } from "@/lib/branding/metadata";
import type { Metadata } from "next";
import { InstructorApplyPage } from "@/components/marketing/instructor-apply-page";

const TITLE = "Become an instructor — {brand}";
const DESCRIPTION =
  "Apply to teach on {brand}. Share your expertise, build Arabic-first courses, and reach learners across the region. Tell us about yourself and what you'd like to teach.";

export async function generateMetadata(): Promise<Metadata> {
  // `{brand}` resolves here, at request time, from the branding API. As a build-time
  // constant this title/description was frozen into the bundle and identical on every
  // instance — and it is the text search engines index.
  return brandedMetadata({
  title: TITLE,
  description: DESCRIPTION,
  alternates: { canonical: "/teach/apply" },
  openGraph: { title: TITLE, description: DESCRIPTION, url: "/teach/apply", type: "website" },
  twitter: { card: "summary_large_image", title: TITLE, description: DESCRIPTION },
});
}

export default function Page() {
  return <InstructorApplyPage />;
}

import { brandedMetadata } from "@/lib/branding/metadata";
import type { Metadata } from "next";
import { TrainerProfileClient } from "./trainer-profile-client";

export async function generateMetadata(): Promise<Metadata> {
  // `{brand}` resolves here, at request time, from the branding API. As a build-time
  // constant this title/description was frozen into the bundle and identical on every
  // instance — and it is the text search engines index.
  return brandedMetadata({
  title: "Trainer",
  description: "Meet the instructor — background, expertise, and the courses they teach at {brand}.",
});
}

export default async function TrainerProfilePage({ params }: { params: Promise<{ id: string }> }) {
  const { id } = await params;
  return <TrainerProfileClient id={id} />;
}

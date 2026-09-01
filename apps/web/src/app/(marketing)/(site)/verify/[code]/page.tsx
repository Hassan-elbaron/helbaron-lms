import { brandedMetadata } from "@/lib/branding/metadata";
import type { Metadata } from "next";
import { VerifyClient } from "../verify-client";

// Static template metadata; the specific certificate is verified client-side against the
// public verification endpoint. Canonical points at the /verify base.
export async function generateMetadata(): Promise<Metadata> {
  // `{brand}` resolves here, at request time, from the branding API. As a build-time
  // constant this title/description was frozen into the bundle and identical on every
  // instance — and it is the text search engines index.
  return brandedMetadata({
  title: "Verify certificate",
  description: "Confirm the authenticity of a {brand} certificate by its verification code.",
  alternates: { canonical: "/verify" },
});
}

export default function VerifyCodePage() {
  return <VerifyClient />;
}

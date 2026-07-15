import type { Metadata } from "next";
import { VerifyClient } from "../verify-client";

// Static template metadata; the specific certificate is verified client-side against the
// public verification endpoint. Canonical points at the /verify base.
export const metadata: Metadata = {
  title: "Verify certificate",
  description: "Confirm the authenticity of a HElbaron certificate by its verification code.",
  alternates: { canonical: "/verify" },
};

export default function VerifyCodePage() {
  return <VerifyClient />;
}

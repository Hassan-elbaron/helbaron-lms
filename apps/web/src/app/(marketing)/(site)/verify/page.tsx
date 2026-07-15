import type { Metadata } from "next";
import { VerifyClient } from "./verify-client";

export const metadata: Metadata = {
  title: "Verify certificate",
  description: "Confirm the authenticity of a HElbaron certificate by entering its verification code.",
  alternates: { canonical: "/verify" },
  openGraph: {
    title: "Verify certificate",
    description: "Confirm the authenticity of a HElbaron certificate by entering its verification code.",
    url: "/verify",
  },
};

export default function VerifyPage() {
  return <VerifyClient />;
}

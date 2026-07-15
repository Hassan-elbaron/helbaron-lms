import type { Metadata } from "next";
import { ServicePage } from "@/components/marketing/service-page";

export const metadata: Metadata = {
  title: "Enterprise Training",
  description: "Scalable training programs, seat management, and analytics for organizations.",
  alternates: { canonical: "/enterprise" },
  openGraph: { title: "Enterprise Training", description: "Scalable training programs, seat management, and analytics for organizations.", url: "/enterprise" },
};

export default function Page() {
  return <ServicePage pageKey="enterprise" />;
}

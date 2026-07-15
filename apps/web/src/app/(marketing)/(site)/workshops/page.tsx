import type { Metadata } from "next";
import { ServicePage } from "@/components/marketing/service-page";

export const metadata: Metadata = {
  title: "Workshops",
  description: "Hands-on practical workshops to build job-ready skills fast.",
  alternates: { canonical: "/workshops" },
  openGraph: { title: "Workshops", description: "Hands-on practical workshops to build job-ready skills fast.", url: "/workshops" },
};

export default function Page() {
  return <ServicePage pageKey="workshops" />;
}

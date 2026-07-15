import type { Metadata } from "next";
import { ServicePage } from "@/components/marketing/service-page";

export const metadata: Metadata = {
  title: "Advisory Services",
  description: "Expert advisory and consulting to guide your learning and development strategy.",
  alternates: { canonical: "/advisory" },
  openGraph: { title: "Advisory Services", description: "Expert advisory and consulting to guide your learning and development strategy.", url: "/advisory" },
};

export default function Page() {
  return <ServicePage pageKey="advisory" />;
}

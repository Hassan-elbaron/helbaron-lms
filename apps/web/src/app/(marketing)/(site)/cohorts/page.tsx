import type { Metadata } from "next";
import { ServicePage } from "@/components/marketing/service-page";

export const metadata: Metadata = {
  title: "Live Cohorts",
  description: "Cohort-based live courses with expert instructors and a community of peers.",
  alternates: { canonical: "/cohorts" },
  openGraph: { title: "Live Cohorts", description: "Cohort-based live courses with expert instructors and a community of peers.", url: "/cohorts" },
};

export default function Page() {
  return <ServicePage pageKey="cohorts" />;
}

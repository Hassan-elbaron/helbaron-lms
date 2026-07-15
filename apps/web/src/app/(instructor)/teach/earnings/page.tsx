import type { Metadata } from "next";
import { ComingSoon } from "@/components/states/coming-soon";

export const metadata: Metadata = { title: "Earnings" };

export default function Page() {
  return <ComingSoon eyebrow="Instructor" title="Earnings" icon="BarChart3" />;
}

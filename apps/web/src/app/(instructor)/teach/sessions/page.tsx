import type { Metadata } from "next";
import { ComingSoon } from "@/components/states/coming-soon";

export const metadata: Metadata = { title: "Live sessions" };

export default function Page() {
  return <ComingSoon eyebrow="Instructor" title="Live sessions" icon="PlayCircle" />;
}

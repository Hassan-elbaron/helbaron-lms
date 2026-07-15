import type { Metadata } from "next";
import { ComingSoon } from "@/components/states/coming-soon";

export const metadata: Metadata = { title: "Become an instructor" };

export default function Page() {
  return <ComingSoon eyebrow="Instructor" title="Become an instructor" icon="GraduationCap" />;
}

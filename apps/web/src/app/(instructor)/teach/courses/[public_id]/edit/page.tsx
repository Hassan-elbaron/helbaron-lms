import type { Metadata } from "next";
import { ComingSoon } from "@/components/states/coming-soon";

export const metadata: Metadata = { title: "Edit course" };

export default function Page() {
  return <ComingSoon eyebrow="Instructor" title="Edit course" icon="GraduationCap" />;
}

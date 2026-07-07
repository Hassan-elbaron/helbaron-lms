import type { Metadata } from "next";
import { PageHeader } from "@/components/student/page-header";

export const metadata: Metadata = { title: "Teach dashboard" };

export default function InstructorPage() {
  return (
    <div className="space-y-6">
      <PageHeader eyebrow="Instructor" title="Teach dashboard" subtitle="Coming soon." icon="Presentation" />
      <p className="text-sm text-muted-foreground">This area is a placeholder pending the Instructor context build.</p>
    </div>
  );
}
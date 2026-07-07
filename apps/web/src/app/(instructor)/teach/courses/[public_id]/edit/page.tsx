import type { Metadata } from "next";
import { PageHeader } from "@/components/student/page-header";

export const metadata: Metadata = { title: "Edit course" };

export default function InstructorPage() {
  return (
    <div className="space-y-6">
      <PageHeader eyebrow="Instructor" title="Edit course" subtitle="Coming soon." icon="BookOpen" />
      <p className="text-sm text-muted-foreground">This area is a placeholder pending the Instructor context build.</p>
    </div>
  );
}
import type { Metadata } from "next";
import { PageHeader } from "@/components/student/page-header";

export const metadata: Metadata = { title: "Become an instructor" };

export default function InstructorPage() {
  return (
    <div className="space-y-6">
      <PageHeader eyebrow="Instructor" title="Become an instructor" subtitle="Coming soon." icon="UserPlus" />
      <p className="text-sm text-muted-foreground">This area is a placeholder pending the Instructor context build.</p>
    </div>
  );
}
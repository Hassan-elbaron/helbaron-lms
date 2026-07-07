import type { Metadata } from "next";
import { PageHeader } from "@/components/student/page-header";

export const metadata: Metadata = { title: "Live sessions" };

export default function InstructorPage() {
  return (
    <div className="space-y-6">
      <PageHeader eyebrow="Instructor" title="Live sessions" subtitle="Coming soon." icon="CalendarClock" />
      <p className="text-sm text-muted-foreground">This area is a placeholder pending the Instructor context build.</p>
    </div>
  );
}
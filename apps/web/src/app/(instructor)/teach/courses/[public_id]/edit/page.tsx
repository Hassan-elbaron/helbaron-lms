import type { Metadata } from "next";
import { CourseBuilder } from "@/components/authoring/course-builder";

export const metadata: Metadata = { title: "Course builder" };

export default async function Page({ params }: { params: Promise<{ public_id: string }> }) {
  const { public_id } = await params;
  return <CourseBuilder courseId={public_id} />;
}

import { api } from "@/lib/api/client";
import type { ApiSuccess, Paginated } from "@/types/api";

export type CourseStatus = "draft" | "published" | "archived";

export type TeachCourseStats = {
  enrollments: number;
  completions: number;
  avg_progress: number;
  sections: number;
  lessons: number;
};

export type TeachCourse = {
  id: string;
  title: string;
  slug: string;
  subtitle: string | null;
  status: CourseStatus;
  visibility: string;
  is_featured: boolean;
  thumbnail_path: string | null;
  published_at: string | null;
  stats: TeachCourseStats | null;
};

export type RecentEnrollment = {
  course: { id: string; title: string };
  student: { id: string | null; name: string | null };
  status: string;
  progress_percentage: number;
  enrolled_at: string | null;
};

export type TeachDashboard = {
  courses: { total: number; draft: number; published: number; archived: number };
  students: number;
  completions: number;
  recent_enrollments: RecentEnrollment[];
};

export type TeachStudent = {
  enrollment_id: string;
  student: { id: string | null; name: string | null };
  status: string;
  progress_percentage: number;
  enrolled_at: string | null;
  completed_at: string | null;
};

export type TeachAnnouncement = {
  id: string;
  title: string;
  body: string;
  published_at: string | null;
  created_at: string | null;
};

export type AnnouncementInput = { title: string; body: string };

export type ReadinessSeverity = "blocker" | "warning";

/**
 * One publish-readiness finding. `code` is the stable identifier — key UI decisions and deep links
 * off it, never off `title`, which is server-authored prose and may be reworded.
 */
export type ReadinessIssue = {
  code: string;
  severity: ReadinessSeverity;
  title: string;
  explanation: string;
  recommended_action: string;
  entity_type: "course" | "section" | "lesson" | null;
  entity_id: string | null;
};

/**
 * The server's verdict on whether a course may publish.
 *
 * `is_publishable` is read directly and never recomputed from `blockers`: the backend owns that
 * decision and derives its own publish guard from the same evaluation. Deriving it again here
 * would create a second rule set that can drift.
 */
export type ReadinessReport = {
  is_publishable: boolean;
  score: number;
  evaluated_at: string;
  blockers: ReadinessIssue[];
  warnings: ReadinessIssue[];
  passed_checks: string[];
};

// ---- Reads (unwrap `.data`) ----
export const getTeachDashboard = () => api.data<TeachDashboard>("teach/dashboard");

export const getTeachCourses = (status?: CourseStatus) =>
  api.data<TeachCourse[]>(`teach/courses${status ? `?status=${status}` : ""}`);

export const getTeachCourse = (id: string) => api.data<TeachCourse>(`teach/courses/${id}`);

export const getTeachStudents = (id: string, page = 1) =>
  api.get<Paginated<TeachStudent>>(`teach/courses/${id}/students?page=${page}`);

export const getTeachAnnouncements = (id: string) =>
  api.data<TeachAnnouncement[]>(`teach/courses/${id}/announcements`);

export const getCourseReadiness = (id: string) =>
  api.data<ReadinessReport>(`teach/courses/${id}/readiness`);

// ---- Writes ----
export const publishCourse = (id: string) =>
  api.post<ApiSuccess<TeachCourse>>(`teach/courses/${id}/publish`);
export const unpublishCourse = (id: string) =>
  api.post<ApiSuccess<TeachCourse>>(`teach/courses/${id}/unpublish`);
export const archiveCourse = (id: string) =>
  api.post<ApiSuccess<TeachCourse>>(`teach/courses/${id}/archive`);

export const createAnnouncement = (id: string, input: AnnouncementInput) =>
  api.post<ApiSuccess<TeachAnnouncement>>(`teach/courses/${id}/announcements`, input);

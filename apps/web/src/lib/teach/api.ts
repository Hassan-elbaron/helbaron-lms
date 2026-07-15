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

// ---- Reads (unwrap `.data`) ----
export const getTeachDashboard = () => api.data<TeachDashboard>("teach/dashboard");

export const getTeachCourses = (status?: CourseStatus) =>
  api.data<TeachCourse[]>(`teach/courses${status ? `?status=${status}` : ""}`);

export const getTeachCourse = (id: string) => api.data<TeachCourse>(`teach/courses/${id}`);

export const getTeachStudents = (id: string, page = 1) =>
  api.get<Paginated<TeachStudent>>(`teach/courses/${id}/students?page=${page}`);

export const getTeachAnnouncements = (id: string) =>
  api.data<TeachAnnouncement[]>(`teach/courses/${id}/announcements`);

// ---- Writes ----
export const publishCourse = (id: string) =>
  api.post<ApiSuccess<TeachCourse>>(`teach/courses/${id}/publish`);
export const unpublishCourse = (id: string) =>
  api.post<ApiSuccess<TeachCourse>>(`teach/courses/${id}/unpublish`);
export const archiveCourse = (id: string) =>
  api.post<ApiSuccess<TeachCourse>>(`teach/courses/${id}/archive`);

export const createAnnouncement = (id: string, input: AnnouncementInput) =>
  api.post<ApiSuccess<TeachAnnouncement>>(`teach/courses/${id}/announcements`, input);

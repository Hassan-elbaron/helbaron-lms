import { api } from "@/lib/api/client";
import type { ApiSuccess } from "@/types/api";

export type LessonType =
  | "video"
  | "audio"
  | "article"
  | "pdf"
  | "download"
  | "external_link"
  | "quiz_placeholder"
  /** Backed by a real Assessment record; see `LessonPayload.assessment`. */
  | "quiz";
export type ProgressStatus = "not_started" | "in_progress" | "completed";

export type LearnLesson = {
  id: string;
  title: string;
  type: LessonType;
  is_preview: boolean;
  has_media: boolean | null;
  completed: boolean;
  locked: boolean;
};
export type LearnSection = { id: string; title: string; lessons: LearnLesson[] };
export type LearnCourse = {
  course: { id: string; title: string; slug: string };
  enrollment: { id: string; status: string; progress_percentage: number };
  sections: LearnSection[];
};

/** Media is ONLY ever a signed, expiring playback object — never a raw s3_key/mux_asset_id. */
export type Playback = { url: string; kind: string; expires_at: string };
export type LessonPayload = {
  id: string;
  title: string;
  type: LessonType;
  content: Record<string, unknown> | null;
  is_preview: boolean;
  playback: Playback | null;
  progress: { status: ProgressStatus; position_seconds: number | null };
  bookmarked: boolean;
  note: string | null;
  navigation: { previous: string | null; next: string | null };
  /**
   * Present only for `quiz` lessons whose assessment is PUBLISHED. Null covers both "no assessment
   * attached" and "attached but still a draft" — the backend publish-gates it deliberately, so the
   * client cannot tell the difference and has no draft id to act on.
   *
   * Carries no answer key, no grading configuration and no instructor settings: the server sends
   * exactly these four fields.
   */
  assessment: { id: string; title: string; question_count: number; version: number } | null;
};

export const getLearnCourse = (courseId: string) => api.data<LearnCourse>(`courses/${courseId}/learn`);
export const getLesson = (lessonId: string) => api.data<LessonPayload>(`lessons/${lessonId}`);

export const recordProgress = (lessonId: string, body: { status: ProgressStatus; position_seconds?: number }) =>
  api.post<ApiSuccess<{ status: ProgressStatus; position_seconds: number | null; course_progress_percentage: number }>>(
    `lessons/${lessonId}/progress`,
    body,
  );

export const toggleBookmark = (lessonId: string) =>
  api.post<ApiSuccess<{ bookmarked: boolean }>>(`lessons/${lessonId}/bookmark`);

export const upsertNote = (lessonId: string, body: string) =>
  api.post<ApiSuccess<{ id: string; body: string }>>(`lessons/${lessonId}/notes`, { body });

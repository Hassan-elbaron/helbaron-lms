"use client";

import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import {
  getLearnCourse,
  getLesson,
  recordProgress,
  toggleBookmark,
  upsertNote,
  type ProgressStatus,
} from "./api";

export const useLearnCourse = (courseId: string) =>
  useQuery({ queryKey: ["learn-course", courseId], queryFn: () => getLearnCourse(courseId), enabled: Boolean(courseId) });

export const useLesson = (lessonId: string) =>
  useQuery({ queryKey: ["lesson", lessonId], queryFn: () => getLesson(lessonId), enabled: Boolean(lessonId) });

export function useRecordProgress(lessonId: string) {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (body: { status: ProgressStatus; position_seconds?: number }) => recordProgress(lessonId, body),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ["lesson", lessonId] });
      qc.invalidateQueries({ queryKey: ["learn-course"] });
      qc.invalidateQueries({ queryKey: ["continue-learning"] });
      qc.invalidateQueries({ queryKey: ["my-learning"] });
    },
  });
}

export function useToggleBookmark(lessonId: string) {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: () => toggleBookmark(lessonId),
    onSuccess: () => qc.invalidateQueries({ queryKey: ["lesson", lessonId] }),
  });
}

export function useUpsertNote(lessonId: string) {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (body: string) => upsertNote(lessonId, body),
    onSuccess: () => qc.invalidateQueries({ queryKey: ["lesson", lessonId] }),
  });
}

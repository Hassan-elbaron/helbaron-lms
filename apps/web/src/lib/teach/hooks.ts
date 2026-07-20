"use client";

import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import {
  archiveCourse,
  createAnnouncement,
  getCourseReadiness,
  getTeachAnnouncements,
  getTeachCourse,
  getTeachCourses,
  getTeachDashboard,
  getTeachStudents,
  publishCourse,
  unpublishCourse,
  type AnnouncementInput,
  type CourseStatus,
} from "./api";

export const useTeachDashboard = () =>
  useQuery({ queryKey: ["teach", "dashboard"], queryFn: getTeachDashboard });

export const useTeachCourses = (status?: CourseStatus) =>
  useQuery({ queryKey: ["teach", "courses", status ?? "all"], queryFn: () => getTeachCourses(status) });

export const useTeachCourse = (id: string) =>
  useQuery({ queryKey: ["teach", "course", id], queryFn: () => getTeachCourse(id), enabled: !!id });

export const useTeachStudents = (id: string, page: number) =>
  useQuery({
    queryKey: ["teach", "students", id, page],
    queryFn: () => getTeachStudents(id, page),
    enabled: !!id,
  });

export const useTeachAnnouncements = (id: string) =>
  useQuery({
    queryKey: ["teach", "announcements", id],
    queryFn: () => getTeachAnnouncements(id),
    enabled: !!id,
  });

/**
 * Publish readiness. Not cached beyond the session: an author edits the curriculum and comes
 * straight back to this panel, so a stale report would tell them their fix did not work.
 */
export const useCourseReadiness = (id: string, enabled = true) =>
  useQuery({
    queryKey: ["teach", "readiness", id],
    queryFn: () => getCourseReadiness(id),
    enabled: !!id && enabled,
    staleTime: 0,
  });

/** Invalidate the course lists + a single course view after a lifecycle change. */
function useLifecycleMutation(fn: (id: string) => Promise<unknown>) {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (id: string) => fn(id),
    onSuccess: (_data, id) => {
      qc.invalidateQueries({ queryKey: ["teach", "courses"] });
      qc.invalidateQueries({ queryKey: ["teach", "course", id] });
      qc.invalidateQueries({ queryKey: ["teach", "dashboard"] });
      // Publishing does not change readiness, but unpublishing and archiving can change what the
      // panel should offer next — and a refetch after any lifecycle change is cheap insurance
      // against showing an author a verdict that no longer applies.
      qc.invalidateQueries({ queryKey: ["teach", "readiness", id] });
    },
  });
}

export const usePublishCourse = () => useLifecycleMutation(publishCourse);
export const useUnpublishCourse = () => useLifecycleMutation(unpublishCourse);
export const useArchiveCourse = () => useLifecycleMutation(archiveCourse);

export function useCreateAnnouncement(id: string) {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (input: AnnouncementInput) => createAnnouncement(id, input),
    onSuccess: () => qc.invalidateQueries({ queryKey: ["teach", "announcements", id] }),
  });
}

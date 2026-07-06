"use client";

import { useMutation, useQuery } from "@tanstack/react-query";
import {
  enrollInCourse,
  getCategories,
  getCourse,
  getCourses,
  getTrainers,
  type CourseFilters,
} from "./api";

export const useCourses = (filters: CourseFilters) =>
  useQuery({ queryKey: ["courses", filters], queryFn: () => getCourses(filters) });
export const useFeaturedCourses = () =>
  useQuery({ queryKey: ["courses", "featured"], queryFn: () => getCourses({ featured: true, per_page: 6 }) });
export const useCourse = (publicId: string) =>
  useQuery({ queryKey: ["course", publicId], queryFn: () => getCourse(publicId), enabled: Boolean(publicId) });
export const useCategories = () => useQuery({ queryKey: ["categories"], queryFn: getCategories });
export const useTrainers = () => useQuery({ queryKey: ["trainers"], queryFn: getTrainers });
export const useEnroll = () => useMutation({ mutationFn: (id: string) => enrollInCourse(id) });

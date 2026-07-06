import { api } from "@/lib/api/client";
import type { Paginated } from "@/types/api";

export type CourseListItem = {
  id: string;
  title: string;
  slug: string;
  subtitle: string | null;
  thumbnail_path: string | null;
  is_featured: boolean;
  level?: string | null;
  language?: string | null;
  published_at: string | null;
};

export type Trainer = { id: string; name: string; headline?: string | null; avatar_path?: string | null };
export type Tag = { id: string; name: string; slug: string };
export type Category = {
  id: string;
  name: string;
  slug: string;
  description?: string | null;
  position?: number;
  children?: Category[];
};

export type CourseDetail = {
  id: string;
  title: string;
  slug: string;
  subtitle: string | null;
  description: string | null;
  status: string;
  visibility: string;
  is_featured: boolean;
  thumbnail_path: string | null;
  seo?: Record<string, unknown> | null;
  level: { id: string; name: string } | null;
  language: { id: string; name: string; code?: string } | null;
  categories: Category[];
  tags: Tag[];
  trainers: Trainer[];
  related: CourseListItem[];
  published_at: string | null;
};

export type CourseFilters = {
  q?: string;
  category?: string;
  featured?: boolean;
  page?: number;
  per_page?: number;
};

function toQuery(filters: CourseFilters): string {
  const p = new URLSearchParams();
  if (filters.q) p.set("q", filters.q);
  if (filters.category) p.set("category", filters.category);
  if (filters.featured) p.set("featured", "1");
  if (filters.page) p.set("page", String(filters.page));
  if (filters.per_page) p.set("per_page", String(filters.per_page));
  const s = p.toString();
  return s ? `?${s}` : "";
}

export const getCourses = (filters: CourseFilters = {}) =>
  api.get<Paginated<CourseListItem>>(`courses${toQuery(filters)}`, { auth: false });
export const getCourse = (publicId: string) =>
  api.data<CourseDetail>(`courses/${publicId}`, { auth: false });
export const getCategories = () => api.data<Category[]>("categories", { auth: false });
export const getTrainers = () => api.data<Trainer[]>("trainers", { auth: false });

/** Free-course enrollment (real Learning endpoint). Paid courses require checkout (out of scope). */
export const enrollInCourse = (publicId: string) => api.post(`courses/${publicId}/enroll`);

/** Flatten a category tree into {id, name, depth} for filter dropdowns. */
export function flattenCategories(tree: Category[], depth = 0): { id: string; name: string; depth: number }[] {
  return tree.flatMap((c) => [
    { id: c.id, name: c.name, depth },
    ...flattenCategories(c.children ?? [], depth + 1),
  ]);
}

import { api } from "@/lib/api/client";
import type { ApiSuccess, Paginated } from "@/types/api";

export type LeadStatus = "new" | "working" | "qualified" | "converted" | "lost";

export type Lead = {
  id: string; // public_id
  name: string;
  email: string | null;
  phone: string | null;
  source: string | null;
  status: LeadStatus;
  stage?: string | null;
  value_minor: number | null;
  currency: string | null;
  created_at: string | null;
};

export type LeadFilters = { q?: string; status?: LeadStatus | ""; page?: number; per_page?: number };

export type CreateLeadInput = {
  name: string;
  email?: string;
  phone?: string;
  source?: string;
  value_minor?: number;
  currency?: string;
};

/** GET /leads — paginated; supports q + status filters. */
export const getLeads = (filters: LeadFilters = {}) => {
  const params = new URLSearchParams();
  if (filters.q) params.set("q", filters.q);
  if (filters.status) params.set("status", filters.status);
  params.set("page", String(filters.page ?? 1));
  params.set("per_page", String(filters.per_page ?? 15));
  return api.get<Paginated<Lead>>(`leads?${params.toString()}`);
};

/** POST /leads — create a lead. */
export const createLead = (body: CreateLeadInput) => api.post<ApiSuccess<Lead>>("leads", body);

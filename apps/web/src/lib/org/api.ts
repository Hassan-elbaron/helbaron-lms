import { api } from "@/lib/api/client";
import type { ApiSuccess, Paginated } from "@/types/api";

export type MemberRole = "owner" | "admin" | "manager" | "member";
export type MemberStatus = "invited" | "active" | "removed";

export type OrganizationMember = {
  id: string;
  email: string;
  role: MemberRole;
  status: MemberStatus;
  invited_at: string | null;
};

export type Organization = {
  id: string; // public_id
  name: string;
  slug: string;
  status: string;
  size: string | null;
  website: string | null;
  members_count?: number;
  members?: OrganizationMember[];
};

export type ConsultingStatus = "new" | "triaged" | "in_progress" | "resolved" | "closed";
export type ConsultingRequest = {
  id: string;
  subject: string;
  description: string | null;
  status: ConsultingStatus;
  sla_due_at: string | null;
  created_at: string | null;
};

/** GET /organizations — paginated (members_count included). */
export const getOrganizations = (page = 1) =>
  api.get<Paginated<Organization>>(`organizations?page=${page}`);

/** GET /organizations/{organization} — profile + members. */
export const getOrganization = (id: string) => api.data<Organization>(`organizations/${id}`);

/** POST /organizations/{organization}/members — invite a member. */
export const inviteMember = (id: string, body: { email: string; role?: MemberRole }) =>
  api.post<ApiSuccess<OrganizationMember>>(`organizations/${id}/members`, body);

/** GET /consulting — the current user's consulting requests (collection, not paginated). */
export const getConsulting = () => api.data<ConsultingRequest[]>("consulting");

/** POST /consulting/request — submit a consulting request. */
export const requestConsulting = (body: { subject: string; description?: string; organization?: string }) =>
  api.post<ApiSuccess<ConsultingRequest>>("consulting/request", body);

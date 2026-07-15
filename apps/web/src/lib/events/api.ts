import { api } from "@/lib/api/client";
import type { Paginated } from "@/types/api";

/**
 * Public "Events" API — a presentation surface over the backend Live domain (/api/v1/events).
 * Read endpoints are public ({ auth: false }); registration reuses the authenticated Live action.
 * The raw meeting join URL is never part of these payloads.
 */

export type EventStatus = "scheduled" | "live" | "completed" | "cancelled";

export type EventSpeaker = { name: string; headline?: string | null; avatar_path?: string | null };

export type EventListItem = {
  id: string;
  title: string;
  description: string | null;
  status: EventStatus;
  timezone: string;
  starts_at: string | null;
  ends_at: string | null;
  capacity: number | null;
  registered_count: number;
  speakers: { name: string }[];
};

export type EventAgendaItem = {
  title: string;
  starts_at: string | null;
  ends_at: string | null;
  summary: string | null;
};

export type EventRelatedCourse = { title: string; public_id: string };

export type EventSeo = {
  name: string;
  startDate: string | null;
  endDate: string | null;
  eventAttendanceMode: string;
  eventStatus: string;
  location: string;
};

export type EventDetail = {
  id: string;
  title: string;
  description: string | null;
  status: EventStatus;
  timezone: string;
  starts_at: string | null;
  ends_at: string | null;
  starts_at_local: string | null;
  capacity: number | null;
  registered_count: number;
  waitlist_count: number;
  is_full: boolean;
  agenda: EventAgendaItem[];
  speakers: EventSpeaker[];
  related_course: EventRelatedCourse | null;
  seo: EventSeo;
};

export type RegistrationResult = {
  id: string;
  status: "registered" | "waitlisted" | "cancelled";
  registered_at: string | null;
};

export type EventFilter = "upcoming" | "past";

export type EventsQuery = { filter?: EventFilter; q?: string; page?: number; per_page?: number };

function toQuery(params: EventsQuery): string {
  const p = new URLSearchParams();
  p.set("filter", params.filter ?? "upcoming");
  if (params.q) p.set("q", params.q);
  if (params.page) p.set("page", String(params.page));
  if (params.per_page) p.set("per_page", String(params.per_page));
  return `?${p.toString()}`;
}

/** Paginated list of public events. Returns the full envelope ({ data, meta, links }). */
export const getEvents = (params: EventsQuery = {}) =>
  api.get<Paginated<EventListItem>>(`events${toQuery(params)}`, { auth: false });

/** Public event detail by public id (unwrapped). */
export const getEvent = (publicId: string) => api.data<EventDetail>(`events/${publicId}`, { auth: false });

/** Register the authenticated user (delegates server-side to the existing Live action). */
export const registerForEvent = (publicId: string) =>
  api.post<{ data: RegistrationResult }>(`events/${publicId}/register`).then((r) => r.data);

/** Cancel the authenticated user's registration. */
export const cancelEventRegistration = (publicId: string) => api.del(`events/${publicId}/register`);

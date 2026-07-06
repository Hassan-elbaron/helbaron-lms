"use client";

import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import {
  getConsulting,
  getOrganization,
  getOrganizations,
  inviteMember,
  requestConsulting,
  type MemberRole,
} from "./api";

export const useOrganizations = (page: number) =>
  useQuery({ queryKey: ["organizations", page], queryFn: () => getOrganizations(page) });

export const useOrganization = (id: string) =>
  useQuery({ queryKey: ["organization", id], queryFn: () => getOrganization(id), enabled: Boolean(id) });

export const useConsulting = () =>
  useQuery({ queryKey: ["consulting"], queryFn: getConsulting });

export function useInviteMember(id: string) {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (body: { email: string; role?: MemberRole }) => inviteMember(id, body),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ["organization", id] });
      qc.invalidateQueries({ queryKey: ["organizations"] });
    },
  });
}

export function useRequestConsulting() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (body: { subject: string; description?: string; organization?: string }) => requestConsulting(body),
    onSuccess: () => qc.invalidateQueries({ queryKey: ["consulting"] }),
  });
}

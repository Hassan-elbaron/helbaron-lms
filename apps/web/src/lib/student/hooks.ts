"use client";

import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import {
  getContinueLearning,
  getMyCertificates,
  getMyLearning,
  getNotifications,
  getProfile,
  markNotificationRead,
  requestCertificateDownload,
  requestCertificateShare,
  updatePreferences,
  updateProfile,
  type PreferencesUpdate,
  type ProfileUpdate,
} from "./api";

export const useMyLearning = () => useQuery({ queryKey: ["my-learning"], queryFn: getMyLearning });
export const useContinueLearning = () =>
  useQuery({ queryKey: ["continue-learning"], queryFn: getContinueLearning });
export const useMyCertificates = () => useQuery({ queryKey: ["my-certificates"], queryFn: getMyCertificates });
export const useProfile = () => useQuery({ queryKey: ["profile"], queryFn: getProfile });
export const useNotifications = (page: number) =>
  useQuery({ queryKey: ["notifications", page], queryFn: () => getNotifications(page) });

export function useUpdateProfile() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (input: ProfileUpdate) => updateProfile(input),
    onSuccess: () => qc.invalidateQueries({ queryKey: ["profile"] }),
  });
}

export function useMarkNotificationRead() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (id: string) => markNotificationRead(id),
    onSuccess: () => qc.invalidateQueries({ queryKey: ["notifications"] }),
  });
}

export function useUpdatePreferences() {
  return useMutation({ mutationFn: (input: PreferencesUpdate) => updatePreferences(input) });
}

export function useCertificateDownload() {
  return useMutation({ mutationFn: (id: string) => requestCertificateDownload(id) });
}

export function useCertificateShare() {
  return useMutation({ mutationFn: (id: string) => requestCertificateShare(id) });
}

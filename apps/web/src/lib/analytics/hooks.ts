"use client";

import { useMutation, useQuery } from "@tanstack/react-query";
import {
  createExport,
  getDashboards,
  getExport,
  getKpis,
  getReport,
  getReports,
  runReport,
  type ExportFormat,
} from "./api";

export const useKpis = (metrics: string[], from?: string, to?: string) =>
  useQuery({ queryKey: ["analytics", "kpis", metrics, from, to], queryFn: () => getKpis(metrics, from, to) });

export const useReports = () => useQuery({ queryKey: ["reports"], queryFn: getReports });
export const useReport = (id: string) =>
  useQuery({ queryKey: ["report", id], queryFn: () => getReport(id), enabled: Boolean(id) });
export const useDashboards = () => useQuery({ queryKey: ["dashboards"], queryFn: getDashboards });

export function useRunReport() {
  return useMutation({ mutationFn: (body: { report: string; from?: string; to?: string }) => runReport(body) });
}

export function useCreateExport() {
  return useMutation({
    mutationFn: (body: { report: string; format: ExportFormat; from?: string; to?: string }) => createExport(body),
  });
}

/** Polls an export job until it is completed/failed, then surfaces the signed download_url. */
export const useExportStatus = (id: string | null) =>
  useQuery({
    queryKey: ["export", id],
    queryFn: () => getExport(id as string),
    enabled: Boolean(id),
    refetchInterval: (query) => {
      const status = query.state.data?.export.status;
      return status === "completed" || status === "failed" ? false : 1500;
    },
  });

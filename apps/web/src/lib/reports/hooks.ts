"use client";

import { useQuery } from "@tanstack/react-query";
import { getReportCatalog, getReportInsight, type ReportParams } from "./api";

export const useReportCatalog = () =>
  useQuery({ queryKey: ["reports", "catalog"], queryFn: getReportCatalog });

export const useReportInsight = (key: string, params: ReportParams) =>
  useQuery({
    queryKey: ["reports", "insight", key, params],
    queryFn: () => getReportInsight(key, params),
    enabled: Boolean(key),
  });

import { api } from "@/lib/api/client";
import type { ApiSuccess } from "@/types/api";

export type MetricUnit = "count" | "currency_minor" | string;

export type Kpi = {
  metric: string;
  unit: MetricUnit;
  total: number;
  series: { period: string; value: number }[];
};
export type KpiResponse = { from: string; to: string; kpis: Kpi[] };

export type ReportType = "metric" | "funnel" | "cohort" | "table";
export type ReportDefinition = {
  id: string;
  name: string;
  type: ReportType;
  metric_keys: string[] | null;
  visibility: "private" | "shared";
};

export type ReportRunResult = {
  type: string;
  from?: string;
  to?: string;
  rows?: Record<string, unknown>[];
  steps?: Record<string, unknown>[];
  [k: string]: unknown;
};
export type ReportRun = { run_id: string; ran_at: string | null; result: ReportRunResult };

export type DashboardWidget = {
  id: string;
  title: string;
  metric_key: string | null;
  type: string;
  config: Record<string, unknown> | null;
};
export type Dashboard = {
  id: string;
  key: string;
  name: string;
  is_default: boolean;
  widgets?: DashboardWidget[];
};

export type ExportFormat = "csv" | "xlsx";
export type ExportStatus = "queued" | "processing" | "completed" | "failed";
export type ExportJob = {
  id: string;
  format: ExportFormat;
  status: ExportStatus;
  row_count: number | null;
  completed_at: string | null;
};
export type ExportEnvelope = { export: ExportJob; download_url: string | null };

/** GET /analytics/kpis?metrics[]=…&from=&to= — requires at least one metric. */
export const getKpis = (metrics: string[], from?: string, to?: string) => {
  const params = new URLSearchParams();
  metrics.forEach((m) => params.append("metrics[]", m));
  if (from) params.set("from", from);
  if (to) params.set("to", to);
  return api.data<KpiResponse>(`analytics/kpis?${params.toString()}`);
};

/** GET /reports — collection of report definitions. */
export const getReports = () => api.data<ReportDefinition[]>("reports");
/** GET /reports/{report}. */
export const getReport = (id: string) => api.data<ReportDefinition>(`reports/${id}`);
/** POST /reports/run — run a report definition, returns the run + result. */
export const runReport = (body: { report: string; from?: string; to?: string }) =>
  api.post<ApiSuccess<ReportRun>>("reports/run", body);

/** GET /dashboards — collection with widgets. */
export const getDashboards = () => api.data<Dashboard[]>("dashboards");

/** POST /analytics/exports — queue an async export job. */
export const createExport = (body: { report: string; format: ExportFormat; from?: string; to?: string }) =>
  api.post<ApiSuccess<ExportJob>>("analytics/exports", body);
/** GET /analytics/exports/{export} — job status + signed download_url when completed. */
export const getExport = (id: string) => api.data<ExportEnvelope>(`analytics/exports/${id}`);

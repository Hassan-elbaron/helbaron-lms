"use client";

import { use, useState } from "react";
import Link from "next/link";
import { ArrowLeft, Play, Download } from "lucide-react";
import { errorMessage } from "@/lib/api/errors";
import { useI18n } from "@/lib/i18n/i18n-context";
import { useCreateExport, useExportStatus, useReport, useRunReport } from "@/lib/analytics/hooks";
import type { ExportFormat, ReportRun } from "@/lib/analytics/api";
import { PageHeader } from "@/components/student/page-header";
import { QueryState } from "@/components/student/query-state";
import { ResultTable } from "@/components/analytics/result-table";
import { SectionCard } from "@/components/org/section-card";
import { FormAlert } from "@/components/auth/form-alert";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";

function ExportControls({ reportId }: { reportId: string }) {
  const { t } = useI18n();
  const create = useCreateExport();
  const [jobId, setJobId] = useState<string | null>(null);
  const status = useExportStatus(jobId);

  const start = (format: ExportFormat) => {
    setJobId(null);
    create.mutate(
      { report: reportId, format },
      { onSuccess: (res) => setJobId(res.data.id) },
    );
  };

  const job = status.data?.export;
  const url = status.data?.download_url;
  const busy = create.isPending || (jobId != null && job?.status !== "completed" && job?.status !== "failed");

  return (
    <div className="space-y-2">
      <div className="flex flex-wrap gap-2">
        <Button size="sm" variant="outline" disabled={busy} onClick={() => start("csv")}>
          <Download className="size-4" aria-hidden /> {t("analytics.reports.exportCsv")}
        </Button>
        <Button size="sm" variant="outline" disabled={busy} onClick={() => start("xlsx")}>
          <Download className="size-4" aria-hidden /> {t("analytics.reports.exportXlsx")}
        </Button>
      </div>
      {busy ? <p className="text-xs text-muted-foreground">{t("analytics.reports.exporting")}</p> : null}
      {job?.status === "completed" && url ? (
        <Button asChild size="sm">
          <a href={url}>{t("analytics.reports.download")}</a>
        </Button>
      ) : null}
      {job?.status === "failed" ? <FormAlert>{t("analytics.reports.exportFailed")}</FormAlert> : null}
    </div>
  );
}

export default function ReportDetailsPage({ params }: { params: Promise<{ public_id: string }> }) {
  const { public_id } = use(params);
  const { t } = useI18n();
  const query = useReport(public_id);
  const run = useRunReport();
  const [result, setResult] = useState<ReportRun | null>(null);
  const [runError, setRunError] = useState<string | null>(null);

  const doRun = () => {
    setRunError(null);
    run.mutate(
      { report: public_id },
      {
        onSuccess: (res) => setResult(res.data),
        onError: (err) => setRunError(errorMessage(err, t("analytics.error"))),
      },
    );
  };

  const rows = result?.result?.rows ?? result?.result?.steps ?? [];

  return (
    <div className="space-y-6">
      <Button asChild variant="ghost" size="sm" className="w-fit">
        <Link href="/reports">
          <ArrowLeft className="size-4" aria-hidden /> {t("analytics.reports.back")}
        </Link>
      </Button>

      <QueryState query={query}>
        {(report) => (
          <div className="space-y-6">
            <PageHeader
              title={report.name}
              action={<Badge variant="outline">{t(`analytics.reportType.${report.type}`)}</Badge>}
            />

            <div className="grid gap-6 lg:grid-cols-3">
              <div className="space-y-6 lg:col-span-2">
                <SectionCard
                  title={t("analytics.reports.result")}
                  action={
                    <Button size="sm" disabled={run.isPending} onClick={doRun}>
                      <Play className="size-4" aria-hidden />
                      {run.isPending ? t("analytics.reports.running") : t("analytics.reports.run")}
                    </Button>
                  }
                >
                  {runError ? <FormAlert>{runError}</FormAlert> : null}
                  {result ? (
                    <div className="space-y-3">
                      {result.ran_at ? (
                        <p className="text-xs text-muted-foreground">
                          {t("analytics.reports.ranAt")}: {new Date(result.ran_at).toLocaleString()}
                        </p>
                      ) : null}
                      <ResultTable rows={rows as Record<string, unknown>[]} />
                    </div>
                  ) : (
                    <p className="text-sm text-muted-foreground">{t("analytics.reports.runFirst")}</p>
                  )}
                </SectionCard>
              </div>

              <div className="space-y-6 lg:col-span-1">
                <SectionCard title={t("analytics.reports.type")}>
                  <dl className="space-y-3">
                    <div>
                      <dt className="text-xs text-muted-foreground">{t("analytics.reports.type")}</dt>
                      <dd className="text-sm font-medium">{t(`analytics.reportType.${report.type}`)}</dd>
                    </div>
                    <div>
                      <dt className="text-xs text-muted-foreground">{t("analytics.reports.visibility")}</dt>
                      <dd className="text-sm font-medium">{report.visibility}</dd>
                    </div>
                    <div>
                      <dt className="text-xs text-muted-foreground">{t("analytics.reports.metrics")}</dt>
                      <dd className="text-sm font-medium">{(report.metric_keys ?? []).join(", ") || "—"}</dd>
                    </div>
                  </dl>
                </SectionCard>

                <SectionCard title={t("analytics.reports.export")}>
                  <ExportControls reportId={public_id} />
                </SectionCard>
              </div>
            </div>
          </div>
        )}
      </QueryState>
    </div>
  );
}

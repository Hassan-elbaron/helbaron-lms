"use client";

import { use, useState } from "react";
import Link from "next/link";
import { ArrowLeft } from "lucide-react";
import { useI18n } from "@/lib/i18n/i18n-context";
import { useReportInsight } from "@/lib/reports/hooks";
import { PageHeader } from "@/components/student/page-header";
import { QueryState } from "@/components/student/query-state";
import { EmptyState } from "@/components/states/empty-state";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { ReportView } from "@/components/reports/report-view";

/**
 * A single operational report: a from/to filter, then the shape-driven ReportView (KPIs, trend
 * charts, tables, funnel, pagination). Data is fetched real-time from the reports insights API.
 */
export default function ReportInsightPage({ params }: { params: Promise<{ report: string }> }) {
  const { report } = use(params);
  const { t } = useI18n();

  const [from, setFrom] = useState("");
  const [to, setTo] = useState("");
  const [applied, setApplied] = useState<{ from?: string; to?: string }>({});
  const [page, setPage] = useState(1);

  const query = useReportInsight(report, { from: applied.from, to: applied.to, page, perPage: 20 });

  return (
    <div className="space-y-6">
      <PageHeader
        eyebrow="REPORTS"
        icon="BarChart3"
        title={t(`reports.names.${report}`)}
        action={
          <Button asChild variant="outline" size="sm">
            <Link href="/reports/insights">
              <ArrowLeft className="me-1 size-4" aria-hidden />
              {t("reports.back")}
            </Link>
          </Button>
        }
      />

      <form
        className="flex flex-wrap items-end gap-3 rounded-lg border bg-card p-4"
        onSubmit={(e) => {
          e.preventDefault();
          setPage(1);
          setApplied({ from: from || undefined, to: to || undefined });
        }}
        aria-label={t("reports.range")}
      >
        <label className="flex flex-col gap-1 text-sm">
          {t("reports.from")}
          <Input type="date" value={from} onChange={(e) => setFrom(e.target.value)} className="w-auto" />
        </label>
        <label className="flex flex-col gap-1 text-sm">
          {t("reports.to")}
          <Input type="date" value={to} onChange={(e) => setTo(e.target.value)} className="w-auto" />
        </label>
        <Button type="submit" size="sm">
          {t("reports.apply")}
        </Button>
      </form>

      <QueryState
        query={query}
        isEmpty={(d) => !d.data || Object.keys(d.data).length === 0}
        empty={<EmptyState title={t("reports.noData")} />}
      >
        {(res) => <ReportView payload={res.data} meta={res.meta} page={page} onPageChange={setPage} />}
      </QueryState>
    </div>
  );
}

"use client";

import { useState } from "react";
import Link from "next/link";
import { FileText } from "lucide-react";
import { useI18n } from "@/lib/i18n/i18n-context";
import { useReports } from "@/lib/analytics/hooks";
import type { ReportType } from "@/lib/analytics/api";
import { PageHeader } from "@/components/student/page-header";
import { QueryState } from "@/components/student/query-state";
import { EmptyState } from "@/components/states/empty-state";
import { Card, CardContent } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";

const TYPES: ReportType[] = ["metric", "funnel", "cohort", "table"];
const controlClass =
  "flex h-10 w-full max-w-xs rounded-md border border-input bg-background px-3 py-2 text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2";

export default function ReportsPage() {
  const { t } = useI18n();
  const [type, setType] = useState<ReportType | "">("");
  const query = useReports();

  return (
    <div className="space-y-6">
      <PageHeader eyebrow="REPORTS" icon="FileText" title={t("analytics.reports.title")} subtitle={t("analytics.reports.subtitle")} />

      <div className="rounded-lg border bg-card p-4">
        <select
          className={controlClass}
          value={type}
          onChange={(e) => setType(e.target.value as ReportType | "")}
          aria-label={t("analytics.reports.allTypes")}
        >
          <option value="">{t("analytics.reports.allTypes")}</option>
          {TYPES.map((tp) => (
            <option key={tp} value={tp}>
              {t(`analytics.reportType.${tp}`)}
            </option>
          ))}
        </select>
      </div>

      <QueryState
        query={query}
        isEmpty={(d) => d.length === 0}
        empty={<EmptyState icon={<FileText className="size-8" />} title={t("analytics.reports.empty")} />}
      >
        {(reports) => {
          const filtered = type ? reports.filter((r) => r.type === type) : reports;
          if (filtered.length === 0) {
            return <EmptyState icon={<FileText className="size-8" />} title={t("analytics.reports.empty")} />;
          }
          return (
            <div className="stagger-in grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
              {filtered.map((r) => (
                <Card key={r.id}>
                  <CardContent className="space-y-3 p-5">
                    <div className="flex items-start justify-between gap-2">
                      <p className="truncate font-semibold">{r.name}</p>
                      <Badge variant="outline">{t(`analytics.reportType.${r.type}`)}</Badge>
                    </div>
                    <p className="text-xs text-muted-foreground">
                      {t("analytics.reports.metrics")}: {(r.metric_keys ?? []).join(", ") || "—"}
                    </p>
                    <Button asChild size="sm" variant="outline" className="w-full">
                      <Link href={`/reports/${r.id}`}>{t("analytics.reports.open")}</Link>
                    </Button>
                  </CardContent>
                </Card>
              ))}
            </div>
          );
        }}
      </QueryState>
    </div>
  );
}

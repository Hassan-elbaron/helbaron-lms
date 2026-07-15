"use client";

import Link from "next/link";
import { BarChart3 } from "lucide-react";
import { useI18n } from "@/lib/i18n/i18n-context";
import { useReportCatalog } from "@/lib/reports/hooks";
import { PageHeader } from "@/components/student/page-header";
import { QueryState } from "@/components/student/query-state";
import { EmptyState } from "@/components/states/empty-state";
import { Card, CardContent } from "@/components/ui/card";
import { Button } from "@/components/ui/button";

/**
 * Report Insights hub — a catalog of the operational reports. Admin gating + shell are inherited
 * from the (analytics) group layout (roles admin / super_admin).
 */
export default function ReportInsightsHub() {
  const { t } = useI18n();
  const query = useReportCatalog();

  return (
    <div className="space-y-6">
      <PageHeader
        eyebrow="REPORTS"
        icon="BarChart3"
        title={t("reports.hubTitle")}
        subtitle={t("reports.hubSubtitle")}
      />

      <QueryState
        query={query}
        isEmpty={(d) => d.length === 0}
        empty={<EmptyState icon={<BarChart3 className="size-8" />} title={t("reports.catalogEmpty")} />}
      >
        {(items) => (
          <div className="stagger-in grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
            {items.map((r) => (
              <Card key={r.key} className="card-hover">
                <CardContent className="flex h-full flex-col gap-3 p-5">
                  <p className="font-serif text-lg">{t(`reports.names.${r.key}`)}</p>
                  <p className="min-h-10 flex-1 text-xs text-muted-foreground">{r.description}</p>
                  <Button asChild size="sm" variant="outline" className="w-full">
                    <Link href={`/reports/insights/${r.key}`}>{t("reports.open")}</Link>
                  </Button>
                </CardContent>
              </Card>
            ))}
          </div>
        )}
      </QueryState>
    </div>
  );
}

"use client";

import { LayoutGrid } from "lucide-react";
import { useI18n } from "@/lib/i18n/i18n-context";
import { useDashboards } from "@/lib/analytics/hooks";
import { PageHeader } from "@/components/student/page-header";
import { QueryState } from "@/components/student/query-state";
import { EmptyState } from "@/components/states/empty-state";
import { WidgetPreview } from "@/components/analytics/widget-preview";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";

export default function DashboardsPage() {
  const { t } = useI18n();
  const query = useDashboards();

  return (
    <div className="space-y-6">
      <PageHeader eyebrow="DASHBOARDS" icon="LayoutGrid" title={t("analytics.dashboards.title")} subtitle={t("analytics.dashboards.subtitle")} />

      <QueryState
        query={query}
        isEmpty={(d) => d.length === 0}
        empty={<EmptyState icon={<LayoutGrid className="size-8" />} title={t("analytics.dashboards.empty")} />}
      >
        {(dashboards) => (
          <div className="grid gap-6 lg:grid-cols-2">
            {dashboards.map((d) => (
              <Card key={d.id}>
                <CardHeader className="flex-row items-center justify-between gap-2">
                  <CardTitle className="text-base">{d.name}</CardTitle>
                  {d.is_default ? <Badge variant="secondary">{t("analytics.dashboards.default")}</Badge> : null}
                </CardHeader>
                <CardContent className="space-y-3">
                  <p className="text-xs text-muted-foreground">
                    {t("analytics.dashboards.widgets")}: {d.widgets?.length ?? 0}
                  </p>
                  {d.widgets && d.widgets.length > 0 ? (
                    <div className="grid gap-2 sm:grid-cols-2">
                      {d.widgets.map((w) => (
                        <WidgetPreview key={w.id} widget={w} />
                      ))}
                    </div>
                  ) : (
                    <p className="text-sm text-muted-foreground">{t("analytics.dashboards.noWidgets")}</p>
                  )}
                </CardContent>
              </Card>
            ))}
          </div>
        )}
      </QueryState>
    </div>
  );
}

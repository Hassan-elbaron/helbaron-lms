"use client";

import { useI18n } from "@/lib/i18n/i18n-context";
import type { DashboardWidget } from "@/lib/analytics/api";
import { Badge } from "@/components/ui/badge";

export function WidgetPreview({ widget }: { widget: DashboardWidget }) {
  const { t } = useI18n();
  return (
    <div className="rounded-md border p-3">
      <div className="flex items-center justify-between gap-2">
        <p className="truncate text-sm font-medium">{widget.title}</p>
        <Badge variant="outline">{widget.type}</Badge>
      </div>
      <p className="mt-1 text-xs text-muted-foreground">
        {widget.metric_key ? `${t("analytics.dashboards.metric")}: ${widget.metric_key}` : t("analytics.dashboards.noMetric")}
      </p>
    </div>
  );
}

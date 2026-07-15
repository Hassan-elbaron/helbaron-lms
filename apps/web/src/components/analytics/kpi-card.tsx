"use client";

import type { LucideIcon } from "lucide-react";
import { useI18n } from "@/lib/i18n/i18n-context";
import { formatMoney } from "@/lib/format";
import type { Kpi } from "@/lib/analytics/api";
import { Card, CardContent } from "@/components/ui/card";
import { Sparkline, seriesToData } from "@/components/ui/charts";

function formatValue(kpi: Kpi, locale: string): string {
  if (kpi.unit === "currency_minor") return formatMoney(kpi.total, "USD", locale);
  return new Intl.NumberFormat(locale).format(kpi.total);
}

export function KpiCard({ kpi, label, icon: Icon }: { kpi: Kpi; label: string; icon?: LucideIcon }) {
  const { locale } = useI18n();
  const series = kpi.series ?? [];
  const points = series.length;
  return (
    <Card className="card-hover h-full hover:border-primary/30 hover:elevation-3">
      <CardContent className="flex h-full items-center gap-4 p-5">
        {Icon ? (
          <div className="flex size-11 shrink-0 items-center justify-center rounded-lg bg-primary/10 text-primary">
            <Icon className="size-5" aria-hidden />
          </div>
        ) : null}
        <div className="min-w-0 flex-1">
          <div className="text-2xl font-bold tabular-nums">{formatValue(kpi, locale)}</div>
          <div className="truncate text-sm text-muted-foreground">{label}</div>
          {/* Additive: a token-driven trend sparkline when a multi-point series is available. */}
          {points >= 2 ? (
            <div className="mt-2">
              <Sparkline data={seriesToData(series)} label={`${label} trend`} height={32} />
            </div>
          ) : points > 0 ? (
            <div className="text-xs text-muted-foreground">{points} pts</div>
          ) : null}
        </div>
      </CardContent>
    </Card>
  );
}

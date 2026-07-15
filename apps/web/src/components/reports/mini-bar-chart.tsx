"use client";

import { BarChart, seriesToData } from "@/components/ui/charts";

export type SeriesPoint = { period: string; value: number };

/**
 * Monthly-series bar chart. Back-compat wrapper that now delegates to the standardized,
 * token-driven `BarChart` from the shared chart layer (dependency-free SVG, dark-mode +
 * Theme-Manager aware colours, accessible figure with a data-table alternative). The public
 * props (`data`, `ariaLabel`) are unchanged so existing callers keep working.
 */
export function MiniBarChart({ data, ariaLabel }: { data: SeriesPoint[]; ariaLabel?: string }) {
  if (data.length === 0) return null;
  return <BarChart data={seriesToData(data)} label={ariaLabel ?? "Bar chart"} height={160} />;
}

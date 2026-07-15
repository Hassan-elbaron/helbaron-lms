"use client";

import { useI18n } from "@/lib/i18n/i18n-context";
import { formatMoney } from "@/lib/format";
import { ResultTable } from "@/components/analytics/result-table";
import { Button } from "@/components/ui/button";
import { MiniBarChart, type SeriesPoint } from "./mini-bar-chart";

type Payload = Record<string, unknown>;
type Pagination = { current_page: number; last_page: number; total: number };

/** Platform default settlement currency (all report money is integer minor units). */
const CURRENCY = "SAR";

function humanize(key: string): string {
  return key.replace(/_/g, " ").replace(/\b\w/g, (c) => c.toUpperCase());
}

function isSeries(v: unknown): v is SeriesPoint[] {
  return (
    Array.isArray(v) &&
    v.length > 0 &&
    typeof v[0] === "object" &&
    v[0] !== null &&
    "period" in (v[0] as object) &&
    "value" in (v[0] as object)
  );
}

function isObjectArray(v: unknown): v is Record<string, unknown>[] {
  return Array.isArray(v) && v.length > 0 && v.every((x) => typeof x === "object" && x !== null);
}

function formatValue(key: string, val: unknown, locale: string): string {
  if (typeof val === "number") {
    if (key.endsWith("_minor")) return formatMoney(val, CURRENCY, locale);
    if (/rate|percentage/.test(key)) return `${val}%`;
    return new Intl.NumberFormat(locale === "ar" ? "ar" : "en").format(val);
  }
  if (val == null) return "—";
  return String(val);
}

/**
 * Generic, shape-driven renderer for any report payload: a KPI grid for `summary`, a funnel for
 * `steps`, bar charts for `{period,value}` series, and data tables for every other object array.
 * `rows` (the paginated tabular reports) also renders a pager wired to the parent's page state.
 */
export function ReportView({
  payload,
  page,
  onPageChange,
}: {
  payload: Payload;
  meta: { from: string; to: string };
  page: number;
  onPageChange: (p: number) => void;
}) {
  const { t, locale } = useI18n();

  const summary =
    payload.summary && typeof payload.summary === "object" ? (payload.summary as Record<string, unknown>) : null;
  const steps = Array.isArray(payload.steps) ? (payload.steps as Record<string, unknown>[]) : null;
  const pagination =
    payload.pagination && typeof payload.pagination === "object" ? (payload.pagination as Pagination) : null;

  const seriesEntries = Object.entries(payload).filter(([, v]) => isSeries(v));
  const tableEntries = Object.entries(payload).filter(
    ([k, v]) => k !== "steps" && isObjectArray(v) && !isSeries(v),
  );

  return (
    <div className="space-y-8">
      {summary && (
        <section aria-labelledby="rpt-summary">
          <h2 id="rpt-summary" className="mb-3 font-serif text-lg">
            {t("reports.summary")}
          </h2>
          <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            {Object.entries(summary).map(([k, v]) => (
              <div key={k} className="rounded-xl border bg-card p-4">
                <p className="text-xs uppercase tracking-wide text-muted-foreground">{humanize(k)}</p>
                <p className="mt-1 text-2xl font-semibold tabular-nums">{formatValue(k, v, locale)}</p>
              </div>
            ))}
          </div>
        </section>
      )}

      {steps && (
        <section aria-labelledby="rpt-funnel">
          <h2 id="rpt-funnel" className="mb-3 font-serif text-lg">
            {t("reports.funnel")}
          </h2>
          <div className="space-y-2">
            {steps.map((s, i) => {
              const pct = typeof s.percentage === "number" ? s.percentage : 0;
              return (
                <div key={i} className="space-y-1">
                  <div className="flex justify-between text-sm">
                    <span>{humanize(String(s.step))}</span>
                    <span className="tabular-nums text-muted-foreground">
                      {String(s.count)} · {pct}%
                    </span>
                  </div>
                  <div className="h-3 w-full overflow-hidden rounded bg-muted">
                    <div
                      className="h-full rounded bg-primary/70"
                      style={{ width: `${Math.max(2, Math.min(100, pct))}%` }}
                    />
                  </div>
                </div>
              );
            })}
          </div>
        </section>
      )}

      {seriesEntries.map(([k, v]) => (
        <section key={k} aria-labelledby={`series-${k}`}>
          <h2 id={`series-${k}`} className="mb-3 font-serif text-lg">
            {humanize(k)}
          </h2>
          <div className="rounded-xl border bg-card p-4">
            <MiniBarChart data={v as SeriesPoint[]} ariaLabel={humanize(k)} />
          </div>
        </section>
      ))}

      {tableEntries.map(([k, v]) => {
        const rows = (v as Record<string, unknown>[]).map((row) =>
          Object.fromEntries(Object.entries(row).map(([rk, rv]) => [humanize(rk), formatValue(rk, rv, locale)])),
        );
        return (
          <section key={k} aria-labelledby={`table-${k}`}>
            <h2 id={`table-${k}`} className="mb-3 font-serif text-lg">
              {humanize(k)}
            </h2>
            <ResultTable rows={rows} />
            {k === "rows" && pagination && (
              <div className="mt-3 flex items-center justify-between gap-2 text-sm">
                <span className="text-muted-foreground">
                  {t("reports.page")} {pagination.current_page} {t("reports.of")} {pagination.last_page}
                </span>
                <div className="flex gap-2">
                  <Button size="sm" variant="outline" disabled={page <= 1} onClick={() => onPageChange(page - 1)}>
                    {t("reports.prev")}
                  </Button>
                  <Button
                    size="sm"
                    variant="outline"
                    disabled={pagination.current_page >= pagination.last_page}
                    onClick={() => onPageChange(page + 1)}
                  >
                    {t("reports.next")}
                  </Button>
                </div>
              </div>
            )}
          </section>
        );
      })}
    </div>
  );
}

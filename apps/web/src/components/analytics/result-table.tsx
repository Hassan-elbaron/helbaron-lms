"use client";

import { useMemo } from "react";
import { useI18n } from "@/lib/i18n/i18n-context";
import { DataGrid, type ColumnDef } from "@/components/ui/data-grid";
import { EmptyState } from "@/components/states/empty-state";

function cell(v: unknown): string {
  if (v == null) return "—";
  if (typeof v === "object") return JSON.stringify(v);
  return String(v);
}

type IndexedRow = { key: string; row: Record<string, unknown> };

/**
 * Renders any array of flat objects as a table; picks up column keys from the union of row
 * keys. Now built on the standardized `DataGrid` primitive (compact density, sortable columns,
 * responsive card fallback, token-driven states) while keeping its original `rows` prop.
 */
export function ResultTable({ rows }: { rows: Record<string, unknown>[] }) {
  const { t } = useI18n();

  const columns = useMemo<ColumnDef<IndexedRow>[]>(() => {
    const keys = Array.from(new Set((rows ?? []).flatMap((r) => Object.keys(r))));
    return keys.map((c) => ({
      key: c,
      header: c,
      sortable: true,
      sortValue: (item) => cell(item.row[c]),
      cell: (item) => <span className="tabular-nums">{cell(item.row[c])}</span>,
    }));
  }, [rows]);

  const data = useMemo<IndexedRow[]>(() => (rows ?? []).map((row, i) => ({ key: String(i), row })), [rows]);

  // Empty rows yield no columns; short-circuit to the canonical empty state (also avoids colSpan=0).
  if (data.length === 0) return <EmptyState title={t("analytics.reports.noRows")} />;

  return (
    <DataGrid
      columns={columns}
      data={data}
      rowKey={(r) => r.key}
      density="compact"
      empty={<EmptyState title={t("analytics.reports.noRows")} />}
    />
  );
}

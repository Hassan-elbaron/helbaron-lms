"use client";

import { useI18n } from "@/lib/i18n/i18n-context";
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from "@/components/ui/table";
import { EmptyState } from "@/components/states/empty-state";

function cell(v: unknown): string {
  if (v == null) return "—";
  if (typeof v === "object") return JSON.stringify(v);
  return String(v);
}

/** Renders any array of flat objects as a table; picks up column keys from the first row. */
export function ResultTable({ rows }: { rows: Record<string, unknown>[] }) {
  const { t } = useI18n();
  if (!rows || rows.length === 0) return <EmptyState title={t("analytics.reports.noRows")} />;

  const columns = Array.from(new Set(rows.flatMap((r) => Object.keys(r))));

  return (
    <div className="overflow-x-auto rounded-md border">
      <Table>
        <TableHeader>
          <TableRow>
            {columns.map((c) => (
              <TableHead key={c}>{c}</TableHead>
            ))}
          </TableRow>
        </TableHeader>
        <TableBody>
          {rows.map((r, i) => (
            <TableRow key={i}>
              {columns.map((c) => (
                <TableCell key={c} className="tabular-nums">
                  {cell(r[c])}
                </TableCell>
              ))}
            </TableRow>
          ))}
        </TableBody>
      </Table>
    </div>
  );
}

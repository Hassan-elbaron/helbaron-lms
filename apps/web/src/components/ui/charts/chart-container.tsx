import type { ReactNode } from "react";
import { cn } from "@/lib/utils";
import {
  chartColor,
  DEFAULT_TABLE_LABELS,
  defaultFormatValue,
  type ChartDatum,
  type ChartTableLabels,
} from "./theme";

export interface ChartFigureProps {
  /** Accessible summary of the whole chart (announced to screen readers as role="img"). */
  label: string;
  /** Data backing the visually-hidden table alternative. */
  data: ChartDatum[];
  /** Formats values in the data-table alternative. */
  formatValue?: (value: number) => string;
  /** Column headers for the data-table alternative (pass localized strings for i18n). */
  tableLabels?: ChartTableLabels;
  className?: string;
  /** The SVG (and any visible legend) for the chart. */
  children: ReactNode;
}

/**
 * Accessible wrapper for every chart: exposes the visual as a single `role="img"` with an
 * `aria-label` summary, and mirrors the data in a visually-hidden `<table>` so assistive-tech
 * users get the underlying numbers, not just the label.
 */
export function ChartFigure({
  label,
  data,
  formatValue = defaultFormatValue,
  tableLabels = DEFAULT_TABLE_LABELS,
  className,
  children,
}: ChartFigureProps) {
  return (
    <figure role="img" aria-label={label} className={cn("w-full", className)}>
      {children}
      <table className="sr-only">
        <caption>{label}</caption>
        <thead>
          <tr>
            <th scope="col">{tableLabels.category}</th>
            <th scope="col">{tableLabels.value}</th>
          </tr>
        </thead>
        <tbody>
          {data.map((d, i) => (
            <tr key={`${d.label}-${i}`}>
              <th scope="row">{d.label}</th>
              <td>{formatValue(d.value)}</td>
            </tr>
          ))}
        </tbody>
      </table>
    </figure>
  );
}

export interface ChartLegendProps {
  items: { label: string; color?: string }[];
  className?: string;
}

/**
 * Static, token-coloured legend. Rendered as a list so it is exposed as structured content;
 * swatches are decorative (the adjacent text carries the meaning).
 */
export function ChartLegend({ items, className }: ChartLegendProps) {
  return (
    <ul className={cn("flex flex-wrap items-center gap-x-4 gap-y-1.5", className)}>
      {items.map((item, i) => (
        <li key={`${item.label}-${i}`} className="flex items-center gap-1.5 text-caption text-muted-foreground">
          <span
            aria-hidden
            className="inline-block size-2.5 shrink-0 rounded-full"
            style={{ backgroundColor: item.color ?? chartColor(i) }}
          />
          <span className="truncate">{item.label}</span>
        </li>
      ))}
    </ul>
  );
}

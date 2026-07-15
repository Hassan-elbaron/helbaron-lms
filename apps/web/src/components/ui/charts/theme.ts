/**
 * Shared chart theme — the single source of truth for the dependency-free chart layer.
 *
 * Colours are expressed as CSS `var(--token)` references (never hardcoded hex), so every
 * chart adapts automatically to light/dark mode AND to Theme-Manager runtime overrides.
 * They are consumed through the SVG `fill` / `stroke` CSS properties (via the `style` prop),
 * because SVG presentation *attributes* cannot resolve `var()` while the CSS properties can.
 */

/** Categorical colour sequence, derived from the semantic design tokens. */
export const CHART_SERIES = [
  "var(--primary)",
  "var(--accent)",
  "var(--success)",
  "var(--warning)",
  "var(--info)",
  "var(--secondary)",
  "var(--copper)",
  "var(--gold)",
] as const;

/** Number of distinct colours before the sequence repeats. */
export const CHART_SERIES_LENGTH = CHART_SERIES.length;

/** Pick a categorical colour for a series/category index (wraps, negative-safe). */
export function chartColor(index: number): string {
  const n = CHART_SERIES_LENGTH;
  return CHART_SERIES[((index % n) + n) % n];
}

/** Non-colour chart tokens (grid/axis/label/surface + geometry + motion). */
export const CHART_TOKENS = {
  /** Gridlines + axis rules. */
  grid: "var(--border)",
  /** Axis + category label text. */
  axis: "var(--muted-foreground)",
  /** Plot surface / track (e.g. donut & progress-ring background arc). */
  track: "var(--muted)",
  /** Value labels drawn on the plot. */
  value: "var(--foreground)",
  /** Corner radius for bars (SVG user units). */
  barRadius: 4,
  /** Default category/axis label font-size (SVG user units ≈ px at natural scale). */
  fontSize: 11,
} as const;

/** A single chart datum. `color` overrides the categorical sequence when provided. */
export interface ChartDatum {
  label: string;
  value: number;
  color?: string;
}

/** Column headers for the visually-hidden data-table alternative (overridable for i18n). */
export interface ChartTableLabels {
  category: string;
  value: string;
}

export const DEFAULT_TABLE_LABELS: ChartTableLabels = { category: "Category", value: "Value" };

/** Default numeric formatter used when a chart consumer does not supply one. */
export function defaultFormatValue(value: number): string {
  return new Intl.NumberFormat().format(value);
}

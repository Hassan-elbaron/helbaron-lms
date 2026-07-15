/**
 * Dependency-free chart layer. SVG-based, token-driven (categorical colour sequence derived
 * from CSS design tokens — dark-mode + Theme-Manager aware), responsive (viewBox + width),
 * accessible (role="img" + aria-label summary + visually-hidden data table), and
 * reduced-motion-safe (`.chart-grow` / `.chart-draw` utilities).
 */
export {
  CHART_SERIES,
  CHART_SERIES_LENGTH,
  CHART_TOKENS,
  chartColor,
  defaultFormatValue,
  DEFAULT_TABLE_LABELS,
  type ChartDatum,
  type ChartTableLabels,
} from "./theme";
export { ChartFigure, ChartLegend, type ChartFigureProps, type ChartLegendProps } from "./chart-container";
export { BarChart, seriesToData, type BarChartProps } from "./bar-chart";
export { LineChart, Sparkline, type LineChartProps, type SparklineProps } from "./line-chart";
export { DonutChart, ProgressRing, type DonutChartProps, type ProgressRingProps } from "./donut-chart";

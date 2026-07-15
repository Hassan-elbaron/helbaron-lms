import type { ReactNode } from "react";
import { cn } from "@/lib/utils";
import { ChartFigure } from "./chart-container";
import {
  chartColor,
  CHART_TOKENS,
  defaultFormatValue,
  type ChartDatum,
  type ChartTableLabels,
} from "./theme";

const VIEWBOX = 100;
const CENTER = VIEWBOX / 2;

export interface DonutChartProps {
  data: ChartDatum[];
  /** Accessible summary (role="img" aria-label + sr-only table caption). */
  label: string;
  formatValue?: (value: number) => string;
  /** Ring thickness in viewBox units (0–50). */
  thickness?: number;
  /** Content rendered in the middle of the donut (e.g. a total). */
  centerContent?: ReactNode;
  /** Pixel size of the rendered donut. */
  size?: number;
  tableLabels?: ChartTableLabels;
  className?: string;
}

/**
 * Dependency-free, responsive SVG donut chart. Each segment uses the tokenized categorical
 * colour sequence (dark-mode + Theme-Manager aware). Accessible figure with data-table alt.
 */
export function DonutChart({
  data,
  label,
  formatValue = defaultFormatValue,
  thickness = 16,
  centerContent,
  size = 160,
  tableLabels,
  className,
}: DonutChartProps) {
  if (data.length === 0) return null;

  const radius = CENTER - thickness / 2;
  const circumference = 2 * Math.PI * radius;
  const total = data.reduce((sum, d) => sum + Math.max(0, d.value), 0) || 1;

  // Prefix-sum the fractions functionally (no mutable render-time binding) so each segment
  // starts where the previous ended, rotating from 12 o'clock and running clockwise.
  const fractions = data.map((d) => Math.max(0, d.value) / total);
  const startFraction = fractions.map((_, i) => fractions.slice(0, i).reduce((a, b) => a + b, 0));
  const segments = data.map((d, i) => {
    const dash = fractions[i] * circumference;
    return {
      color: d.color ?? chartColor(i),
      dash,
      gap: circumference - dash,
      rotation: startFraction[i] * 360 - 90,
    };
  });

  return (
    <ChartFigure label={label} data={data} formatValue={formatValue} tableLabels={tableLabels} className={cn("inline-block w-auto", className)}>
      <div className="relative inline-flex items-center justify-center" style={{ width: size, height: size }}>
        <svg viewBox={`0 0 ${VIEWBOX} ${VIEWBOX}`} width={size} height={size} aria-hidden focusable="false">
          <circle
            cx={CENTER}
            cy={CENTER}
            r={radius}
            fill="none"
            style={{ stroke: CHART_TOKENS.track }}
            strokeWidth={thickness}
            opacity={0.5}
          />
          {segments.map((s, i) => (
            <circle
              key={i}
              cx={CENTER}
              cy={CENTER}
              r={radius}
              fill="none"
              style={{ stroke: s.color, transform: `rotate(${s.rotation}deg)`, transformOrigin: "center" }}
              strokeWidth={thickness}
              strokeDasharray={`${s.dash} ${s.gap}`}
              strokeLinecap="butt"
            />
          ))}
        </svg>
        {centerContent && (
          <div className="absolute inset-0 flex flex-col items-center justify-center text-center">{centerContent}</div>
        )}
      </div>
    </ChartFigure>
  );
}

export interface ProgressRingProps {
  /** Current value. */
  value: number;
  /** Full-scale value (default 100 → treat `value` as a percentage). */
  max?: number;
  /** Accessible summary. */
  label: string;
  /** Ring thickness in viewBox units. */
  thickness?: number;
  /** Index into the categorical colour sequence. */
  colorIndex?: number;
  /** Pixel size. */
  size?: number;
  /** Content in the middle (defaults to the rounded percentage). */
  centerContent?: ReactNode;
  className?: string;
}

/**
 * Single-value progress ring (donut with one segment + track). Exposes an accessible
 * `role="img"` summary and honours reduced motion via the `.chart-draw` utility.
 */
export function ProgressRing({
  value,
  max = 100,
  label,
  thickness = 12,
  colorIndex = 0,
  size = 96,
  centerContent,
  className,
}: ProgressRingProps) {
  const pct = Math.max(0, Math.min(1, value / (max || 1)));
  const radius = CENTER - thickness / 2;
  const circumference = 2 * Math.PI * radius;
  const color = chartColor(colorIndex);

  return (
    <figure
      role="img"
      aria-label={label}
      className={cn("relative inline-flex items-center justify-center", className)}
      style={{ width: size, height: size }}
    >
      <svg viewBox={`0 0 ${VIEWBOX} ${VIEWBOX}`} width={size} height={size} aria-hidden focusable="false">
        <circle
          cx={CENTER}
          cy={CENTER}
          r={radius}
          fill="none"
          style={{ stroke: CHART_TOKENS.track }}
          strokeWidth={thickness}
          opacity={0.5}
        />
        <circle
          cx={CENTER}
          cy={CENTER}
          r={radius}
          fill="none"
          className="chart-draw"
          style={{ stroke: color, transform: "rotate(-90deg)", transformOrigin: "center" }}
          strokeWidth={thickness}
          strokeDasharray={`${pct * circumference} ${circumference}`}
          strokeLinecap="round"
        />
      </svg>
      <div className="absolute inset-0 flex flex-col items-center justify-center text-center">
        {centerContent ?? <span className="text-sm font-semibold tabular-nums">{Math.round(pct * 100)}%</span>}
      </div>
    </figure>
  );
}

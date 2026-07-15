import { useId } from "react";
import { cn } from "@/lib/utils";
import { ChartFigure } from "./chart-container";
import {
  chartColor,
  CHART_TOKENS,
  defaultFormatValue,
  type ChartDatum,
  type ChartTableLabels,
} from "./theme";

export interface LineChartProps {
  data: ChartDatum[];
  /** Accessible summary (role="img" aria-label + sr-only table caption). */
  label: string;
  formatValue?: (value: number) => string;
  /** Fill the area under the line. */
  area?: boolean;
  /** Draw a dot at each data point. */
  showDots?: boolean;
  /** Draw horizontal gridlines. */
  showGrid?: boolean;
  /** Render category labels beneath the line (first/last only to avoid crowding). */
  showLabels?: boolean;
  /** Index into the categorical colour sequence. */
  colorIndex?: number;
  /** SVG viewBox height in user units. */
  height?: number;
  tableLabels?: ChartTableLabels;
  className?: string;
}

function buildPoints(data: ChartDatum[], vbW: number, vbH: number, padX: number, padY: number) {
  const max = Math.max(...data.map((d) => d.value), 1);
  const min = Math.min(...data.map((d) => d.value), 0);
  const range = max - min || 1;
  const plotW = vbW - padX * 2;
  const plotH = vbH - padY * 2;
  const step = data.length > 1 ? plotW / (data.length - 1) : 0;
  return data.map((d, i) => {
    const x = padX + (data.length > 1 ? i * step : plotW / 2);
    const y = padY + plotH * (1 - (d.value - min) / range);
    return { x, y };
  });
}

/**
 * Dependency-free, responsive SVG line/area chart. Token-driven colour (dark-mode +
 * Theme-Manager aware), reduced-motion-safe draw animation, accessible figure wrapper.
 */
export function LineChart({
  data,
  label,
  formatValue = defaultFormatValue,
  area = false,
  showDots = false,
  showGrid = true,
  showLabels = false,
  colorIndex = 0,
  height = 200,
  tableLabels,
  className,
}: LineChartProps) {
  const gradientId = useId();
  if (data.length === 0) return null;

  const padX = 8;
  const padY = 12;
  const vbW = Math.max(data.length * 44, 160);
  const vbH = height;
  const color = chartColor(colorIndex);
  const points = buildPoints(data, vbW, vbH, padX, padY);
  const linePath = points.map((p, i) => `${i === 0 ? "M" : "L"} ${p.x} ${p.y}`).join(" ");
  const areaPath =
    points.length > 0
      ? `${linePath} L ${points[points.length - 1].x} ${vbH - padY} L ${points[0].x} ${vbH - padY} Z`
      : "";
  const gridLines = [0, 0.5, 1];

  return (
    <ChartFigure label={label} data={data} formatValue={formatValue} tableLabels={tableLabels} className={className}>
      <svg
        viewBox={`0 0 ${vbW} ${vbH}`}
        width="100%"
        className="h-auto max-w-full overflow-visible"
        preserveAspectRatio="none"
        aria-hidden
        focusable="false"
      >
        {area && (
          <defs>
            <linearGradient id={gradientId} x1="0" y1="0" x2="0" y2="1">
              <stop offset="0%" style={{ stopColor: color }} stopOpacity={0.28} />
              <stop offset="100%" style={{ stopColor: color }} stopOpacity={0.02} />
            </linearGradient>
          </defs>
        )}

        {showGrid &&
          gridLines.map((f) => {
            const y = padY + (vbH - padY * 2) * f;
            return (
              <line
                key={f}
                x1={padX}
                x2={vbW - padX}
                y1={y}
                y2={y}
                vectorEffect="non-scaling-stroke"
                style={{ stroke: CHART_TOKENS.grid }}
                strokeWidth={0.75}
                opacity={0.5}
              />
            );
          })}

        {area && areaPath && <path d={areaPath} fill={`url(#${gradientId})`} stroke="none" />}

        <path
          d={linePath}
          fill="none"
          className="chart-draw"
          vectorEffect="non-scaling-stroke"
          style={{ stroke: color }}
          strokeWidth={2}
          strokeLinecap="round"
          strokeLinejoin="round"
        />

        {showDots &&
          points.map((p, i) => (
            <circle
              key={i}
              cx={p.x}
              cy={p.y}
              r={2.5}
              vectorEffect="non-scaling-stroke"
              style={{ fill: "var(--card)", stroke: color }}
              strokeWidth={2}
            />
          ))}

      </svg>
      {showLabels && data.length > 1 && (
        // Labels live in HTML (not SVG) so they are not distorted by preserveAspectRatio="none"
        // and mirror correctly under RTL via logical flex ordering.
        <div className="mt-1 flex justify-between text-caption text-muted-foreground">
          <span className="truncate">{data[0].label}</span>
          <span className="truncate">{data[data.length - 1].label}</span>
        </div>
      )}
    </ChartFigure>
  );
}

export interface SparklineProps {
  data: ChartDatum[];
  label: string;
  formatValue?: (value: number) => string;
  colorIndex?: number;
  height?: number;
  className?: string;
}

/** Compact area sparkline (no grid, no labels) for inline trend cues (e.g. KPI cards). */
export function Sparkline({ data, label, formatValue, colorIndex = 0, height = 40, className }: SparklineProps) {
  return (
    <LineChart
      data={data}
      label={label}
      formatValue={formatValue}
      area
      showGrid={false}
      showLabels={false}
      colorIndex={colorIndex}
      height={height}
      className={cn("block", className)}
    />
  );
}

import { ChartFigure } from "./chart-container";
import {
  chartColor,
  CHART_TOKENS,
  defaultFormatValue,
  type ChartDatum,
  type ChartTableLabels,
} from "./theme";

export interface BarChartProps {
  data: ChartDatum[];
  /** Accessible summary (role="img" aria-label + sr-only table caption). */
  label: string;
  formatValue?: (value: number) => string;
  /** Colour each bar from the categorical sequence instead of a single colour. */
  multicolor?: boolean;
  /** Draw horizontal gridlines. */
  showGrid?: boolean;
  /** Render category labels beneath the bars. */
  showLabels?: boolean;
  /** Draw the numeric value above each bar. */
  showValues?: boolean;
  /** SVG viewBox height in user units (controls the drawn aspect ratio). */
  height?: number;
  tableLabels?: ChartTableLabels;
  className?: string;
}

/**
 * Dependency-free, responsive SVG bar chart. Colours come from the tokenized categorical
 * sequence (dark-mode + Theme-Manager aware), bars grow via the reduced-motion-safe
 * `.chart-grow` utility, and the whole thing is exposed as an accessible figure.
 */
export function BarChart({
  data,
  label,
  formatValue = defaultFormatValue,
  multicolor = false,
  showGrid = true,
  showLabels = true,
  showValues = false,
  height = 200,
  tableLabels,
  className,
}: BarChartProps) {
  if (data.length === 0) return null;

  const max = Math.max(...data.map((d) => d.value), 1);
  const padX = 8;
  const padTop = showValues ? 20 : 12;
  const padBottom = showLabels ? 22 : 8;
  const vbW = Math.max(data.length * 44, 160);
  const vbH = height;
  const plotH = vbH - padTop - padBottom;
  const slotW = (vbW - padX * 2) / data.length;
  const barW = Math.min(slotW * 0.62, 44);
  const gridLines = [0, 0.25, 0.5, 0.75, 1];

  return (
    <ChartFigure label={label} data={data} formatValue={formatValue} tableLabels={tableLabels} className={className}>
      <svg
        viewBox={`0 0 ${vbW} ${vbH}`}
        width="100%"
        className="h-auto max-w-full overflow-visible"
        preserveAspectRatio="xMidYMid meet"
        aria-hidden
        focusable="false"
      >
        {showGrid &&
          gridLines.map((f) => {
            const y = padTop + plotH * f;
            return (
              <line
                key={f}
                x1={padX}
                x2={vbW - padX}
                y1={y}
                y2={y}
                style={{ stroke: CHART_TOKENS.grid }}
                strokeWidth={0.75}
                opacity={f === 1 ? 1 : 0.5}
              />
            );
          })}

        {data.map((d, i) => {
          const h = Math.max(1.5, (d.value / max) * plotH);
          const x = padX + i * slotW + (slotW - barW) / 2;
          const y = padTop + (plotH - h);
          const fill = d.color ?? (multicolor ? chartColor(i) : chartColor(0));
          return (
            <g key={`${d.label}-${i}`}>
              <rect
                x={x}
                y={y}
                width={barW}
                height={h}
                rx={CHART_TOKENS.barRadius}
                className="chart-grow"
                style={{ fill, animationDelay: `${i * 40}ms` }}
                fillOpacity={0.9}
              />
              {showValues && (
                <text
                  x={x + barW / 2}
                  y={y - 5}
                  textAnchor="middle"
                  style={{ fill: CHART_TOKENS.value, fontSize: CHART_TOKENS.fontSize }}
                  className="tabular-nums"
                >
                  {formatValue(d.value)}
                </text>
              )}
              {showLabels && (
                <text
                  x={x + barW / 2}
                  y={vbH - 7}
                  textAnchor="middle"
                  style={{ fill: CHART_TOKENS.axis, fontSize: CHART_TOKENS.fontSize }}
                >
                  {d.label}
                </text>
              )}
            </g>
          );
        })}
      </svg>
    </ChartFigure>
  );
}

/** Compatibility helper: map a `{ period, value }` series to `ChartDatum[]`. */
export function seriesToData(series: { period: string; value: number }[]): ChartDatum[] {
  return series.map((p) => ({ label: p.period, value: p.value }));
}

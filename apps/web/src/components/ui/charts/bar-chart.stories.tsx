import type { Meta, StoryObj } from "@storybook/react";
import { BarChart } from "@/components/ui/charts";
import type { ChartDatum } from "@/components/ui/charts";

const enrollments: ChartDatum[] = [
  { label: "Jan", value: 320 },
  { label: "Feb", value: 410 },
  { label: "Mar", value: 380 },
  { label: "Apr", value: 520 },
  { label: "May", value: 610 },
  { label: "Jun", value: 720 },
];

const meta = {
  title: "Charts/Bar",
  component: BarChart,
  parameters: { layout: "padded" },
  args: {
    data: enrollments,
    label: "Monthly enrollments over the last six months",
  },
  argTypes: {
    multicolor: { control: "boolean" },
    showGrid: { control: "boolean" },
    showLabels: { control: "boolean" },
    showValues: { control: "boolean" },
    height: { control: { type: "number", min: 80, max: 400 } },
  },
  decorators: [(Story: () => import("react").ReactElement) => <div className="max-w-xl">{Story()}</div>],
} satisfies Meta<typeof BarChart>;

export default meta;
type Story = StoryObj<typeof meta>;

/** Single-colour bar chart with gridlines and category labels. */
export const Default: Story = {};

/** Each bar drawn from the tokenized categorical colour sequence. */
export const Multicolor: Story = {
  args: { multicolor: true },
};

/** Numeric value drawn above every bar. */
export const WithValues: Story = {
  args: { showValues: true },
};

/** Minimal chart: no grid, no labels. */
export const Minimal: Story = {
  args: { showGrid: false, showLabels: false },
};

/** Custom value formatter (currency) feeding both the visual and the sr-only table. */
export const Formatted: Story = {
  args: {
    data: [
      { label: "Q1", value: 12400 },
      { label: "Q2", value: 18800 },
      { label: "Q3", value: 15200 },
      { label: "Q4", value: 22100 },
    ],
    label: "Quarterly revenue",
    showValues: true,
    formatValue: (v: number) => `$${(v / 1000).toFixed(1)}k`,
  },
};

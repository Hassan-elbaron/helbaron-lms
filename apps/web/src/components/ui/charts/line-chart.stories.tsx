import type { Meta, StoryObj } from "@storybook/react";
import { LineChart } from "@/components/ui/charts";
import type { ChartDatum } from "@/components/ui/charts";

const activeUsers: ChartDatum[] = [
  { label: "Week 1", value: 1200 },
  { label: "Week 2", value: 1340 },
  { label: "Week 3", value: 1280 },
  { label: "Week 4", value: 1520 },
  { label: "Week 5", value: 1610 },
  { label: "Week 6", value: 1490 },
  { label: "Week 7", value: 1780 },
  { label: "Week 8", value: 1920 },
];

const meta = {
  title: "Charts/Line",
  component: LineChart,
  parameters: { layout: "padded" },
  args: {
    data: activeUsers,
    label: "Weekly active learners over eight weeks",
  },
  argTypes: {
    area: { control: "boolean" },
    showDots: { control: "boolean" },
    showGrid: { control: "boolean" },
    showLabels: { control: "boolean" },
    colorIndex: { control: { type: "number", min: 0, max: 7 } },
    height: { control: { type: "number", min: 80, max: 400 } },
  },
  decorators: [(Story: () => import("react").ReactElement) => <div className="max-w-xl">{Story()}</div>],
} satisfies Meta<typeof LineChart>;

export default meta;
type Story = StoryObj<typeof meta>;

/** Plain line with gridlines. */
export const Default: Story = {};

/** Line with dots at each data point and first/last labels. */
export const WithDots: Story = {
  args: { showDots: true, showLabels: true },
};

/** Area variant: fills the region beneath the line with a token-coloured gradient. */
export const Area: Story = {
  args: { area: true, showDots: true },
};

/** Alternate series colour via `colorIndex`. */
export const AltColor: Story = {
  args: { area: true, colorIndex: 2 },
};

/** Minimal line: no grid, no labels. */
export const Minimal: Story = {
  args: { showGrid: false, showLabels: false },
};

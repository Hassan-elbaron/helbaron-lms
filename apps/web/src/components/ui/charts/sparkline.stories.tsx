import type { Meta, StoryObj } from "@storybook/react";
import { Sparkline } from "@/components/ui/charts";
import type { ChartDatum } from "@/components/ui/charts";

const trend: ChartDatum[] = [
  { label: "Mon", value: 42 },
  { label: "Tue", value: 48 },
  { label: "Wed", value: 40 },
  { label: "Thu", value: 55 },
  { label: "Fri", value: 61 },
  { label: "Sat", value: 58 },
  { label: "Sun", value: 67 },
];

const meta = {
  title: "Charts/Sparkline",
  component: Sparkline,
  parameters: { layout: "padded" },
  args: {
    data: trend,
    label: "Daily completions trend",
  },
  argTypes: {
    colorIndex: { control: { type: "number", min: 0, max: 7 } },
    height: { control: { type: "number", min: 20, max: 120 } },
  },
  decorators: [(Story: () => import("react").ReactElement) => <div className="max-w-[180px]">{Story()}</div>],
} satisfies Meta<typeof Sparkline>;

export default meta;
type Story = StoryObj<typeof meta>;

/** Compact inline area sparkline (no grid, no labels) for KPI cards. */
export const Default: Story = {};

/** Alternate colour from the categorical sequence. */
export const AltColor: Story = {
  args: { colorIndex: 3 },
};

/** Embedded next to a KPI value, as it would appear on a dashboard card. */
export const InKpiCard: Story = {
  render: (args: import("react").ComponentProps<typeof Sparkline>) => (
    <div className="rounded-lg border bg-card p-4">
      <p className="text-sm text-muted-foreground">Completions</p>
      <p className="mb-2 text-2xl font-semibold tabular-nums">67</p>
      <Sparkline {...args} />
    </div>
  ),
};

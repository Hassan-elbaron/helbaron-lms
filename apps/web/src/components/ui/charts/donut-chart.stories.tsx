import type { Meta, StoryObj } from "@storybook/react";
import { DonutChart } from "@/components/ui/charts";
import type { ChartDatum } from "@/components/ui/charts";

const byStatus: ChartDatum[] = [
  { label: "Completed", value: 640 },
  { label: "In progress", value: 320 },
  { label: "Not started", value: 180 },
  { label: "At risk", value: 60 },
];

const meta = {
  title: "Charts/Donut",
  component: DonutChart,
  parameters: { layout: "padded" },
  args: {
    data: byStatus,
    label: "Enrollments by completion status",
  },
  argTypes: {
    thickness: { control: { type: "number", min: 4, max: 50 } },
    size: { control: { type: "number", min: 80, max: 320 } },
  },
  decorators: [(Story: () => import("react").ReactElement) => <div className="max-w-md">{Story()}</div>],
} satisfies Meta<typeof DonutChart>;

export default meta;
type Story = StoryObj<typeof meta>;

/** Four-segment donut with the tokenized categorical colour sequence. */
export const Default: Story = {};

/** Center content showing the total. */
export const WithCenterTotal: Story = {
  args: {
    centerContent: (
      <>
        <span className="text-2xl font-semibold tabular-nums">1,200</span>
        <span className="text-caption text-muted-foreground">Learners</span>
      </>
    ),
  },
};

/** A thinner ring. */
export const ThinRing: Story = {
  args: { thickness: 8, size: 180 },
};

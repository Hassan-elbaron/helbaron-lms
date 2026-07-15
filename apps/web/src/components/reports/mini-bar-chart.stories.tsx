import type { Meta, StoryObj } from "@storybook/react";
import { MiniBarChart } from "@/components/reports/mini-bar-chart";

const monthly = [
  { period: "2026-01", value: 820 },
  { period: "2026-02", value: 960 },
  { period: "2026-03", value: 1180 },
  { period: "2026-04", value: 1090 },
  { period: "2026-05", value: 1340 },
  { period: "2026-06", value: 1520 },
];

const meta = {
  title: "Widgets/MiniBarChart",
  component: MiniBarChart,
  tags: ["autodocs"],
  decorators: [(Story: () => import("react").ReactElement) => (
    <div className="w-full max-w-md rounded-xl border bg-card p-4">
      {Story()}
    </div>
  )],
  argTypes: {
    ariaLabel: { control: { type: "text" } },
    data: { control: false },
  },
  args: {
    data: monthly,
    ariaLabel: "Monthly enrollments",
  },
} satisfies Meta<typeof MiniBarChart>;

export default meta;
type Story = StoryObj<typeof meta>;

/** Monthly enrollment series. */
export const Default: Story = {};

/** A shorter, quarterly series. */
export const Quarterly: Story = {
  args: {
    ariaLabel: "Quarterly revenue",
    data: [
      { period: "Q1", value: 2175000 },
      { period: "Q2", value: 2700000 },
      { period: "Q3", value: 2410000 },
      { period: "Q4", value: 3050000 },
    ],
  },
};

/** Empty data → the component renders nothing (guarded). */
export const Empty: Story = {
  args: { data: [], ariaLabel: "No data" },
};

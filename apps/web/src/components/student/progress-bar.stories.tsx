import type { Meta, StoryObj } from "@storybook/react";
import { ProgressBar } from "@/components/student/progress-bar";

const meta = {
  title: "Widgets/ProgressBar",
  component: ProgressBar,
  tags: ["autodocs"],
  argTypes: {
    value: { control: { type: "range", min: 0, max: 100, step: 1 } },
    className: { control: false },
  },
  args: {
    value: 40,
    className: "w-72",
  },
} satisfies Meta<typeof ProgressBar>;

export default meta;
type Story = StoryObj<typeof meta>;

/** Direction-agnostic fill (fills from the inline start under RTL/LTR). */
export const Default: Story = {};

/** Range of values — the thin wrapper only exposes `value` and `className`. */
export const Values: Story = {
  render: () => (
    <div className="w-72 space-y-4">
      {[0, 40, 100].map((value) => (
        <div key={value} className="space-y-1">
          <p className="text-xs text-muted-foreground">{value}%</p>
          <ProgressBar value={value} />
        </div>
      ))}
    </div>
  ),
};

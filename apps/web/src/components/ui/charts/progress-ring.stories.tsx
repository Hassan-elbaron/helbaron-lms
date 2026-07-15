import type { Meta, StoryObj } from "@storybook/react";
import { ProgressRing } from "@/components/ui/charts";

const meta = {
  title: "Charts/ProgressRing",
  component: ProgressRing,
  parameters: { layout: "padded" },
  args: {
    value: 72,
    label: "Course completion: 72%",
  },
  argTypes: {
    value: { control: { type: "number", min: 0, max: 100 } },
    max: { control: { type: "number", min: 1 } },
    thickness: { control: { type: "number", min: 4, max: 40 } },
    colorIndex: { control: { type: "number", min: 0, max: 7 } },
    size: { control: { type: "number", min: 48, max: 240 } },
  },
} satisfies Meta<typeof ProgressRing>;

export default meta;
type Story = StoryObj<typeof meta>;

/** Single-value progress ring; the center defaults to the rounded percentage. */
export const Default: Story = {};

/** Nearly-complete ring in an alternate colour. */
export const AlmostDone: Story = {
  args: { value: 94, label: "Course completion: 94%", colorIndex: 2 },
};

/** Non-percentage scale via `max`, with custom center content. */
export const CustomScale: Story = {
  args: {
    value: 18,
    max: 24,
    label: "18 of 24 lessons complete",
    size: 140,
    thickness: 14,
    centerContent: (
      <>
        <span className="text-xl font-semibold tabular-nums">18/24</span>
        <span className="text-caption text-muted-foreground">lessons</span>
      </>
    ),
  },
};

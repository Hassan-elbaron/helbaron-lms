import type { Meta, StoryObj } from "@storybook/react";
import { Progress } from "@/components/ui/progress";

const variants = ["default", "success", "warning", "destructive", "info"] as const;

const meta = {
  title: "Primitives/Progress",
  component: Progress,
  tags: ["autodocs"],
  argTypes: {
    value: { control: { type: "range", min: 0, max: 100, step: 1 } },
    variant: {
      control: { type: "select" },
      options: variants,
    },
    label: { control: { type: "text" } },
  },
  args: {
    value: 50,
    variant: "default",
    label: "Progress",
    className: "w-72",
  },
} satisfies Meta<typeof Progress>;

export default meta;
type Story = StoryObj<typeof meta>;

export const Default: Story = {};

export const Values: Story = {
  render: () => (
    <div className="w-72 space-y-4">
      {[0, 50, 100].map((value) => (
        <div key={value} className="space-y-1">
          <p className="text-xs text-muted-foreground">{value}%</p>
          <Progress value={value} label={`Progress ${value}%`} />
        </div>
      ))}
    </div>
  ),
};

export const Variants: Story = {
  render: () => (
    <div className="w-72 space-y-4">
      {variants.map((variant) => (
        <div key={variant} className="space-y-1">
          <p className="text-xs text-muted-foreground">{variant}</p>
          <Progress value={65} variant={variant} label={`${variant} progress`} />
        </div>
      ))}
    </div>
  ),
};

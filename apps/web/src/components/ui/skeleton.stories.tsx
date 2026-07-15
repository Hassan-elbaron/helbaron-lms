import type { Meta, StoryObj } from "@storybook/react";
import { Skeleton, SkeletonText } from "@/components/ui/skeleton";

const variants = ["block", "text", "avatar", "card", "table-row"] as const;

const meta = {
  title: "Primitives/Skeleton",
  component: Skeleton,
  tags: ["autodocs"],
  argTypes: {
    variant: {
      control: { type: "select" },
      options: variants,
    },
  },
  args: {
    variant: "text",
    className: "w-64",
  },
} satisfies Meta<typeof Skeleton>;

export default meta;
type Story = StoryObj<typeof meta>;

export const Default: Story = {};

export const Shapes: Story = {
  render: () => (
    <div className="flex flex-col gap-4">
      <div className="flex items-center gap-3">
        {/* circle */}
        <Skeleton className="size-12 rounded-full" />
        {/* line */}
        <Skeleton className="h-4 w-40" />
      </div>
      {/* block */}
      <Skeleton className="h-24 w-64" />
    </div>
  ),
};

export const Variants: Story = {
  render: () => (
    <div className="flex w-72 flex-col gap-4">
      {variants.map((variant) => (
        <div key={variant} className="space-y-1">
          <p className="text-xs text-muted-foreground">{variant}</p>
          <Skeleton variant={variant} />
        </div>
      ))}
    </div>
  ),
};

export const MultiLineText: Story = {
  render: () => <SkeletonText lines={4} className="w-72" />,
};

export const CardPlaceholder: Story = {
  render: () => (
    <div className="w-72 space-y-3 rounded-lg border p-4">
      <div className="flex items-center gap-3">
        <Skeleton variant="avatar" />
        <div className="flex-1 space-y-2">
          <Skeleton className="h-4 w-32" />
          <Skeleton className="h-3 w-20" />
        </div>
      </div>
      <Skeleton variant="card" />
      <SkeletonText lines={3} />
    </div>
  ),
};

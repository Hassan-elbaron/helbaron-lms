import type { Meta, StoryObj } from "@storybook/react";
import { Badge } from "@/components/ui/badge";

const variants = [
  "default",
  "secondary",
  "destructive",
  "success",
  "warning",
  "info",
  "outline",
] as const;

const meta = {
  title: "Primitives/Badge",
  component: Badge,
  tags: ["autodocs"],
  argTypes: {
    variant: {
      control: { type: "select" },
      options: variants,
    },
  },
  args: {
    variant: "default",
    children: "Badge",
  },
} satisfies Meta<typeof Badge>;

export default meta;
type Story = StoryObj<typeof meta>;

export const Default: Story = {};

export const AllVariants: Story = {
  render: () => (
    <div className="flex flex-wrap items-center gap-2">
      {variants.map((variant) => (
        <Badge key={variant} variant={variant}>
          {variant}
        </Badge>
      ))}
    </div>
  ),
};

export const StatusExamples: Story = {
  render: () => (
    <div className="flex flex-wrap items-center gap-2">
      <Badge variant="success">Published</Badge>
      <Badge variant="warning">Pending review</Badge>
      <Badge variant="destructive">Archived</Badge>
      <Badge variant="info">New</Badge>
      <Badge variant="secondary">Draft</Badge>
      <Badge variant="outline">Beta</Badge>
    </div>
  ),
};

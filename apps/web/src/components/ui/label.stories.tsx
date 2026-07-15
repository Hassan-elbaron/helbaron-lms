import type { Meta, StoryObj } from "@storybook/react";
import { Label } from "@/components/ui/label";
import { Input } from "@/components/ui/input";

const meta = {
  title: "Primitives/Label",
  component: Label,
  tags: ["autodocs"],
  args: {
    children: "Email address",
  },
} satisfies Meta<typeof Label>;

export default meta;
type Story = StoryObj<typeof meta>;

export const Default: Story = {};

export const WithInput: Story = {
  render: () => (
    <div className="space-y-1.5">
      <Label htmlFor="email">Email address</Label>
      <Input id="email" type="email" placeholder="you@example.com" />
    </div>
  ),
};

export const Required: Story = {
  render: () => (
    <div className="space-y-1.5">
      <Label htmlFor="name">
        Full name
        <span aria-hidden className="ms-0.5 text-destructive">
          *
        </span>
        <span className="sr-only"> (required)</span>
      </Label>
      <Input id="name" required placeholder="Ada Lovelace" />
    </div>
  ),
};

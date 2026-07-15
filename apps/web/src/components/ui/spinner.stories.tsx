import type { Meta, StoryObj } from "@storybook/react";
import { Spinner } from "@/components/ui/spinner";

const sizes = ["sm", "md", "lg", "icon"] as const;

const meta = {
  title: "Primitives/Spinner",
  component: Spinner,
  tags: ["autodocs"],
  argTypes: {
    size: {
      control: { type: "inline-radio" },
      options: sizes,
    },
    label: { control: { type: "text" } },
  },
  args: {
    size: "md",
    label: "Loading",
  },
} satisfies Meta<typeof Spinner>;

export default meta;
type Story = StoryObj<typeof meta>;

export const Default: Story = {};

export const Sizes: Story = {
  render: () => (
    <div className="flex items-center gap-6">
      {sizes.map((size) => (
        <div key={size} className="flex flex-col items-center gap-2">
          <Spinner size={size} label={`Loading (${size})`} />
          <span className="text-xs text-muted-foreground">{size}</span>
        </div>
      ))}
    </div>
  ),
};

export const OnColoredSurface: Story = {
  render: () => (
    <div className="flex items-center gap-2 rounded-md bg-primary px-4 py-2 text-primary-foreground">
      <Spinner size="sm" label="Saving" />
      <span className="text-sm">Saving…</span>
    </div>
  ),
};

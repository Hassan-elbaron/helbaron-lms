import type { Meta, StoryObj } from "@storybook/react";
import { Textarea } from "@/components/ui/textarea";

const meta = {
  title: "Primitives/Textarea",
  component: Textarea,
  tags: ["autodocs"],
  argTypes: {
    disabled: { control: "boolean" },
    readOnly: { control: "boolean" },
    rows: { control: "number" },
    placeholder: { control: "text" },
  },
  args: {
    placeholder: "Write your message...",
    rows: 4,
  },
} satisfies Meta<typeof Textarea>;

export default meta;
type Story = StoryObj<typeof meta>;

export const Default: Story = {};

export const WithValue: Story = {
  args: { defaultValue: "The quick brown fox jumps over the lazy dog." },
};

export const Disabled: Story = {
  args: { disabled: true, defaultValue: "Cannot edit this content." },
};

export const ReadOnly: Story = {
  args: { readOnly: true, defaultValue: "Read-only content." },
};

export const Invalid: Story = {
  args: { "aria-invalid": true, defaultValue: "This value is invalid." },
};

export const Rows: Story = {
  args: { rows: 8, placeholder: "Taller textarea (8 rows)" },
};

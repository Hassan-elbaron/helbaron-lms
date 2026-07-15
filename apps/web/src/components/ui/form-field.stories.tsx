import type { Meta, StoryObj } from "@storybook/react";
import { FormField } from "@/components/ui/form-field";
import { Input } from "@/components/ui/input";

const meta = {
  title: "Forms/FormField",
  component: FormField,
  tags: ["autodocs"],
  argTypes: {
    required: { control: "boolean" },
    loading: { control: "boolean" },
    hideLabel: { control: "boolean" },
    hint: { control: "text" },
    error: { control: "text" },
    success: { control: "text" },
  },
  // Default args satisfy FormField's required `label`/`children`. The render-only `AllStates`
  // story inherits them (its render ignores args); the arg-based stories override as needed.
  args: {
    label: "Field label",
    children: <Input placeholder="Type here..." />,
  },
} satisfies Meta<typeof FormField>;

export default meta;
type Story = StoryObj<typeof meta>;

export const Default: Story = {
  args: {
    label: "Email address",
    children: <Input type="email" placeholder="you@example.com" />,
  },
};

export const WithHint: Story = {
  args: {
    label: "Password",
    hint: "Use at least 8 characters.",
    children: <Input type="password" />,
  },
};

export const WithError: Story = {
  args: {
    label: "Email address",
    error: "Please enter a valid email address.",
    children: <Input type="email" defaultValue="not-an-email" />,
  },
};

export const WithSuccess: Story = {
  args: {
    label: "Username",
    success: "This username is available.",
    children: <Input defaultValue="ada" />,
  },
};

export const Required: Story = {
  args: {
    label: "Full name",
    required: true,
    hint: "As it appears on your ID.",
    children: <Input placeholder="Ada Lovelace" />,
  },
};

export const Disabled: Story = {
  args: {
    label: "Account ID",
    children: <Input defaultValue="ACC-000123" disabled />,
  },
};

export const AllStates: Story = {
  render: () => (
    <div className="max-w-sm space-y-6">
      <FormField label="Default field">
        <Input placeholder="Type here..." />
      </FormField>
      <FormField label="With hint" hint="We'll never share this.">
        <Input type="email" placeholder="you@example.com" />
      </FormField>
      <FormField label="Required" required>
        <Input placeholder="Required value" />
      </FormField>
      <FormField label="With error" error="This field is required.">
        <Input aria-invalid />
      </FormField>
      <FormField label="With success" success="Looks good!">
        <Input defaultValue="ada" />
      </FormField>
      <FormField label="Disabled">
        <Input defaultValue="Cannot edit" disabled />
      </FormField>
    </div>
  ),
};

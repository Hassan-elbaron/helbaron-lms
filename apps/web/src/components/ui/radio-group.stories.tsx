import type { Meta, StoryObj } from "@storybook/react";
import { RadioGroup, RadioGroupItem } from "@/components/ui/radio-group";
import { Label } from "@/components/ui/label";

const meta = {
  title: "Primitives/RadioGroup",
  component: RadioGroup,
  tags: ["autodocs"],
} satisfies Meta<typeof RadioGroup>;

export default meta;
type Story = StoryObj<typeof meta>;

export const Default: Story = {
  render: () => (
    <RadioGroup defaultValue="monthly">
      <div className="flex items-center gap-2">
        <RadioGroupItem id="plan-monthly" value="monthly" />
        <Label htmlFor="plan-monthly">Monthly billing</Label>
      </div>
      <div className="flex items-center gap-2">
        <RadioGroupItem id="plan-yearly" value="yearly" />
        <Label htmlFor="plan-yearly">Yearly billing</Label>
      </div>
      <div className="flex items-center gap-2">
        <RadioGroupItem id="plan-lifetime" value="lifetime" />
        <Label htmlFor="plan-lifetime">Lifetime</Label>
      </div>
    </RadioGroup>
  ),
};

export const WithDisabledOption: Story = {
  render: () => (
    <RadioGroup defaultValue="standard">
      <div className="flex items-center gap-2">
        <RadioGroupItem id="tier-standard" value="standard" />
        <Label htmlFor="tier-standard">Standard</Label>
      </div>
      <div className="flex items-center gap-2">
        <RadioGroupItem id="tier-pro" value="pro" />
        <Label htmlFor="tier-pro">Pro</Label>
      </div>
      <div className="flex items-center gap-2">
        <RadioGroupItem id="tier-enterprise" value="enterprise" disabled />
        <Label htmlFor="tier-enterprise">Enterprise (contact sales)</Label>
      </div>
    </RadioGroup>
  ),
};

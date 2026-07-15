import type { Meta, StoryObj } from "@storybook/react";
import { I18nProvider } from "@/lib/i18n/i18n-context";
import { ComingSoon } from "@/components/states/coming-soon";

const meta = {
  title: "States/ComingSoon",
  component: ComingSoon,
  tags: ["autodocs"],
  decorators: [(Story: () => import("react").ReactElement) => (
    <I18nProvider>
      {Story()}
    </I18nProvider>
  )],
  argTypes: {
    title: { control: { type: "text" } },
    eyebrow: { control: { type: "text" } },
    icon: {
      control: { type: "select" },
      options: ["LayoutGrid", "BarChart3", "Users", "Headset", "FileText", "Award"],
    },
  },
  args: {
    title: "Instructor Analytics",
    eyebrow: "Instructor",
    icon: "BarChart3",
  },
} satisfies Meta<typeof ComingSoon>;

export default meta;
type Story = StoryObj<typeof meta>;

/** Honest placeholder for an area whose backend isn't built yet. */
export const Default: Story = {};

/** Without an eyebrow or icon tile. */
export const Minimal: Story = {
  args: { title: "Community Forums", eyebrow: undefined, icon: undefined },
};

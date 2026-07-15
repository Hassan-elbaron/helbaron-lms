import type { Meta, StoryObj } from "@storybook/react";
import { Breadcrumb } from "@/components/ui/breadcrumb";
import { I18nProvider } from "@/lib/i18n/i18n-context";

// Breadcrumb reads text direction from useI18n(), so it must be wrapped in I18nProvider.
const meta = {
  title: "Primitives/Breadcrumb",
  component: Breadcrumb,
  tags: ["autodocs"],
  decorators: [
    (Story: () => import("react").ReactElement) => (
      <I18nProvider>
        {Story()}
      </I18nProvider>
    ),
  ],
} satisfies Meta<typeof Breadcrumb>;

export default meta;
type Story = StoryObj<typeof meta>;

export const Default: Story = {
  args: {
    items: [
      { label: "Home", href: "/" },
      { label: "Courses", href: "/courses" },
      { label: "Web Development", href: "/courses/web" },
      { label: "Advanced TypeScript" },
    ],
  },
};

export const TwoLevels: Story = {
  args: {
    items: [
      { label: "Dashboard", href: "/dashboard" },
      { label: "Settings" },
    ],
  },
};

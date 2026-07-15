import type { Meta, StoryObj } from "@storybook/react";
import { PageHeader } from "@/components/student/page-header";
import { Button } from "@/components/ui/button";

const meta = {
  title: "Widgets/PageHeader",
  component: PageHeader,
  tags: ["autodocs"],
  parameters: { layout: "fullscreen" },
  argTypes: {
    title: { control: { type: "text" } },
    subtitle: { control: { type: "text" } },
    eyebrow: { control: { type: "text" } },
    icon: {
      control: { type: "select" },
      options: [
        "LayoutDashboard", "GraduationCap", "PlayCircle", "Award", "Bell",
        "User", "Users", "BarChart3", "FileText", "LayoutGrid", "ShoppingCart",
      ],
    },
    action: { control: false },
  },
  args: {
    title: "My Learning",
    subtitle: "Continue where you left off across 12 enrolled courses.",
    eyebrow: "Student",
    icon: "GraduationCap",
  },
} satisfies Meta<typeof PageHeader>;

export default meta;
type Story = StoryObj<typeof meta>;

/** Full header: eyebrow + icon tile + serif title + subtitle. */
export const Default: Story = {};

/** With a trailing action slot. */
export const WithAction: Story = {
  args: {
    title: "Certificates",
    subtitle: "Download and share your earned certificates.",
    eyebrow: "Achievements",
    icon: "Award",
    action: <Button>Verify a certificate</Button>,
  },
};

/** Title only — no eyebrow, icon, or subtitle. */
export const TitleOnly: Story = {
  args: { title: "Notifications", subtitle: undefined, eyebrow: undefined, icon: undefined },
};

import type { Meta, StoryObj } from "@storybook/react";
import { Users, GraduationCap, Award, TrendingUp } from "lucide-react";
import { StatCard } from "@/components/student/stat-card";

const meta = {
  title: "Widgets/StatCard",
  component: StatCard,
  tags: ["autodocs"],
  argTypes: {
    label: { control: { type: "text" } },
    value: { control: { type: "text" } },
    icon: { control: false },
  },
  args: {
    label: "Enrolled courses",
    value: 12,
    icon: GraduationCap,
  },
} satisfies Meta<typeof StatCard>;

export default meta;
type Story = StoryObj<typeof meta>;

/** Default stat with an icon tile. */
export const Default: Story = {};

/** Numeric value formatted upstream (thousands separator). */
export const LargeCount: Story = {
  args: { label: "Active learners", value: "12,540", icon: Users },
};

/** Without an icon. */
export const NoIcon: Story = {
  args: { label: "Certificates issued", value: 340, icon: undefined },
};

/** A grid of stat cards, as used on the student dashboard. */
export const DashboardGrid: Story = {
  render: () => (
    <div className="grid w-full max-w-3xl grid-cols-2 gap-4 lg:grid-cols-4">
      <StatCard label="Enrolled courses" value={12} icon={GraduationCap} />
      <StatCard label="Active learners" value="12,540" icon={Users} />
      <StatCard label="Certificates" value={340} icon={Award} />
      <StatCard label="Completion rate" value="87%" icon={TrendingUp} />
    </div>
  ),
};

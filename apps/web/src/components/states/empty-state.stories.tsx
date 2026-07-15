import type { Meta, StoryObj } from "@storybook/react";
import { BookOpen } from "lucide-react";
import { I18nProvider } from "@/lib/i18n/i18n-context";
import { EmptyState } from "@/components/states/empty-state";
import { Button } from "@/components/ui/button";

const meta = {
  title: "States/Empty",
  component: EmptyState,
  tags: ["autodocs"],
  decorators: [(Story: () => import("react").ReactElement) => (
    <I18nProvider>
      {Story()}
    </I18nProvider>
  )],
  argTypes: {
    title: { control: { type: "text" } },
    description: { control: { type: "text" } },
    icon: { control: false },
    action: { control: false },
    className: { control: false },
  },
} satisfies Meta<typeof EmptyState>;

export default meta;
type Story = StoryObj<typeof meta>;

/** Default empty surface — localized `common.empty` title and the Inbox icon. */
export const Default: Story = {};

/** Title + description, e.g. an empty course catalogue. */
export const WithDescription: Story = {
  args: {
    title: "No courses yet",
    description: "Browse the catalogue to enrol in your first course.",
  },
};

/** Custom icon and a primary action to recover from the empty state. */
export const WithAction: Story = {
  args: {
    title: "No enrolled courses",
    description: "You haven't enrolled in any courses. Explore the catalogue to get started.",
    icon: <BookOpen className="size-8" aria-hidden />,
    action: <Button size="sm">Browse catalogue</Button>,
  },
};

import type { Meta, StoryObj } from "@storybook/react";
import { I18nProvider } from "@/lib/i18n/i18n-context";
import { CourseProgressCard } from "@/components/student/course-progress-card";

const meta = {
  title: "Widgets/CourseProgressCard",
  component: CourseProgressCard,
  tags: ["autodocs"],
  decorators: [(Story: () => import("react").ReactElement) => (
    <I18nProvider>
      <div className="w-80">
        {Story()}
      </div>
    </I18nProvider>
  )],
  argTypes: {
    title: { control: { type: "text" } },
    progress: { control: { type: "range", min: 0, max: 100, step: 1 } },
    status: { control: { type: "text" } },
    subtitle: { control: { type: "text" } },
    continueHref: { control: false },
    continueLabel: { control: { type: "text" } },
  },
  args: {
    title: "Introduction to Islamic Finance",
    progress: 45,
    subtitle: "12 lessons · 4h 30m",
    status: "In progress",
  },
} satisfies Meta<typeof CourseProgressCard>;

export default meta;
type Story = StoryObj<typeof meta>;

/** Partially completed course. */
export const InProgress: Story = {};

/** Just started. */
export const JustStarted: Story = {
  args: {
    title: "Data Science with Python",
    subtitle: "28 lessons · 11h",
    progress: 8,
    status: "In progress",
  },
};

/** Completed course — badge turns `success` and the CTA switches to the outline variant. */
export const Completed: Story = {
  args: {
    title: "Arabic Calligraphy Fundamentals",
    subtitle: "9 lessons · 3h",
    progress: 100,
    status: "Completed",
  },
};

/** Without a status badge or subtitle. */
export const Minimal: Story = {
  args: {
    title: "Foundations of Project Management",
    progress: 62,
    status: undefined,
    subtitle: undefined,
  },
};

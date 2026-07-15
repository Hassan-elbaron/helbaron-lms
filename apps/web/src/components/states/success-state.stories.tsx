import type { Meta, StoryObj } from "@storybook/react";
import { I18nProvider } from "@/lib/i18n/i18n-context";
import { SuccessState } from "@/components/states/success-state";
import { Button } from "@/components/ui/button";

const meta = {
  title: "States/Success",
  component: SuccessState,
  tags: ["autodocs"],
  decorators: [(Story: () => import("react").ReactElement) => (
    <I18nProvider>
      {Story()}
    </I18nProvider>
  )],
  argTypes: {
    title: { control: { type: "text" } },
    message: { control: { type: "text" } },
    action: { control: false },
    className: { control: false },
  },
} satisfies Meta<typeof SuccessState>;

export default meta;
type Story = StoryObj<typeof meta>;

/** Default success surface — localized `common.success` title. */
export const Default: Story = {};

/** With a confirming message, e.g. after completing a course. */
export const WithMessage: Story = {
  args: {
    title: "Enrollment complete",
    message: "You're enrolled in ‘Introduction to Islamic Finance’. It's now in your dashboard.",
  },
};

/** With a follow-up action to continue the journey. */
export const WithAction: Story = {
  args: {
    title: "Certificate issued",
    message: "Your certificate for ‘Arabic Calligraphy Fundamentals’ is ready to download.",
    action: <Button size="sm">View certificate</Button>,
  },
};

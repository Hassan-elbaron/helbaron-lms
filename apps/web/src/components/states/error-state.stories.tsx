import type { Meta, StoryObj } from "@storybook/react";
import { I18nProvider } from "@/lib/i18n/i18n-context";
import { ErrorState } from "@/components/states/error-state";

const meta = {
  title: "States/Error",
  component: ErrorState,
  tags: ["autodocs"],
  decorators: [(Story: () => import("react").ReactElement) => (
    <I18nProvider>
      {Story()}
    </I18nProvider>
  )],
  argTypes: {
    title: { control: { type: "text" } },
    message: { control: { type: "text" } },
    onRetry: { control: false },
    className: { control: false },
  },
} satisfies Meta<typeof ErrorState>;

export default meta;
type Story = StoryObj<typeof meta>;

/** Default error surface — localized `common.error` title, no retry affordance. */
export const Default: Story = {};

/** With a descriptive message but no retry handler. */
export const WithMessage: Story = {
  args: {
    title: "Couldn't load your dashboard",
    message: "The analytics service is temporarily unavailable. Please try again shortly.",
  },
};

/** Providing `onRetry` renders the localized retry button. */
export const WithRetry: Story = {
  args: {
    title: "Failed to load courses",
    message: "We couldn't reach the server. Check your connection and retry.",
    onRetry: () => alert("Retrying…"),
  },
};

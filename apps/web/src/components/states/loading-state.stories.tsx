import type { Meta, StoryObj } from "@storybook/react";
import { I18nProvider } from "@/lib/i18n/i18n-context";
import { LoadingState, PageLoading } from "@/components/states/loading-state";

const meta = {
  title: "States/Loading",
  component: LoadingState,
  tags: ["autodocs"],
  decorators: [(Story: () => import("react").ReactElement) => (
    <I18nProvider>
      {Story()}
    </I18nProvider>
  )],
  argTypes: {
    label: { control: { type: "text" } },
    className: { control: false },
  },
} satisfies Meta<typeof LoadingState>;

export default meta;
type Story = StoryObj<typeof meta>;

/** Default inline loader — falls back to the localized `common.loading` label. */
export const Default: Story = {};

/** A custom label, e.g. while enrolling in a course. */
export const WithLabel: Story = {
  args: { label: "Loading your courses…" },
};

/** Full-viewport loader used by route guards while auth resolves. */
export const FullPage: Story = {
  render: () => <PageLoading />,
};

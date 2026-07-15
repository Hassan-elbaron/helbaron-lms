import { useState } from "react";
import type { Meta, StoryObj } from "@storybook/react";
import { I18nProvider } from "@/lib/i18n/i18n-context";
import { Pagination } from "@/components/ui/pagination";

const meta = {
  title: "Data/Pagination",
  component: Pagination,
  parameters: { layout: "padded" },
  decorators: [
    (Story: () => import("react").ReactElement) => (
      <I18nProvider>
        {Story()}
      </I18nProvider>
    ),
  ],
  argTypes: {
    page: { control: { type: "number", min: 1 } },
    lastPage: { control: { type: "number", min: 1 } },
  },
} satisfies Meta<typeof Pagination>;

export default meta;
type Story = StoryObj<typeof meta>;

/** Interactive, controlled pagination. Prev is disabled on the first page, Next on the last. */
function InteractivePagination(args: import("react").ComponentProps<typeof Pagination>) {
  const [page, setPage] = useState(args.page);
  return <Pagination {...args} page={page} onPageChange={setPage} />;
}

export const Interactive: Story = {
  args: { page: 3, lastPage: 10, onPageChange: () => {} },
  render: (args: import("react").ComponentProps<typeof Pagination>) => <InteractivePagination {...args} />,
};

/** First page — the Prev button is disabled. */
export const FirstPage: Story = {
  args: { page: 1, lastPage: 10, onPageChange: () => {} },
};

/** Middle page — both Prev and Next are enabled. */
export const MiddlePage: Story = {
  args: { page: 5, lastPage: 10, onPageChange: () => {} },
};

/** Last page — the Next button is disabled. */
export const LastPage: Story = {
  args: { page: 10, lastPage: 10, onPageChange: () => {} },
};

/** Only two pages available. */
export const FewPages: Story = {
  args: { page: 1, lastPage: 2, onPageChange: () => {} },
};

/** A large range of pages. */
export const ManyPages: Story = {
  args: { page: 42, lastPage: 100, onPageChange: () => {} },
};

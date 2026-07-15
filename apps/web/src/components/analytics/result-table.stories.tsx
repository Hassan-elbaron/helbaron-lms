import type { Meta, StoryObj } from "@storybook/react";
import { I18nProvider } from "@/lib/i18n/i18n-context";
import { ResultTable } from "@/components/analytics/result-table";

const courseRows: Record<string, unknown>[] = [
  { Course: "Introduction to Islamic Finance", Enrollments: 1240, Completion: "78%", Rating: 4.7 },
  { Course: "Data Science with Python", Enrollments: 3180, Completion: "64%", Rating: 4.5 },
  { Course: "Arabic Calligraphy Fundamentals", Enrollments: 860, Completion: "91%", Rating: 4.9 },
  { Course: "Foundations of Project Management", Enrollments: 2015, Completion: "72%", Rating: 4.6 },
  { Course: "Digital Marketing Essentials", Enrollments: 1770, Completion: "58%", Rating: 4.3 },
];

const meta = {
  title: "Widgets/ResultTable",
  component: ResultTable,
  tags: ["autodocs"],
  decorators: [(Story: () => import("react").ReactElement) => (
    <I18nProvider>
      <div className="w-full max-w-3xl">
        {Story()}
      </div>
    </I18nProvider>
  )],
  argTypes: {
    rows: { control: false },
  },
  args: {
    rows: courseRows,
  },
} satisfies Meta<typeof ResultTable>;

export default meta;
type Story = StoryObj<typeof meta>;

/** Columns are inferred from the union of row keys; every column is sortable. */
export const Default: Story = {};

/** No rows → short-circuits to the canonical empty state. */
export const Empty: Story = {
  args: { rows: [] },
};

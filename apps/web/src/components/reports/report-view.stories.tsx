import type { Meta, StoryObj } from "@storybook/react";
import { useState } from "react";
import { I18nProvider } from "@/lib/i18n/i18n-context";
import { ReportView } from "@/components/reports/report-view";

/** Wrapper that owns the `page` state so the pager is interactive in Storybook. */
function PagedReport({ payload }: { payload: Record<string, unknown> }) {
  const [page, setPage] = useState(1);
  return (
    <div className="w-full max-w-4xl">
      <ReportView payload={payload} meta={{ from: "2026-01-01", to: "2026-06-30" }} page={page} onPageChange={setPage} />
    </div>
  );
}

const fullPayload: Record<string, unknown> = {
  summary: {
    total_enrollments: 6910,
    revenue_minor: 4875000,
    completion_rate: 71,
    active_learners: 4210,
  },
  steps: [
    { step: "visited_catalog", count: 12400, percentage: 100 },
    { step: "viewed_course", count: 8300, percentage: 67 },
    { step: "started_checkout", count: 3100, percentage: 25 },
    { step: "enrolled", count: 1980, percentage: 16 },
  ],
  enrollments_by_month: [
    { period: "2026-01", value: 820 },
    { period: "2026-02", value: 960 },
    { period: "2026-03", value: 1180 },
    { period: "2026-04", value: 1090 },
    { period: "2026-05", value: 1340 },
    { period: "2026-06", value: 1520 },
  ],
  rows: [
    { course: "Introduction to Islamic Finance", enrollments: 1240, revenue_minor: 992000 },
    { course: "Data Science with Python", enrollments: 3180, revenue_minor: 2544000 },
    { course: "Arabic Calligraphy Fundamentals", enrollments: 860, revenue_minor: 344000 },
  ],
  pagination: { current_page: 1, last_page: 4, total: 42 },
};

const meta = {
  title: "Widgets/ReportView",
  component: ReportView,
  tags: ["autodocs"],
  decorators: [(Story: () => import("react").ReactElement) => (
    <I18nProvider>
      {Story()}
    </I18nProvider>
  )],
  parameters: {
    docs: {
      description: {
        component:
          "Shape-driven report renderer: `summary` → KPI grid, `steps` → funnel, `{period,value}` arrays → bar charts, other object arrays → data tables. The `rows` table gets a pager wired to parent page state.",
      },
    },
  },
} satisfies Meta<typeof ReportView>;

export default meta;
type Story = StoryObj<typeof meta>;

/** Full report: summary KPIs, funnel, monthly series, and a paginated table. */
export const FullReport: Story = {
  render: () => <PagedReport payload={fullPayload} />,
};

/** Summary-only payload. */
export const SummaryOnly: Story = {
  render: () => (
    <PagedReport
      payload={{
        summary: { total_enrollments: 6910, revenue_minor: 4875000, completion_rate: 71, active_learners: 4210 },
      }}
    />
  ),
};

/** Funnel-only payload. */
export const FunnelOnly: Story = {
  render: () => (
    <PagedReport
      payload={{
        steps: [
          { step: "visited_catalog", count: 12400, percentage: 100 },
          { step: "viewed_course", count: 8300, percentage: 67 },
          { step: "started_checkout", count: 3100, percentage: 25 },
          { step: "enrolled", count: 1980, percentage: 16 },
        ],
      }}
    />
  ),
};

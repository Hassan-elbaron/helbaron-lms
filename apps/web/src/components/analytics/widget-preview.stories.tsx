import type { Meta, StoryObj } from "@storybook/react";
import { I18nProvider } from "@/lib/i18n/i18n-context";
import type { DashboardWidget } from "@/lib/analytics/api";
import { WidgetPreview } from "@/components/analytics/widget-preview";

const metricWidget: DashboardWidget = {
  id: "w1",
  title: "Total Enrollments",
  metric_key: "enrollments",
  type: "metric",
  config: null,
};

const chartWidget: DashboardWidget = {
  id: "w2",
  title: "Revenue Trend",
  metric_key: "revenue",
  type: "line_chart",
  config: null,
};

const noMetricWidget: DashboardWidget = {
  id: "w3",
  title: "Welcome Note",
  metric_key: null,
  type: "text",
  config: null,
};

const meta = {
  title: "Widgets/WidgetPreview",
  component: WidgetPreview,
  tags: ["autodocs"],
  decorators: [(Story: () => import("react").ReactElement) => (
    <I18nProvider>
      <div className="w-72">
        {Story()}
      </div>
    </I18nProvider>
  )],
  argTypes: {
    widget: { control: false },
  },
  args: {
    widget: metricWidget,
  },
} satisfies Meta<typeof WidgetPreview>;

export default meta;
type Story = StoryObj<typeof meta>;

/** A metric widget bound to a metric key. */
export const MetricWidget: Story = {};

/** A chart widget (type badge reflects `line_chart`). */
export const ChartWidget: Story = {
  args: { widget: chartWidget },
};

/** No metric key → shows the "no metric" fallback line. */
export const NoMetric: Story = {
  args: { widget: noMetricWidget },
};

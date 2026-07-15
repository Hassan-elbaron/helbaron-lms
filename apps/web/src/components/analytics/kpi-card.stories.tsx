import type { Meta, StoryObj } from "@storybook/react";
import { Users, DollarSign, GraduationCap } from "lucide-react";
import { I18nProvider } from "@/lib/i18n/i18n-context";
import type { Kpi } from "@/lib/analytics/api";
import { KpiCard } from "@/components/analytics/kpi-card";

const enrollmentSeries = [
  { period: "2026-01", value: 820 },
  { period: "2026-02", value: 960 },
  { period: "2026-03", value: 1180 },
  { period: "2026-04", value: 1090 },
  { period: "2026-05", value: 1340 },
  { period: "2026-06", value: 1520 },
];

const enrollmentsKpi: Kpi = {
  metric: "enrollments",
  unit: "count",
  total: 6910,
  series: enrollmentSeries,
};

const revenueKpi: Kpi = {
  metric: "revenue",
  unit: "currency_minor",
  total: 4875000, // 48,750.00 in minor units
  series: [
    { period: "2026-01", value: 620000 },
    { period: "2026-02", value: 710000 },
    { period: "2026-03", value: 845000 },
    { period: "2026-04", value: 905000 },
    { period: "2026-05", value: 980000 },
    { period: "2026-06", value: 815000 },
  ],
};

const meta = {
  title: "Widgets/KpiCard",
  component: KpiCard,
  tags: ["autodocs"],
  decorators: [(Story: () => import("react").ReactElement) => (
    <I18nProvider>
      <div className="w-72">
        {Story()}
      </div>
    </I18nProvider>
  )],
  argTypes: {
    label: { control: { type: "text" } },
    icon: { control: false },
    kpi: { control: false },
  },
  args: {
    kpi: enrollmentsKpi,
    label: "Total enrollments",
    icon: GraduationCap,
  },
} satisfies Meta<typeof KpiCard>;

export default meta;
type Story = StoryObj<typeof meta>;

/** Count metric with a multi-point trend sparkline. */
export const Enrollments: Story = {};

/** Currency metric — formatted via `formatMoney` for the active locale. */
export const Revenue: Story = {
  args: { kpi: revenueKpi, label: "Revenue", icon: DollarSign },
};

/** Single-point series → renders a "N pts" hint instead of a sparkline. */
export const SinglePoint: Story = {
  args: {
    kpi: { metric: "active_learners", unit: "count", total: 4210, series: [{ period: "2026-06", value: 4210 }] },
    label: "Active learners",
    icon: Users,
  },
};

/** No series data at all. */
export const NoSeries: Story = {
  args: {
    kpi: { metric: "active_learners", unit: "count", total: 4210, series: [] },
    label: "Active learners",
    icon: Users,
  },
};

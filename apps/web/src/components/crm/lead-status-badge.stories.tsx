import type { Meta, StoryObj } from "@storybook/react";
import { I18nProvider } from "@/lib/i18n/i18n-context";
import type { LeadStatus } from "@/lib/crm/api";
import { LeadStatusBadge } from "@/components/crm/lead-status-badge";

const statuses: LeadStatus[] = ["new", "working", "qualified", "converted", "lost"];

const meta = {
  title: "CRM/LeadStatusBadge",
  component: LeadStatusBadge,
  tags: ["autodocs"],
  decorators: [(Story: () => import("react").ReactElement) => (
    <I18nProvider>
      {Story()}
    </I18nProvider>
  )],
  argTypes: {
    status: { control: { type: "select" }, options: statuses },
  },
  args: { status: "new" },
} satisfies Meta<typeof LeadStatusBadge>;

export default meta;
type Story = StoryObj<typeof meta>;

/** New lead — Sparkles icon, `warning` badge. */
export const New: Story = { args: { status: "new" } };

/** Being worked — Loader icon, `secondary` badge. */
export const Working: Story = { args: { status: "working" } };

/** Qualified — CheckCircle icon, `default` badge. */
export const Qualified: Story = { args: { status: "qualified" } };

/** Converted — Trophy icon, `success` badge. */
export const Converted: Story = { args: { status: "converted" } };

/** Lost — XCircle icon, `destructive` badge. */
export const Lost: Story = { args: { status: "lost" } };

/** Every status the component supports, side by side. */
export const AllStatuses: Story = {
  render: () => (
    <div className="flex flex-wrap items-center gap-2">
      {statuses.map((status) => (
        <LeadStatusBadge key={status} status={status} />
      ))}
    </div>
  ),
};

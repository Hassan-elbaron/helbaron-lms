import type { Meta, StoryObj } from "@storybook/react";
import type { Category } from "@/lib/catalog/api";
import { CategoryCard } from "@/components/catalog/category-card";

const base: Category = {
  id: "cat_project_management",
  name: "Project Management",
  slug: "project-management",
  description: "Plan, execute, and deliver projects on time and on budget.",
  children: [
    { id: "cat_agile", name: "Agile & Scrum", slug: "agile-scrum" },
    { id: "cat_pmp", name: "PMP Prep", slug: "pmp-prep" },
    { id: "cat_risk", name: "Risk Management", slug: "risk-management" },
  ],
};

const meta = {
  title: "Catalog/CategoryCard",
  component: CategoryCard,
  tags: ["autodocs"],
  decorators: [(Story: () => import("react").ReactElement) => (
    <div className="w-96">
      {Story()}
    </div>
  )],
  args: { category: base },
} satisfies Meta<typeof CategoryCard>;

export default meta;
type Story = StoryObj<typeof meta>;

/** Category with a description and sub-category chips. */
export const Default: Story = {};

/** Arabic (RTL) category with Arabic children. */
export const Arabic: Story = {
  args: {
    category: {
      id: "cat_business_ai",
      name: "الذكاء الاصطناعي للأعمال",
      slug: "business-ai",
      description: "طبّق الذكاء الاصطناعي لاتخاذ قرارات أعمال أسرع وأذكى.",
      children: [
        { id: "cat_ai_ops", name: "أتمتة العمليات", slug: "ai-ops" },
        { id: "cat_ai_analytics", name: "التحليلات التنبؤية", slug: "predictive-analytics" },
      ],
    },
  },
};

/** No description and no children — just the icon + title header. */
export const Minimal: Story = {
  args: {
    category: {
      id: "cat_finance",
      name: "Finance & Analysis",
      slug: "finance-analysis",
      description: null,
      children: [],
    },
  },
};

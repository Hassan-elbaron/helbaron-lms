import type { Meta, StoryObj } from "@storybook/react";
import { I18nProvider } from "@/lib/i18n/i18n-context";
import type { CourseListItem } from "@/lib/catalog/api";
import { CourseCard } from "@/components/catalog/course-card";

const base: CourseListItem = {
  id: "crs_pm_foundations",
  title: "Project Management Foundations",
  slug: "project-management-foundations",
  subtitle: "Plan, run, and deliver projects the MENA way — from charter to close.",
  thumbnail_path: null,
  is_featured: false,
  level: "Beginner",
  language: "English",
  published_at: "2026-01-12T09:00:00Z",
};

const meta = {
  title: "Catalog/CourseCard",
  component: CourseCard,
  tags: ["autodocs"],
  decorators: [(Story: () => import("react").ReactElement) => (
    <I18nProvider>
      <div className="w-80">
        {Story()}
      </div>
    </I18nProvider>
  )],
  args: { course: base },
} satisfies Meta<typeof CourseCard>;

export default meta;
type Story = StoryObj<typeof meta>;

/** Standard catalog card: title, subtitle, level + language pills. */
export const Default: Story = {};

/** Featured course — renders the gold `warning` "Featured" badge next to the title. */
export const Featured: Story = {
  args: {
    course: {
      ...base,
      id: "crs_business_ai",
      title: "Business AI for Decision Makers",
      slug: "business-ai-for-decision-makers",
      subtitle: "Use AI to make faster, better calls — no code required.",
      is_featured: true,
      level: "Intermediate",
      language: "English",
    },
  },
};

/** Arabic (RTL) content — title, subtitle and level render in Arabic. */
export const Arabic: Story = {
  args: {
    course: {
      ...base,
      id: "crs_leadership_ar",
      title: "القيادة في بيئة العمل الحديثة",
      slug: "leadership-modern-workplace",
      subtitle: "قُد فرقك بثقة عبر التغيير والنمو في سوق المنطقة.",
      is_featured: true,
      level: "متوسط",
      language: "العربية",
    },
  },
};

/** Minimal — no subtitle, level, or language (all optional/nullable). */
export const Minimal: Story = {
  args: {
    course: {
      ...base,
      id: "crs_minimal",
      title: "Marketing Strategy Masterclass",
      slug: "marketing-strategy-masterclass",
      subtitle: null,
      level: null,
      language: null,
      is_featured: false,
    },
  },
};

import type { Meta, StoryObj } from "@storybook/react";
import { I18nProvider } from "@/lib/i18n/i18n-context";
import type { HomepageSection } from "@/lib/homepage/api";
import { CategoriesBlock } from "@/components/homepage/blocks/categories-block";

/**
 * CategoriesBlock — CMS categories grid. The heading/subheading come from
 * `section.content` ({ heading?, subheading? }) but the cards render from the
 * SERVER-RESOLVED `section.resolved.categories` (ResolvedCategory[]), NOT from content.
 * Renders nothing when `resolved.categories` is empty.
 */
const section: HomepageSection = {
  key: "categories",
  type: "categories",
  position: 1,
  content: {
    heading: { en: "Explore by field", ar: "استكشف حسب المجال" },
    subheading: {
      en: "Curated learning tracks built for the MENA workplace.",
      ar: "مسارات تعلّم مصمّمة لبيئة العمل في المنطقة.",
    },
  },
  resolved: {
    categories: [
      { id: "cat_business", slug: "business", href: "/categories/business", name: { en: "Business & Management", ar: "الأعمال والإدارة" }, description: { en: "Leadership, strategy, operations.", ar: "القيادة والاستراتيجية والعمليات." } },
      { id: "cat_tech", slug: "technology", href: "/categories/technology", name: { en: "Technology & Data", ar: "التقنية والبيانات" }, description: { en: "Software, cloud, analytics, AI.", ar: "البرمجيات والسحابة والتحليلات والذكاء الاصطناعي." } },
      { id: "cat_marketing", slug: "marketing", href: "/categories/marketing", name: { en: "Marketing & Growth", ar: "التسويق والنمو" }, description: { en: "Digital, brand, and performance.", ar: "الرقمي والعلامة والأداء." } },
      { id: "cat_finance", slug: "finance", href: "/categories/finance", name: { en: "Finance & Accounting", ar: "المالية والمحاسبة" }, description: { en: "Financial modeling and IFRS.", ar: "النمذجة المالية والمعايير الدولية." } },
      { id: "cat_design", slug: "design", href: "/categories/design", name: { en: "Design & UX", ar: "التصميم وتجربة المستخدم" }, description: { en: "Product, UI/UX, and research.", ar: "المنتج وواجهة المستخدم والبحث." } },
      { id: "cat_language", slug: "languages", href: "/categories/languages", name: { en: "Professional Languages", ar: "اللغات المهنية" }, description: { en: "Business English & Arabic.", ar: "الإنجليزية والعربية للأعمال." } },
      { id: "cat_hr", slug: "hr", href: "/categories/hr", name: { en: "HR & People", ar: "الموارد البشرية" }, description: { en: "Talent, culture, and L&D.", ar: "المواهب والثقافة والتطوير." } },
      { id: "cat_pm", slug: "project-management", href: "/categories/project-management", name: { en: "Project Management", ar: "إدارة المشاريع" }, description: { en: "Agile, PMP, and delivery.", ar: "أجايل وPMP والتسليم." } },
    ],
  },
};

const meta = {
  title: "Homepage Blocks/Categories",
  component: CategoriesBlock,
  parameters: { layout: "fullscreen" },
  tags: ["autodocs"],
  decorators: [(Story: () => import("react").ReactElement) => (
    <I18nProvider>
      {Story()}
    </I18nProvider>
  )],
  args: { section },
} satisfies Meta<typeof CategoriesBlock>;

export default meta;
type Story = StoryObj<typeof meta>;

/** Eight resolved categories in a responsive 2/3/4-column grid. */
export const Default: Story = {};

/** Arabic locale — names and descriptions render in Arabic, grid mirrors. */
export const Arabic: Story = {
  decorators: [(Story: () => import("react").ReactElement) => (
    <I18nProvider initialLocale="ar">
      {Story()}
    </I18nProvider>
  )],
};

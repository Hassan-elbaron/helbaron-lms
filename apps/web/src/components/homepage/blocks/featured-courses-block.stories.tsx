import type { Meta, StoryObj } from "@storybook/react";
import { I18nProvider } from "@/lib/i18n/i18n-context";
import type { HomepageSection } from "@/lib/homepage/api";
import { FeaturedCoursesBlock } from "@/components/homepage/blocks/featured-courses-block";

/**
 * FeaturedCoursesBlock — CMS featured-courses grid. Heading/subheading/CTA come from
 * `section.content` ({ heading?, subheading?, cta?: LocalizedLink }); the cards render from
 * SERVER-RESOLVED `section.resolved.courses` (ResolvedCourse[]). Renders nothing when empty.
 */
const img = (seed: string) => `https://picsum.photos/seed/${seed}/640/360`;

const section: HomepageSection = {
  key: "featured_courses",
  type: "featured_courses",
  position: 1,
  content: {
    heading: { en: "Featured courses", ar: "دورات مختارة" },
    subheading: {
      en: "Hand-picked programs our learners rate highest this season.",
      ar: "برامج مختارة يقيّمها متعلّمونا الأعلى هذا الموسم.",
    },
    cta: { label: { en: "Browse all courses", ar: "تصفّح كل الدورات" }, href: "/courses" },
  },
  resolved: {
    courses: [
      { id: "crs_pm", slug: "project-management-foundations", href: "/courses/project-management-foundations", level: "Beginner", thumbnail: img("pmcourse"), title: { en: "Project Management Foundations", ar: "أساسيات إدارة المشاريع" }, subtitle: { en: "Plan, run, and deliver projects from charter to close.", ar: "خطّط وأدر وسلّم المشاريع من البداية للنهاية." } },
      { id: "crs_ai", slug: "business-ai-for-decision-makers", href: "/courses/business-ai-for-decision-makers", level: "Intermediate", thumbnail: img("aicourse"), title: { en: "Business AI for Decision Makers", ar: "الذكاء الاصطناعي لصنّاع القرار" }, subtitle: { en: "Use AI to make faster, better calls — no code required.", ar: "استخدم الذكاء الاصطناعي لقرارات أسرع وأفضل دون برمجة." } },
      { id: "crs_fin", slug: "financial-modeling-ifrs", href: "/courses/financial-modeling-ifrs", level: "Advanced", thumbnail: img("fincourse"), title: { en: "Financial Modeling & IFRS", ar: "النمذجة المالية والمعايير الدولية" }, subtitle: { en: "Build board-ready models aligned with IFRS.", ar: "ابنِ نماذج جاهزة للمجلس متوافقة مع المعايير الدولية." } },
    ],
  },
};

const meta = {
  title: "Homepage Blocks/Featured Courses",
  component: FeaturedCoursesBlock,
  parameters: { layout: "fullscreen" },
  tags: ["autodocs"],
  decorators: [(Story: () => import("react").ReactElement) => (
    <I18nProvider>
      {Story()}
    </I18nProvider>
  )],
  args: { section },
} satisfies Meta<typeof FeaturedCoursesBlock>;

export default meta;
type Story = StoryObj<typeof meta>;

/** Three resolved courses with thumbnails, level pills, and a "browse all" CTA. */
export const Default: Story = {};

/** No thumbnails — falls back to the primary-tinted placeholder panel. */
export const WithoutThumbnails: Story = {
  args: {
    section: {
      ...section,
      resolved: {
        courses: (section.resolved?.courses ?? []).map((c) => ({ ...c, thumbnail: null })),
      },
    },
  },
};

/** Arabic locale — titles, subtitles and CTA render in Arabic. */
export const Arabic: Story = {
  decorators: [(Story: () => import("react").ReactElement) => (
    <I18nProvider initialLocale="ar">
      {Story()}
    </I18nProvider>
  )],
};

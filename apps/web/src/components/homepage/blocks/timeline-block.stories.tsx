import type { Meta, StoryObj } from "@storybook/react";
import { I18nProvider } from "@/lib/i18n/i18n-context";
import type { HomepageSection } from "@/lib/homepage/api";
import { TimelineBlock } from "@/components/homepage/blocks/timeline-block";

/**
 * TimelineBlock — CMS vertical timeline. Reads `section.content` as
 * { heading?: Localized; items?: { date?: Localized; title?: Localized; description?: Localized }[] }.
 * The connecting border sits on the inline-start edge (RTL-safe). Empty → nothing.
 */
const section: HomepageSection = {
  key: "timeline",
  type: "timeline",
  position: 1,
  content: {
    heading: { en: "Our journey", ar: "مسيرتنا" },
    items: [
      { date: { en: "2019", ar: "٢٠١٩" }, title: { en: "Founded in Riyadh", ar: "التأسيس في الرياض" }, description: { en: "HElbaron launches with 12 flagship courses.", ar: "انطلاق الباذرون بـ 12 دورة رئيسية." } },
      { date: { en: "2021", ar: "٢٠٢١" }, title: { en: "10,000 learners", ar: "١٠٬٠٠٠ متعلّم" }, description: { en: "We cross ten thousand active learners across the Gulf.", ar: "تجاوزنا عشرة آلاف متعلّم نشط في الخليج." } },
      { date: { en: "2023", ar: "٢٠٢٣" }, title: { en: "Enterprise platform", ar: "منصة المؤسسات" }, description: { en: "Launched SSO, team analytics, and custom paths.", ar: "أطلقنا الدخول الموحّد وتحليلات الفرق والمسارات المخصّصة." } },
      { date: { en: "2026", ar: "٢٠٢٦" }, title: { en: "AI-assisted learning", ar: "تعلّم بمساعدة الذكاء الاصطناعي" }, description: { en: "Personalized recommendations powered by AI.", ar: "توصيات مخصّصة مدعومة بالذكاء الاصطناعي." } },
    ],
  },
};

const meta = {
  title: "Homepage Blocks/Timeline",
  component: TimelineBlock,
  parameters: { layout: "fullscreen" },
  tags: ["autodocs"],
  decorators: [(Story: () => import("react").ReactElement) => (
    <I18nProvider>
      {Story()}
    </I18nProvider>
  )],
  args: { section },
} satisfies Meta<typeof TimelineBlock>;

export default meta;
type Story = StoryObj<typeof meta>;

/** Four milestones with date eyebrow, title, and description. */
export const Default: Story = {};

/** Arabic locale — dates, titles and descriptions in Arabic; border flips to the right edge. */
export const Arabic: Story = {
  decorators: [(Story: () => import("react").ReactElement) => (
    <I18nProvider initialLocale="ar">
      {Story()}
    </I18nProvider>
  )],
};

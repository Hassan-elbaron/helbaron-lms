import type { Meta, StoryObj } from "@storybook/react";
import { I18nProvider } from "@/lib/i18n/i18n-context";
import type { FeaturesContent } from "@/lib/homepage/api";
import { FeaturesSection } from "@/components/homepage/features-section";

/**
 * FeaturesSection — standalone homepage "what we offer" grid. Takes `content?: FeaturesContent`:
 * { items?: { title?: Localized; description?: Localized; icon?: string|null }[] }.
 * When no items are supplied it falls back to the brand's service lines, so it is never empty.
 */
const content: FeaturesContent = {
  items: [
    { title: { en: "Bilingual catalog", ar: "دليل ثنائي اللغة" }, description: { en: "Learn in Arabic or English across 1,200+ courses built for MENA careers.", ar: "تعلّم بالعربية أو الإنجليزية عبر أكثر من 1200 دورة مصمّمة لمهن المنطقة." } },
    { title: { en: "Live instructor events", ar: "فعاليات مباشرة مع المدرّبين" }, description: { en: "Join expert-led sessions, workshops, and Q&A across time zones.", ar: "انضم إلى جلسات وورش ولقاءات يقودها الخبراء عبر المناطق الزمنية." } },
    { title: { en: "Accredited certificates", ar: "شهادات معتمدة" }, description: { en: "Earn verifiable credentials that employers across the region trust.", ar: "احصل على شهادات قابلة للتحقّق يثق بها أصحاب العمل في المنطقة." } },
    { title: { en: "Team analytics", ar: "تحليلات الفريق" }, description: { en: "Track progress, skills, and ROI with dashboards built for L&D leaders.", ar: "تابع التقدّم والمهارات والعائد عبر لوحات مصمّمة لقادة التطوير." } },
    { title: { en: "Personalized paths", ar: "مسارات مخصّصة" }, description: { en: "AI-assisted recommendations map each learner to the right next course.", ar: "توصيات مدعومة بالذكاء الاصطناعي توجّه كل متعلّم إلى الدورة التالية المناسبة." } },
    { title: { en: "Enterprise-grade security", ar: "أمان بمستوى المؤسسات" }, description: { en: "SSO, role-based access, and regional data residency options.", ar: "الدخول الموحّد والصلاحيات حسب الدور وخيارات إقامة البيانات الإقليمية." } },
  ],
};

const meta = {
  title: "Homepage Blocks/Features",
  component: FeaturesSection,
  parameters: { layout: "fullscreen" },
  tags: ["autodocs"],
  decorators: [(Story: () => import("react").ReactElement) => (
    <I18nProvider>
      {Story()}
    </I18nProvider>
  )],
  args: { content },
} satisfies Meta<typeof FeaturesSection>;

export default meta;
type Story = StoryObj<typeof meta>;

/** Six CMS feature cards with the brand service heading. */
export const Default: Story = {};

/** No content — the section falls back to the brand's built-in service lines. */
export const BrandFallback: Story = {
  args: { content: undefined },
};

/** Arabic locale — titles and descriptions render in Arabic. */
export const Arabic: Story = {
  decorators: [(Story: () => import("react").ReactElement) => (
    <I18nProvider initialLocale="ar">
      {Story()}
    </I18nProvider>
  )],
};

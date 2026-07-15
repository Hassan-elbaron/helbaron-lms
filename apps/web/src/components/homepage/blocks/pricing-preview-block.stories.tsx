import type { Meta, StoryObj } from "@storybook/react";
import { I18nProvider } from "@/lib/i18n/i18n-context";
import type { HomepageSection } from "@/lib/homepage/api";
import { PricingPreviewBlock } from "@/components/homepage/blocks/pricing-preview-block";

/**
 * PricingPreviewBlock — CMS pricing preview. Reads `section.content` as
 * { heading?; subheading?; plans?: PricingPlan[] } where a plan is
 * { name?: Localized; price?: string; period?: Localized; highlighted?: boolean;
 *   features?: Localized[]; cta?: LocalizedLink }. Renders nothing without plans.
 */
const section: HomepageSection = {
  key: "pricing_preview",
  type: "pricing_preview",
  position: 1,
  content: {
    heading: { en: "Simple, transparent pricing", ar: "أسعار بسيطة وشفّافة" },
    subheading: {
      en: "Start free, upgrade as your team grows. Prices in SAR, VAT excluded.",
      ar: "ابدأ مجانًا وطوّر باقتك مع نمو فريقك. الأسعار بالريال السعودي دون ضريبة القيمة المضافة.",
    },
    plans: [
      {
        name: { en: "Starter", ar: "المبتدئ" },
        price: "SAR 0",
        period: { en: "/ forever", ar: "/ للأبد" },
        features: [
          { en: "Access to 50+ free courses", ar: "الوصول إلى أكثر من 50 دورة مجانية" },
          { en: "Community discussion boards", ar: "منتديات النقاش المجتمعية" },
          { en: "Completion certificates", ar: "شهادات إتمام" },
        ],
        cta: { label: { en: "Get started", ar: "ابدأ الآن" }, href: "/signup" },
      },
      {
        name: { en: "Professional", ar: "المحترف" },
        price: "SAR 149",
        period: { en: "/ month", ar: "/ شهريًا" },
        highlighted: true,
        features: [
          { en: "Everything in Starter", ar: "كل ما في المبتدئ" },
          { en: "Unlimited premium courses", ar: "دورات مميّزة غير محدودة" },
          { en: "Live instructor-led events", ar: "فعاليات مباشرة مع المدرّبين" },
          { en: "Accredited certificates", ar: "شهادات معتمدة" },
        ],
        cta: { label: { en: "Start free trial", ar: "ابدأ التجربة المجانية" }, href: "/signup?plan=pro" },
      },
      {
        name: { en: "Enterprise", ar: "المؤسسات" },
        price: "SAR 899",
        period: { en: "/ seat / year", ar: "/ مقعد / سنويًا" },
        features: [
          { en: "Everything in Professional", ar: "كل ما في المحترف" },
          { en: "SSO & team analytics", ar: "الدخول الموحّد وتحليلات الفريق" },
          { en: "Dedicated success manager", ar: "مدير نجاح مخصّص" },
          { en: "Custom learning paths", ar: "مسارات تعلّم مخصّصة" },
        ],
        cta: { label: { en: "Contact sales", ar: "تواصل مع المبيعات" }, href: "/contact" },
      },
    ],
  },
};

const meta = {
  title: "Homepage Blocks/Pricing Preview",
  component: PricingPreviewBlock,
  parameters: { layout: "fullscreen" },
  tags: ["autodocs"],
  decorators: [(Story: () => import("react").ReactElement) => (
    <I18nProvider>
      {Story()}
    </I18nProvider>
  )],
  args: { section },
} satisfies Meta<typeof PricingPreviewBlock>;

export default meta;
type Story = StoryObj<typeof meta>;

/** Three SAR plans; the middle "Professional" plan is `highlighted`. */
export const Default: Story = {};

/** Arabic locale — plan names, periods, features and CTAs in Arabic. */
export const Arabic: Story = {
  decorators: [(Story: () => import("react").ReactElement) => (
    <I18nProvider initialLocale="ar">
      {Story()}
    </I18nProvider>
  )],
};

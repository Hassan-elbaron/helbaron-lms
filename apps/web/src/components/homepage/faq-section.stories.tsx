import type { Meta, StoryObj } from "@storybook/react";
import { I18nProvider } from "@/lib/i18n/i18n-context";
import type { FaqContent } from "@/lib/homepage/api";
import { FaqSection } from "@/components/homepage/faq-section";

/**
 * FaqSection — standalone homepage FAQ. Takes a `content?: FaqContent` prop:
 * { items?: { question?: Localized; answer?: Localized }[] }. Uses native <details>/<summary>
 * (keyboard-accessible, works without JS). Items without a `question` are filtered out; empty → nothing.
 */
const content: FaqContent = {
  items: [
    { question: { en: "Are the certificates accredited?", ar: "هل الشهادات معتمدة؟" }, answer: { en: "Professional and Enterprise plans issue accredited, verifiable certificates recognized by regional employers.", ar: "تصدر باقتا المحترف والمؤسسات شهادات معتمدة وقابلة للتحقّق ومعترف بها لدى أصحاب العمل في المنطقة." } },
    { question: { en: "Can I learn in Arabic?", ar: "هل يمكنني التعلّم بالعربية؟" }, answer: { en: "Yes — the platform and a large share of our catalog are fully bilingual (Arabic and English).", ar: "نعم، المنصة وجزء كبير من دليل الدورات ثنائيا اللغة بالكامل (عربي وإنجليزي)." } },
    { question: { en: "Do you offer team or enterprise plans?", ar: "هل تقدّمون باقات للفرق والمؤسسات؟" }, answer: { en: "We do. Enterprise plans add SSO, team analytics, custom learning paths, and a dedicated success manager.", ar: "نعم. تضيف باقات المؤسسات الدخول الموحّد وتحليلات الفرق ومسارات مخصّصة ومدير نجاح مخصّص." } },
    { question: { en: "What payment methods are accepted?", ar: "ما طرق الدفع المقبولة؟" }, answer: { en: "We accept major cards and mada, with prices shown in SAR. Annual invoicing is available for enterprises.", ar: "نقبل البطاقات الرئيسية ومدى، والأسعار بالريال السعودي. تتوفّر الفوترة السنوية للمؤسسات." } },
  ],
};

const meta = {
  title: "Homepage Blocks/FAQ",
  component: FaqSection,
  parameters: { layout: "fullscreen" },
  tags: ["autodocs"],
  decorators: [(Story: () => import("react").ReactElement) => (
    <I18nProvider>
      {Story()}
    </I18nProvider>
  )],
  args: { content },
} satisfies Meta<typeof FaqSection>;

export default meta;
type Story = StoryObj<typeof meta>;

/** Four expandable Q&A rows with the brand eyebrow heading. */
export const Default: Story = {};

/** Arabic locale — questions and answers render in Arabic. */
export const Arabic: Story = {
  decorators: [(Story: () => import("react").ReactElement) => (
    <I18nProvider initialLocale="ar">
      {Story()}
    </I18nProvider>
  )],
};

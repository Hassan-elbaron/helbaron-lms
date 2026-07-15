import type { Meta, StoryObj } from "@storybook/react";
import { I18nProvider } from "@/lib/i18n/i18n-context";
import type { TestimonialsContent } from "@/lib/homepage/api";
import { TestimonialsSection } from "@/components/homepage/testimonials-section";

/**
 * TestimonialsSection — standalone homepage testimonials grid. Takes `content?: TestimonialsContent`:
 * { items?: { quote?: Localized; author?: string; role?: Localized; avatar?: string|null }[] }.
 * Items without a `quote` are filtered out; empty → nothing (the page stays valid).
 */
const content: TestimonialsContent = {
  items: [
    { quote: { en: "We rolled HElbaron out to 400 staff in a month. The bilingual catalog and team analytics made adoption effortless.", ar: "طرحنا الباذرون على 400 موظف خلال شهر. الدليل ثنائي اللغة وتحليلات الفريق جعلا التبنّي سهلاً." }, author: "Noura Al-Qahtani", role: { en: "L&D Director, Riyad Bank", ar: "مديرة التطوير، بنك الرياض" } },
    { quote: { en: "The PMP bootcamp was the reason I passed on my first attempt. Practical, focused, and taught in a way that stuck.", ar: "كان معسكر PMP سبب نجاحي من المحاولة الأولى. عملي ومركّز ومقدّم بطريقة تثبت في الذهن." }, author: "Omar Haddad", role: { en: "Program Manager, Careem", ar: "مدير برنامج، كريم" } },
    { quote: { en: "As a founder I needed AI fluency fast. The Business AI course paid for itself in the first week.", ar: "كمؤسس احتجت إتقان الذكاء الاصطناعي بسرعة. دورة الذكاء الاصطناعي للأعمال عوّضت تكلفتها في الأسبوع الأول." }, author: "Layla Mansour", role: { en: "Founder, Suhoor Labs", ar: "مؤسِّسة، سحور لابز" } },
  ],
};

const meta = {
  title: "Homepage Blocks/Testimonials",
  component: TestimonialsSection,
  parameters: { layout: "fullscreen" },
  tags: ["autodocs"],
  decorators: [(Story: () => import("react").ReactElement) => (
    <I18nProvider>
      {Story()}
    </I18nProvider>
  )],
  args: { content },
} satisfies Meta<typeof TestimonialsSection>;

export default meta;
type Story = StoryObj<typeof meta>;

/** Three-up quote cards with author + localized role. */
export const Default: Story = {};

/** Arabic locale — quotes and roles render in Arabic (quote mark mirrors). */
export const Arabic: Story = {
  decorators: [(Story: () => import("react").ReactElement) => (
    <I18nProvider initialLocale="ar">
      {Story()}
    </I18nProvider>
  )],
};

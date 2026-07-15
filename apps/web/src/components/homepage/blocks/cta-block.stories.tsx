import type { Meta, StoryObj } from "@storybook/react";
import { I18nProvider } from "@/lib/i18n/i18n-context";
import type { HomepageSection } from "@/lib/homepage/api";
import { CtaBlock } from "@/components/homepage/blocks/cta-block";

/**
 * CtaBlock — CMS call-to-action banner. Reads `section.content` as
 * { headline?: Localized; subheadline?: Localized; cta_primary?: LocalizedLink; cta_secondary?: LocalizedLink }.
 * Renders nothing without a `headline`; each CTA only renders when it has an `href`.
 */
const section: HomepageSection = {
  key: "cta",
  type: "cta",
  position: 1,
  content: {
    headline: { en: "Start learning with HElbaron today", ar: "ابدأ التعلّم مع الباذرون اليوم" },
    subheadline: {
      en: "Join thousands of professionals building in-demand skills across the region.",
      ar: "انضم إلى آلاف المحترفين الذين يبنون مهارات مطلوبة في المنطقة.",
    },
    cta_primary: { label: { en: "Create free account", ar: "أنشئ حسابًا مجانيًا" }, href: "/signup" },
    cta_secondary: { label: { en: "Talk to our team", ar: "تحدّث مع فريقنا" }, href: "/contact" },
  },
};

const meta = {
  title: "Homepage Blocks/CTA",
  component: CtaBlock,
  parameters: { layout: "fullscreen" },
  tags: ["autodocs"],
  decorators: [(Story: () => import("react").ReactElement) => (
    <I18nProvider>
      {Story()}
    </I18nProvider>
  )],
  args: { section },
} satisfies Meta<typeof CtaBlock>;

export default meta;
type Story = StoryObj<typeof meta>;

/** Centered card with primary + secondary CTAs (arrow flips for RTL). */
export const Default: Story = {};

/** Primary CTA only — the secondary link is omitted. */
export const PrimaryOnly: Story = {
  args: {
    section: {
      ...section,
      content: {
        headline: { en: "Ready to upskill your team?", ar: "جاهز لتطوير مهارات فريقك؟" },
        subheadline: { en: "Bring HElbaron to your whole organization.", ar: "أحضر الباذرون إلى مؤسستك بالكامل." },
        cta_primary: { label: { en: "Get a demo", ar: "احصل على عرض توضيحي" }, href: "/demo" },
      },
    },
  },
};

/** Inverted theme variant via CMS presentation metadata. */
export const Inverted: Story = {
  args: { section: { ...section, presentation: { theme_variant: "inverted" } } },
};

/** Arabic locale — headline, subheadline and buttons in Arabic. */
export const Arabic: Story = {
  decorators: [(Story: () => import("react").ReactElement) => (
    <I18nProvider initialLocale="ar">
      {Story()}
    </I18nProvider>
  )],
};

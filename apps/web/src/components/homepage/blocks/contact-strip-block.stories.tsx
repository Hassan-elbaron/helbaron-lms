import type { Meta, StoryObj } from "@storybook/react";
import { I18nProvider } from "@/lib/i18n/i18n-context";
import type { HomepageSection } from "@/lib/homepage/api";
import { ContactStripBlock } from "@/components/homepage/blocks/contact-strip-block";

/**
 * ContactStripBlock — CMS contact strip. Reads `section.content` as
 * { heading?; subheading?; phone?: string; email?: string; address?: Localized; cta?: LocalizedLink }.
 * Phone/email/address each render only when present. Renders nothing without a `heading`.
 */
const section: HomepageSection = {
  key: "contact_strip",
  type: "contact_strip",
  position: 1,
  content: {
    heading: { en: "Talk to our learning advisors", ar: "تحدّث مع مستشاري التعلّم" },
    subheading: {
      en: "We help teams pick the right paths and plans.",
      ar: "نساعد الفرق على اختيار المسارات والباقات المناسبة.",
    },
    phone: "+966 11 234 5678",
    email: "hello@helbaron.academy",
    address: { en: "King Fahd Road, Riyadh, Saudi Arabia", ar: "طريق الملك فهد، الرياض، السعودية" },
    cta: { label: { en: "Book a call", ar: "احجز مكالمة" }, href: "/contact" },
  },
};

const meta = {
  title: "Homepage Blocks/Contact Strip",
  component: ContactStripBlock,
  parameters: { layout: "fullscreen" },
  tags: ["autodocs"],
  decorators: [(Story: () => import("react").ReactElement) => (
    <I18nProvider>
      {Story()}
    </I18nProvider>
  )],
  args: { section },
} satisfies Meta<typeof ContactStripBlock>;

export default meta;
type Story = StoryObj<typeof meta>;

/** Heading + phone/email/address + CTA button. */
export const Default: Story = {};

/** Email only — phone, address and CTA omitted. */
export const EmailOnly: Story = {
  args: {
    section: {
      ...section,
      content: {
        heading: { en: "Questions? Reach out", ar: "أسئلة؟ تواصل معنا" },
        email: "hello@helbaron.academy",
      },
    },
  },
};

/** Arabic locale — heading, subheading and address in Arabic (phone stays LTR). */
export const Arabic: Story = {
  decorators: [(Story: () => import("react").ReactElement) => (
    <I18nProvider initialLocale="ar">
      {Story()}
    </I18nProvider>
  )],
};

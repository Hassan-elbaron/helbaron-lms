import type { Meta, StoryObj } from "@storybook/react";
import { I18nProvider } from "@/lib/i18n/i18n-context";
import type { HomepageSection } from "@/lib/homepage/api";
import { NewsletterBlock } from "@/components/homepage/blocks/newsletter-block";

/**
 * NewsletterBlock — CMS sign-up card. Reads `section.content` as
 * { heading?; subheading?; placeholder?: Localized; cta?: Localized; action_url?: string|null }.
 * Posts to `action_url` when present, otherwise the form is inert. Renders nothing without a `heading`.
 */
const section: HomepageSection = {
  key: "newsletter",
  type: "newsletter",
  position: 1,
  content: {
    heading: { en: "Get learning tips in your inbox", ar: "نصائح التعلّم في بريدك" },
    subheading: {
      en: "One practical email a week — new courses, events, and career advice.",
      ar: "بريد عملي واحد أسبوعيًا — دورات جديدة وفعاليات ونصائح مهنية.",
    },
    placeholder: { en: "you@company.com", ar: "you@company.com" },
    cta: { en: "Subscribe", ar: "اشترك" },
    action_url: null,
  },
};

const meta = {
  title: "Homepage Blocks/Newsletter",
  component: NewsletterBlock,
  parameters: { layout: "fullscreen" },
  tags: ["autodocs"],
  decorators: [(Story: () => import("react").ReactElement) => (
    <I18nProvider>
      {Story()}
    </I18nProvider>
  )],
  args: { section },
} satisfies Meta<typeof NewsletterBlock>;

export default meta;
type Story = StoryObj<typeof meta>;

/** Inert form (no action_url) — submit is prevented client-side. */
export const Default: Story = {};

/** With an `action_url` — the form posts to the admin-provided endpoint. */
export const WithAction: Story = {
  args: {
    section: {
      ...section,
      content: { ...section.content, action_url: "https://example.com/subscribe" },
    },
  },
};

/** Arabic locale — heading, subheading and button in Arabic. */
export const Arabic: Story = {
  decorators: [(Story: () => import("react").ReactElement) => (
    <I18nProvider initialLocale="ar">
      {Story()}
    </I18nProvider>
  )],
};

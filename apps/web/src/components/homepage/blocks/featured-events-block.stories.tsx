import type { Meta, StoryObj } from "@storybook/react";
import { I18nProvider } from "@/lib/i18n/i18n-context";
import type { HomepageSection } from "@/lib/homepage/api";
import { FeaturedEventsBlock } from "@/components/homepage/blocks/featured-events-block";

/**
 * FeaturedEventsBlock — CMS upcoming-events list. Heading/subheading/CTA come from
 * `section.content` ({ heading?, subheading?, cta?: LocalizedLink }); the cards render from
 * SERVER-RESOLVED `section.resolved.events` (ResolvedEvent[], with ISO `starts_at`).
 * Renders nothing when empty.
 */
const section: HomepageSection = {
  key: "featured_events",
  type: "featured_events",
  position: 1,
  content: {
    heading: { en: "Upcoming live events", ar: "فعاليات مباشرة قادمة" },
    subheading: {
      en: "Join instructor-led sessions and expert panels across the region.",
      ar: "انضم إلى جلسات مباشرة ولقاءات مع الخبراء في المنطقة.",
    },
    cta: { label: { en: "See all events", ar: "كل الفعاليات" }, href: "/events" },
  },
  resolved: {
    events: [
      { id: "evt_ai", href: "/events/ai-leadership-summit", starts_at: "2026-08-12T16:00:00Z", title: { en: "AI for Leadership Summit", ar: "قمة الذكاء الاصطناعي للقيادة" }, description: { en: "A half-day virtual summit on applied AI for executives.", ar: "قمة افتراضية لنصف يوم حول تطبيقات الذكاء الاصطناعي للتنفيذيين." } },
      { id: "evt_pmp", href: "/events/pmp-exam-bootcamp", starts_at: "2026-08-20T06:30:00Z", title: { en: "PMP Exam Bootcamp", ar: "معسكر امتحان PMP" }, description: { en: "Two intensive days to prepare for the PMP certification.", ar: "يومان مكثّفان للتحضير لشهادة PMP." } },
      { id: "evt_ux", href: "/events/ux-research-workshop", starts_at: "2026-09-03T13:00:00Z", title: { en: "UX Research Workshop", ar: "ورشة بحوث تجربة المستخدم" }, description: { en: "Hands-on methods for interviews and usability testing.", ar: "أساليب عملية للمقابلات واختبار قابلية الاستخدام." } },
      { id: "evt_fin", href: "/events/ifrs-update-briefing", starts_at: "2026-09-15T09:00:00Z", title: { en: "IFRS Update Briefing", ar: "إحاطة تحديثات المعايير الدولية" }, description: { en: "What finance teams need to know for the new fiscal year.", ar: "ما تحتاج فرق المالية معرفته للسنة المالية الجديدة." } },
    ],
  },
};

const meta = {
  title: "Homepage Blocks/Featured Events",
  component: FeaturedEventsBlock,
  parameters: { layout: "fullscreen" },
  tags: ["autodocs"],
  decorators: [(Story: () => import("react").ReactElement) => (
    <I18nProvider>
      {Story()}
    </I18nProvider>
  )],
  args: { section },
} satisfies Meta<typeof FeaturedEventsBlock>;

export default meta;
type Story = StoryObj<typeof meta>;

/** Two-up event cards with localized date/time (Intl) and a CTA. */
export const Default: Story = {};

/** Arabic locale — titles/descriptions in Arabic and dates via the `ar` Intl formatter. */
export const Arabic: Story = {
  decorators: [(Story: () => import("react").ReactElement) => (
    <I18nProvider initialLocale="ar">
      {Story()}
    </I18nProvider>
  )],
};

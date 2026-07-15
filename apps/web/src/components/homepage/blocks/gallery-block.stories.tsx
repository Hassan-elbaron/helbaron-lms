import type { Meta, StoryObj } from "@storybook/react";
import { I18nProvider } from "@/lib/i18n/i18n-context";
import type { HomepageSection } from "@/lib/homepage/api";
import { GalleryBlock } from "@/components/homepage/blocks/gallery-block";

/**
 * GalleryBlock — CMS image gallery. Reads `section.content` as
 * { heading?: Localized; items?: { image?: string|null; caption?: Localized }[] }.
 * Only items WITH an `image` render; empty → nothing.
 */
const img = (seed: string) => `https://picsum.photos/seed/${seed}/600/600`;

const section: HomepageSection = {
  key: "gallery",
  type: "gallery",
  position: 1,
  content: {
    heading: { en: "Life at HElbaron Academy", ar: "الحياة في أكاديمية الباذرون" },
    items: [
      { image: img("gal-workshop"), caption: { en: "Riyadh leadership workshop", ar: "ورشة القيادة في الرياض" } },
      { image: img("gal-studio"), caption: { en: "Course production studio", ar: "استوديو إنتاج الدورات" } },
      { image: img("gal-graduation"), caption: { en: "Cohort graduation day", ar: "يوم تخرّج الدفعة" } },
      { image: img("gal-hackathon"), caption: { en: "Dubai product hackathon", ar: "هاكاثون المنتجات في دبي" } },
      { image: img("gal-mentoring"), caption: { en: "1:1 mentoring session", ar: "جلسة إرشاد فردية" } },
      { image: img("gal-panel"), caption: { en: "Cairo alumni panel", ar: "لقاء خريجي القاهرة" } },
    ],
  },
};

const meta = {
  title: "Homepage Blocks/Gallery",
  component: GalleryBlock,
  parameters: { layout: "fullscreen" },
  tags: ["autodocs"],
  decorators: [(Story: () => import("react").ReactElement) => (
    <I18nProvider>
      {Story()}
    </I18nProvider>
  )],
  args: { section },
} satisfies Meta<typeof GalleryBlock>;

export default meta;
type Story = StoryObj<typeof meta>;

/** Six captioned square images in a 2/3-column grid. */
export const Default: Story = {};

/** Images without captions — figures render with no figcaption. */
export const NoCaptions: Story = {
  args: {
    section: {
      ...section,
      content: {
        items: [
          { image: img("nc-1") },
          { image: img("nc-2") },
          { image: img("nc-3") },
        ],
      },
    },
  },
};

/** Arabic locale — heading and captions in Arabic. */
export const Arabic: Story = {
  decorators: [(Story: () => import("react").ReactElement) => (
    <I18nProvider initialLocale="ar">
      {Story()}
    </I18nProvider>
  )],
};

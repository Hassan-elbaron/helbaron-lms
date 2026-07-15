import type { Meta, StoryObj } from "@storybook/react";
import { I18nProvider } from "@/lib/i18n/i18n-context";
import type { HomepageSection } from "@/lib/homepage/api";
import { StatisticsBlock } from "@/components/homepage/blocks/statistics-block";

/**
 * StatisticsBlock — CMS "by the numbers" grid. Reads `section.content` as
 * { heading?: Localized; items?: { value?; suffix?; label?: Localized }[] }.
 * Renders nothing when `items` is empty.
 */
const section: HomepageSection = {
  key: "statistics",
  type: "statistics",
  position: 1,
  content: {
    heading: { en: "HElbaron Academy in numbers", ar: "أكاديمية الباذرون بالأرقام" },
    items: [
      { value: "48", suffix: "K+", label: { en: "Learners across MENA", ar: "متعلّم في المنطقة" } },
      { value: "1,200", suffix: "+", label: { en: "Courses & workshops", ar: "دورة وورشة عمل" } },
      { value: "96", suffix: "%", label: { en: "Completion satisfaction", ar: "رضا عن الإتمام" } },
      { value: "120", suffix: "+", label: { en: "Enterprise partners", ar: "شريك مؤسسي" } },
    ],
  },
};

const meta = {
  title: "Homepage Blocks/Statistics",
  component: StatisticsBlock,
  parameters: { layout: "fullscreen" },
  tags: ["autodocs"],
  decorators: [(Story: () => import("react").ReactElement) => (
    <I18nProvider>
      {Story()}
    </I18nProvider>
  )],
  args: { section },
} satisfies Meta<typeof StatisticsBlock>;

export default meta;
type Story = StoryObj<typeof meta>;

/** Four-stat grid with bilingual labels and value suffixes (K+, %, +). */
export const Default: Story = {};

/** Arabic locale — heading and labels render in Arabic. */
export const Arabic: Story = {
  decorators: [(Story: () => import("react").ReactElement) => (
    <I18nProvider initialLocale="ar">
      {Story()}
    </I18nProvider>
  )],
};

/** Primary-tinted background via the CMS `presentation.theme_variant`. */
export const PrimaryTheme: Story = {
  args: {
    section: { ...section, presentation: { theme_variant: "primary", container_width: "wide" } },
  },
};

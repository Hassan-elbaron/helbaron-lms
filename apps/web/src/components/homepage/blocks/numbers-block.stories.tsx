import type { Meta, StoryObj } from "@storybook/react";
import { I18nProvider } from "@/lib/i18n/i18n-context";
import type { HomepageSection } from "@/lib/homepage/api";
import { NumbersBlock } from "@/components/homepage/blocks/numbers-block";

/**
 * NumbersBlock — CMS KPI strip (three-up divided panel). Reads `section.content` as
 * { heading?: Localized; items?: { value?; label?: Localized }[] }.
 * Renders nothing when `items` is empty.
 */
const section: HomepageSection = {
  key: "numbers",
  type: "numbers",
  position: 1,
  content: {
    heading: { en: "Trusted outcomes", ar: "نتائج موثوقة" },
    items: [
      { value: "4.9/5", label: { en: "Average learner rating", ar: "متوسط تقييم المتعلّمين" } },
      { value: "37 hrs", label: { en: "Median learning per learner", ar: "متوسط ساعات التعلّم للفرد" } },
      { value: "89%", label: { en: "Job-relevant skill uplift", ar: "تحسّن المهارات المرتبطة بالعمل" } },
    ],
  },
};

const meta = {
  title: "Homepage Blocks/Numbers",
  component: NumbersBlock,
  parameters: { layout: "fullscreen" },
  tags: ["autodocs"],
  decorators: [(Story: () => import("react").ReactElement) => (
    <I18nProvider>
      {Story()}
    </I18nProvider>
  )],
  args: { section },
} satisfies Meta<typeof NumbersBlock>;

export default meta;
type Story = StoryObj<typeof meta>;

/** Three-up KPI strip with a divided card; divider flips for RTL. */
export const Default: Story = {};

/** Arabic locale — heading and labels in Arabic (divider mirrors). */
export const Arabic: Story = {
  decorators: [(Story: () => import("react").ReactElement) => (
    <I18nProvider initialLocale="ar">
      {Story()}
    </I18nProvider>
  )],
};

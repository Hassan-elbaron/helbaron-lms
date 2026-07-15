import type { Meta, StoryObj } from "@storybook/react";
import { I18nProvider } from "@/lib/i18n/i18n-context";
import type { HomepageSection } from "@/lib/homepage/api";
import { ComparisonTableBlock } from "@/components/homepage/blocks/comparison-table-block";

/**
 * ComparisonTableBlock — CMS comparison table. Reads `section.content` as
 * { heading?: Localized; columns?: Localized[]; rows?: { cells?: Localized[] }[] }.
 * The first cell of each row is emphasized. Renders nothing if columns OR rows are empty.
 */
const section: HomepageSection = {
  key: "comparison_table",
  type: "comparison_table",
  position: 1,
  content: {
    heading: { en: "Compare plans", ar: "قارن الباقات" },
    columns: [
      { en: "Feature", ar: "الميزة" },
      { en: "Starter", ar: "المبتدئ" },
      { en: "Professional", ar: "المحترف" },
      { en: "Enterprise", ar: "المؤسسات" },
    ],
    rows: [
      { cells: [{ en: "Free courses", ar: "دورات مجانية" }, { en: "50+", ar: "أكثر من 50" }, { en: "All", ar: "الكل" }, { en: "All", ar: "الكل" }] },
      { cells: [{ en: "Premium courses", ar: "دورات مميّزة" }, { en: "—", ar: "—" }, { en: "Unlimited", ar: "غير محدود" }, { en: "Unlimited", ar: "غير محدود" }] },
      { cells: [{ en: "Live events", ar: "فعاليات مباشرة" }, { en: "—", ar: "—" }, { en: "Included", ar: "مشمولة" }, { en: "Included", ar: "مشمولة" }] },
      { cells: [{ en: "Accredited certificates", ar: "شهادات معتمدة" }, { en: "—", ar: "—" }, { en: "Yes", ar: "نعم" }, { en: "Yes", ar: "نعم" }] },
      { cells: [{ en: "SSO & team analytics", ar: "الدخول الموحّد وتحليلات الفريق" }, { en: "—", ar: "—" }, { en: "—", ar: "—" }, { en: "Yes", ar: "نعم" }] },
      { cells: [{ en: "Dedicated success manager", ar: "مدير نجاح مخصّص" }, { en: "—", ar: "—" }, { en: "—", ar: "—" }, { en: "Yes", ar: "نعم" }] },
    ],
  },
};

const meta = {
  title: "Homepage Blocks/Comparison Table",
  component: ComparisonTableBlock,
  parameters: { layout: "fullscreen" },
  tags: ["autodocs"],
  decorators: [(Story: () => import("react").ReactElement) => (
    <I18nProvider>
      {Story()}
    </I18nProvider>
  )],
  args: { section },
} satisfies Meta<typeof ComparisonTableBlock>;

export default meta;
type Story = StoryObj<typeof meta>;

/** Four-column plan comparison with six feature rows. */
export const Default: Story = {};

/** Arabic locale — headers and cells render in Arabic with logical (RTL) alignment. */
export const Arabic: Story = {
  decorators: [(Story: () => import("react").ReactElement) => (
    <I18nProvider initialLocale="ar">
      {Story()}
    </I18nProvider>
  )],
};

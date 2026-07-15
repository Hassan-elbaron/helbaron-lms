import type { Meta, StoryObj } from "@storybook/react";
import { I18nProvider } from "@/lib/i18n/i18n-context";
import type { HomepageSection } from "@/lib/homepage/api";
import { TeamBlock } from "@/components/homepage/blocks/team-block";

/**
 * TeamBlock — CMS team grid. Reads `section.content` as
 * { heading?: Localized; items?: { name?: string; role?: Localized; avatar?: string|null; href?: string|null }[] }.
 * Falls back to a monogram avatar when `avatar` is absent. Empty → nothing.
 */
const avatar = (seed: string) => `https://i.pravatar.cc/160?img=${seed}`;

const section: HomepageSection = {
  key: "team",
  type: "team",
  position: 1,
  content: {
    heading: { en: "Meet the team", ar: "تعرّف على الفريق" },
    items: [
      { name: "Hassan Elbaron", role: { en: "Founder & CEO", ar: "المؤسس والرئيس التنفيذي" }, avatar: avatar("11"), href: "https://example.com/hassan" },
      { name: "Layla Al-Nasser", role: { en: "Head of Learning", ar: "رئيسة التعلّم" }, avatar: avatar("32") },
      { name: "Omar Haddad", role: { en: "Lead Instructor, Tech", ar: "المدرّب الرئيسي، التقنية" }, avatar: avatar("14") },
      { name: "Sara Mansour", role: { en: "Director of Partnerships", ar: "مديرة الشراكات" }, avatar: null },
    ],
  },
};

const meta = {
  title: "Homepage Blocks/Team",
  component: TeamBlock,
  parameters: { layout: "fullscreen" },
  tags: ["autodocs"],
  decorators: [(Story: () => import("react").ReactElement) => (
    <I18nProvider>
      {Story()}
    </I18nProvider>
  )],
  args: { section },
} satisfies Meta<typeof TeamBlock>;

export default meta;
type Story = StoryObj<typeof meta>;

/** Four members; the last has no avatar and renders a monogram fallback. */
export const Default: Story = {};

/** Arabic locale — roles render in Arabic (names stay as provided strings). */
export const Arabic: Story = {
  decorators: [(Story: () => import("react").ReactElement) => (
    <I18nProvider initialLocale="ar">
      {Story()}
    </I18nProvider>
  )],
};

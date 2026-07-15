import type { Meta, StoryObj } from "@storybook/react";
import { I18nProvider } from "@/lib/i18n/i18n-context";
import type { HomepageSection } from "@/lib/homepage/api";
import { ClientsBlock } from "@/components/homepage/blocks/clients-block";

/**
 * ClientsBlock — shared logo-row renderer for both the `clients` and `logo_cloud` block types
 * (same shape). Reads `section.content` as { heading?: Localized; items?: { name?; logo?; href? }[] }.
 * Renders a logo image when `logo` is present, else the `name` as a text mark. Empty items → nothing.
 */
const section: HomepageSection = {
  key: "clients",
  type: "clients",
  position: 1,
  content: {
    heading: { en: "Trusted by teams across MENA", ar: "موثوق من فرق في المنطقة" },
    items: [
      { name: "Aramco Digital", href: "https://example.com/aramco" },
      { name: "STC Academy", href: "https://example.com/stc" },
      { name: "Majid Al Futtaim", href: null },
      { name: "Careem", href: "https://example.com/careem" },
      { name: "Riyad Bank", href: null },
      { name: "Vodafone Egypt", href: "https://example.com/vodafone" },
    ],
  },
};

const meta = {
  title: "Homepage Blocks/Clients",
  component: ClientsBlock,
  parameters: { layout: "fullscreen" },
  tags: ["autodocs"],
  decorators: [(Story: () => import("react").ReactElement) => (
    <I18nProvider>
      {Story()}
    </I18nProvider>
  )],
  args: { section },
} satisfies Meta<typeof ClientsBlock>;

export default meta;
type Story = StoryObj<typeof meta>;

/** Text-mark logos (no image URLs) — names render as serif wordmarks. */
export const Default: Story = {};

/** With logo images — grayscale that colorizes on hover. */
export const WithLogos: Story = {
  args: {
    section: {
      ...section,
      content: {
        heading: { en: "Trusted by teams across MENA", ar: "موثوق من فرق في المنطقة" },
        items: [
          { name: "Aramco Digital", logo: "https://picsum.photos/seed/aramco/120/32", href: "https://example.com/aramco" },
          { name: "STC Academy", logo: "https://picsum.photos/seed/stc/120/32", href: null },
          { name: "Careem", logo: "https://picsum.photos/seed/careem/120/32", href: "https://example.com/careem" },
          { name: "Vodafone Egypt", logo: "https://picsum.photos/seed/voda/120/32", href: null },
        ],
      },
    },
  },
};

/** logo_cloud block type — identical renderer, different registry key. */
export const LogoCloud: Story = {
  args: { section: { ...section, key: "logo_cloud", type: "logo_cloud" } },
};

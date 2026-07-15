import type { Meta, StoryObj } from "@storybook/react";
import { I18nProvider } from "@/lib/i18n/i18n-context";
import type { HomepageSection } from "@/lib/homepage/api";
import { VideoBlock } from "@/components/homepage/blocks/video-block";

/**
 * VideoBlock — CMS responsive video embed. Reads `section.content` as
 * { heading?: Localized; url?: string|null; poster?: string|null; caption?: Localized }.
 * Embeds `url` in a 16:9 iframe. Renders nothing without a `url`.
 */
const section: HomepageSection = {
  key: "video",
  type: "video",
  position: 1,
  content: {
    heading: { en: "See HElbaron in action", ar: "شاهد الباذرون على أرض الواقع" },
    url: "https://www.youtube.com/embed/aqz-KE-bpKQ",
    caption: {
      en: "A two-minute tour of the learner and instructor experience.",
      ar: "جولة في دقيقتين عبر تجربة المتعلّم والمدرّب.",
    },
  },
};

const meta = {
  title: "Homepage Blocks/Video",
  component: VideoBlock,
  parameters: { layout: "fullscreen" },
  tags: ["autodocs"],
  decorators: [(Story: () => import("react").ReactElement) => (
    <I18nProvider>
      {Story()}
    </I18nProvider>
  )],
  args: { section },
} satisfies Meta<typeof VideoBlock>;

export default meta;
type Story = StoryObj<typeof meta>;

/** Heading + 16:9 embed + bilingual caption. */
export const Default: Story = {};

/** No heading or caption — bare responsive embed. */
export const EmbedOnly: Story = {
  args: {
    section: { ...section, content: { url: "https://www.youtube.com/embed/aqz-KE-bpKQ" } },
  },
};

/** Arabic locale — heading and caption render in Arabic. */
export const Arabic: Story = {
  decorators: [(Story: () => import("react").ReactElement) => (
    <I18nProvider initialLocale="ar">
      {Story()}
    </I18nProvider>
  )],
};

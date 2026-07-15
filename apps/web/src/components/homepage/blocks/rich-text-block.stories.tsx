import type { Meta, StoryObj } from "@storybook/react";
import { I18nProvider } from "@/lib/i18n/i18n-context";
import type { HomepageSection } from "@/lib/homepage/api";
import { RichTextBlock } from "@/components/homepage/blocks/rich-text-block";

/**
 * RichTextBlock — CMS prose block. Reads `section.content` as
 * { title?: Localized; body?: Localized } where `body` holds HTML per locale.
 * The HTML is re-sanitized with DOMPurify before injection (defense in depth).
 * Renders nothing when both title and body are empty.
 */
const bodyEn = `
  <p>HElbaron Academy exists to make <strong>world-class professional education</strong> accessible across the Arab world — in both Arabic and English.</p>
  <h3>What we believe</h3>
  <ul>
    <li>Learning should map to real, in-demand skills.</li>
    <li>Great instruction is <em>practical</em>, not theoretical.</li>
    <li>Credentials should be verifiable and respected by employers.</li>
  </ul>
  <blockquote>“The best time to invest in your team's skills was yesterday. The second best time is today.”</blockquote>
  <p>Explore our <a href="/courses">course catalog</a> to get started.</p>
`;

const bodyAr = `
  <p>توجد أكاديمية الباذرون لجعل <strong>التعليم المهني عالمي المستوى</strong> متاحًا في العالم العربي — بالعربية والإنجليزية.</p>
  <h3>بماذا نؤمن</h3>
  <ul>
    <li>ينبغي أن يرتبط التعلّم بمهارات حقيقية ومطلوبة.</li>
    <li>التعليم الجيّد <em>عملي</em> وليس نظريًا.</li>
    <li>يجب أن تكون الشهادات قابلة للتحقّق ومحترمة لدى أصحاب العمل.</li>
  </ul>
  <blockquote>«أفضل وقت للاستثمار في مهارات فريقك كان بالأمس، وثاني أفضل وقت هو اليوم.»</blockquote>
  <p>استكشف <a href="/courses">دليل الدورات</a> للبدء.</p>
`;

const section: HomepageSection = {
  key: "rich_text",
  type: "rich_text",
  position: 1,
  content: {
    title: { en: "About HElbaron", ar: "عن الباذرون" },
    body: { en: bodyEn, ar: bodyAr },
  },
};

const meta = {
  title: "Homepage Blocks/Rich Text",
  component: RichTextBlock,
  parameters: { layout: "fullscreen" },
  tags: ["autodocs"],
  decorators: [(Story: () => import("react").ReactElement) => (
    <I18nProvider>
      {Story()}
    </I18nProvider>
  )],
  args: { section },
} satisfies Meta<typeof RichTextBlock>;

export default meta;
type Story = StoryObj<typeof meta>;

/** Title + sanitized prose (headings, lists, blockquote, link) in the `prose` container. */
export const Default: Story = {};

/** Arabic locale — the Arabic `body` HTML renders in the prose container. */
export const Arabic: Story = {
  decorators: [(Story: () => import("react").ReactElement) => (
    <I18nProvider initialLocale="ar">
      {Story()}
    </I18nProvider>
  )],
};

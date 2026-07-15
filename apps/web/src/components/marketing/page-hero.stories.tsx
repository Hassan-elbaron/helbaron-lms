import type { Meta, StoryObj } from "@storybook/react";
import { I18nProvider } from "@/lib/i18n/i18n-context";
import { pageHeroes } from "@/config/page-heroes";
import { PageHero } from "@/components/marketing/page-hero";

const pages = Object.keys(pageHeroes) as (keyof typeof pageHeroes)[];

const meta = {
  title: "Marketing/PageHero",
  component: PageHero,
  tags: ["autodocs"],
  parameters: { layout: "fullscreen" },
  decorators: [(Story: () => import("react").ReactElement) => (
    <I18nProvider>
      <div className="p-4">
        {Story()}
      </div>
    </I18nProvider>
  )],
  argTypes: {
    page: { control: { type: "select" }, options: pages },
  },
  args: { page: "courses" },
} satisfies Meta<typeof PageHero>;

export default meta;
type Story = StoryObj<typeof meta>;

/** Courses hero — includes a stat block ("12 verticals") and the GraduationCap icon. */
export const Courses: Story = { args: { page: "courses" } };

/** Trainers/mentors hero. */
export const Trainers: Story = { args: { page: "trainers" } };

/** Products/pricing hero — no stat block, uses the Tag icon. */
export const Products: Story = { args: { page: "products" } };

/** Orders/account hero. */
export const Orders: Story = { args: { page: "orders" } };

/** Every configured page hero rendered in a stack. */
export const AllPages: Story = {
  render: () => (
    <div className="space-y-4">
      {pages.map((page) => (
        <PageHero key={page} page={page} />
      ))}
    </div>
  ),
};

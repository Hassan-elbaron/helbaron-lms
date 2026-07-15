import type { Meta, StoryObj } from "@storybook/react";
import type { Trainer } from "@/lib/catalog/api";
import { TrainerCard } from "@/components/catalog/trainer-card";

const base: Trainer = {
  id: "trn_omar_farouk",
  name: "Omar Farouk",
  headline: "Ex-McKinsey · Leadership & operations coach for MENA scale-ups",
  avatar_path: null,
};

const meta = {
  title: "Catalog/TrainerCard",
  component: TrainerCard,
  tags: ["autodocs"],
  decorators: [(Story: () => import("react").ReactElement) => (
    <div className="w-96">
      {Story()}
    </div>
  )],
  args: { trainer: base },
} satisfies Meta<typeof TrainerCard>;

export default meta;
type Story = StoryObj<typeof meta>;

/** Trainer with an avatar fallback (initials) + headline. */
export const Default: Story = {};

/** Arabic (RTL) trainer profile. */
export const Arabic: Story = {
  args: {
    trainer: {
      id: "trn_nour_hassan",
      name: "نور حسن",
      headline: "خبيرة الذكاء الاصطناعي للأعمال · درّبت أكثر من 5000 متعلّم في المنطقة",
      avatar_path: null,
    },
  },
};

/** No headline — only the initials avatar and name are shown. */
export const Minimal: Story = {
  args: {
    trainer: { id: "trn_laila_mansour", name: "Laila Mansour", headline: null, avatar_path: null },
  },
};

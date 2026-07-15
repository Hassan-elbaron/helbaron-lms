import type { Meta, StoryObj } from "@storybook/react";
import { I18nProvider } from "@/lib/i18n/i18n-context";
import type { DemoCourse } from "@/config/demo";
import { CoursePreviewCard } from "@/components/marketing/course-preview-card";

const base: DemoCourse = {
  id: "d1",
  code: "PM",
  color: "teal",
  title: { en: "Project Management Foundations", ar: "أساسيات إدارة المشاريع" },
  category: { en: "Project Management", ar: "إدارة المشاريع" },
  level: { en: "Beginner", ar: "مبتدئ" },
  trainer: "Yara Adel",
  price: "SAR 109",
  rating: "4.9",
  lessons: 42,
  hours: 6,
  youtubeId: "u4ZoJKF_VuA",
};

const meta = {
  title: "Marketing/CoursePreviewCard",
  component: CoursePreviewCard,
  tags: ["autodocs"],
  decorators: [(Story: () => import("react").ReactElement) => (
    <I18nProvider>
      <div className="w-80">
        {Story()}
      </div>
    </I18nProvider>
  )],
  argTypes: {
    onPlay: { action: "play-preview" },
  },
  args: { course: base, onPlay: () => {} },
} satisfies Meta<typeof CoursePreviewCard>;

export default meta;
type Story = StoryObj<typeof meta>;

/** Teal beginner course with rating, lesson count, and runtime. */
export const Default: Story = {};

/** Gold "hot" AI course, advanced level. */
export const BusinessAI: Story = {
  args: {
    course: {
      ...base,
      id: "d3",
      code: "AI",
      color: "gold",
      title: { en: "Business AI for Decision Makers", ar: "الذكاء الاصطناعي للأعمال لصنّاع القرار" },
      category: { en: "Business AI", ar: "الذكاء الاصطناعي للأعمال" },
      level: { en: "Intermediate", ar: "متوسط" },
      trainer: "Nour Hassan",
      price: "SAR 149",
      rating: "5.0",
      lessons: 28,
      hours: 4,
    },
  },
};

/** Copper entrepreneurship course. */
export const Entrepreneurship: Story = {
  args: {
    course: {
      ...base,
      id: "d6",
      code: "EN",
      color: "copper",
      title: { en: "Entrepreneurship: 0 to Launch", ar: "ريادة الأعمال: من الصفر للإطلاق" },
      category: { en: "Entrepreneurship", ar: "ريادة الأعمال" },
      level: { en: "Beginner", ar: "مبتدئ" },
      trainer: "Hana Zaki",
      price: "SAR 135",
      rating: "4.9",
      lessons: 52,
      hours: 9,
    },
  },
};

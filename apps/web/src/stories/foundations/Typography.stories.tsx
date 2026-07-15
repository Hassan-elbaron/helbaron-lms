import type { Meta, StoryObj } from "@storybook/react";

/**
 * Foundations · Typography
 * -------------------------
 * The fluid type scale from `globals.css` (`.text-display` … `.text-button` plus the
 * documented role aliases). Each role clamps between a mobile min and a desktop max.
 * Samples are shown in English (LTR) and Arabic (RTL) — Arabic headings fall back to
 * the IBM Plex Sans Arabic family automatically via the `[dir="rtl"]` rule.
 */

type Role = { cls: string; label: string };

const ROLES: Role[] = [
  { cls: "text-display", label: ".text-display" },
  { cls: "text-h1", label: ".text-h1" },
  { cls: "text-h2", label: ".text-h2" },
  { cls: "text-h3", label: ".text-h3" },
  { cls: "text-h4", label: ".text-h4" },
  { cls: "text-h5", label: ".text-h5" },
  { cls: "text-h6", label: ".text-h6" },
  { cls: "text-subtitle", label: ".text-subtitle" },
  { cls: "text-body", label: ".text-body" },
  { cls: "text-caption", label: ".text-caption" },
  { cls: "text-label", label: ".text-label" },
  { cls: "text-button", label: ".text-button" },
];

const ALIASES: Role[] = [
  { cls: "text-hero", label: ".text-hero → display" },
  { cls: "text-dashboard-title", label: ".text-dashboard-title → h2" },
  { cls: "text-card-title", label: ".text-card-title → h4" },
  { cls: "text-table", label: ".text-table → caption" },
  { cls: "text-nav", label: ".text-nav → label" },
  { cls: "text-form-label", label: ".text-form-label → label" },
];

const EN = "The quick brown fox jumps over the lazy dog";
const AR = "الأكاديمية المهنية ثنائية اللغة — تعلّم بلا حدود";

function Sample({ role }: { role: Role }) {
  return (
    <div className="border-b border-border py-4">
      <code className="mb-1 block font-mono text-caption text-muted-foreground">{role.label}</code>
      <p className={role.cls}>{EN}</p>
      <p className={role.cls} dir="rtl" lang="ar">
        {AR}
      </p>
    </div>
  );
}

const meta: Meta = {
  title: "Foundations/Typography",
  parameters: { layout: "padded", a11y: { test: "off" } },
  tags: ["autodocs"],
};
export default meta;
type Story = StoryObj;

export const TypeScale: Story = {
  render: () => (
    <div className="max-w-4xl">
      {ROLES.map((r) => (
        <Sample key={r.cls} role={r} />
      ))}
    </div>
  ),
};

export const RoleAliases: Story = {
  render: () => (
    <div className="max-w-4xl">
      {ALIASES.map((r) => (
        <Sample key={r.cls} role={r} />
      ))}
    </div>
  ),
};

export const Bilingual: Story = {
  name: "Bilingual (EN / AR)",
  render: () => (
    <div className="grid gap-8 lg:grid-cols-2">
      <div>
        <h3 className="text-h4 mb-4">English · LTR</h3>
        <h1 className="text-h1">Learn without limits</h1>
        <p className="text-subtitle mt-2 text-muted-foreground">
          A bilingual professional academy for the MENA region.
        </p>
        <p className="text-body mt-4">
          Enrol in expert-led courses, earn verifiable certificates, and track your
          progress across every programme.
        </p>
      </div>
      <div dir="rtl" lang="ar">
        <h3 className="text-h4 mb-4">العربية · RTL</h3>
        <h1 className="text-h1">تعلّم بلا حدود</h1>
        <p className="text-subtitle mt-2 text-muted-foreground">
          أكاديمية مهنية ثنائية اللغة لمنطقة الشرق الأوسط وشمال أفريقيا.
        </p>
        <p className="text-body mt-4">
          سجّل في دورات يقودها الخبراء، واحصل على شهادات قابلة للتحقق، وتابع تقدّمك عبر كل
          برنامج.
        </p>
      </div>
    </div>
  ),
};

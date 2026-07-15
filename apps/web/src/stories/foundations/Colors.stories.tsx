import type { Meta, StoryObj } from "@storybook/react";

/**
 * Foundations · Colors & Tokens
 * ------------------------------
 * Visual swatch reference for every semantic colour family (light + dark shown
 * side-by-side) plus the spacing, radius, shadow/elevation, z-index and opacity
 * scales. Everything is read straight from the CSS custom properties defined in
 * `src/app/globals.css` — this story is a mirror, never a redefinition.
 */

type Swatch = { name: string; varName: string; fg?: string };

const CORE: Swatch[] = [
  { name: "background", varName: "--background", fg: "--foreground" },
  { name: "foreground", varName: "--foreground", fg: "--background" },
  { name: "card", varName: "--card", fg: "--card-foreground" },
  { name: "popover", varName: "--popover", fg: "--popover-foreground" },
  { name: "surface", varName: "--surface", fg: "--surface-foreground" },
  { name: "muted", varName: "--muted", fg: "--muted-foreground" },
  { name: "border", varName: "--border", fg: "--foreground" },
  { name: "input", varName: "--input", fg: "--foreground" },
  { name: "ring", varName: "--ring", fg: "--background" },
];

const BRAND: Swatch[] = [
  { name: "primary", varName: "--primary", fg: "--primary-foreground" },
  { name: "secondary", varName: "--secondary", fg: "--secondary-foreground" },
  { name: "accent", varName: "--accent", fg: "--accent-foreground" },
  { name: "copper", varName: "--copper", fg: "--copper-foreground" },
  { name: "gold", varName: "--gold", fg: "--gold-foreground" },
];

const STATUS: Swatch[] = [
  { name: "destructive", varName: "--destructive", fg: "--destructive-foreground" },
  { name: "success", varName: "--success", fg: "--success-foreground" },
  { name: "warning", varName: "--warning", fg: "--warning-foreground" },
  { name: "info", varName: "--info", fg: "--info-foreground" },
];

const CHROME: Swatch[] = [
  { name: "sidebar", varName: "--sidebar", fg: "--sidebar-foreground" },
  { name: "header", varName: "--header", fg: "--header-foreground" },
  { name: "footer", varName: "--footer", fg: "--footer-foreground" },
  { name: "overlay", varName: "--overlay", fg: "--foreground" },
];

function SwatchGrid({ items }: { items: Swatch[] }) {
  return (
    <div className="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4">
      {items.map((s) => (
        <div
          key={s.name}
          className="rounded-lg border border-border overflow-hidden text-caption"
        >
          <div
            className="flex h-20 items-end p-2"
            style={{
              background: `var(${s.varName})`,
              color: s.fg ? `var(${s.fg})` : undefined,
            }}
          >
            <span className="font-medium">{s.name}</span>
          </div>
          <div className="bg-card px-2 py-1 font-mono text-[11px] text-muted-foreground">
            {s.varName}
          </div>
        </div>
      ))}
    </div>
  );
}

function ThemedPanel({ dark, children }: { dark?: boolean; children: React.ReactNode }) {
  return (
    <div className={dark ? "dark" : ""}>
      <div className="rounded-xl border border-border bg-background p-4 text-foreground">
        <p className="mb-3 text-label uppercase tracking-wide text-muted-foreground">
          {dark ? "Dark" : "Light"}
        </p>
        {children}
      </div>
    </div>
  );
}

function ColorSection({ title, items }: { title: string; items: Swatch[] }) {
  return (
    <section className="space-y-3">
      <h3 className="text-h4">{title}</h3>
      <div className="grid gap-4 lg:grid-cols-2">
        <ThemedPanel>
          <SwatchGrid items={items} />
        </ThemedPanel>
        <ThemedPanel dark>
          <SwatchGrid items={items} />
        </ThemedPanel>
      </div>
    </section>
  );
}

const SPACING = [
  "0", "px", "0-5", "1", "1-5", "2", "3", "4", "5", "6", "8", "10", "12", "16", "20", "24",
];
const RADII = ["sm", "md", "lg", "xl", "2xl", "full"];
const SHADOWS = ["xs", "sm", "md", "lg", "xl", "2xl"];
const ELEVATIONS = [0, 1, 2, 3, 4, 5];
const ZINDEX = [
  ["--z-base", "0"],
  ["--z-dropdown", "1000"],
  ["--z-sticky", "1100"],
  ["--z-overlay", "1200"],
  ["--z-drawer", "1300"],
  ["--z-modal", "1400"],
  ["--z-popover", "1500"],
  ["--z-toast", "1600"],
  ["--z-tooltip", "1700"],
];
const OPACITY = ["0", "5", "10", "20", "40", "60", "80", "100", "disabled", "muted", "hover"];

const meta: Meta = {
  title: "Foundations/Colors",
  parameters: {
    layout: "padded",
    a11y: { test: "off" },
  },
  tags: ["autodocs"],
};
export default meta;
type Story = StoryObj;

export const SemanticColors: Story = {
  render: () => (
    <div className="space-y-8">
      <ColorSection title="Core surfaces" items={CORE} />
      <ColorSection title="Brand" items={BRAND} />
      <ColorSection title="Status" items={STATUS} />
      <ColorSection title="App chrome" items={CHROME} />
    </div>
  ),
};

export const Spacing: Story = {
  render: () => (
    <div className="space-y-2">
      <h3 className="text-h4 mb-3">Spacing scale (--space-*)</h3>
      {SPACING.map((s) => (
        <div key={s} className="flex items-center gap-4 text-caption">
          <code className="w-24 font-mono text-muted-foreground">--space-{s}</code>
          <div className="h-4 bg-primary rounded-sm" style={{ width: `var(--space-${s})` }} />
        </div>
      ))}
    </div>
  ),
};

export const Radius: Story = {
  render: () => (
    <div className="flex flex-wrap gap-6">
      {RADII.map((r) => (
        <div key={r} className="text-center text-caption">
          <div
            className="h-20 w-20 border-2 border-primary bg-accent"
            style={{ borderRadius: `var(--radius-${r})` }}
          />
          <code className="mt-2 block font-mono text-muted-foreground">--radius-{r}</code>
        </div>
      ))}
    </div>
  ),
};

export const Shadows: Story = {
  render: () => (
    <div className="flex flex-wrap gap-8 p-4">
      {SHADOWS.map((s) => (
        <div key={s} className="text-center text-caption">
          <div
            className="h-24 w-24 rounded-lg bg-card"
            style={{ boxShadow: `var(--shadow-${s})` }}
          />
          <code className="mt-3 block font-mono text-muted-foreground">--shadow-{s}</code>
        </div>
      ))}
    </div>
  ),
};

export const Elevation: Story = {
  render: () => (
    <div className="flex flex-wrap gap-8 p-4">
      {ELEVATIONS.map((e) => (
        <div key={e} className="text-center text-caption">
          <div className={`elevation-${e} h-24 w-24 rounded-lg bg-card`} />
          <code className="mt-3 block font-mono text-muted-foreground">.elevation-{e}</code>
        </div>
      ))}
    </div>
  ),
};

export const ZIndex: Story = {
  render: () => (
    <div className="space-y-1 text-caption">
      <h3 className="text-h4 mb-3">Z-index scale</h3>
      {ZINDEX.map(([name, val]) => (
        <div key={name} className="flex items-center gap-4">
          <code className="w-40 font-mono text-muted-foreground">{name}</code>
          <span className="font-mono">{val}</span>
        </div>
      ))}
    </div>
  ),
};

export const Opacity: Story = {
  render: () => (
    <div className="flex flex-wrap gap-4">
      {OPACITY.map((o) => (
        <div key={o} className="text-center text-caption">
          <div
            className="h-16 w-16 rounded-md bg-primary"
            style={{ opacity: `var(--opacity-${o})` }}
          />
          <code className="mt-2 block font-mono text-muted-foreground">--opacity-{o}</code>
        </div>
      ))}
    </div>
  ),
};

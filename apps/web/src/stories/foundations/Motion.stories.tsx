import * as React from "react";
import type { Meta, StoryObj } from "@storybook/react";

/**
 * Foundations · Motion
 * ---------------------
 * The consolidated, token-driven motion system from `globals.css`. Every pattern is a
 * `.motion-*` utility built on the duration (`--duration-*`) and easing (`--ease-*`)
 * tokens. All patterns are **reduced-motion safe** — under `prefers-reduced-motion:
 * reduce` the animations are disabled (see the note below). Use the **Replay** button
 * to re-trigger a pattern (it re-mounts the element so the `both`-filled animation runs
 * again).
 */

const PATTERNS = [
  "motion-fade-in",
  "motion-scale-in",
  "motion-slide-up",
  "motion-slide-down",
  "motion-slide-start",
  "motion-slide-end",
  "motion-page",
  "motion-modal",
  "motion-toast",
  "motion-drawer",
  "motion-dropdown",
  "motion-expand",
];

const LOOPING = ["motion-spin", "motion-pulse", "motion-hover"];

function ReplayCard({ cls }: { cls: string }) {
  const [k, setK] = React.useState(0);
  return (
    <div className="rounded-lg border border-border bg-card p-4">
      <div className="mb-3 flex items-center justify-between">
        <code className="font-mono text-caption text-muted-foreground">.{cls}</code>
        <button
          type="button"
          onClick={() => setK((n) => n + 1)}
          className="rounded-md bg-primary px-2 py-1 text-[11px] font-medium text-primary-foreground hover:bg-primary/90"
        >
          Replay
        </button>
      </div>
      <div className="flex h-24 items-center justify-center overflow-hidden rounded-md bg-muted">
        <div
          key={k}
          className={`${cls} flex h-14 w-28 items-center justify-center rounded-md bg-primary text-caption text-primary-foreground`}
        >
          {cls.replace("motion-", "")}
        </div>
      </div>
    </div>
  );
}

function LoopCard({ cls }: { cls: string }) {
  return (
    <div className="rounded-lg border border-border bg-card p-4">
      <code className="mb-3 block font-mono text-caption text-muted-foreground">.{cls}</code>
      <div className="flex h-24 items-center justify-center rounded-md bg-muted">
        <div
          className={`${cls} flex h-14 w-28 items-center justify-center rounded-md bg-primary text-caption text-primary-foreground`}
        >
          {cls.replace("motion-", "")}
        </div>
      </div>
    </div>
  );
}

const meta: Meta = {
  title: "Foundations/Motion",
  parameters: { layout: "padded", a11y: { test: "off" } },
  tags: ["autodocs"],
};
export default meta;
type Story = StoryObj;

export const EntrancePatterns: Story = {
  render: () => (
    <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
      {PATTERNS.map((p) => (
        <ReplayCard key={p} cls={p} />
      ))}
    </div>
  ),
};

export const LoopingAndHover: Story = {
  render: () => (
    <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
      {LOOPING.map((p) => (
        <LoopCard key={p} cls={p} />
      ))}
    </div>
  ),
};

export const ReducedMotionNote: Story = {
  render: () => (
    <div className="max-w-2xl space-y-3 text-body">
      <h3 className="text-h4">Reduced motion</h3>
      <p className="text-muted-foreground">
        Every <code className="font-mono">.motion-*</code> utility is guarded by a{" "}
        <code className="font-mono">@media (prefers-reduced-motion: reduce)</code> block
        that sets <code className="font-mono">animation: none</code> and removes hover
        transforms. Enable “Reduce motion” in your OS accessibility settings (or the
        browser emulation) and the patterns above render in their final state instantly —
        no movement, no flashing.
      </p>
      <p className="text-muted-foreground">
        The scrim/overlay also honours{" "}
        <code className="font-mono">prefers-reduced-transparency</code>, becoming opaque
        and dropping backdrop blur.
      </p>
    </div>
  ),
};

export const Durations: Story = {
  render: () => (
    <div className="space-y-2 text-caption">
      <h3 className="text-h4 mb-3">Duration &amp; easing tokens</h3>
      {[
        ["--duration-instant", "75ms"],
        ["--duration-fast", "150ms"],
        ["--duration-normal", "250ms"],
        ["--duration-slow", "400ms"],
        ["--duration-slower", "600ms"],
      ].map(([name, val]) => (
        <div key={name} className="flex items-center gap-4">
          <code className="w-44 font-mono text-muted-foreground">{name}</code>
          <span className="font-mono">{val}</span>
        </div>
      ))}
      <div className="mt-4 space-y-1">
        {[
          "--ease-standard",
          "--ease-emphasized",
          "--ease-spring",
          "--ease-decelerate",
          "--ease-accelerate",
        ].map((name) => (
          <code key={name} className="block font-mono text-muted-foreground">
            {name}
          </code>
        ))}
      </div>
    </div>
  ),
};

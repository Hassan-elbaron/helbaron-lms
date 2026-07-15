import type { StorybookConfig } from "@storybook/nextjs";

/**
 * Storybook is the visual source of truth for the HElbaron Design System.
 * Framework: @storybook/nextjs (Next 15 App Router + SWC + next/font + Tailwind v4).
 * Additive only — it documents the existing DS; it does not modify it.
 */
const config: StorybookConfig = {
  stories: ["../src/**/*.stories.@(ts|tsx)"],
  addons: [
    "@storybook/addon-essentials",
    "@storybook/addon-a11y",
    "@storybook/addon-interactions",
    "@storybook/addon-themes",
  ],
  framework: {
    name: "@storybook/nextjs",
    options: {},
  },
  staticDirs: ["../public"],
  core: {
    disableTelemetry: true,
  },
  docs: {
    autodocs: "tag",
  },
  typescript: {
    // react-docgen keeps prop tables lightweight and avoids slow TS program builds.
    reactDocgen: "react-docgen",
  },
};

export default config;

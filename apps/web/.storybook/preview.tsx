import * as React from "react";
import type { Preview, Decorator } from "@storybook/react";
import { withThemeByClassName } from "@storybook/addon-themes";

// Design-system tokens + Tailwind v4 layers (colors, spacing, radius, shadow,
// z-index, motion, fluid type). Everything downstream reads these CSS variables.
import "../src/app/globals.css";
// Storybook-only font fallbacks (next/font is not loaded here).
import "./storybook.css";

/**
 * Direction toggle (LTR/RTL). Sets `dir` + `lang` on the story wrapper AND on the
 * iframe <html> element so that Radix portals (dialogs, dropdowns, tooltips, toasts)
 * — which render to document.body, outside the wrapper — also mirror correctly.
 * Mirrors next-themes/i18n: `dir="rtl" lang="ar"` drives the logical-property layout.
 */
const withDirection: Decorator = (Story, context) => {
  const dir = (context.globals.direction as "ltr" | "rtl") ?? "ltr";
  const lang = dir === "rtl" ? "ar" : "en";

  React.useEffect(() => {
    const html = document.documentElement;
    html.setAttribute("dir", dir);
    html.setAttribute("lang", lang);
    return () => {
      html.removeAttribute("dir");
      html.removeAttribute("lang");
    };
  }, [dir, lang]);

  return (
    <div dir={dir} lang={lang} className="sb-story-root p-6 min-h-[8rem]">
      <Story />
    </div>
  );
};

const preview: Preview = {
  parameters: {
    controls: {
      matchers: {
        color: /(background|color)$/i,
        date: /Date$/i,
      },
    },
    a11y: {
      // Report issues but do not fail the story render.
      test: "todo",
    },
    layout: "fullscreen",
    options: {
      storySort: {
        order: [
          "Foundations",
          ["Colors", "Typography", "Icons", "Motion"],
          "Primitives",
          "Forms",
          "Data",
          "Charts",
          "States",
          "Widgets",
          "Catalog",
          "Commerce",
          "Marketing",
          "CRM",
          "Homepage Blocks",
        ],
      },
    },
  },
  // The Direction toolbar item (light/dark comes from addon-themes below).
  globalTypes: {
    direction: {
      name: "Direction",
      description: "Text direction (LTR / RTL)",
      defaultValue: "ltr",
      toolbar: {
        icon: "transfer",
        items: [
          { value: "ltr", title: "LTR — English", right: "EN" },
          { value: "rtl", title: "RTL — العربية", right: "AR" },
        ],
        dynamicTitle: true,
      },
    },
  },
  decorators: [
    withDirection,
    // Toggles the `.dark` class on <html> (matching next-themes' class strategy),
    // so tokens AND portalled content switch theme together.
    withThemeByClassName({
      themes: { light: "", dark: "dark" },
      defaultTheme: "light",
      parentSelector: "html",
    }),
  ],
  tags: ["autodocs"],
};

export default preview;

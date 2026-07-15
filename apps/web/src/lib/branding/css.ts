import type { BrandColors, Branding } from "@/lib/branding/api";

/**
 * Maps the branding `theme.colors` keys onto the ACTUAL CSS custom properties used in
 * apps/web/src/app/globals.css. Every key now has a real CSS variable (the design-token
 * foundation added info/sidebar/header/footer as first-class families). Because defaultBranding
 * mirrors the globals.css values, emitting these lines stays a visual no-op by default.
 *
 *   primary    -> --primary (+ --ring, which globals.css derives from primary)
 *   secondary  -> --secondary
 *   accent     -> --accent
 *   success    -> --success
 *   warning    -> --warning
 *   danger     -> --destructive   (globals.css names the danger colour `--destructive`)
 *   info       -> --info
 *   background -> --background
 *   surface    -> --card          (globals.css names the raised surface colour `--card`)
 *   sidebar    -> --sidebar
 *   header     -> --header
 *   footer     -> --footer
 */
const COLOR_VAR_MAP: Partial<Record<keyof BrandColors, string[]>> = {
  primary: ["--primary", "--ring"],
  secondary: ["--secondary"],
  accent: ["--accent"],
  success: ["--success"],
  warning: ["--warning"],
  danger: ["--destructive"],
  info: ["--info"],
  background: ["--background"],
  surface: ["--card"],
  sidebar: ["--sidebar"],
  header: ["--header"],
  footer: ["--footer"],
};

/** Emit `--var: value;` lines for every mapped, non-empty colour. */
function colorLines(colors: Partial<BrandColors>): string[] {
  const lines: string[] = [];
  for (const [key, vars] of Object.entries(COLOR_VAR_MAP) as [keyof BrandColors, string[]][]) {
    const value = colors[key];
    if (typeof value === "string" && value.trim() !== "") {
      for (const cssVar of vars) lines.push(`${cssVar}: ${value};`);
    }
  }
  return lines;
}

/**
 * Build the `<style id="brand-theme">` body that overrides the globals.css CSS variables on `:root`
 * (light) and `.dark` (from theme.dark) plus radius/container width. Values equal to the defaults are
 * a visual no-op, so partial or empty branding degrades gracefully to the Editorial Academy design.
 */
export function brandThemeCss(branding: Branding): string {
  const t = branding.theme;

  const root = [...colorLines(t.colors)];
  if (t.radius?.trim()) root.push(`--radius: ${t.radius};`);
  if (t.container_width?.trim()) root.push(`--container-width: ${t.container_width};`);

  const dark = colorLines(t.dark);

  const blocks: string[] = [];
  if (root.length > 0) blocks.push(`:root{${root.join("")}}`);
  if (dark.length > 0) blocks.push(`.dark{${dark.join("")}}`);
  return blocks.join("");
}

/**
 * A Google Fonts <link> href for the chosen family, or null. NOTE: the two bundled fonts (Inter body,
 * Fraunces headings) are loaded at build time via next/font and remain the default typography. A
 * custom `theme.google_font` is loaded as an ADDITIONAL web font and applied to body/UI text via the
 * --font-sans variable override in the brand-theme style; swapping the build-time bundled fonts
 * themselves is a build concern and is not done at runtime.
 */
export function googleFontHref(family: string): string | null {
  const name = family.trim();
  if (name === "") return null;
  const q = encodeURIComponent(name).replace(/%20/g, "+");
  return `https://fonts.googleapis.com/css2?family=${q}:wght@400;500;600;700&display=swap`;
}

/** CSS overriding --font-sans to the chosen Google font (headings keep the bundled serif). */
export function googleFontCss(family: string): string {
  const name = family.trim();
  if (name === "") return "";
  return `:root{--font-sans:"${name}",var(--font-inter),ui-sans-serif,system-ui,sans-serif;}`;
}

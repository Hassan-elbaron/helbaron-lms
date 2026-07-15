# HElbaron Design System

> Editorial Academy — a premium MENA-academy identity for the HElbaron LMS.
> Laravel 12 API + Next.js 15 (App Router) web client, Tailwind CSS v4, bilingual
> EN/AR with full RTL, light + dark themes, and a runtime white-label Theme Manager.

This document is the entry point for the design system. It explains the principles,
the architecture, how the pieces fit together (design tokens ↔ Theme Manager ↔
Tailwind v4), how to consume the system in application code, the folder map, and the
rules for contributing.

Companion documents in this folder:

| Document | Scope |
| --- | --- |
| `DESIGN_TOKENS.md` | Every token family, exact CSS variable names, light/dark values, Theme-Manager overrides, how to add a token |
| `COMPONENT_LIBRARY.md` | Every UI primitive, its variants/props/states, the new primitives, domain card language, usage examples |
| `TYPOGRAPHY.md` | Fluid type scale, role tokens/utilities, EN/AR fonts, RTL heading behaviour |
| `SPACING.md` | The single spacing scale, container widths, layout conventions |
| `ACCESSIBILITY.md` | WCAG 2.2 AA approach: landmarks, focus, keyboard, per-component ARIA, contrast, reduced motion/transparency, axe specs |
| `MOTION.md` | Duration/easing tokens, `.motion-*` utilities, reduced-motion/transparency guarding |
| `ICONOGRAPHY.md` | Single icon family (lucide), the `Icon` wrapper, sizes/stroke, ARIA conventions |
| `DASHBOARD_GUIDELINES.md` | Page scaffold: `PageHeader` + responsive widget grid + standardized cards + charts + filters/quick actions |
| `MARKETING_GUIDELINES.md` | Hierarchy, CTA, whitespace rhythm, fluid headings, responsive, a11y for marketing surfaces |

---

## 1. Principles

1. **Token-first.** Colour, spacing, radius, shadow/elevation, z-index, opacity, focus,
   motion and typography are all expressed as CSS custom properties in one place
   (`apps/web/src/app/globals.css`). Components consume tokens, never hard-coded values.
2. **Additive and non-breaking.** The token foundation was layered in without overriding
   Tailwind's built-in numeric scales (spacing, shadow, `max-w-*`) — new families use
   distinct names (`--space-*`, `--shadow-*`/`.elevation-*`, `--container-width-*`) so
   existing utilities keep working. Every addition is a visual no-op until a component
   opts in.
3. **Theme-Manager-safe.** The runtime white-label layer only ever overrides a known set
   of variables. Because the shipped defaults mirror the token defaults, injecting the
   default branding is a visual no-op. See §4.
4. **Bilingual + RTL by construction.** Components use CSS logical properties
   (`ms-/me-/ps-/pe-`, `start/end`, `text-start`) so they mirror automatically under
   `dir="rtl"`. Arabic typography falls back to a dedicated Arabic family.
5. **Accessible by default.** Focus-visible rings are global and token-driven; every
   interactive primitive ships correct roles/ARIA; colour is never the sole carrier of
   meaning; motion and transparency respect user preferences. Target: WCAG 2.2 AA.
6. **Dependency-light.** Charts are a hand-rolled, dependency-free tokenized SVG layer;
   several primitives (tooltip, popover, accordion, radio-group, switch) are custom rather
   than pulling additional libraries.

---

## 2. Architecture

```
                       ┌───────────────────────────────────────────┐
                       │  globals.css  (single source of truth)     │
                       │  :root / .dark  → CSS custom properties     │
                       │  @theme inline  → expose tokens to Tailwind │
                       │  @layer utilities → fluid type, elevation,  │
                       │                     motion, rhythm helpers  │
                       └───────────────┬───────────────────────────┘
                                       │  var(--token)
        ┌──────────────────────────────┼──────────────────────────────┐
        │                              │                               │
┌───────▼────────┐            ┌────────▼─────────┐            ┌────────▼─────────┐
│ Tailwind v4     │            │ UI primitives     │            │ Theme Manager     │
│ utilities        │            │ (CVA + tokens)    │            │ (runtime override)│
│ bg-primary, …    │            │ Button, Card, …   │            │ <style id=        │
│ text-h1, …       │            │ charts, data-grid │            │  "brand-theme">   │
└──────────────────┘            └───────────────────┘            └───────────────────┘
```

- **`globals.css`** declares the tokens on `:root` (light) and `.dark` (dark), then uses
  Tailwind v4's `@theme inline` to expose the colour/radius/font/easing tokens as
  first-class utilities (`bg-primary`, `text-info-foreground`, `rounded-lg`, …). Fluid
  type, elevation, spacing rhythm and the motion system are defined as `@layer utilities`
  classes that read the raw tokens.
- **Tailwind v4** is configured CSS-first (no `tailwind.config.js`) via `@import
  "tailwindcss"` + `@theme inline`. The `.dark` class is driven by `next-themes`; RTL is
  driven by the `dir` attribute on `<html>`.
- **UI primitives** live in `apps/web/src/components/ui/` and use
  `class-variance-authority` (CVA) with a consistent variant vocabulary. They only ever
  reference token-backed utilities.
- **The Theme Manager** (`apps/web/src/lib/branding/css.ts`) emits a small
  `<style id="brand-theme">` block at runtime that re-assigns a curated subset of the
  same variables from admin-configured branding.

---

## 3. How tokens + Tailwind v4 fit

Tailwind v4 is used in its CSS-first form. The relevant sections of `globals.css`:

```css
@import "tailwindcss";
@plugin "tailwindcss-animate";
@custom-variant dark (&:where(.dark, .dark *));

:root { --primary: oklch(0.36 0.045 185); /* … all tokens … */ }
.dark { --primary: oklch(0.62 0.07 183); /* … dark overrides … */ }

@theme inline {
  --color-primary: var(--primary);
  --color-info: var(--info);
  --radius-lg: var(--radius);
  --font-sans: var(--font-inter), var(--font-arabic), system-ui, …;
  --ease-standard: cubic-bezier(0.2, 0, 0, 1);
}
```

- Colours are declared once as raw `--primary`, `--info`, … then re-exported inside
  `@theme inline` as `--color-*`, which is what generates `bg-*` / `text-* `/ `border-*`
  utilities. This indirection is deliberate: the Theme Manager overrides the raw
  `--primary`, and every utility that resolves through `--color-primary → var(--primary)`
  updates automatically.
- Non-colour token families (`--space-*`, `--shadow-*`, `--z-*`, `--opacity-*`, the fluid
  type tokens) are intentionally **not** registered in `@theme`, so Tailwind's built-in
  numeric scales stay intact. They are consumed either through arbitrary-value utilities
  (e.g. `duration-[--duration-fast]`, `opacity-[--opacity-disabled]`, `z-[--z-modal]`) or
  through the purpose-built utility classes (`.elevation-2`, `.text-h1`, `.stack-4`,
  `.motion-fade-in`).

---

## 4. How the Theme Manager fits

`apps/web/src/lib/branding/css.ts` is the contract between admin white-labelling and the
token layer. `brandThemeCss(branding)` builds the body of `<style id="brand-theme">`,
which is injected in `app/layout.tsx`.

`COLOR_VAR_MAP` maps branding colour keys onto the real CSS variables:

| Branding key | CSS variable(s) |
| --- | --- |
| `primary` | `--primary`, `--ring` |
| `secondary` | `--secondary` |
| `accent` | `--accent` |
| `success` | `--success` |
| `warning` | `--warning` |
| `danger` | `--destructive` |
| `info` | `--info` |
| `background` | `--background` |
| `surface` | `--card` |
| `sidebar` | `--sidebar` |
| `header` | `--header` |
| `footer` | `--footer` |

Plus `--radius` and `--container-width` from `theme.radius` / `theme.container_width`, and
an optional Google-font override of `--font-sans` (headings keep the bundled serif). Light
values go on `:root`, dark values (`theme.dark`) go on `.dark`.

**The key invariant:** `defaultBranding` mirrors the values already in `globals.css`, so
emitting the default brand theme reassigns each variable to the value it already has — a
visual no-op. Partial or empty branding therefore degrades gracefully back to the shipped
Editorial Academy design. Any component that consumes tokens is automatically
white-label-ready; no component needs branding-specific code.

---

## 5. How to consume the system

- **Colour:** use the semantic utilities — `bg-primary text-primary-foreground`,
  `bg-card`, `bg-surface`, `text-muted-foreground`, `border-border`, `bg-info`,
  `bg-sidebar text-sidebar-foreground`. Never hard-code hex.
- **Typography:** apply the role utilities — `text-display`, `text-h1`…`text-h6`,
  `text-subtitle`, `text-body`, `text-caption`, `text-label`, `text-button`, or the
  aliases `text-hero`, `text-card-title`, `text-dashboard-title`, `text-nav`,
  `text-table`, `text-form-label`. See `TYPOGRAPHY.md`.
- **Spacing:** use Tailwind's numeric spacing (it mirrors the `--space-*` rhythm) or the
  `.stack-*` vertical-rhythm helpers; use `.container`-style widths via
  `--container-width*`. See `SPACING.md`.
- **Elevation & radius:** `.elevation-0`…`.elevation-5` (dark-aware) for raised surfaces;
  `rounded-sm/md/lg/xl/2xl/full` from the radius scale.
- **Motion:** `.motion-fade-in`, `.motion-scale-in`, `.motion-slide-up/-start/-end`,
  `.motion-modal`, `.motion-toast`, `.motion-spin`, etc. All reduced-motion safe. See
  `MOTION.md`.
- **Components:** import from the barrel `@/components/ui` (Button, Card, Badge, Icon,
  DataGrid, charts, FormField, Form, …) and the state set from `@/components/states`.
- **Icons:** always via the `Icon` wrapper around a lucide icon. See `ICONOGRAPHY.md`.

---

## 6. Folder map

```
apps/web/src/
├─ app/
│  ├─ globals.css                     # token foundation + utilities (source of truth)
│  ├─ layout.tsx                      # fonts, skip link, landmarks, brand-theme injection
│  ├─ robots.ts, sitemap.ts           # design-showcase excluded
│  └─ (dev)/design-system/            # gated design showcase (page.tsx, showcase.tsx, sample-blocks.ts)
├─ components/
│  ├─ ui/                             # primitives (button, badge, card, icon, tooltip, popover,
│  │  │                               #  accordion, spinner, radio-group, switch, textarea, progress,
│  │  │                               #  input, checkbox, select, tabs, dialog, drawer, dropdown-menu,
│  │  │                               #  toast, table, separator, avatar, breadcrumb, pagination, label)
│  │  ├─ data-grid.tsx                # rich table
│  │  ├─ form-field.tsx, form.tsx     # form scaffolding
│  │  ├─ charts/                      # dependency-free tokenized SVG chart layer
│  │  └─ index.ts                     # barrel
│  ├─ states/                         # Loading/Skeleton/Empty/Error/Success/Offline + ComingSoon + ErrorBoundary
│  ├─ student/page-header.tsx         # PageHeader dashboard scaffold
│  └─ crm/lead-status-badge.tsx       # colour-independent status badge
├─ lib/branding/css.ts                # Theme Manager contract (COLOR_VAR_MAP, brandThemeCss)
└─ ...
apps/web/e2e/
├─ a11y.spec.ts                       # axe-core Playwright specs (CI-only)
└─ visual/                            # visual-regression specs + helpers + README (CI-only)
```

---

## 7. Contribution rules

1. **No hard-coded values.** New colour → add a token in `globals.css` (and, if it should
   be a utility, register `--color-*` in `@theme inline`). New spacing/shadow → reuse the
   existing scale.
2. **Keep additions additive.** Do not rename or repurpose existing tokens or Tailwind
   built-ins. Prefer new, distinctly-named families.
3. **Preserve the Theme-Manager contract.** If you add a brandable colour, update
   `COLOR_VAR_MAP` and ensure `defaultBranding` mirrors the new default so injection stays
   a no-op.
4. **CVA vocabulary.** New component variants follow the established vocabulary
   (`default/primary`, `secondary`, `destructive`, `success`, `warning`, `info`,
   `outline`, `ghost`, `link`) and size vocabulary (`sm`, `md`/`default`, `lg`, `icon`).
5. **RTL + a11y are acceptance criteria.** Use logical properties, provide correct
   roles/labels, keep colour non-sole-signal, and honour reduced motion/transparency.
6. **Validate.** `npx tsc --noEmit`, `npx vitest run`, `npx eslint src tests` must stay
   green; add/adjust axe and visual specs where relevant.

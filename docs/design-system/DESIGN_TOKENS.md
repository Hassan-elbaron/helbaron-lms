# Design Tokens

All tokens live in **`apps/web/src/app/globals.css`** and are declared as CSS custom
properties on `:root` (light) and `.dark` (dark). Colour tokens are re-exported to
Tailwind via `@theme inline` (as `--color-*`); non-colour families are consumed directly
through arbitrary-value utilities or purpose-built utility classes so Tailwind's built-in
numeric scales stay untouched.

Colour values are authored in **OKLCH** for perceptual uniformity and predictable
light/dark pairs.

---

## 1. Semantic colour tokens

Each colour family ships a base and a `-foreground` companion (the accessible text/icon
colour to place on top of it).

### Core surfaces & text

| Token | Light | Dark |
| --- | --- | --- |
| `--background` | `oklch(0.962 0.017 88)` | `oklch(0.21 0.022 190)` |
| `--foreground` | `oklch(0.30 0.017 190)` | `oklch(0.94 0.015 88)` |
| `--card` / `--card-foreground` | `oklch(0.99 0.008 88)` / fg | `oklch(0.25 0.026 190)` / fg |
| `--popover` / `--popover-foreground` | `oklch(0.99 0.008 88)` / fg | `oklch(0.25 0.026 190)` / fg |
| `--surface` / `--surface-foreground` | `oklch(0.955 0.014 88)` / fg | `oklch(0.24 0.024 190)` / fg |
| `--overlay` | `oklch(0.21 0.022 190 / 55%)` | `oklch(0.12 0.02 190 / 62%)` |

`--surface` is a subtly recessed neutral (distinct from `--card`, the raised panel).
`--overlay` carries alpha and is the modal/drawer scrim tint.

### Brand & accent

| Token | Light | Dark |
| --- | --- | --- |
| `--primary` / `-foreground` | `oklch(0.36 0.045 185)` (deep teal) | `oklch(0.62 0.07 183)` |
| `--secondary` / `-foreground` | `oklch(0.91 0.03 86)` | `oklch(0.30 0.03 190)` |
| `--accent` / `-foreground` | `oklch(0.90 0.035 70)` (warm) | `oklch(0.33 0.035 60)` |
| `--muted` / `-foreground` | `oklch(0.93 0.02 86)` / `oklch(0.48 0.018 188)` | `oklch(0.29 0.025 190)` / `oklch(0.74 0.02 90)` |
| `--copper` / `-foreground` | `oklch(0.57 0.12 47)` (terracotta) | `oklch(0.64 0.12 47)` |
| `--gold` / `-foreground` | `oklch(0.74 0.10 88)` | `oklch(0.78 0.10 88)` |

### Status / feedback

| Token | Light | Dark |
| --- | --- | --- |
| `--destructive` / `-foreground` | `oklch(0.55 0.19 30)` | `oklch(0.62 0.18 28)` |
| `--success` / `-foreground` | `oklch(0.55 0.11 165)` | `oklch(0.68 0.12 165)` |
| `--warning` / `-foreground` | `oklch(0.74 0.12 82)` | `oklch(0.80 0.13 84)` |
| `--info` / `-foreground` | `oklch(0.60 0.11 240)` | `oklch(0.66 0.11 240)` |

### Form & border

| Token | Light | Dark |
| --- | --- | --- |
| `--border` | `oklch(0.88 0.02 86)` | `oklch(1 0 0 / 12%)` |
| `--input` | `oklch(0.88 0.02 86)` | `oklch(1 0 0 / 14%)` |
| `--ring` | `oklch(0.36 0.045 185)` (= primary) | `oklch(0.62 0.07 183)` |

### Chrome regions (sidebar / header / footer)

| Token | Light | Dark |
| --- | --- | --- |
| `--sidebar` / `-foreground` | `oklch(0.36 0.045 185)` / cream | `oklch(0.25 0.026 190)` / fg |
| `--sidebar-border` | `oklch(0.32 0.04 185)` | `oklch(1 0 0 / 12%)` |
| `--sidebar-accent` | `oklch(0.90 0.035 70)` | `oklch(0.33 0.035 60)` |
| `--header` / `-foreground` | `oklch(0.962 0.017 88)` / fg | `oklch(0.21 0.022 190)` / fg |
| `--footer` / `-foreground` | `oklch(0.36 0.045 185)` / cream | `oklch(0.25 0.026 190)` / fg |

**Tailwind exposure.** Every colour above is re-exported in `@theme inline` as
`--color-<name>`, producing utilities such as `bg-primary`, `text-primary-foreground`,
`bg-card`, `bg-surface`, `bg-info`, `text-info-foreground`, `bg-sidebar`,
`text-sidebar-foreground`, `border-sidebar-border`, `bg-header`, `bg-footer`. (`--copper`
and `--gold` are also exposed as `bg-copper`/`bg-gold`.)

---

## 2. Radius scale

Base `--radius: 0.75rem`. Registered in `@theme inline` so `rounded-*` utilities resolve:

| Utility | Token | Value |
| --- | --- | --- |
| `rounded-sm` | `--radius-sm` | `calc(var(--radius) - 4px)` |
| `rounded-md` | `--radius-md` | `calc(var(--radius) - 2px)` |
| `rounded-lg` | `--radius-lg` | `var(--radius)` |
| `rounded-xl` | `--radius-xl` | `calc(var(--radius) + 4px)` |
| `rounded-2xl` | `--radius-2xl` | `calc(var(--radius) + 12px)` |
| `rounded-full` | `--radius-full` | `9999px` |

Because every step is derived from `--radius`, the Theme Manager overriding `--radius`
rescales the whole family.

---

## 3. Spacing scale

Rem-based single source of truth (mirrors Tailwind's rhythm; **not** registered in
`@theme`, so Tailwind's numeric spacing is untouched):

`--space-0 (0)`, `--space-px (1px)`, `--space-0-5 (.125rem)`, `--space-1 (.25rem)`,
`--space-1-5 (.375rem)`, `--space-2 (.5rem)`, `--space-3 (.75rem)`, `--space-4 (1rem)`,
`--space-5 (1.25rem)`, `--space-6 (1.5rem)`, `--space-8 (2rem)`, `--space-10 (2.5rem)`,
`--space-12 (3rem)`, `--space-16 (4rem)`, `--space-20 (5rem)`, `--space-24 (6rem)`.

See `SPACING.md` for usage and the `.stack-*` rhythm helpers.

---

## 4. Container widths

Deliberately named `--container-width*` (not `--container-*`) so Tailwind's `max-w-sm/md/…`
utilities are untouched. `--container-width` is the injector's base:

`--container-width (80rem)`, `--container-width-sm (40rem)`, `--container-width-md (48rem)`,
`--container-width-lg (64rem)`, `--container-width-xl (80rem)`, `--container-width-2xl (96rem)`.

---

## 5. Shadow scale + semantic elevation

Consumed as `box-shadow: var(--shadow-md)` or via `.elevation-*` utility classes. **Not**
in `@theme` (Tailwind's `shadow-*` untouched). The `.dark` block re-tints them
(softer spread, darker/deeper).

| Token | Light | Dark |
| --- | --- | --- |
| `--shadow-xs` | `0 1px 2px 0 oklch(0.30 0.02 190 / .05)` | `0 1px 2px 0 oklch(0 0 0 / .35)` |
| `--shadow-sm` | two-layer soft | darker two-layer |
| `--shadow-md` | two-layer | darker |
| `--shadow-lg` | two-layer | darker |
| `--shadow-xl` | two-layer | darker |
| `--shadow-2xl` | `0 25px 50px -12px …/.22` | `…/.70` |

Semantic elevation maps onto shadows and is exposed as utilities `.elevation-0`…
`.elevation-5` (+ `.hover:elevation-2..4`, `.group-hover:elevation-3/4`):

`--elevation-0: none`, `--elevation-1: var(--shadow-xs)`, `--elevation-2: var(--shadow-sm)`,
`--elevation-3: var(--shadow-md)`, `--elevation-4: var(--shadow-lg)`,
`--elevation-5: var(--shadow-xl)`.

---

## 6. Z-index scale

Ascending, ample gaps for local stacking. Consumed via `z-[--z-*]`:

`--z-base (0)`, `--z-dropdown (1000)`, `--z-sticky (1100)`, `--z-overlay (1200)`,
`--z-drawer (1300)`, `--z-modal (1400)`, `--z-popover (1500)`, `--z-toast (1600)`,
`--z-tooltip (1700)`.

---

## 7. Opacity scale + semantic states

`--opacity-0/5/10/20/40/60/80/100` plus semantic `--opacity-disabled (0.5)`,
`--opacity-muted (0.7)`, `--opacity-hover (0.9)`. Consumed via `opacity-[--opacity-disabled]`
etc. (Button disabled state uses `disabled:opacity-[--opacity-disabled]`.)

---

## 8. Focus-ring tokens

The global `:focus-visible` rule reads these (defaults identical to the prior 2px/2px):

`--focus-ring-width (2px)`, `--focus-ring-offset (2px)`, `--focus-ring-color (var(--ring))`.

```css
:focus-visible {
  outline: var(--focus-ring-width, 2px) solid var(--focus-ring-color, var(--color-ring));
  outline-offset: var(--focus-ring-offset, 2px);
}
```

---

## 9. Motion tokens

Durations: `--duration-instant (75ms)`, `--duration-fast (150ms)`,
`--duration-normal (250ms)`, `--duration-slow (400ms)`, `--duration-slower (600ms)`.

Easings: `--ease-standard`, `--ease-emphasized` (both `cubic-bezier(0.2,0,0,1)`),
`--ease-spring (cubic-bezier(0.34,1.56,0.64,1))` — registered in `@theme` as
`ease-standard/emphasized/spring` utilities — plus helpers
`--ease-decelerate (cubic-bezier(0.16,1,0.3,1))` and
`--ease-accelerate (cubic-bezier(0.4,0,1,1))`. Full usage in `MOTION.md`.

---

## 10. Typography tokens

Each role ships a size (fluid `clamp()`), line-height, weight and tracking token, applied
together by the `.text-*` utilities. Roles: `display, h1–h6, subtitle, body, caption,
label, button`. Example:

```css
--text-display: clamp(2.5rem, 1.6rem + 4.2vw, 4.5rem);
--leading-display: 1.02;  --weight-display: 700;  --tracking-display: -0.02em;
```

Font-family tokens (in `@theme inline`):
`--font-sans` = `var(--font-inter), var(--font-arabic), system-ui, …`;
`--font-serif` = `var(--font-fraunces), ui-serif, Georgia, serif`;
`--font-arabic` = `var(--font-ibm-plex-arabic), "Segoe UI", Tahoma, sans-serif`.

Full scale and RTL behaviour in `TYPOGRAPHY.md`.

---

## 11. How the Theme Manager overrides tokens

`apps/web/src/lib/branding/css.ts` builds `<style id="brand-theme">` via
`brandThemeCss(branding)`, re-assigning a curated subset of the raw variables from
admin branding. `COLOR_VAR_MAP`:

| Branding key | Variable(s) overridden |
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

Plus `--radius` (from `theme.radius`), `--container-width` (from `theme.container_width`),
and an optional `--font-sans` override (`googleFontCss`). Light values are written on
`:root`, dark values (`theme.dark`) on `.dark`. Because `defaultBranding` mirrors the
`globals.css` defaults, emitting the default theme is a **visual no-op** — partial/empty
branding degrades gracefully. Any utility resolving through `--color-primary →
var(--primary)` (or a radius/font derived from the overridden base) updates automatically.

---

## 12. How to add a token

1. **Colour:** add `--myfamily` and `--myfamily-foreground` to both `:root` and `.dark` in
   `globals.css`. If it should generate utilities, register
   `--color-myfamily: var(--myfamily);` (and `-foreground`) inside `@theme inline`.
   If it should be brandable, add a `COLOR_VAR_MAP` entry and mirror the default in
   `defaultBranding`.
2. **Scale value (spacing/shadow/z/opacity):** add to the existing family in `:root` (and
   `.dark` for shadows). Consume via arbitrary-value utility or a new `@layer utilities`
   class. Do **not** register it in `@theme` unless you intend to replace a Tailwind
   built-in.
3. **Typography role:** add the four tokens (`--text-*`, `--leading-*`, `--weight-*`,
   `--tracking-*`) and a matching `.text-*` utility class in the fluid-type `@layer
   utilities` block.
4. Verify `npx tsc --noEmit` and the CSS compile (via `next build`) stay clean, and check
   both light/dark and LTR/RTL in the design showcase.

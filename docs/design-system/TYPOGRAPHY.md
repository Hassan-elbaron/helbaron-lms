# Typography

A fluid, role-based type system defined in `apps/web/src/app/globals.css`. Each role ships
four tokens — size (a `clamp()` that scales from mobile→desktop), line-height, weight and
letter-spacing — applied together by a single `.text-*` utility class. This keeps type
consistent, responsive without breakpoints, and RTL-safe.

---

## 1. Fonts (EN + AR)

Wired in `apps/web/src/app/layout.tsx` via `next/font` (self-hosted, no CLS):

| Family | CSS var | Role |
| --- | --- | --- |
| **Inter** | `--font-inter` | Body / UI (Latin) |
| **Fraunces** | `--font-fraunces` | Serif headings (Latin), italic + optical-size axis |
| **IBM Plex Sans Arabic** | `--font-ibm-plex-arabic` | Arabic body + headings (weights 400/500/600/700) |

Stacks (in `@theme inline`):

```css
--font-sans:  var(--font-inter), var(--font-arabic), system-ui, ui-sans-serif, sans-serif;
--font-serif: var(--font-fraunces), ui-serif, "Iowan Old Style", Georgia, serif;
--font-arabic: var(--font-ibm-plex-arabic), "Segoe UI", Tahoma, sans-serif;
```

`<body>` uses `--font-sans`; `h1,h2,h3` default to `--font-serif`. The `<html>` element
carries `lang` and `dir`, and body class chains all three font variables.

---

## 2. Fluid scale (roles + tokens)

Every role: `--text-<role>` (fluid size), `--leading-<role>`, `--weight-<role>`,
`--tracking-<role>`.

| Role | Utility | Fluid size (`clamp`) | Line-h | Weight | Tracking |
| --- | --- | --- | --- | --- | --- |
| Display | `.text-display` | `2.5rem → 4.5rem` | 1.02 | 700 | −0.02em |
| H1 | `.text-h1` | `2rem → 3.25rem` | 1.08 | 700 | −0.02em |
| H2 | `.text-h2` | `1.625rem → 2.5rem` | 1.12 | 700 | −0.015em |
| H3 | `.text-h3` | `1.375rem → 1.875rem` | 1.18 | 600 | −0.01em |
| H4 | `.text-h4` | `1.2rem → 1.5rem` | 1.25 | 600 | −0.005em |
| H5 | `.text-h5` | `1.075rem → 1.25rem` | 1.3 | 600 | 0 |
| H6 | `.text-h6` | `1rem → 1.125rem` | 1.4 | 600 | 0 |
| Subtitle | `.text-subtitle` | `1.05rem → 1.25rem` | 1.5 | 400 | 0 |
| Body | `.text-body` | `0.95rem → 1rem` | 1.6 | 400 | 0 |
| Caption | `.text-caption` | `0.8rem → 0.875rem` | 1.45 | 400 | 0.005em |
| Label | `.text-label` | `0.8rem → 0.875rem` | 1.4 | 500 | 0.01em |
| Button | `.text-button` | `0.875rem → 0.9375rem` | 1.2 | 600 | 0.005em |

Each `.text-*` utility sets all four properties at once, e.g.:

```css
.text-h1 { font-size: var(--text-h1); line-height: var(--leading-h1);
           font-weight: var(--weight-h1); letter-spacing: var(--tracking-h1); }
```

---

## 3. Role aliases

Documented aliases map onto the scale so intent reads clearly in markup:

| Alias | Maps to |
| --- | --- |
| `.text-hero` | display |
| `.text-card-title` | h4 |
| `.text-dashboard-title` | h2 |
| `.text-table` | caption |
| `.text-nav` | label |
| `.text-form-label` | label |

---

## 4. EN/AR + RTL behaviour

- **Body** already inherits `--font-sans`, which includes `--font-arabic`, so Arabic text
  renders in IBM Plex Sans Arabic with no per-component work.
- **Headings.** Fraunces has no Arabic glyphs, so headings fall back to the Arabic family
  under Arabic/RTL and drop the negative tracking:

```css
:where(html[lang="ar"]) h1, :where(html[lang="ar"]) h2, :where(html[lang="ar"]) h3,
:where([dir="rtl"]) h1, :where([dir="rtl"]) h2, :where([dir="rtl"]) h3 {
  font-family: var(--font-arabic), var(--font-serif);
  letter-spacing: 0;
}
```

- The fluid `.text-*` utilities use only logical/non-directional properties, so they are
  identical under LTR and RTL. The design showcase exposes EN + AR side-by-side.

---

## 5. Usage

```tsx
<h1 className="text-display">HElbaron Academy</h1>
<p className="text-subtitle text-muted-foreground">Learn without limits</p>
<span className="text-label">Category</span>
```

Prefer role utilities over ad-hoc `text-2xl font-bold` combinations so the system stays the
single source of truth. Marketing hero/section headings and dashboard titles already adopt
the fluid scale.

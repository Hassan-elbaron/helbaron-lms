# HElbaron — Brand Identity

> The premium bilingual (AR/EN) business academy for the MENA region.
> This document is the single reference for the HElbaron visual identity. The live values are
> driven by code: colors/tokens in `apps/web/src/app/globals.css`, brand + content config in
> `apps/web/src/config/theme.ts`, and a live preview at **/settings/theme**.

---

## 1. Brand essence

| | |
|---|---|
| **Name** | HElbaron |
| **Tagline** | Master the core. Lead the future. |
| **Positioning** | One academy, five service lines (Courses, Live Cohorts, In‑person Workshops, B2B/B2G Training, Advisory) across twelve business verticals. |
| **Audience** | MENA professionals, founders, and enterprises. |
| **Personality** | Premium, editorial, trustworthy, warm, MENA‑rooted. |
| **Logo** | Serif wordmark “HElbaron” + an **H** monogram tile (teal on light, gold on dark surfaces). |

---

## 2. Color palette

Colors are defined as CSS variables in **oklch** (light + dark). The hex values below are the
display equivalents shown on the Brand page.

### Core (light theme)

| Token | Role | HEX | oklch |
|---|---|---|---|
| `--primary` | Deep dark teal (primary) | `#134E4A` | `oklch(0.36 0.045 185)` |
| `--primary-foreground` | On primary | `#F7F1E3` | `oklch(0.97 0.015 88)` |
| `--background` | Warm cream background | `#F7F1E3` | `oklch(0.962 0.017 88)` |
| `--card` | Warm ivory surface | `#FFFDF7` | `oklch(0.99 0.008 88)` |
| `--foreground` | Text (deep teal‑charcoal) | `#21302E` | `oklch(0.30 0.017 190)` |
| `--secondary` | Soft warm sand | `#E7DEC9` | `oklch(0.91 0.03 86)` |
| `--muted` | Muted sand | — | `oklch(0.93 0.02 86)` |
| `--border` / `--input` | Soft warm border | `#E4DBC9` | `oklch(0.88 0.02 86)` |
| `--ring` | Focus ring (teal) | `#134E4A` | `oklch(0.36 0.045 185)` |

### Brand accents

| Token | Role | HEX | oklch |
|---|---|---|---|
| `--copper` | Burnt copper / terracotta accent | `#B85C38` | `oklch(0.57 0.12 47)` |
| `--gold` | Muted gold accent | `#C9A24B` | `oklch(0.74 0.10 88)` |

### Status

| Token | Role | oklch (light) |
|---|---|---|
| `--destructive` | Errors / danger | `oklch(0.55 0.19 30)` |
| `--success` | Success | `oklch(0.55 0.11 165)` |
| `--warning` | Warning | `oklch(0.74 0.12 82)` |

### Dark theme (deep‑teal night)

| Token | oklch |
|---|---|
| `--background` | `oklch(0.21 0.022 190)` |
| `--card` | `oklch(0.25 0.026 190)` |
| `--foreground` | `oklch(0.94 0.015 88)` |
| `--primary` | `oklch(0.62 0.07 183)` (luminous teal) |
| `--copper` | `oklch(0.64 0.12 47)` |
| `--gold` | `oklch(0.78 0.10 88)` |
| `--border` | `oklch(1 0 0 / 12%)` |

**Usage rules**
- **Primary (teal)** = main brand surfaces, buttons, links, focus ring.
- **Copper** = editorial accents, eyebrows, emphasis words, “HOT” badges, filled advisory cards.
- **Gold** = numbers/stats, sparkle details, secondary highlights — use sparingly.
- **Cream/ivory** = page and card backgrounds for the warm academy feel.
- Keep the subtle `--accent` for hover tints; never use copper/gold as large hover fills.

---

## 3. Typography

| Use | Font | Notes |
|---|---|---|
| **Headings (h1–h3)** | **Fraunces** (serif, opsz + italic) | Elegant, editorial. Emphasis words are *italic copper*. |
| **Body / UI** | **Inter** (sans) | Clean, legible, bilingual. |

Tokens: `--font-serif` (Fraunces), `--font-sans` (Inter). Loaded via `next/font/google` in
`apps/web/src/app/layout.tsx`. Headings get `letter-spacing: -0.01em`.

Scale (marketing): hero `~4.25rem`, section titles `~2.6rem`, card titles `text-lg`, eyebrows
`0.75rem uppercase, tracking 0.22em` in copper with dashed flanks.

---

## 4. Shape, elevation, motion

- **Radius:** `--radius: 0.75rem` (sm/md/lg/xl/2xl derived). Cards use `rounded-2xl`/`rounded-3xl`.
- **Borders:** soft warm 1px (`--border`). **Shadows:** soft, tinted with `primary/5–20`.
- **Buttons:** solid primary (default), outline, secondary (sand), ghost, destructive; sweeping
  “shine” on primary CTAs.
- **Motion system** (`globals.css`, no JS dependency, respects `prefers-reduced-motion`):
  `page-enter` (route transition), `stagger-in` (list cascade), `animate-float` / `animate-float-slow`,
  `animate-blob`, `marquee-track`, `shine`, `hb-spin`, count‑up numbers, and scroll‑reveal
  (`Reveal` via IntersectionObserver).

---

## 5. Logo & iconography

- **Wordmark:** “HElbaron” in Fraunces, weight 600.
- **Monogram:** rounded tile with **H** — `bg-primary text-primary-foreground` on light surfaces,
  `bg-gold text-gold-foreground` on the dark footer.
- **Icons:** `lucide-react`, stroke style, `size-4/5`, colored with brand tokens.
- **Illustration:** custom themed **SVG** (open book, graduation cap, growth chart, certificate
  seal) that adapts to light/dark via CSS variables — `apps/web/src/components/landing/hero-art.tsx`.

---

## 6. Voice & content pillars

- **Hero:** eyebrow “FOR MENA’S BUSINESS BUILDERS” → *Master / the core. / Lead the future.*
- **Five service lines:** Courses · Live Cohorts · In‑person Workshops · B2B/B2G Training · Advisory.
- **Twelve verticals:** Project Management, Agile Mindset, Business Development, Business Strategies,
  Entrepreneurship, Business Skills, Leadership, Marketing Strategies, Sales Management,
  Finance & Analysis, Business AI, Investment & Trading.
- **Proof stats:** 100+ courses · 25K+ learners · 75 enterprise customers · $25M year‑3 target.
- **Locations:** Cairo · Dubai · Riyadh.
- Bilingual: every string exists as `{ en, ar }` and mirrors under RTL.

---

## 7. Where it lives in code

| Concern | File |
|---|---|
| Color tokens, dark mode, motion keyframes | `apps/web/src/app/globals.css` |
| Fonts (Fraunces + Inter) | `apps/web/src/app/layout.tsx` |
| Brand + landing content config (EN/AR) | `apps/web/src/config/theme.ts` |
| Per‑page hero content | `apps/web/src/config/page-heroes.ts` |
| Demo course/content layer (toggle) | `apps/web/src/config/demo.ts` (`DEMO_ENABLED`) |
| Live brand preview page | `apps/web/src/app/settings/theme/page.tsx` → **/settings/theme** |

> To edit the brand: change the CSS variables in `globals.css` (colors/dark mode) and the content in
> `theme.ts`. The whole app inherits automatically. A backend‑editable version can be wired later
> without redesign.

---

_HElbaron — Master the core. Lead the future._

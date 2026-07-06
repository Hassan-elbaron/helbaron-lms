# HElbaron LMS — UI & Design System Review (03)

**Repository:** local working copy (`apps/web`).
**Scope:** Visual interface + design system ONLY. No backend, architecture, or business logic.
**Assumes:** Reviews 01 (Product) and 02 (UX/IA) exist; not repeated.
**Method:** Direct inspection of `app/globals.css` (tokens), the `ui/*` primitives (button, card, input, badge, dialog, table, skeleton), landing/marketing components, dashboard cards, and quantitative sweeps of `shadow-*`, `rounded-*`, and hardcoded hex usage across `components/` and `app/`.
**Benchmark bar:** Stripe, Linear, Notion, Vercel, Framer, Webflow, Kajabi, Circle, Coursera.

---

## Executive Summary

The foundation is genuinely strong for a product at this stage: a modern **oklch token system** with full **light/dark parity**, a **serif-display + sans-body** type pairing, a **fully centralized motion system** that respects `prefers-reduced-motion`, and — importantly — **color is 100% tokenized** (a sweep found no stray theme hex values; the only `#000/#fff` are inside SVG mask gradients). This already puts the color layer ahead of many commercial LMSs.

Where it falls short of a premium-SaaS bar (Stripe/Linear/Vercel) is in **systematization and finishing**:

1. **No elevation scale.** There are **six different shadow levels** in use (`shadow-sm ×9`, `shadow-lg ×10`, `shadow-md ×6`, `shadow-xl ×4`, `shadow-2xl ×1`, `shadow-primary ×4`) with no `--shadow-*` tokens — depth is applied by feel, not by system. Premium products use 3–5 tokenized elevations.
2. **Radius drift.** A clean radius scale exists (`--radius-sm…2xl`) but components use **eight raw radius values** including `rounded-3xl ×5` and `rounded-2xl ×7` that aren't bound to the scale. Cards (`rounded-lg`), buttons (`rounded-md`), and hero blocks (`rounded-3xl`) don't share a rhythm.
3. **Inputs have no error/invalid state.** The `Input` primitive has only base + focus + disabled; there is no `aria-invalid`/destructive styling, so every form renders validation errors differently. This is the single biggest "unfinished" tell.
4. **No brand button variant.** Copper and gold are core to the identity and are tokenized, but `Button` exposes only default/destructive/outline/secondary/ghost/link. Brand CTAs on the landing are styled with **one-off classNames**, so the primary marketing accent isn't a reusable component.
5. **Two page-title systems.** The app uses a gradient "band" `PageHeader`; marketing uses `PageHero`. Same product, two title languages.

None of this is a redesign — it's tokenizing what already exists and adding 4–5 missing variants. Strong bones, unfinished joints.

---

## Overall UI Score

**6.6 / 10** — "attractive and coherent in color/motion; unfinished in system rigor and form/state polish."

## Design System Score

**6.2 / 10** — "great token foundation, incomplete elevation/variant/state layers."

| Category | Score | One-line justification |
|----------|-------|------------------------|
| Color system | 8.5 | oklch, dark parity, fully tokenized, no stray hex |
| Typography | 7.0 | strong serif/sans pairing; scale not tokenized as steps |
| Spacing/rhythm | 6.5 | Tailwind defaults, consistent-ish; no documented scale |
| Radius | 5.5 | scale exists but 8 values used; `3xl` off-scale |
| Elevation/shadows | 4.0 | 6 ad-hoc levels, no tokens |
| Motion | 8.0 | centralized, reduced-motion aware, applied widely |
| Buttons | 7.0 | good hierarchy; missing brand + `xl` variants |
| Inputs/Forms | 4.5 | no error/invalid state; no icon slots |
| Cards | 6.0 | consistent base; no elevation/interactive variants |
| Navigation (visual) | 6.0 | clean, but flat; two chromes differ |
| Landing pages | 7.5 | best-finished surface; hierarchy strong |
| Dashboards | 5.5 | functional; density/hierarchy uneven |
| Dark mode | 8.0 | full parity, thought-through |
| RTL | 8.0 | logical properties throughout |
| Accessibility (visual) | 6.5 | global focus-visible; contrast unverified |
| Responsive | 5.5 | app good; public/tables weak (see 02) |

---

## Landing Pages Review

**Score: 7.5/10** — the most finished surface.

| Aspect | Assessment | Severity | Evidence | Recommendation |
|--------|-----------|----------|----------|----------------|
| Visual hierarchy | Strong: eyebrow→serif headline→sub→CTA | — | `landing/hero.tsx`, `theme.ts` | Keep |
| Hero | SVG editorial art + float/blob motion; premium feel | Low | `hero.tsx`, `hero-art.tsx` | Ensure single primary CTA (see UX 02 CTA1) |
| Feature/service sections | Numbered service lines w/ surface variants | Low | `service-lines.tsx` | Keep; unify card radius (RD-1) |
| Pricing | **No dedicated pricing section/page** — prices are inline strings in service copy | High | `theme.ts` serviceLines desc | Add a real pricing block with tiered cards |
| CTA sections | `final-cta.tsx` present, good | Low | — | One primary per section |
| Social proof | `trusted-by.tsx` marquee | Medium | logos are text/placeholder | Replace with real logo lockups; consistent grayscale treatment |
| Trust indicators | Rating chip in hero | Medium | `theme.ts` hero.rating | Add verifiable proof (counts, testimonials) |
| Course showcase | `featured-courses.tsx` + SVG covers | Low | `marketing/course-thumb.tsx` | Keep; align card radius with app cards |
| Brand consistency | Serif headings, copper/gold accents consistent | Low | globals + theme | Keep |

Landing radius uses `rounded-2xl/3xl` while app cards use `rounded-lg` → cross-surface inconsistency (see RD-1).

---

## Dashboard Review

**Score: 5.5/10.**

| Dashboard | Visual issues | Severity | Evidence | Recommendation |
|-----------|---------------|----------|----------|----------------|
| Student `/dashboard` | Uniform card sizing → no visual priority; no hero stat; even grid reads flat | High | `student/stat-card.tsx`, dashboard page | Introduce a 12-col grid with 1 hero widget + supporting cards of varied span |
| Organization `/org` | List-first, no KPI band; weak density hierarchy | Medium | org pages | Add KPI stat row (reuse `stat-card`) above lists |
| CRM `/crm` | Table-dense, little white space, no summary cards | Medium | crm pages | Add summary cards; increase row padding |
| Analytics `/analytics` | KPI cards OK but **no charts** (text/number only per constraints) → looks unfinished vs Coursera/Vercel | High | `analytics/kpi-card.tsx`, `result-table.tsx` | Add lightweight sparkline/bar visuals per KPI |
| Admin (Filament) | Default Filament theme, not brand-aligned with web app | Medium | `PlatformOverview` widget | Apply brand palette to Filament panel |

Cross-cutting: **card sizing is uniform everywhere** → no information hierarchy; premium dashboards vary card span/height to signal priority.

---

## Component Review

| Component | State | Severity | Evidence | Fix |
|-----------|-------|----------|----------|-----|
| Button | Good variants/sizes; **no brand (copper/gold) variant, no `xl`** | High | `ui/button.tsx` cva | Add `copper`/`gold` variants + `xl` size; migrate landing one-offs |
| Input | **No error/invalid state**, no icon slot, no size variants | High | `ui/input.tsx` | Add `aria-invalid` destructive styling, leading/trailing icon slots, `sm`/`lg` |
| Card | Fixed `shadow-sm`+`rounded-lg`+`p-6`; **no elevation/interactive/compact variants** | Medium | `ui/card.tsx` | Add `variant` (flat/raised/interactive) + `padding` (compact/default) |
| Badge | Uses `focus` not `focus-visible` (inconsistent w/ button/input) | Low | `ui/badge.tsx` | Switch to `focus-visible`; add `copper`/`gold`/`outline-*` |
| Dialog/Drawer/Dropdown | Radix-based; check shadow/radius alignment with tokens | Medium | `ui/*` | Bind to elevation + radius tokens (EL-1/RD-1) |
| Table/DataGrid | No zebra/hover/density tokens; mobile overflow (see 02) | Medium | `ui/table.tsx`, `ui/data-grid.tsx` | Add row hover, sticky header, density prop, responsive wrapper |
| Skeleton | Used in only 3 pages; generic | Medium | `ui/skeleton.tsx` | Provide layout-shaped skeleton presets |
| PageHeader vs PageHero | **Two title systems** | High | `student/page-header.tsx`, `marketing/page-hero.tsx` | Consolidate to one titling component with context variants |

**Duplicate/dead:** `catalog/public-header.tsx` is unused (see 02) — a second header component that visually competes. Remove.

---

## Typography Review

**Score: 7.0/10.**

- **Strengths:** Fraunces serif for `h1–h3` (letter-spacing -0.01em), Inter for body; antialiased; clear display/body contrast. This pairing reads premium.
- **Issues:**
  - **No tokenized type scale.** Sizes come from raw Tailwind (`text-lg`, `text-sm`, `text-xs`) ad hoc; there's no documented step scale (display/h1/h2/h3/body/caption). *Severity: Medium.* → Define a type scale in `@theme` or a `typography` doc and apply via a small set of classes.
  - **`h4–h6` not styled** — only `h1,h2,h3` get serif; deeper headings silently fall back to sans, creating hierarchy gaps on content pages. *Severity: Medium.* → Extend rule to `h4` or define explicit heading utilities.
  - **CardTitle uses `text-lg` sans**, not serif — titles inside cards diverge from page headings. *Severity: Low.* → Decide: serif card titles or documented exception.
  - **Line-height/measure** not constrained on long marketing copy. *Severity: Low.*

---

## Color Review

**Score: 8.5/10 — the strongest layer.**

- **Strengths:** oklch throughout; semantic tokens (primary/secondary/muted/accent/copper/gold/destructive/success/warning) with `-foreground` pairs; full `.dark` parity; **no stray hex** (verified). Border/input/ring tokenized.
- **Issues:**
  - **Contrast unverified.** `--muted-foreground` (light `oklch(0.48…)`) on `--background` cream, and `--gold`/`--warning` foregrounds, need WCAG AA checks. *Severity: Medium.* → Run contrast audit; adjust muted/gold if < 4.5:1 (text) / 3:1 (UI).
  - **No `info` semantic color** (only success/warning/destructive) — informational states borrow primary. *Severity: Low.* → Add `--info`.
  - **Copper/gold underused in components** despite being identity — only in landing. *Severity: Low.* → Expose via Button/Badge variants (ties to Button fix).

---

## Layout Review

**Score: 6.0/10.**

- Two chromes with different containers: `(public)` uses `max-w-6xl px-4 py-10`; `AppShell` uses full-width `p-4 md:p-6`. No shared layout token. *Severity: High* (visual inconsistency across the product). → Define `--container-max` + page padding tokens; apply to both.
- **No explicit grid system** — dashboards use ad-hoc fl/grid utilities; no 12-col rhythm. *Severity: Medium.* → Adopt a documented 12-col grid for dashboards/detail pages.
- Vertical rhythm inconsistent (section paddings vary between `py-10` app and larger marketing paddings). *Severity: Low.*

---

## Responsive Review

**Score: 5.5/10** (visual-only; behavioral gaps covered in 02).

| Breakpoint | Assessment | Severity |
|-----------|-----------|----------|
| Mobile (375) | App shell OK (drawer); **public header nav hidden with no menu** (02/N1); tables overflow | High |
| Tablet (768) | Sidebar appears at `md`; storefront nav still `lg`-gated → tablet loses nav | High |
| Desktop (1280) | Best-supported; looks intended | Low |
| Ultra-wide (>1536) | `max-w-6xl` on public leaves large empty gutters; app is full-bleed → inconsistent max width across product | Medium |

→ Set a consistent max content width and center both chromes; raise storefront nav breakpoint handling (mobile menu).

---

## Dark Mode Review

**Score: 8.0/10.**

- Full `.dark` token set, deep-teal night palette, sensible foreground inversions, border/input use translucent white (`oklch(1 0 0 / 12–14%)`) — a premium touch.
- Issues: shadows (`shadow-lg` etc.) are tuned for light bg and can look heavy/invisible on dark — no dark-specific elevation. *Severity: Medium.* → Elevation tokens should adapt per theme (lighter borders + subtle shadows in dark). Verify `shadow-primary` glow reads in dark.

---

## RTL Review

**Score: 8.0/10.**

- Logical properties used throughout (`ms-/me-/ps-/pe-/start/end`), `dir`-driven, sidebar `border-e`. This is well done and rare.
- Issues: verify icon-direction glyphs (chevrons/arrows in back-nav, carousels) mirror in RTL; SVG hero art and marquee direction should be checked for RTL. *Severity: Low–Medium.* → Audit directional icons; mirror where semantic.

---

## Accessibility Review (visual)

**Score: 6.5/10.**

- **Strengths:** global `:focus-visible` 2px ring + offset; button/input use `focus-visible:ring`; sidebar `aria-current`; icons `aria-hidden`.
- **Issues:**
  - Badge uses `focus` (not `focus-visible`) — inconsistent focus behavior. *Low.*
  - Contrast unverified (muted/gold/warning). *Medium.*
  - No visible skip-link (02/A3). *Medium.*
  - Touch targets: `size-9`/`h-9` controls are 36px — below the 44px comfortable target on mobile. *Medium.* → Ensure ≥44px on touch.

---

## Motion Review

**Score: 8.0/10 — a highlight.**

- Centralized keyframes (float/blob/marquee/shine/pop/page-enter/stagger-in/spin), all with `prefers-reduced-motion` fallbacks; reveal-on-scroll; hover lift via `.card-hover`; applied across catalog, org, student, and marketing cards (verified in 16 files).
- Issues:
  - Motion is rich on marketing/cards but **app interactions (buttons, table rows, dialogs) rely only on `transition-colors`** — no micro-interactions on primary actions. *Severity: Low.* → Add subtle press/scale on primary buttons; row hover elevation.
  - No standardized easing/duration tokens (durations are inlined per keyframe/class). *Severity: Low.* → Define `--ease-*`/`--dur-*` tokens.

---

## Forms Review

**Score: 4.5/10 — weakest layer.**

| Aspect | Issue | Severity | Evidence | Fix |
|--------|-------|----------|----------|-----|
| Error state | **No invalid styling in `Input`** | High | `ui/input.tsx` | Add `aria-invalid` → destructive border/ring |
| Validation display | Handled per-form (`auth/field.tsx`, `form-alert.tsx`) but not systemized for all inputs (select/checkbox) | High | components | Standard `Field` wrapper: label + control + error + hint |
| Labels | Auth forms label well; other forms inconsistent | Medium | forms across pages | Enforce `Field` everywhere |
| Focus | Consistent ring (good) | — | globals | Keep |
| Field grouping/spacing | No standard `space-y` rhythm between fields | Medium | forms | Define form layout tokens |
| Button placement | Varies (primary sometimes leading/trailing) | Medium | dialogs/forms | Convention: primary trailing, honor RTL |
| Disabled state | Consistent (`opacity-50`) | — | primitives | Keep |
| Success state | No inline success affordance on fields | Low | — | Optional success variant |

---

## Tables Review

**Score: 5.0/10.**

- No zebra striping, no row hover, no sticky header, no density control, no column alignment conventions; mobile overflow unhandled (02/M2).
- *Severity: Medium–High.* → Add table tokens: header bg, row hover, optional zebra, sticky header, `density` prop, right-align numerics, and a responsive wrapper (horizontal scroll or card fallback < md).

---

## Cards Review

**Score: 6.0/10.**

- Base card consistent (`rounded-lg border bg-card shadow-sm p-6`). But: fixed padding blocks compact dashboard use; single `shadow-sm` = no elevation hierarchy; interactive cards apply `.card-hover` ad hoc (not a variant). Marketing cards use `rounded-2xl/3xl` → radius mismatch with app cards.
- → Add `Card` variants: `flat | raised | interactive` (bind to elevation tokens) and `padding: compact | default`; unify radius to the scale.

---

## Navigation Visual Review

**Score: 6.0/10.**

- Sidebar: clean active state (`bg-primary text-primary-foreground`), icon+label, `aria-current` — good. But flat (no section labels), brand not a link, fixed `w-64`.
- Topbar: minimal (menu/lang/theme/user) — visually fine but sparse (no title/breadcrumb/search — see 02).
- Two headers (Landing vs App) differ in structure and height treatment; `PublicHeader` dead duplicate. *Severity: Medium.* → One nav language; grouped sidebar; consistent header height/shadow token.

---

## Visual Consistency Problems (consolidated)

| ID | Severity | Problem | Evidence | Recommendation | Priority | Impact |
|----|----------|---------|----------|----------------|----------|--------|
| VC-1 | High | 6 ad-hoc shadow levels, no elevation tokens | `shadow-sm/md/lg/xl/2xl/primary` counts | Define `--elevation-1..4`; map components | P0 | Cohesive depth |
| VC-2 | High | 8 radius values; `rounded-3xl/2xl` off the scale | `rounded-*` counts | Bind all to `--radius-*`; retire 3xl or add as `--radius-3xl` | P0 | Unified rhythm |
| VC-3 | High | Input has no error state | `ui/input.tsx` | Add invalid styling + `Field` wrapper | P0 | Coherent forms |
| VC-4 | High | No brand (copper/gold) Button variant; landing CTAs hardcoded | `ui/button.tsx`, landing | Add variants; migrate one-offs | P0 | Reusable brand CTA |
| VC-5 | High | Two page-title systems | PageHeader vs PageHero | Consolidate | P1 | Consistent titling |
| VC-6 | Medium | Two container widths across chromes | `(public)` vs `AppShell` | Shared layout tokens | P1 | Cross-product unity |
| VC-7 | Medium | Tables lack hover/zebra/sticky/density | `ui/table.tsx` | Table tokens + responsive | P1 | Data readability |
| VC-8 | Medium | Card has no elevation/compact variants | `ui/card.tsx` | Add variants | P1 | Dashboard hierarchy |
| VC-9 | Medium | No tokenized type scale; h4–h6 unstyled | globals `@layer base` | Type scale | P1 | Typographic order |
| VC-10 | Medium | Contrast unverified (muted/gold/warning) | tokens | AA audit | P1 | Accessibility |
| VC-11 | Low | Badge `focus` vs `focus-visible` | `ui/badge.tsx` | Align | P2 | Consistency |
| VC-12 | Low | No easing/duration tokens | globals | Add `--ease/--dur` | P2 | Motion consistency |

---

## Design System Problems (structural)

1. **Missing token layers:** elevation, type scale, spacing scale (documented), z-index scale, easing/duration. *Add to `@theme`/globals.*
2. **Variant gaps:** Button (brand, xl), Card (elevation, padding), Input (invalid, icon, size), Table (density), Badge (brand). *Fill.*
3. **No single source of truth doc** mapping tokens→usage (radius/shadow/spacing rules). *Add `docs/design/DESIGN_SYSTEM.md` or Storybook-style reference.*
4. **Two competing chromes/title systems** — consolidate into one layout language.
5. **Admin (Filament) not brand-aligned** — visual disconnect between `/admin` and web app.

---

## High Priority Fixes (ordered)

- **P0-1 (VC-1):** Add elevation tokens `--elevation-1..4` (light + dark) and replace ad-hoc `shadow-*`.
- **P0-2 (VC-2):** Normalize radius: map all components to `--radius-sm..2xl`; add `--radius-3xl` only if kept.
- **P0-3 (VC-3):** Add `Input` invalid state + a shared `Field` wrapper (label/control/error/hint).
- **P0-4 (VC-4):** Add `copper`/`gold` Button variants + `xl` size; migrate landing one-offs.
- **P1-1 (VC-5/VC-6):** Consolidate page-title system + shared container/layout tokens.
- **P1-2 (VC-7/VC-8):** Table density/hover/sticky + responsive; Card elevation/padding variants.
- **P1-3 (VC-9/VC-10):** Tokenized type scale (+ h4–h6); WCAG AA contrast audit.

---

## AI Implementation Prompts

**AIP-1 — Elevation tokens (VC-1)**
> In `apps/web/src/app/globals.css`, add elevation tokens for light and dark themes: `--elevation-1..4` as layered box-shadows (subtle → prominent), with dark-mode variants that use lower opacity + lighter hairline borders. Expose them in `@theme inline` as `--shadow-1..4`. Then replace ad-hoc `shadow-sm/md/lg/xl/2xl` usages in `components/ui/*` and cards with the nearest token utility. Keep `shadow-primary` only where an intentional brand glow is desired and document it.

**AIP-2 — Radius normalization (VC-2)**
> Audit all `rounded-*` usages under `apps/web/src/components` and `app`. Map them to the existing scale (`rounded-sm/md/lg/xl/2xl`). Replace `rounded-3xl` either by adding `--radius-3xl` to `@theme` (if the large marketing radius is intentional) or by downgrading to `2xl`. Ensure cards use one radius token, buttons another, pills `rounded-full`, and document the rule in a comment block at the top of `globals.css`.

**AIP-3 — Input error state + Field wrapper (VC-3)**
> Extend `apps/web/src/components/ui/input.tsx` to accept `invalid?: boolean` (or read `aria-invalid`) and apply `border-destructive focus-visible:ring-destructive` when set. Create `apps/web/src/components/ui/field.tsx` composing Label + control slot + optional hint + error message (with `aria-describedby`/`aria-invalid` wiring). Refactor existing forms (`auth/*`, profile, checkout, CRM create) to use `Field`.

**AIP-4 — Brand button variants (VC-4)**
> In `apps/web/src/components/ui/button.tsx`, add `copper` and `gold` variants to `buttonVariants` (using `bg-copper text-copper-foreground hover:bg-copper/90` and the gold equivalents) and an `xl` size (`h-12 px-10 text-base rounded-lg`). Find landing/marketing CTAs using one-off brand classNames and replace them with these variants.

**AIP-5 — Unified page title + layout tokens (VC-5/VC-6)**
> Create one `PageTitle` component supporting `variant="app" | "hero"`. Replace `student/page-header.tsx` and `marketing/page-hero.tsx` usages with it. Add `--container-max` and page-padding tokens to `globals.css` and apply them to both `(public)` layout and `AppShell` so content width/padding match across chromes.

**AIP-6 — Table system (VC-7)**
> Enhance `apps/web/src/components/ui/table.tsx` and `data-grid.tsx`: add header background, row `hover:bg-muted/50`, optional zebra, sticky header, a `density: comfortable | compact` prop, right-aligned numeric columns, and a responsive wrapper (`overflow-x-auto` with a card fallback under `md`). Apply to CRM, orders, and analytics tables.

**AIP-7 — Card variants (VC-8)**
> Extend `apps/web/src/components/ui/card.tsx` with a `variant: flat | raised | interactive` (interactive adds `card-hover` + elevation on hover) and `padding: compact | default`. Migrate dashboard KPI cards to `padding="compact"` and interactive list cards to `variant="interactive"`.

**AIP-8 — Type scale + headings (VC-9)**
> Define a type scale (display, h1–h4, body-lg, body, caption) as utility classes or `@theme` steps in `globals.css`, extend the base heading rule to style `h4`, and apply the scale to page titles, card titles, and marketing sections. Keep serif for display/headings, sans for body.

**AIP-9 — Contrast + a11y polish (VC-10/VC-11)**
> Run a WCAG audit on token pairs (`muted-foreground`/`background`, `gold`/`gold-foreground`, `warning`/`warning-foreground`) in light and dark; adjust lightness to meet AA (4.5:1 text, 3:1 UI). Switch `badge.tsx` from `focus` to `focus-visible`. Ensure interactive controls are ≥44px on touch.

---

## Acceptance Criteria

- AC1 (VC-1): All component shadows reference `--elevation-*`/`--shadow-1..4`; a grep for raw `shadow-(sm|md|lg|xl|2xl)` in `components/ui` returns only documented exceptions.
- AC2 (VC-2): All `rounded-*` values map to the radius scale; no unmapped `rounded-3xl` remains (or `--radius-3xl` is defined and documented).
- AC3 (VC-3): Every form input renders a consistent error state (border + ring + message) via the shared `Field`; validation looks identical across auth, profile, checkout, CRM.
- AC4 (VC-4): Brand CTAs use `Button variant="copper|gold"`; no landing CTA uses one-off brand color classes.
- AC5 (VC-5): One page-title component is used across app and marketing; no `PageHeader`/`PageHero` duplication remains.
- AC6 (VC-6): `(public)` and `AppShell` share identical max content width and page padding tokens.
- AC7 (VC-7): Tables have hover, sticky header, density control, right-aligned numerics, and no horizontal cutoff on mobile.
- AC8 (VC-8): Card exposes elevation + padding variants; dashboards show visible hierarchy (varied span/emphasis).
- AC9 (VC-9): A documented type scale exists; `h1–h4` are styled; card titles follow the documented rule.
- AC10 (VC-10): All token color pairs pass WCAG AA in light and dark; contrast report attached.
- AC11 (VC-11/12): Badge uses `focus-visible`; easing/duration tokens exist and are used by motion utilities.
- AC12 (traceability): Every issue (VC-1…VC-12, per-category items) maps to a fix (AIP-1…AIP-9) and a criterion here.

---

### Appendix — Evidence index
- Tokens: `app/globals.css` (`:root`, `.dark`, `@theme inline`, motion keyframes).
- Primitives: `components/ui/{button,card,input,badge,dialog,table,data-grid,skeleton}.tsx`.
- Title systems: `components/student/page-header.tsx`, `components/marketing/page-hero.tsx`.
- Sweeps: `shadow-*` → 6 distinct levels (sm 9, lg 10, md 6, xl 4, 2xl 1, primary 4); `rounded-*` → 8 values (md 20, full 20, lg 13, 2xl 7, sm 5, 3xl 5, xl 3, t 1); theme hex → 0 (only `#000/#fff` in SVG masks).
- Motion usage: `.card-hover`/`.shine` across 15 component files + globals.
- Dead duplicate: `components/catalog/public-header.tsx` (unused).

# Accessibility

Target: **WCAG 2.2 AA**. Accessibility is treated as an acceptance criterion, not a
retrofit — primitives ship correct roles/ARIA, focus is global and token-driven, colour is
never the sole carrier of meaning, and motion/transparency respect user preferences. Axe
Playwright specs guard the public surfaces in CI.

---

## 1. Landmarks & skip link

- `app/layout.tsx` renders a **skip link** (`<a href="#main-content">`, `sr-only` until
  focused) as the first focusable element, so keyboard users can jump past the nav.
- `<html>` carries `lang` and `dir`; the page shells provide a single `<main
  id="main-content">` landmark, distinct `<nav>` landmarks with accessible names, and
  header/footer regions.
- Distinct navigation landmarks are named (e.g. primary nav vs. breadcrumb vs. pagination)
  so screen-reader users can tell them apart.

## 2. Focus visibility

Global `:focus-visible` rule reads the focus-ring tokens:

```css
:focus-visible {
  outline: var(--focus-ring-width, 2px) solid var(--focus-ring-color, var(--color-ring));
  outline-offset: var(--focus-ring-offset, 2px);
}
```

Interactive primitives additionally apply `focus-visible:ring-2 focus-visible:ring-ring`
where a ring reads better than an outline. Because the ring colour derives from `--ring`
(= primary), it re-themes with the brand.

## 3. Keyboard support

- **Dialog / Drawer:** focus trap, Escape to close, restore focus on close.
- **Popover / Tooltip:** open on focus/hover, Escape + outside-click to close;
  `aria-expanded`/`aria-haspopup` on triggers.
- **DropdownMenu / Select / Tabs:** Radix roving-tabindex keyboard model.
- **Accordion:** trigger toggles with Enter/Space; `aria-expanded`/`aria-controls`.
- **RadioGroup:** arrow-key selection over native radios; `role="radiogroup"`.
- **DataGrid:** sortable headers are buttons (Enter/Space), selection checkboxes are
  focusable, pagination is a labelled `<nav>`.

## 4. ARIA patterns per component

| Component | Pattern |
| --- | --- |
| Tooltip | `role="tooltip"`, `aria-describedby` on trigger |
| Popover | `role="dialog"`, `aria-expanded`, `aria-haspopup` |
| Accordion | `aria-expanded`, `aria-controls`, content `role="region"` + `aria-labelledby` + `hidden` |
| Switch | `role="switch"`, `aria-checked`, `data-state` |
| Progress | `role="progressbar"`, `aria-valuenow/min/max`, `label` |
| Spinner / LoadingState | `role="status"` (when labelled) / `aria-live="polite"` |
| ErrorState / FormAlert(error) | `role="alert"` |
| SuccessState / OfflineBanner / FormAlert(success/info) | `role="status" aria-live="polite"` |
| FormField | `aria-invalid`, `aria-describedby` (hint+error+success), `aria-required`, `aria-busy` |
| DataGrid | `aria-sort` on sortable headers, labelled select-all/pagination |
| Breadcrumb | `<nav aria-label="Breadcrumb">`, `aria-current="page"` |
| Menu toggle (mobile nav) | `aria-expanded`, `aria-controls`, accessible name |

## 5. Contrast & colour independence

- Colour pairs are authored in OKLCH with `-foreground` companions chosen for legibility on
  their base; light and dark both ship deliberate foregrounds.
- **Colour is never the only signal.** Status uses icon + text (e.g.
  `crm/lead-status-badge.tsx` maps each `LeadStatus` to a distinct icon *and* label, not
  just a hue — WCAG 1.4.1). `FormAlert`/state components pair colour with an icon and a
  live-region role.

> Note: exact contrast ratios were not machine-verified in this sandbox (no headless
> browser). Contrast auditing (axe/Lighthouse) is wired for CI — see §7 — and remains the
> mechanism of record.

## 6. Reduced motion & transparency

- A global `@media (prefers-reduced-motion: reduce)` block disables the entire
  `.motion-*` system plus the `hb-*` and chart-entrance animations, and neutralises
  `.motion-hover`.
- A global `@media (prefers-reduced-transparency: reduce)` block makes scrims opaque and
  drops `backdrop-blur`.

See `MOTION.md`.

## 7. Automated checks (CI)

- **`apps/web/e2e/a11y.spec.ts`** — `@axe-core/playwright` with tags `wcag2a`, `wcag2aa`,
  `wcag22aa`; asserts no serious/critical violations, verifies the skip link is the first
  Tab target (→ `#main-content`), a single `<main>`, and a labelled primary nav. Public
  surfaces (home, login, pricing) always run; the authenticated dashboard shell runs when
  `E2E_EMAIL`/`E2E_PASSWORD` are provided.
- These specs require a running server + browsers and therefore execute in CI, not in the
  offline validation sandbox ("Not verifiable from repository." there).

## 8. RTL

Components use CSS logical properties throughout (`ms-/me-/ps-/pe-`, `start/end`,
`text-start`, `margin-block-*`), so mirroring under Arabic/`dir="rtl"` is automatic —
including chevron flips (breadcrumb, pagination), switch thumb, toast position, and the
logical `.motion-slide-start/-end` animations.

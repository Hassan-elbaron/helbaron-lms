# Design System Review (Audit)

**Date:** 2026-07-16 · **Scope:** `apps/web/src`. Evidence-based; no changes applied.

## Current state (consistent, token-driven)
- **Single canonical UI kit** at `src/components/ui/` — ~34 primitives, each with a Storybook story: accordion, avatar, badge, breadcrumb, button, card, checkbox, confirm-dialog, data-grid, dialog, drawer, dropdown-menu, form, form-field, icon, input, label, pagination, popover, progress, radio-group, select, separator, skeleton, spinner, switch, table, tabs, textarea, toast, tooltip. **No duplicate primitives found.**
- **Tokens**: colours/spacing/radius/shadow/typography/motion defined in `globals.css` + `config/theme.ts`, driven by CSS variables that admin Branding overrides at runtime (`lib/branding/css.ts`). **0 hardcoded 6-digit hex** in `src/components` and `src/app`; the 9 three-digit hex are decorative SVG art.
- **Discipline**: only **3 files** use a raw `<button>` outside the kit; **0 `TODO`/`FIXME`/`@deprecated`** comments in the whole `src`.

## Problems found (low severity — polish, not defects)
| # | Severity | Finding | Evidence |
|---|---|---|---|
| DS-1 | Low | **39 arbitrary Tailwind bracket values** (`w-[…]`, `text-[…]`, `gap-[…]`) bypass the spacing/size scale | concentrated in `components/landing/{announcement-bar,categories-section,final-cta,hero}.tsx`, `layout/app-shell.tsx`, `learning/{curriculum-sidebar,lesson-content}.tsx`, `route/route-error.tsx`. Some (micro-typography `text-[0.7rem]`) are legitimate; most could map to scale tokens. |
| DS-2 | Low | **3 raw `<button>`** in feature components could use `ui/Button` for consistent focus/hover/disabled states | grep: 3 files outside `components/ui/` |
| DS-3 | Info | **Showcase-only components** — e.g. `breadcrumb` is referenced only by the design-system showcase/stories, not real routes (see CODE_QUALITY_REVIEW + SEO-3) | grep |

## Changes applied
None (audit). DS-1/DS-2 are safe token-alignment polish (implementation phase G); each is visually verifiable via the existing Playwright visual project + Storybook.

## Verdict
The design system is genuinely consistent: one canonical kit, token-driven theming, zero hardcoded brand colours in components, zero dead-comment debt. Findings are minor polish (arbitrary values → scale, a few raw buttons), not structural problems. **Do not redesign** — only align stragglers to tokens.

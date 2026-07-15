# Visual Regression Report — HElbaron LMS

**Date:** 2026-07-15
**Honesty note:** Visual regression requires generating **screenshot baselines** and diffing against them in a consistent rendering environment (fixed OS/browser/fonts) — normally in CI. This QA environment **cannot** generate or diff baselines reliably (no headless runner against the host; fonts/DPR would differ from CI). **No baselines were generated and no diffs are claimed here.** The repo already contains the correct instrument.

## What exists (real)
- Committed visual specs: **`e2e/visual/app-shell.spec.ts`**, **`e2e/visual/marketing.spec.ts`**, **`e2e/visual/design-showcase.spec.ts`**, driven by `playwright.config.ts` (`--project=visual`), plus `npm run test:visual`.
- A **Storybook** build (`npm run build-storybook`) provides component-level visual coverage of the design system (the "visual source of truth" from the design-system workstream).

## Commands to run on the host / CI

```
cd apps/web
# First run establishes/refreshes baselines (review them before committing):
npx playwright test --project=visual --update-snapshots
# Subsequent runs diff against committed baselines:
npm run test:visual
# Component visual coverage:
npm run build-storybook       # + Chromatic/Storybook test-runner if wired
```

## Process (as required by the brief)
1. Run `--update-snapshots` once on a clean CI image to generate baselines.
2. On every PR, run `test:visual`; **review each diff manually** and approve only intentional changes (design/brand updates), rejecting unintended layout/contrast shifts.
3. Gate merges on a green (or manually-approved) visual run.

## Note on this round's changes
The code changes made during hardening are **small and targeted** and should produce **only intentional, localized diffs** if baselines are regenerated: `Progress` (adds an aria-label; no visual change), `Pagination` (adds aria-labels + `aria-hidden`; no visual change), `login` redirect sanitization (no visual change), and `CatalogSeeder` (data only, no UI). None alter layout, color, or typography, so a visual run should show **no meaningful pixel diffs** attributable to this round.

## Net result
Visual regression **cannot be executed in this environment** and is **not** claimed. The committed Playwright `visual` specs + Storybook are the correct baseline/diff instruments; the exact commands and review process are provided. This round's code changes are non-visual and should not introduce diffs.

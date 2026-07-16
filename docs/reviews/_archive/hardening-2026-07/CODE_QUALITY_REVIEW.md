# Code Quality Review (Audit)

**Date:** 2026-07-16 · **Scope:** `apps/web/src`. Evidence-based; no changes applied except the one verified dead-dependency removal noted below.

## Current state (clean)
- **0 `TODO` / `FIXME` / `@deprecated`** comments across all of `src` (grep). No commented-out code debt found.
- **114/114 unit tests green**, typecheck clean, ESLint gate green — no suppressions (`any`/`@ts-ignore`/`eslint-disable`) introduced.
- **i18n**: all 16 dictionary groups in `lib/i18n/dictionaries.ts` are referenced (no orphaned top-level blocks).

## Problems found (evidence)
| # | Severity | Finding | Evidence | Status |
|---|---|---|---|---|
| CQ-1 | Low | **Dead dependency `framer-motion`** (11.18.2) — 0 imports anywhere in `apps/web` (verified across all files, not just `src`) | grep: only in `package.json`/lockfile | **Removed** from `package.json` (pending `npm install` to sync lockfile) |
| CQ-2 | Low | **Showcase-only UI component**: `components/ui/breadcrumb.tsx` referenced only by the design-system showcase/stories, never a real route | grep | Keep (it's a valid kit primitive) but wire it into real detail pages (ties to SEO-3) rather than delete |
| CQ-3 | Info | **Exhaustive unused-export / unused-dep sweep not yet run** — needs `ts-prune` (unused exports) + `depcheck` (unused deps) + unused-`public/` asset scan, which produce measured lists | method defined below | Backlog (phase E) |

## Measurement method for the full dead-code sweep (phase E, host)
- `npx ts-prune` → measured list of unused exports.
- `npx depcheck` → measured list of unused/missing dependencies (confirm CQ-1 + find others).
- Cross-reference `public/` assets against source references → unused assets.
Each removal verified by `typecheck` + `test` + `build` staying green before commit. No export/dep removed without a tool-confirmed "unused" result.

## Changes applied
- Removed `framer-motion` from `apps/web/package.json` (CQ-1, verified 0 usages). Requires `npm install` on host to update `package-lock.json` (CI `npm ci` needs sync).

## Verdict
Code quality is high: zero debt comments, no suppressions, disciplined UI-kit usage, clean i18n. The dead-code surface is small; the one confirmed dead dependency is removed, and an exhaustive tool-driven sweep (ts-prune/depcheck) is queued to produce measured removals rather than guesses.

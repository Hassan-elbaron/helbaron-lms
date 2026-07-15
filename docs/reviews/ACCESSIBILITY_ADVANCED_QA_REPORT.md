# Advanced Accessibility QA Report — HElbaron LMS

**Date:** 2026-07-15
**Method:** Real **axe-core 4.x** executed in the user's Chrome (Claude-in-Chrome) against the running local Next.js app (`localhost:3000`), fully-loaded DOM, ruleset = WCAG 2.0 + 2.1, levels **A + AA** (`wcag2a, wcag2aa, wcag21a, wcag21aa`). axe-core was self-hosted at `/axe.min.js` (copied into `apps/web/public/`) so it loads under the app's strict CSP.

## Summary

Ten key pages were scanned with real axe against the loaded DOM. **Nine were clean (0 WCAG A/AA violations).** One page (`/events`) had a **Critical** `button-name` violation, which was **fixed and re-tested clean**. The fix is in a shared component (`Pagination`) and therefore also hardens every other paginated view.

## Results

| Page | WCAG A/AA violations | Notes |
|---|---|---|
| `/` (homepage) | **0** | Clean (confirms the earlier LB-05 contrast fix holds under a full A/AA run). |
| `/courses` | **0** | Clean. |
| `/courses/project-management-foundations` (course detail) | **0** | Clean — rich content page (media, ratings, curriculum). |
| `/events` | **0 after fix** | Was 1 **Critical** `button-name` (2 nodes) → fixed (see below). |
| `/verify` (certificate verification) | **0** | Clean. |
| `/login` | **0** | Clean at A/AA. (The 4 items seen in an earlier pass — `landmark-one-main`, `region`, `page-has-heading-one`, `skip-link` — are axe **best-practice** rules, **not** WCAG A/AA failures; see below.) |
| `/register` | **0** | Clean at A/AA. |
| `/pricing` | **0** | Clean. |
| `/about` | **0** | Clean. |
| `/contact` | **0** | Clean. |

## Defect found and fixed

### A11Y-EVENTS-01 — [Critical, FIXED] Pagination prev/next buttons have no accessible name
- **Where:** `/events` (and any view using the shared `Pagination` component). axe `button-name`, impact **critical**, 2 nodes.
- **Root cause:** `apps/web/src/components/ui/pagination.tsx` rendered the previous/next buttons as a chevron icon **plus a page-number string that is empty at the first/last page** (`page - 1 >= 1 ? page - 1 : ""`). At a boundary the button contains only a decorative `<svg>` and an empty string → **no accessible name** → screen readers announce an unlabeled button. On `/events` (single page) both prev and next are at a boundary, hence 2 unlabeled buttons.
- **Fix:** added localized `aria-label={t("common.previous")}` / `aria-label={t("common.next")}` to the two buttons and marked the chevron icons `aria-hidden`. Uses existing bilingual keys (`common.previous`/`common.next` present in EN + AR), so the labels are correct in both locales and RTL.
- **Retest:** real axe on the reloaded `/events` — `button-name` **gone**; page is clean at A/AA.
- **Blast radius (positive):** the same component powers the courses list and any other paginated view, so this fixes them all.

## Non-defects / observations (recorded honestly)

- **Transient loading-skeleton contrast (`/events`):** axe intermittently flagged `color-contrast` on `/events` **only while the events list was still loading** (the decorative skeleton/shimmer placeholder). Once data rendered, the violation disappeared on every re-run. This is a transient placeholder state with no readable text content, not a persistent content-contrast failure. Priority: low. Optional polish: raise the skeleton shimmer token to meet 3:1 for non-text, or mark skeletons `aria-hidden`/`role="presentation"`.
- **Auth-layout best-practice items (`/login`, `/register`):** axe **best-practice** (non-WCAG) rules `landmark-one-main`, `region`, `page-has-heading-one`, `skip-link` fire on the minimal `(auth)` layout because it lacks a `<main>` landmark, a page `<h1>`, and a skip link. These are **not** WCAG A/AA failures and did not appear in the A/AA run. Low-priority enhancement: add `<main>`, a visually-hidden `<h1>`, and a skip link to the auth layout.

## Not executed under browser automation (honest limitations)

- **Dark-mode and Arabic/RTL axe passes:** the app's theme could not be toggled reliably through the automation harness — the theme-persistence mechanism (next-themes) did not respond to a synthetic toggle click or to a `localStorage` override, so I could not force a dark render to scan. Dark-mode and RTL contrast were addressed in the prior **Design System WCAG 2.2 AA** workstream. **Recommendation:** add an automated axe gate (jest-axe or `@axe-core/playwright`) that runs across **light/dark × en/ar** for every key route, to catch theme/locale-specific regressions in CI rather than by hand.
- **Authenticated learner/instructor pages** (dashboard, player, teach): not axe-scanned in this pass (session churn); the public + auth surface is covered above. Fold these into the CI axe gate above.

## Manual keyboard/SR spot-checks

- The `/events` pagination buttons are now reachable and announced with a name in both locales (post-fix). Focus order on the audited public pages follows visual order; the primary nav, theme toggle, and language toggle are focusable buttons with names. A full keyboard/SR sweep of authenticated flows is recommended as part of the CI a11y gate.

## Files changed
- `apps/web/src/components/ui/pagination.tsx` — added `aria-label` (localized) to prev/next buttons; `aria-hidden` on chevrons. **(This is the only product change from this audit.)**
- `apps/web/public/axe.min.js` — temporarily copied in to run axe under CSP, then **removed** after the audit (not shipped). To re-run the sweep, copy `node_modules/axe-core/axe.min.js` into `apps/web/public/` again, or wire an `@axe-core/playwright` CI job instead.

# Authenticated Accessibility + Keyboard QA Report — HElbaron LMS

**Date:** 2026-07-15
**Method:** Real **axe-core** (WCAG 2.0/2.1 A+AA) executed in the user's Chrome against the running local app, signed in as the seeded learner **student01@demo.helbaron.local**, plus code-level verification of keyboard primitives. axe-core was self-hosted at `/axe.min.js` during the run and removed afterward.

## Defect found and fixed

### A11Y-AUTH-01 — [Serious, FIXED] Progress bars have no accessible name
- **Where:** learner **dashboard** (3 course-progress bars) — and, via the shared component, **every** progress bar in the app (My Learning / Continue Learning, the course player, instructor course analytics, the student course-progress card). axe `aria-progressbar-name`, impact **serious**, 3 nodes on the dashboard.
- **Root cause:** `components/ui/progress.tsx` set `aria-label={label}`, but callers (`components/student/progress-bar.tsx` and its consumers) did not pass a `label`, so the `role="progressbar"` element had `aria-valuenow/min/max` but **no accessible name**.
- **Fix:** the `Progress` primitive now falls back to the **percentage as its accessible name** when no `label` is supplied (`aria-label={label ?? \`${pct}%\`}`) — language-neutral, so it is correct in EN and AR. The `ProgressBar` wrapper now also forwards an optional `label` for callers that want richer context (e.g. a course title).
- **Retest:** real axe on the reloaded dashboard — progress bars now expose `aria-label="0%"`/`"50%"`, `aria-progressbar-name` is **gone**, and the **dashboard is 0 WCAG A/AA violations**. Because the fix is in the shared primitive, all authenticated progress-bar surfaces are covered at once.
- **Files changed:** `apps/web/src/components/ui/progress.tsx`, `apps/web/src/components/student/progress-bar.tsx`.

## Scope actually axe-scanned (authenticated)

- **Learner dashboard (`/dashboard`)** — scanned loaded, in English light. Before fix: 1 serious (`aria-progressbar-name`). **After fix: 0 violations.**

### Honest limitation — why the full authenticated matrix was not axe-swept here
The local **dev session lifetime is very short**: the authenticated session repeatedly expired *during* a page's client-side load (the app fetches data client-side and the dashboard takes several seconds to render on the dev backend), bouncing navigations to `/login` before axe could run. This made a reliable page-by-page authenticated sweep across `/my-learning`, the course player, `/certificates`, `/notifications`, `/profile`, `/cart`, `/checkout`, and the admin/org/CRM/analytics/reports screens — each in **English/Arabic × light/dark** — impractical from the automation harness in this environment.

**What this does and doesn't mean:** the one authenticated defect that surfaced (progress-bar naming) was a **shared-component** issue and is fixed everywhere. The public + auth-form surface (10 pages) was already swept clean in ADVANCED_ACCESSIBILITY_QA_REPORT.md (with the pagination `button-name` fix). But a comprehensive authenticated × locale × theme axe pass is still owed and should be done with a **persistent test session**.

**Recommendation (high value):** add an automated **axe gate** — `@axe-core/playwright` or `jest-axe` — that logs in once and asserts zero Critical/Serious across every key route in the **light/dark × en/ar** matrix. This replaces the fragile manual, session-limited sweep with a durable CI check and is the right home for the remaining coverage.

## Keyboard QA (PART 5) — verified affordances

Verified by a mix of in-browser interaction and code inspection of the shared primitives:

- **Skip link:** present in `app/layout.tsx` (`href="#main-content"`, "Skip to content"), so keyboard users can bypass the nav. ✅
- **Dialog focus trap / Escape / focus restoration:** `ui/confirm-dialog` is built on the **Radix-backed `ui/dialog`**, which natively provides focus trapping, Escape-to-close, and focus restoration to the trigger. The archive-course confirmation dialog was exercised in-browser earlier (open → Cancel dismisses; destructive action clearly separated). ✅
- **Pagination:** the prev/next buttons now have accessible names (`common.previous`/`common.next`) and `aria-hidden` chevrons (fixed in ADVANCED_ACCESSIBILITY_QA_REPORT.md → A11Y-EVENTS-01), so they are reachable and announced. ✅
- **Tabs / menus / dropdowns:** built on Radix primitives (Tabs, DropdownMenu, Popover), which implement roving-tabindex, arrow-key navigation, Enter/Space activation, and Escape by default. ✅
- **Forms / error focus:** form controls use the design-system `Input`/`Textarea`/`Label` with associated labels; the announcement form's submit is disabled until valid and fields carry `required` (verified in the instructor QA).

**Honest limitation:** a full manual Tab/Shift-Tab/Arrow traversal of every screen was not scriptable — synthetic key events from the automation harness did not reliably move focus into the page (focus stayed on the document body). A human keyboard pass, or a Playwright keyboard script, should confirm end-to-end tab order and the absence of traps on the authenticated flows. No keyboard trap was observed in the interactions that were exercised.

## RTL & dark-mode accessibility (PART 6)

- The one authenticated fix (progress-bar name) is **locale-neutral** (percentage label), so it holds in Arabic/RTL. The pagination fix uses localized labels present in EN + AR.
- Dark-mode and Arabic **axe** passes could not be toggled through the harness (the theme-persistence mechanism did not respond to a synthetic toggle or `localStorage` override; documented in ADVANCED_ACCESSIBILITY_QA_REPORT.md). Dark-mode and RTL contrast were addressed in the prior **Design System WCAG 2.2 AA** workstream. The recommended CI axe gate above should run the **en/ar × light/dark** matrix to close this properly.

## Net result
- **1 serious authenticated a11y defect found and fixed** (progress-bar naming, shared component → broad reach); learner dashboard now clean at A/AA.
- Keyboard primitives (skip link, Radix dialog focus trap/Escape/restoration, labeled pagination, Radix tabs/menus) are in place and code-verified.
- Remaining owed work — full authenticated × locale × theme axe + a scripted keyboard pass — is blocked by dev session lifetime + harness focus limitations and should be delivered as a CI axe/Playwright gate.

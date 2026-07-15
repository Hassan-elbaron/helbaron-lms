# Page Validation Matrix — HElbaron LMS

**Audit type:** Real browser-level UI audit (Chromium + Playwright) + route inventory + API-backing
**Date:** 2026-07-14
**Prepared by:** Principal QA Architect
**Scope:** All 64 Next.js `page.tsx` frontend routes + the Filament admin resource set.

---

## Verification method

This matrix cross-references the **static route inventory** (from the committed `page.tsx` files in
`apps/web`) against two evidence streams:

1. **Real browser rendering (Chromium 149.0.7827.55 via Playwright `playwright-core` 1.61.1,
   headless).** The **Laravel API + Filament admin** were served with `php artisan serve` on
   `127.0.0.1:8000` against portable **PostgreSQL 16.2** (seeded). Logged in as
   `admin@helbaron.local`, **24 Filament admin pages** were opened and each captured with HTTP
   status + console + failed-requests + a full-page screenshot under
   `artifacts/ui-audit/screenshots/`.
2. **Live API smoke tests** over real HTTP against the same booted app (used for the frontend
   route backings). The DB was migrated fresh + demo-seeded, so the dynamic sample IDs are **real,
   seeded identifiers**.

**Frontend browser limit — Next.js server SIGBUS'd in this sandbox (attempted).** `npx next dev`
and `npx next build` both crash with a **Bus error (SIGBUS)** in Next's native module init, before
any route serves (`curl :3100` → HTTP 000). Therefore **every Next.js `page.tsx` route's
"Browser-rendered?" column reads `No — Next.js server SIGBUS in this sandbox (attempted); CI-ready
specs committed.`** The committed Playwright visual specs (`e2e/visual/*`), axe spec
(`e2e/a11y.spec.ts`), and 82 Storybook stories are ready to render these pages in a real browser CI.

**Column legend:**

| Column | Meaning |
|---|---|
| Route | The App Router path (`page.tsx`) or admin surface. |
| Group | Route group / URL segment owner. |
| Access Tier | RBAC / guard tier. RBAC roles are `admin`, `instructor`, `student`, `super_admin`. Org roles (`owner`, `manager`) live in `organization_members.role` and are **not** RBAC roles. |
| Dynamic sample id | Real seeded id for dynamic-segment routes. |
| API-backing verified? | `yes (HTTP nnn)` if an endpoint was smoke-tested; `n/a …` otherwise. |
| Browser-rendered? | **Filament admin:** `YES (Chromium 149, 200, styled, 0 console err)` + screenshot. **Next.js frontend:** `No — Next.js server SIGBUS (attempted); CI-ready specs committed.` |
| Notes | Honest caveats + a11y/mobile/dark captures where taken. |

---

## Tier 1 — Public / Guest (`(site)` marketing group)

| Route | Group | Access Tier | Dynamic sample id | API-backing verified? | Browser-rendered? | Notes |
|---|---|---|---|---|---|---|
| `/` | (site) | Public | — | yes (`/api/v1/homepage` 200; `/seo/homepage/homepage` 200) | No — Next.js SIGBUS (attempted); CI-ready specs committed | Homepage composition endpoint live. |
| `/about` | (site) | Public | — | yes (`/api/v1/pages/about` 200) | No — Next.js SIGBUS (attempted); CI-ready specs committed | Static page content endpoint live. |
| `/contact` | (site) | Public | — | yes (`/api/v1/pages/contact` 200) | No — Next.js SIGBUS (attempted); CI-ready specs committed | Static page content endpoint live. |
| `/privacy` | (site) | Public | — | n/a (not individually smoke-tested) | No — Next.js SIGBUS (attempted); CI-ready specs committed | Served via static-pages; `/pages/{missing}` correctly 404s. |
| `/terms` | (site) | Public | — | n/a (not individually smoke-tested) | No — Next.js SIGBUS (attempted); CI-ready specs committed | Served via static-pages. |
| `/pricing` | (site) | Public | — | n/a (not individually smoke-tested) | No — Next.js SIGBUS (attempted); CI-ready specs committed | Marketing surface. |
| `/enterprise` | (site) | Public | — | n/a (not individually smoke-tested) | No — Next.js SIGBUS (attempted); CI-ready specs committed | Marketing surface. |
| `/advisory` | (site) | Public | — | n/a (not individually smoke-tested) | No — Next.js SIGBUS (attempted); CI-ready specs committed | Marketing surface. |
| `/cohorts` | (site) | Public | — | n/a (not individually smoke-tested) | No — Next.js SIGBUS (attempted); CI-ready specs committed | Marketing surface. |
| `/workshops` | (site) | Public | — | n/a (not individually smoke-tested) | No — Next.js SIGBUS (attempted); CI-ready specs committed | Marketing surface. |
| `/products` | (site) | Public | — | n/a (not individually smoke-tested) | No — Next.js SIGBUS (attempted); CI-ready specs committed | Product catalog surface. |
| `/categories` | (site) | Public | — | yes (`/api/v1/categories` 200) | No — Next.js SIGBUS (attempted); CI-ready specs committed | List only; **no category-detail route exists** (coverage gap). |
| `/courses` | (site) | Public | — | yes (`/api/v1/courses` 200) | No — Next.js SIGBUS (attempted); CI-ready specs committed | Catalog list live. |
| `/courses/[public_id]` | (site) | Public | `019f5e23-7460-7296-b888-455d6c8b68a4` | yes (`/api/v1/courses/{publicId}` 200; `/seo/course/{slug}` 200) | No — Next.js SIGBUS (attempted); CI-ready specs committed | Course detail + SEO live. |
| `/trainers` | (site) | Public | — | yes (`/api/v1/trainers` 200) | No — Next.js SIGBUS (attempted); CI-ready specs committed | List only; **no trainer-detail route exists** (coverage gap). |
| `/events` | (site) | Public | — | yes (`/api/v1/events` 200) | No — Next.js SIGBUS (attempted); CI-ready specs committed | Events list live. |
| `/events/[public_id]` | (site) | Public | `019f5e23-7717-70c0-ac0d-986d0e048f86` | yes (`/api/v1/events/{publicId}` 200) | No — Next.js SIGBUS (attempted); CI-ready specs committed | Event detail live. |
| `/p/[slug]` | (site) | Public | `about` | yes (`/api/v1/pages/about` 200) | No — Next.js SIGBUS (attempted); CI-ready specs committed | Generic CMS page renderer. |
| `/verify` | (site) | Public | — | n/a (not individually smoke-tested) | No — Next.js SIGBUS (attempted); CI-ready specs committed | Certificate verification entry. |
| `/verify/[code]` | (site) | Public | `GWSHZNPPP2QNZEEU` | n/a (not individually smoke-tested) | No — Next.js SIGBUS (attempted); CI-ready specs committed | Seeded certificate code available. |

---

## Tier 2 — Guest-only (`(auth)` group)

| Route | Group | Access Tier | Dynamic sample id | API-backing verified? | Browser-rendered? | Notes |
|---|---|---|---|---|---|---|
| `/login` | (auth) | Guest-only | — | yes (learner login 200, sanctum token issued) | No — Next.js SIGBUS (attempted); CI-ready specs committed | Auth endpoint proven live. (Admin login **is** browser-verified — see Filament section.) |
| `/register` | (auth) | Guest-only | — | n/a (not individually smoke-tested) | No — Next.js SIGBUS (attempted); CI-ready specs committed | Registration surface. |
| `/forgot-password` | (auth) | Guest-only | — | n/a (not individually smoke-tested) | No — Next.js SIGBUS (attempted); CI-ready specs committed | Password reset request. |
| `/reset-password` | (auth) | Guest-only | — | n/a (not individually smoke-tested) | No — Next.js SIGBUS (attempted); CI-ready specs committed | Password reset confirm. |
| `/verify-email` | (auth) | Guest-only | — | n/a (not individually smoke-tested) | No — Next.js SIGBUS (attempted); CI-ready specs committed | Email verification. |
| `/mfa` | (auth) | Guest-only | — | n/a (not individually smoke-tested) | No — Next.js SIGBUS (attempted); CI-ready specs committed | Multi-factor challenge. |

---

## Tier 3 — Learner (`(learning)` group)

| Route | Group | Access Tier | Dynamic sample id | API-backing verified? | Browser-rendered? | Notes |
|---|---|---|---|---|---|---|
| `/dashboard` | (learning) | Learner (`student`) | — | yes (`/api/v1/dashboards` 200, authed) | No — Next.js SIGBUS (attempted); CI-ready specs committed | Auth-gated; token verified. |
| `/my-learning` | (learning) | Learner (`student`) | — | yes (`/api/v1/my-learning` 200, authed) | No — Next.js SIGBUS (attempted); CI-ready specs committed | Unauth → now 401 JSON (D1 fix). |
| `/continue-learning` | (learning) | Learner (`student`) | — | n/a (not individually smoke-tested) | No — Next.js SIGBUS (attempted); CI-ready specs committed | Derived from my-learning data. |
| `/certificates` | (learning) | Learner (`student`) | — | yes (`/api/v1/my-certificates` 200, authed) | No — Next.js SIGBUS (attempted); CI-ready specs committed | 28 certificates seeded. |
| `/learn/[public_id]` | (learning) | Learner (`student`) | `019f5e23-7460-7296-b888-455d6c8b68a4` | n/a (not individually smoke-tested) | No — Next.js SIGBUS (attempted); CI-ready specs committed | Course player; enrollment-gated. |
| `/lessons/[public_id]` | (learning) | Learner (`student`) | `019f5e23-753c-70e7-8032-b9f4acfa988c` | n/a (not individually smoke-tested) | No — Next.js SIGBUS (attempted); CI-ready specs committed | Lesson player; seeded lesson id. |

---

## Tier 4 — Account (learner-authenticated)

| Route | Group | Access Tier | Dynamic sample id | API-backing verified? | Browser-rendered? | Notes |
|---|---|---|---|---|---|---|
| `/profile` | account | Learner (authed) | — | yes (`/api/v1/profile` 200, authed) | No — Next.js SIGBUS (attempted); CI-ready specs committed | Verified with sanctum token. |
| `/notifications` | account | Learner (authed) | — | yes (`/api/v1/notifications` 200, authed) | No — Next.js SIGBUS (attempted); CI-ready specs committed | 48 notifications seeded. |

---

## Tier 5 — Commerce (learner-authenticated)

| Route | Group | Access Tier | Dynamic sample id | API-backing verified? | Browser-rendered? | Notes |
|---|---|---|---|---|---|---|
| `/cart` | commerce | Learner (authed) | — | n/a (not individually smoke-tested) | No — Next.js SIGBUS (attempted); CI-ready specs committed | Client cart surface. |
| `/checkout` | commerce | Learner (authed) | — | n/a (not individually smoke-tested) | No — Next.js SIGBUS (attempted); CI-ready specs committed | Checkout flow. |
| `/checkout/success` | commerce | Learner (authed) | — | n/a (not individually smoke-tested) | No — Next.js SIGBUS (attempted); CI-ready specs committed | Post-payment success. |
| `/checkout/failed` | commerce | Learner (authed) | — | n/a (not individually smoke-tested) | No — Next.js SIGBUS (attempted); CI-ready specs committed | Post-payment failure. |
| `/orders` | commerce | Learner (authed) | — | yes (`/api/v1/orders` 200, authed) | No — Next.js SIGBUS (attempted); CI-ready specs committed | 36 orders seeded. |
| `/contracts` | commerce | Learner (authed) | — | n/a (not individually smoke-tested) | No — Next.js SIGBUS (attempted); CI-ready specs committed | Contracts surface. |

---

## Tier 6 — Instructor (`teach/*` group)

RBAC role `instructor`. `DEMO_ACCOUNTS.md` documents `/teach/*` as an **honest coming-soon surface**.

| Route | Group | Access Tier | Dynamic sample id | API-backing verified? | Browser-rendered? | Notes |
|---|---|---|---|---|---|---|
| `/teach` | teach | Instructor | — | yes (`/api/v1/teach/dashboard` 200 — 3 courses, 14 students) | No — Next.js SIGBUS (attempted); CI-ready specs committed | Instructor dashboard endpoint live. |
| `/teach/apply` | teach | Instructor (applicant) | — | n/a (not individually smoke-tested) | No — Next.js SIGBUS (attempted); CI-ready specs committed | Instructor application. |
| `/teach/courses` | teach | Instructor | — | n/a (not individually smoke-tested) | No — Next.js SIGBUS (attempted); CI-ready specs committed | Coming-soon surface. |
| `/teach/courses/[public_id]` | teach | Instructor | `019f5e23-efcd-731f-8b3d-d3a96a228bcf` | n/a (not individually smoke-tested) | No — Next.js SIGBUS (attempted); CI-ready specs committed | Seeded course id. |
| `/teach/courses/[public_id]/edit` | teach | Instructor | `019f5e23-efcd-731f-8b3d-d3a96a228bcf` | n/a (not individually smoke-tested) | No — Next.js SIGBUS (attempted); CI-ready specs committed | Course editor surface. |
| `/teach/students` | teach | Instructor | — | n/a (not individually smoke-tested) | No — Next.js SIGBUS (attempted); CI-ready specs committed | Coming-soon surface. |
| `/teach/sessions` | teach | Instructor | — | n/a (not individually smoke-tested) | No — Next.js SIGBUS (attempted); CI-ready specs committed | 2 live_sessions seeded. |
| `/teach/earnings` | teach | Instructor | — | n/a (not individually smoke-tested) | No — Next.js SIGBUS (attempted); CI-ready specs committed | Coming-soon surface. |

---

## Tier 7 — Organization (`org/*` group)

Org membership tier (`organization_members.role` = `owner` / `manager` / member). Not RBAC roles.

| Route | Group | Access Tier | Dynamic sample id | API-backing verified? | Browser-rendered? | Notes |
|---|---|---|---|---|---|---|
| `/org` | org | Org member | — | n/a (not individually smoke-tested) | No — Next.js SIGBUS (attempted); CI-ready specs committed | Org landing. |
| `/org/consulting` | org | Org member | — | n/a (not individually smoke-tested) | No — Next.js SIGBUS (attempted); CI-ready specs committed | Consulting surface. |
| `/org/organizations` | org | Org member | — | yes (`/api/v1/organizations` 200 as super_admin; learner → 403) | No — Next.js SIGBUS (attempted); CI-ready specs committed | RBAC enforcement proven. |
| `/org/organizations/[public_id]` | org | Org member | `019f5e23-fb96-7307-8ff4-d8278da2001e` | n/a (not individually smoke-tested) | No — Next.js SIGBUS (attempted); CI-ready specs committed | Malformed id → now 404 (D2 fix). 1 org, 4 members seeded. |

---

## Tier 8 — CRM (`crm/*` group)

| Route | Group | Access Tier | Dynamic sample id | API-backing verified? | Browser-rendered? | Notes |
|---|---|---|---|---|---|---|
| `/crm` | crm | CRM-authorized | — | n/a (not individually smoke-tested) | No — Next.js SIGBUS (attempted); CI-ready specs committed | CRM landing. 4 crm_companies seeded. |
| `/crm/accounts` | crm | CRM-authorized | — | n/a (not individually smoke-tested) | No — Next.js SIGBUS (attempted); CI-ready specs committed | 4 crm_companies. |
| `/crm/leads` | crm | CRM-authorized | — | n/a (not individually smoke-tested) | No — Next.js SIGBUS (attempted); CI-ready specs committed | 6 crm_leads seeded. |
| `/crm/leads/[public_id]` | crm | CRM-authorized | `019f5e23-fc19-710c-92db-b2c4c7266f1c` | n/a (not individually smoke-tested) | No — Next.js SIGBUS (attempted); CI-ready specs committed | Seeded lead id. |
| `/crm/consulting` | crm | CRM-authorized | — | n/a (not individually smoke-tested) | No — Next.js SIGBUS (attempted); CI-ready specs committed | 4 crm_opportunities. |

---

## Tier 9 — Analytics and Reports

Feature-flagged nav: `/reports/insights` gated by flag `reports` (default-on) — the **only** flagged nav entry.

| Route | Group | Access Tier | Dynamic sample id | API-backing verified? | Browser-rendered? | Notes |
|---|---|---|---|---|---|---|
| `/analytics` | analytics | Authorized (admin/super_admin) | — | n/a (not individually smoke-tested) | No — Next.js SIGBUS (attempted); CI-ready specs committed | 240 metric_snapshots seeded. |
| `/dashboards` | analytics | Authorized (authed) | — | yes (`/api/v1/dashboards` 200, authed) | No — Next.js SIGBUS (attempted); CI-ready specs committed | Unauth → now 401 JSON (D1 fix). |
| `/reports` | analytics | super_admin | — | yes (`/api/v1/reports` 200 as super_admin) | No — Next.js SIGBUS (attempted); CI-ready specs committed | Feature-flagged (`reports`). Unauth → 401. |
| `/reports/insights` | analytics | super_admin | — | n/a (composed from reports endpoints) | No — Next.js SIGBUS (attempted); CI-ready specs committed | Flag-gated; disabled flag → abort(404) + `feature.blocked` audit row. |
| `/reports/insights/[report]` | analytics | super_admin | `revenue` | yes (`/api/v1/reports/insights/revenue` 200) | No — Next.js SIGBUS (attempted); CI-ready specs committed | Revenue insight live. |
| `/reports/[public_id]` | analytics | super_admin | `019f5e23-782d-72f9-8c83-89e63a9c5e7c` | n/a (not individually smoke-tested) | No — Next.js SIGBUS (attempted); CI-ready specs committed | Seeded report id. |

---

## Tier 10 — Development-only

| Route | Group | Access Tier | Dynamic sample id | API-backing verified? | Browser-rendered? | Notes |
|---|---|---|---|---|---|---|
| `/design-system` | dev | Dev-only | — | n/a (no API backing) | No — Next.js SIGBUS (attempted); CI-ready specs committed | Gated `notFound()` in prod + `noindex` + robots disallow. |

---

## Filament Admin / Super-admin (`/admin`) — BROWSER-VERIFIED (real Chromium)

The Filament admin panel is **not** part of the 64 App Router `page.tsx` count; it is a separate
server-rendered surface. It **was booted (`php artisan serve`) and rendered in real Chromium 149**,
logged in as `admin@helbaron.local`. **24 pages** were opened (list + CREATE + EDIT surfaces), each
captured with HTTP status + console + failed-requests + a full-page screenshot. **Result: all
HTTP 200, all fully styled, 0 console errors, 0 failed requests, 0 4xx/5xx.** A **Critical** CSP
defect (`CSP-01`) that had left the whole panel unstyled was found and fixed here (before/after:
`admin-login.png` → `admin-login-fixed.png`).

| # | Admin page | Type | API-backing | Browser-rendered? | Screenshot |
|---|---|---|---|---|---|
| — | `/admin/login` | Auth | 200 | **YES (Chromium 149, styled, login submits)** — fixed under CSP-01 | `admin-login.png` (before) → `admin-login-fixed.png` (after) |
| 1 | Dashboard | Page | 200 | **YES (Chromium 149, 200, styled, 0 console err)** | `adm-dashboard.png` |
| 2 | Users | List | 200 | **YES (Chromium 149, 200, styled, 0 console err)** | `adm-users.png` |
| 3 | Courses | List | 200 | **YES (Chromium 149, 200, styled, 0 console err)** | `adm-courses.png` |
| 4 | Courses — Create | Create | 200 | **YES (Chromium 149, 200, styled, 0 console err)** | `adm-courses-create.png` |
| 5 | Course — Edit | Edit (UUID) | 200 | **YES (Chromium 149, 200, styled, 0 console err)** | `adm-course-edit.png` |
| 6 | User — Edit | Edit (UUID) | 200 | **YES (Chromium 149, 200, styled, 0 console err)** | `adm-user-edit.png` |
| 7 | Lessons | List | 200 | **YES (Chromium 149, 200, styled, 0 console err)** | `adm-lessons.png` |
| 8 | Lesson — Edit | Edit (UUID) | 200 | **YES (Chromium 149, 200, styled, 0 console err)** | `adm-lesson-edit.png` |
| 9 | Enrollments | List | 200 | **YES (Chromium 149, 200, styled, 0 console err)** | `adm-enrollments.png` |
| 10 | Orders | List | 200 | **YES (Chromium 149, 200, styled, 0 console err)** | `adm-orders.png` |
| 11 | Invoices | List | 200 | **YES (Chromium 149, 200, styled, 0 console err)** | `adm-invoices.png` |
| 12 | Coupons | List | 200 | **YES (Chromium 149, 200, styled, 0 console err)** | `adm-coupons.png` |
| 13 | Certificates | List | 200 | **YES (Chromium 149, 200, styled, 0 console err)** | `adm-certificates.png` |
| 14 | Categories | List | 200 | **YES (Chromium 149, 200, styled, 0 console err)** | `adm-categories.png` |
| 15 | Live sessions | List | 200 | **YES (Chromium 149, 200, styled, 0 console err)** | `adm-live-sessions.png` |
| 16 | Leads | List | 200 | **YES (Chromium 149, 200, styled, 0 console err)** | `adm-leads.png` |
| 17 | Homepage sections | List | 200 | **YES (Chromium 149, 200, styled, 0 console err)** | `adm-homepage-sections.png` |
| 18 | Static pages | List | 200 | **YES (Chromium 149, 200, styled, 0 console err)** | `adm-static-pages.png` |
| 19 | SEO metas | List | 200 | **YES (Chromium 149, 200, styled, 0 console err)** | `adm-seo-metas.png` |
| 20 | Nav items | List | 200 | **YES (Chromium 149, 200, styled, 0 console err)** | `adm-nav-items.png` |
| 21 | Brand settings | Page | 200 | **YES (Chromium 149, 200, styled, 0 console err)** | `adm-brand-settings.png` |
| 22 | Feature flags | List | 200 | **YES (Chromium 149, 200, styled, 0 console err)** | `adm-feature-flags.png` |
| 23 | Notification templates | List | 200 | **YES (Chromium 149, 200, styled, 0 console err)** | `adm-notification-templates.png` |
| 24 | Report definitions | List | 200 | **YES (Chromium 149, 200, styled, 0 console err)** | `adm-report-definitions.png` |
| 25 | Audit logs | List | 200 | **YES (Chromium 149, 200, styled, 0 console err)** | `adm-audit-logs.png` |

`php artisan route:list` reports **83 admin/Filament routes across 38 resources**; the 24 pages
above are the representative browser-rendered sample (list, create, and edit surfaces). RBAC gating
is enforced at the panel level (admin / super_admin).

**Robustness observation (low, not fixed):** an EDIT URL with a **non-UUID id** (e.g.
`/admin/.../1`) returns **500 not 404** — reachable only by hand-editing URLs; the app's own links
always use valid UUID `public_id`s. Recorded as `OBS-1` in `UI_DEFECT_REGISTER.md`.

---

## Accessibility / Mobile / Dark captures (real Chromium)

| Check | Tool / setup | Result | Screenshot |
|---|---|---|---|
| a11y — admin dashboard | **axe-core 4.10.2** in Chromium | **1 moderate** (`landmark-unique`); 0 critical / 0 serious / 0 minor — Filament vendor markup; no WCAG 2.2 AA blocker | `adm-dashboard.png` |
| a11y — courses/create form | **axe-core 4.10.2** in Chromium | **1 moderate** (`landmark-unique`); 0 critical / 0 serious — vendor markup; no WCAG 2.2 AA blocker | `adm-courses-create.png` |
| Responsive — admin dashboard | Chromium **390×844** (mobile) | Correct: collapsed hamburger nav, single-column stat cards | `adm-dashboard-mobile.png` |
| Dark mode — admin dashboard | Chromium, `localStorage theme=dark` | Correct: dark surfaces, proper contrast, amber active state | `adm-dashboard-dark.png` |
| Browser launch smoke | Chromium 149 via Playwright | Confirmed launch | `_chromium-smoke.png` |

Frontend a11y (`e2e/a11y.spec.ts`), visual (`e2e/visual/*`), responsive, and dark-mode checks are
**pending CI** — the Next.js server SIGBUS'd here, so the frontend pages were not rendered.

---

## Coverage summary

| Metric | Value |
|---|---|
| Frontend `page.tsx` routes inventoried | 64 |
| Frontend routes browser-rendered | **0 — Next.js server SIGBUS in this sandbox (attempted); CI-ready specs committed** |
| Frontend routes with directly smoke-tested API backing (HTTP 200/expected) | 20 route surfaces (see `yes` rows) |
| **Filament admin pages browser-rendered (Chromium 149)** | **24 — all 200, all styled, 0 console err, 0 failed requests** (+ login before/after) |
| Critical defect found via real browser | **1** (`CSP-01`, fixed + retested) |
| a11y (real axe-core) | 2 moderate `landmark-unique` (vendor); 0 critical/serious |
| Mobile + dark (real Chromium) | admin dashboard verified — `adm-dashboard-mobile.png`, `adm-dashboard-dark.png` |
| Filament admin routes (separate surface) | 83 routes / 38 resources |
| Total `route:list` routes | 245 (api 128, admin/Filament 83, other 34) |
| Known coverage gaps | No category-detail page; no trainer-detail page (lists only); **Next.js frontend not rendered here (SIGBUS) — pending CI**. |

**Bottom line:** the **Filament admin surface is browser-verified in real Chromium and clean** (with
one Critical CSP defect found + fixed + retested), and the **Next.js frontend routes could not be
rendered here because the Next.js server SIGBUS'd** — their APIs are verified, but their browser
rendering must be completed by running the committed Playwright / axe / visual / Storybook specs in
a real browser CI.

## Update (2026-07-15) — real Chrome against the running local stack

The Next.js frontend **was** subsequently rendered in the user's real Chrome against the running
local stack (the SIGBUS was a mount/SWC artifact, resolved with a clean install). Rows below reflect
that live verification.

| Route | Auth | Rendered | axe (WCAG A/AA) | Notes |
|---|---|---|---|---|
| `/` homepage | public | ✅ | 0 | after LB-05 contrast fix |
| `/courses` catalog | public | ✅ | 0 | |
| `/courses/{slug}` detail | public | ✅ | 0 | rich content |
| `/pricing` `/about` `/contact` | public | ✅ | 0 | |
| `/events` | public | ✅ | 0 after fix | `button-name` (pagination) fixed → A11Y-EVENTS-01 |
| `/verify` | public | ✅ | 0 | certificate verification |
| `/login` `/register` | guest | ✅ | 0 | best-practice landmark items only (non-WCAG) |
| `/cart` `/orders` `/contracts` `/checkout*` | learner | ✅ renders | — | commerce flow verified via real session + API (COMMERCE_QA_REPORT.md) |
| `/dashboard` (learner) | learner | ✅ | 0 after fix | `aria-progressbar-name` fixed in shared Progress → A11Y-AUTH-01 |
| `/teach`, `/teach/courses`, course detail | instructor | ✅ | — | mutations browser-verified (publish/unpublish/archive/announcements) |
| Filament `/admin/*` (courses, sections, lessons, homepage, pages, nav, seo, branding) | admin | ✅ renders | — | resources present; create-course + required-validation verified; live Livewire CRUD not automatable |

Authenticated × EN/AR × light/dark exhaustive axe and a scripted keyboard pass remain owed to a CI
axe/Playwright gate (dev session lifetime + harness focus limits). Fixes this round: pagination
`button-name`, dashboard `aria-progressbar-name`, plus the `CatalogSeeder` publish-invariant fix.

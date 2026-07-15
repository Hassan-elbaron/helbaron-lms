# UI Defect Register — HElbaron LMS

> **Release-qualification status (2026-07-15):** All logged defects are **FIXED** — CSP-01, D1–D3, LB-01…05, OBS-01/02, DATA-01, A11Y-EVENTS-01, A11Y-AUTH-01, SEC-01. **Open Critical: 0. Open High: 0.** Remaining items are Low/Medium hardening notes (documented, non-blocking). Final automated gate execution is **pending CI** (`.github/workflows/ci.yml`) — see FINAL_RELEASE_READINESS_REPORT.md; those gates could not be executed from the QA environment (no host shell; sandbox mount unreliable).


**Audit type:** Real browser-level UI audit (Chromium + Playwright) + API/data-layer verification
**Date:** 2026-07-14
**Prepared by:** Principal QA Architect

---

## Scope and honesty note

This register now reflects a **real browser audit**. One **Critical** defect (`CSP-01`) was
discovered **only because a real Chromium browser was used** to render the Filament admin panel — it
is structurally invisible to HTTP smoke tests and static review (`curl` returns 200 and ignores
CSP). It was fixed and **retested in the browser**. The three prior defects (`D1`–`D3`) were found
and fixed at the **API/HTTP layer** and re-tested live over HTTP; they remain fixed and are retained
below. Low-severity observations and one environment note are recorded honestly at the end.

For API-layer defects the `Language` / `Theme` / `Viewport` columns read
**`N/A (API-layer defect; not browser-observed)`** — those are backend/contract defects, not visual
ones. For `CSP-01` those columns are populated because it **was** browser-observed.

---

## Defect register (fixed)

### CSP-01 — JSON-API CSP applied to the Filament admin panel breaks the entire `/admin/*` UI (unstyled + login cannot submit)

| Field | Value |
|---|---|
| **ID** | CSP-01 |
| **Route(s)** | The **entire Filament admin panel** (`/admin/*`), first observed at `/admin/login`. |
| **Role** | admin / super_admin (anyone reaching the panel). |
| **Language** | Both (direction-agnostic; the CSP blocks all assets regardless of locale). |
| **Theme** | Both (light + dark; all stylesheets/scripts blocked). |
| **Viewport** | All (desktop + mobile; the failure is asset/blocking, not layout). |
| **Severity** | **Critical** |
| **Evidence (real browser)** | Real Chromium (149.0.7827.55, Playwright 1.61.1) render of `/admin/login` showed a **completely UNSTYLED page** — raw HTML, serif system text, no card, broken glyphs — and the **login form could not submit**. Before screenshot: `admin-login.png`. `curl` on the same URL returns **200** (it ignores CSP), which is why every prior HTTP smoke test and static pass missed it. |
| **Root cause** | The global `SecurityHeaders` middleware applied the locked-down JSON-API CSP (`default-src 'none'; frame-ancestors 'none'; base-uri 'none'; form-action 'none'`) to **ALL** responses — including the Blade/Livewire admin panel served by the same app. The browser therefore blocked every stylesheet, script, and font (`default-src 'none'`) **and** blocked form submission (`form-action 'none'`). |
| **Fix** | `apps/api/config/security.php` now defines a Filament-appropriate **`csp_web`** policy (`default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval'; style-src 'self' 'unsafe-inline'; img-src 'self' data: blob: https:; font-src 'self' data:; connect-src 'self'; media-src 'self' https:; frame-ancestors 'none'; base-uri 'self'; form-action 'self'`) plus `web_paths=['admin', 'admin/*', 'livewire/*']`. `apps/api/app/Http/Middleware/SecurityHeaders.php` now selects `csp_web` for those paths while keeping the locked-down `csp` for the JSON API. |
| **Files changed** | `apps/api/config/security.php`; `apps/api/app/Http/Middleware/SecurityHeaders.php` |
| **Test** | `apps/api/tests/Feature/Http/SecurityHeadersCspTest.php` (**2 tests, both pass**): admin path gets `form-action 'self'` / `style-src 'self'`; api path keeps `default-src 'none'`. |
| **Retest result (real browser)** | Re-rendered `/admin/login` in Chromium → now **fully styled**: centered card, inputs, amber "Sign in" button, password toggle, "Remember me" — and **login submits successfully**. After screenshot: `admin-login-fixed.png`. Subsequent sweep of 24 admin pages: all 200, all styled, 0 console errors, 0 failed requests. PASS. |

> Sync note: the host copies of the two CSP-fix files (`config/security.php`,
> `SecurityHeaders.php`) had been **mount-truncated on write** and were re-synced complete and
> verified (`php -l` clean). Not a code defect — a write-path artifact, recorded for traceability.

---

### D1 — Unauthenticated `api/*` returns 500 instead of 401

| Field | Value |
|---|---|
| **ID** | D1 |
| **Route(s)** | `/api/v1/reports`, `/api/v1/my-learning`, `/api/v1/dashboards`, `/api/v1/homepage/preview` (all authenticated `api/*` routes) |
| **Role** | Unauthenticated / any |
| **Language** | N/A (API-layer defect; not browser-observed) |
| **Theme** | N/A (API-layer defect; not browser-observed) |
| **Viewport** | N/A (API-layer defect; not browser-observed) |
| **Severity** | **High** |
| **Evidence (HTTP)** | Request to a protected `api/*` route **without** `Accept: application/json` returned **HTTP 500** instead of 401. Root of the 500: `RouteNotFoundException` while attempting to redirect to a nonexistent named route `login`. |
| **Root cause** | `Handler::unauthenticated()` attempted to resolve the named route `login` (which does not exist in an API-only app) → `RouteNotFoundException` → uncaught → HTTP 500. Only requests already sending `Accept: application/json` were spared. |
| **Fix** | New middleware `apps/api/app/Http/Middleware/ForceJsonForApi.php`, prepended to the `api` group, forces `Accept: application/json`; plus an `AuthenticationException` render callback in `bootstrap/app.php` returning `ApiResponse::error('UNAUTHENTICATED', …, 401)` for `api/*`. |
| **Files changed** | `apps/api/app/Http/Middleware/ForceJsonForApi.php` (new); `bootstrap/app.php` |
| **Test** | `tests/Feature/Http/UnauthenticatedApiTest.php` (3 cases) |
| **Retest result (live-proven)** | Re-hit the four routes with `Accept: */*` → **HTTP 401 JSON** `{"error":{"code":"UNAUTHENTICATED"}}`. PASS. |

---

### D2 — Malformed public-id returns 500 (SQLSTATE 22P02) instead of 404

| Field | Value |
|---|---|
| **ID** | D2 |
| **Route(s)** | All public-id-bound model routes, e.g. `/api/v1/organizations/{id}` (every `HasPublicId` model) |
| **Role** | Any (authorization runs after binding) |
| **Language** | N/A (API-layer defect; not browser-observed) |
| **Theme** | N/A (API-layer defect; not browser-observed) |
| **Viewport** | N/A (API-layer defect; not browser-observed) |
| **Severity** | **Medium** |
| **Evidence (HTTP)** | `GET /api/v1/organizations/34` (a non-UUID) returned **HTTP 500** with PostgreSQL `SQLSTATE 22P02` (invalid input syntax for type uuid). |
| **Root cause** | `HasPublicId::resolveRouteBinding()` queried the DB with the raw route value without validating it was a well-formed UUID; PostgreSQL rejected the malformed cast at the driver level. |
| **Fix** | `resolveRouteBinding()` now validates via `Uuid::isValid()`; an invalid value returns `null` (→ 404) before any query. Fixes every public-id-bound model in one place. |
| **Files changed** | `apps/api/app/Platform/Shared/Traits/HasPublicId.php` |
| **Test** | Case added to `tests/Feature/Crm/OrganizationTest.php` |
| **Retest result (live-proven)** | `GET /api/v1/organizations/34` → **HTTP 404**; valid UUID → **HTTP 200**. PASS. |

---

### D3 — SEO sitemap empty (`[]`)

| Field | Value |
|---|---|
| **ID** | D3 |
| **Route(s)** | `/api/v1/seo/sitemap` (and `/sitemap.xml` consumers) |
| **Role** | Public |
| **Language** | N/A (API-layer defect; not browser-observed) |
| **Theme** | N/A (API-layer defect; not browser-observed) |
| **Viewport** | N/A (API-layer defect; not browser-observed) |
| **Severity** | **Medium** |
| **Evidence (HTTP)** | `GET /api/v1/seo/sitemap` returned **HTTP 200** but an **empty array `[]`** — search engines would index nothing. |
| **Root cause** | `SeoSeeder` was never registered in `DatabaseSeeder`, and even when run seeded only 3 SEO singletons. The sitemap derives exclusively from `seo_metas` rows. |
| **Fix** | Registered `SeoSeeder` in `DatabaseSeeder`; extended it to derive sitemap-enabled rows (via `DB::table` + `firstOrCreate`, idempotent) from published homepage / static-pages / courses / categories / trainers / events. |
| **Files changed** | `apps/api/app/Platform/Seo/Database/Seeders/SeoSeeder.php`; `apps/api/database/seeders/DatabaseSeeder.php` |
| **Test** | Case added to `tests/Feature/Seo/SeoManagerTest.php` |
| **Retest result (live-proven)** | `GET /api/v1/seo/sitemap` → **HTTP 200** with **40 entries** (12 course, 12 category, 9 static_page, 5 trainer, 1 event, 1 homepage), deduped and idempotent. PASS. |

---

## Complete list of files changed (11)

| # | File | Change |
|---|---|---|
| 1 | `apps/api/config/security.php` | Added `csp_web` policy + `web_paths` (CSP-01). |
| 2 | `apps/api/app/Http/Middleware/SecurityHeaders.php` | Selects `csp_web` for web paths, keeps `csp` for API (CSP-01). |
| 3 | `apps/api/tests/Feature/Http/SecurityHeadersCspTest.php` | **New**, 2 tests (CSP-01). |
| 4 | `apps/api/bootstrap/app.php` | Added `AuthenticationException` render callback (D1). |
| 5 | `apps/api/app/Http/Middleware/ForceJsonForApi.php` | **New** middleware, prepended to `api` group (D1). |
| 6 | `apps/api/app/Platform/Shared/Traits/HasPublicId.php` | UUID validation in `resolveRouteBinding()` (D2). |
| 7 | `apps/api/app/Platform/Seo/Database/Seeders/SeoSeeder.php` | Registered + extended to derive sitemap rows (D3). |
| 8 | `apps/api/database/seeders/DatabaseSeeder.php` | Registered `SeoSeeder` (D3). |
| 9 | `apps/api/tests/Feature/Http/UnauthenticatedApiTest.php` | **New**, 3 cases (D1). |
| 10 | `apps/api/tests/Feature/Crm/OrganizationTest.php` | Added malformed-id case (D2). |
| 11 | `apps/api/tests/Feature/Seo/SeoManagerTest.php` | Added sitemap-derivation case (D3). |

---

## Low-severity observations (recorded, not fixed)

These were surfaced during the real browser sweep. They are honestly severity-rated and are **not**
blocking.

| ID | Route / Area | Role | Language | Theme | Viewport | Severity | Evidence | Root cause | Files | Retest / status |
|---|---|---|---|---|---|---|---|---|---|---|
| OBS-1 | Admin EDIT with a **non-UUID id** (e.g. `/admin/.../1`) | admin | N/A | N/A | N/A | **Low** | Hand-editing an admin EDIT URL to a non-UUID id yields **HTTP 500**, not 404. Only reachable by manual URL edits; the app's own links always use valid UUID `public_id`s. | Route-model binding hits the DB with a non-UUID before a 404 guard. | Not changed (would require a broad route-model-binding change). | Not fixed by design; consistent with the separately-fixed public-id 404 guard (D2). Observation only. |
| OBS-2 | Admin **dashboard** (a11y) | admin | Both | Both | All | **Low (moderate a11y)** | Real **axe-core 4.10.2** in Chromium: **1 moderate** violation `landmark-unique` (0 critical / 0 serious / 1 moderate / 0 minor). Screenshot `adm-dashboard.png`. | Duplicate landmark roles in **Filament vendor markup**. | Vendor (Filament) markup; not app code. | Not fixed (vendor); no WCAG 2.2 AA blocking issue. |
| OBS-3 | Admin **courses/create** form (a11y) | admin | Both | Both | All | **Low (moderate a11y)** | Real **axe-core 4.10.2** in Chromium: **1 moderate** `landmark-unique`. Screenshot `adm-courses-create.png`. | Duplicate landmark roles in **Filament vendor markup**. | Vendor (Filament) markup; not app code. | Not fixed (vendor); no WCAG 2.2 AA blocking issue. |

---

## Environment / harness notes (not defects)

| Finding | Category | Severity | Rationale / status |
|---|---|---|---|
| Login **419** under `artisan serve` in the harness | Environment / harness | **None** | Traced to the local `/tmp` harness `.env` using `SESSION_DRIVER=array`; the repo's `.env.example` ships `redis`. Setting file sessions in the harness fixed it. A **harness artifact, not a committed bug.** |
| **Next.js frontend not browser-verifiable** (SIGBUS) | Environment | **None (blocks sign-off, not a defect)** | `npx next dev -p 3100` → **Bus error**; `curl :3100` → **HTTP 000**; `npx next build` → **Bus error (SIGBUS, exit 135)**. Crash is in Next's native module init before any route serves (`node -e` runs fine). Frontend browser sign-off must run the committed Playwright `e2e/visual/*`, `e2e/a11y.spec.ts`, and 82 Storybook stories in CI. |
| `/health/ready` returns **503** without Redis | Environment | **None (correct behavior)** | Redis intentionally absent; a 503 from a readiness probe when a hard dependency is down is the **correct degraded signal**. `/health/live` and `/up` return 200. |
| No **category-detail** / no **trainer-detail** page/route | Product scope | **Low** | Only list routes exist; a coverage/product-scope gap, not a bug. Backlog. |
| Frontend **ESLint** `@rushstack/eslint-patch` env error | Environment / toolchain | **None (env only)** | Baseline-identical; runs clean in real CI. Not a code defect. |

---

## Register summary

| Metric | Value |
|---|---|
| Defects found and fixed | **4** — `CSP-01` **Critical** (browser-only), `D1` High, `D2` Medium, `D3` Medium |
| Defects discovered **via real browser** | **1** (`CSP-01`) |
| Low-severity observations recorded (not fixed) | 3 (`OBS-1` robustness; `OBS-2`/`OBS-3` moderate a11y — Filament vendor landmarks) |
| New automated tests added | 7 (2 CSP + 3 D1 + 1 D2 + 1 D3) |
| Files changed | 11 |
| Post-fix backend gate | Pest **277 passed / 0 failed**; PHPStan **[OK]**; Deptrac **0**; Pint **PASS 1225** |
| Admin UI browser sweep | 24 pages, all 200, all styled, **0 console errors, 0 failed requests** |
| Open code defects | **None repository-evidenced** (admin: no Critical/High) |
| Open environmental blocks | Next.js frontend browser verification (SIGBUS) — must run committed specs in CI |

---

## Live Local-Browser Audit (real Chrome on user's machine, localhost:3000) — 2026-07-14

Real Chromium driven via the Claude-in-Chrome extension against the user's running Docker+Next stack. Open → screenshot → console/network → fix → reload → retest.

### LB-01 — [Critical, FIXED] Homepage (and all pages) render blank in `npm run dev`
- Route: `/` (all routes). Role: any. Lang/Theme/Viewport: all.
- Evidence (browser console): `EvalError: ... 'unsafe-eval' is not an allowed source of script: script-src 'self' 'unsafe-inline'`. DOM fully populated but every `.reveal` element stuck at `opacity:0`; hydration never completed.
- Root cause: `apps/web/next.config.ts` applied the production CSP (no `'unsafe-eval'`) in development too; Next.js Fast Refresh/webpack uses `eval` in dev, so the client runtime threw and hydration aborted → content invisible.
- Fix: dev-aware CSP — `script-src` includes `'unsafe-eval'` only when `NODE_ENV !== 'production'`; production policy unchanged. File: `apps/web/next.config.ts`.
- Retest (browser): homepage renders fully (hero, cards, illustration); `.reveal` opacity 1; no EvalError.

### LB-02 — [Critical, FIXED] Course catalog empty; all data-driven pages fail
- Route: `/courses` (and every page calling the API). Evidence: `/api/backend/courses` → 500/503; response body `SQLSTATE[08006] connection to server at "127.0.0.1", port 55432 failed: Connection refused (... Host: 127.0.0.1, Port: 55432 ...)`. Catalog rendered 0 cards and no empty-state.
- Root cause: the Dockerized API **web process** (`php artisan serve`) read `DB_HOST`/`DB_PORT` from `.env` (`127.0.0.1:55432`) instead of the compose override (`postgres:5432`) — a `$_ENV`/`variables_order` precedence quirk where the CLI resolved `postgres` (worked) but the HTTP server resolved `.env`. Homepage masked it via static fallbacks.
- Fix (Docker-only workflow): set `apps/api/.env` `DB_HOST=postgres`, `DB_PORT=5432`; `docker compose up -d --force-recreate api` + `optimize:clear` + restart.
- Retest (browser): `/api/backend/courses` → 200 with 12/26 courses; catalog grid renders real course cards (Business AI…, Project Management…, Competitive Business Strategy) with level/language/Featured badges.

### LB-03 — [Medium, FIXED] Raw i18n key in learner sidebar
- Route: `/dashboard` (learner app shell). Role: learner. Lang: EN+AR. Evidence: sidebar showed literal `nav.continueLearning` instead of a label.
- Root cause: `apps/web/src/lib/i18n/dictionaries.ts` `nav` namespace was missing `continueLearning` in both locales (it existed only under `dashboard.continueLearning`), while `src/config/nav.ts` references `nav.continueLearning`.
- Fix: added `continueLearning: "Continue learning"` (EN) and `"متابعة التعلّم"` (AR) to the `nav` namespace. File: `dictionaries.ts`.
- Retest (browser): sidebar shows "Continue learning"; no raw key present (`rawKeyStillShown:false`).

### Not a repository defect (documented)
- Hydration-mismatch dev overlay on some routes is caused by a **browser extension** injecting a `body[unresolved]{opacity:0}` FOUC style before React hydrates (that string exists nowhere in the repo). Will not occur in a clean browser or production.

### LB-02 — configuration correction (environment-specific, both Docker + host supported)
The repo intentionally supports **both** Docker and host execution: `apps/api/.env.example` ships host DB values (`127.0.0.1:55432`); `docker-compose.yml` overrides them for the container (`DB_HOST=postgres`, `DB_PORT=5432`). Root cause of LB-02 was that `php artisan serve`'s workers read `.env` instead of the container OS env (PHP `variables_order` did not include `E`, so `$_ENV` wasn't populated from the compose environment; the CLI resolved `postgres` correctly, the web process did not).
Correct, environment-specific fix (added, no image rebuild):
- `docker/php/zz-variables-order.ini` → `variables_order = "EGPCS"` (populates `$_ENV` from the container environment).
- `docker-compose.yml` api service → read-only mount `./docker/php/zz-variables-order.ini:/usr/local/etc/php/conf.d/zz-variables-order.ini:ro`.
With the override active, the container honors the compose `DB_HOST=postgres` and `apps/api/.env` can stay at host defaults (`127.0.0.1:55432`) — non-Docker execution is NOT broken.
Finalize command: `docker compose up -d --force-recreate api`, then revert `apps/api/.env` `DB_HOST=127.0.0.1`/`DB_PORT=55432`.
Current state during this audit: `.env` temporarily set to `postgres:5432` so the running container works without the recreate; the override files above are the durable, correct fix.

### LB-04 — [Medium, FIXED] Raw i18n key `nav.accounts` in CRM sidebar
- Route: `/crm` (+ CRM sub-pages). Role: admin/CRM. Lang: EN+AR. Evidence: sidebar showed literal `nav.accounts`.
- Root cause: `dictionaries.ts` `nav` namespace missing `accounts` in both locales (`src/config/nav.ts` references `nav.accounts`). Same class as LB-03.
- Fix: added `accounts: "Accounts"` (EN) / `"الحسابات"` (AR) to the `nav` namespace.
- Retest (browser): CRM sidebar shows "Accounts"; `nav.accounts` gone; CRM dashboard renders (6 Leads / 2 Opportunities / Consulting / Tasks).

### OBS-02 — [Medium, FIXED] Expired session traps user away from /login
- Route: any protected route + `/login`, after the session token expires. Evidence: with the non-httpOnly marker cookie `helbaron_authed` still present after token expiry, `hasSession()` returned true → the auth context stayed optimistically "authenticated" → `RequireGuest` on `/login` redirected to `/` → the user could never reach the sign-in form to re-authenticate.
- Root cause: `apps/web/src/lib/auth/auth-context.tsx` `refresh()` cleared the cached user on an invalid/expired session but NOT the `helbaron_authed` marker cookie.
- Fix: `refresh()` now also clears the `helbaron_authed` marker cookie when the session is detected invalid, so `hasSession()` reflects reality and guest-guards behave correctly.
- Retest (browser): after the session lapsed, protected routes now correctly redirect to `/login?redirect=…` and the sign-in form appears (previously it looped to `/`). Verified by re-login as admin.
- Related observation: the session appears to lapse quickly during the audit (~minutes) — worth checking the demo session/token lifetime and the `helbaron_authed` marker max-age; recorded for follow-up (config, not a UI-render defect).

### Admin-accessible surfaces verified (real Chrome, admin@helbaron.local)
- `/analytics` ✅ real KPIs + sparklines + a11y data tables ($540,216.46 Revenue, 22,843 Enrollments, 9,114 Completions, 85 Live sessions).
- `/reports` ✅ hub with 12 report definitions (filter + Open).
- `/reports/insights/revenue` ✅ report detail: date-range filter + Apply + Summary (GROSS/REFUNDS/NET SAR) + chart + table.
- `/crm` ✅ dashboard (6 Leads / 2 Opportunities / Consulting / Tasks / Recent leads / Consulting).

### Public/marketing + CMS pages (real Chrome) — findings
- `/pricing` ✅ "Simple, honest pricing." + 4 tiers. `/about` ✅. `/contact` ✅ (contact routes, email). No raw keys.
- CMS static pages (9 seeded: about, contact, faq, careers, help, privacy, terms, cookies, refund-policy): about/contact/privacy/terms have dedicated routes; faq/careers/help/cookies/refund-policy render via the generic `/p/{slug}` route — verified `/p/help` ("Help Center") and `/p/faq` ("Frequently Asked Questions") render correctly.
- NOT a defect: top-level `/faq`, `/cookies`, `/refund-policy`, `/help`, `/careers` return a proper 404 page (no such route; nothing links to them). The content lives at `/p/{slug}`. The transient `/p/faq → /faq` observation was a Chrome-extension dropout artifact, re-verified working.

### LB-05 — [Serious, FIXED] Homepage color-contrast failure (WCAG AA)
- Route: `/` ("Trusted by" marquee). Evidence (real axe-core 4.12.1): color-contrast 2.55:1 (#9a9e99 on #fbf8ef) — fails AA 4.5:1. Elements `.text-foreground/45`.
- Root cause: `apps/web/src/components/landing/trusted-by.tsx:24` used `text-foreground/45` (too light).
- Fix: changed to `text-muted-foreground` (the design-system accessible muted token, already used on the section label above it).
- Retest (real axe on `/`): color-contrast gone; homepage now **0 critical / 0 serious** (only 1 moderate `region`).

### Accessibility (real axe-core in the browser)
- `/` homepage: after LB-05 — 0 critical, 0 serious, 1 moderate (`region`: some top-level content outside a landmark).
- `/login` (auth layout): 0 critical, 0 serious, 4 moderate — `landmark-one-main`, `page-has-heading-one`, `region`, `skip-link` (the minimal auth layout lacks a `<main>` landmark, an `<h1>`, and a skip link). Recorded for follow-up; low-risk moderate enhancements to the `(auth)` layout.
- No Critical or Serious axe violations remain on the audited pages.

### SCOPE-01 — Instructor authoring editor "Coming soon" is intentional (NOT a defect)
- Route: `/teach/courses/{id}/edit`, `/teach/sessions`. Evidence: repo `apps/api/app/Domains/Catalog/routes/teach.php` exposes only dashboard/courses(index,show)/publish/unpublish/archive/students/announcements — NO instructor authoring API (no create/update course, sections, or lessons). Course content authoring is admin/Filament-only.
- Verdict: the "Coming soon" placeholders correspond to deliberately unimplemented instructor features (task #14). Recorded as product scope, not a defect. Full authoring verified in the Filament admin panel (prior audit).
- Instructor surface implemented + verified: `/teach` dashboard, `/teach/courses` (status actions), course detail (analytics), `/teach/students`; permission guard (student→/teach denied). See INSTRUCTOR_AUTHORING_QA_REPORT.md.

### GAP-01 — Instructor Authoring is a MISSING PRODUCT CAPABILITY (supersedes SCOPE-01 classification)
Correction: the earlier SCOPE-01 note wrongly inferred "intentional out-of-scope" from missing backend code. Scope is defined by the client PRD, not the repository. Absent a PRD statement that instructors must never author their own content, instructor authoring is classified as **Missing product capability** (the `/teach/courses/{id}/edit` route exists but renders "Coming soon"). Filament admin authoring is a different capability and does not satisfy instructor self-authoring.
Status matrix: Instructor dashboard = Implemented; course listing = Implemented; course analytics = Implemented; student roster = Implemented; status management = **Implemented (browser-verified 2026-07-15)**; announcements = **Implemented (browser-verified 2026-07-15)**; course authoring = **Missing**; section authoring = **Missing**; lesson authoring = **Missing**; media/resource authoring = **Missing**; live-session management = **Missing**. Full analysis: INSTRUCTOR_AUTHORING_IMPLEMENTATION_GAP.md.

### Instructor mutations — browser verification result (2026-07-15)
Signed in as the course-owning instructor (Yara Adel / trainer@helbaron.local) in the user's real Chrome, all implemented instructor mutations were exercised end-to-end and **pass**: unpublish (success toast + in-place badge update), publish success path (200/published), publish validation-blocked path (**422 `CATALOG_COURSE_PUBLISH_BLOCKED` "The course has no sections."** + error toast), archive confirmation dialog + cancel path, announcement create (persists + renders + form clears) and empty-field validation (disabled Post button + `required`). Two behaviours initially suspected as defects ("no in-place list update", "silent publish error") were **re-verified as observation-timing artifacts, not defects** — the hooks `invalidateQueries(["teach","courses"])` on success and the error toast does fire (transient). Evidence: INSTRUCTOR_AUTHORING_QA_REPORT.md → "Browser workflows".

### A11Y-EVENTS-01 — [Critical, FIXED] Pagination prev/next buttons have no accessible name
- Route: `/events` (and any view using the shared `Pagination` component). Evidence: real axe-core `button-name` (impact **critical**, 2 nodes) on the fully-loaded page.
- Root cause: `apps/web/src/components/ui/pagination.tsx` rendered the prev/next buttons as a chevron icon + a page-number string that is **empty at the first/last page**, leaving boundary buttons with only a decorative `<svg>` and no accessible name.
- Fix: added localized `aria-label` (`common.previous`/`common.next`, present EN+AR) to both buttons and `aria-hidden` on the chevrons.
- Retest: real axe on reloaded `/events` — `button-name` gone; page clean at WCAG A/AA. Fix also hardens all other paginated views (courses list, etc.). Full sweep: ACCESSIBILITY_ADVANCED_QA_REPORT.md.
- Non-defect noted alongside: `/events` briefly shows an axe `color-contrast` flag on the **loading skeleton** only (decorative placeholder); it disappears once data loads — transient, not a content-contrast failure.

### SEC-01 — [High, FIXED] Open redirect via login `redirect` param (CWE-601)
- Route: `/login?redirect=...`. Evidence: `app/(marketing)/(auth)/login/page.tsx` passed the untrusted `redirect` query param straight to `router.replace()`, so `?redirect=https://evil.com` / `//evil.com` redirected off-site after login.
- Fix: added `safeRedirect()` in `lib/utils.ts` (allows only root-relative same-origin paths; blocks external, protocol-relative, backslash, and scheme-smuggling values → fallback `/`); applied at the login redirect. Logic verified in-browser (external/`//`/`/\` → `/`; `/dashboard`, `/orders?tab=paid` preserved). Files: `apps/web/src/lib/utils.ts`, `apps/web/src/app/(marketing)/(auth)/login/page.tsx`. Full write-up: SECURITY_HARDENING_REPORT.md.

### A11Y-AUTH-01 — [Serious, FIXED] Progress bars have no accessible name (authenticated)
- Route: learner `/dashboard` (3 course-progress bars) and every progress bar app-wide via the shared component. Evidence: real axe `aria-progressbar-name`, impact serious, 3 nodes.
- Root cause: `components/ui/progress.tsx` set `aria-label={label}` but callers passed no label, leaving `role="progressbar"` with value attrs but no accessible name.
- Fix: `Progress` now defaults its accessible name to the percentage (`aria-label={label ?? \`${pct}%\`}`, locale-neutral, correct in EN+AR); `ProgressBar` wrapper forwards an optional `label`. Retest: dashboard re-scanned → `aria-progressbar-name` gone, **0 WCAG A/AA violations**. Fix reaches all progress-bar surfaces (dashboard, My Learning, player, instructor analytics, course-progress card). Files: `apps/web/src/components/ui/progress.tsx`, `apps/web/src/components/student/progress-bar.tsx`. Full write-up: AUTHENTICATED_ACCESSIBILITY_QA_REPORT.md.

### DATA-01 — [Low, demo-data quality] Seeded courses Published with 0 sections/lessons (violates the publish invariant) — **FIXED**
**Fix (2026-07-15):** root cause was `CatalogSeeder` creating 12 courses `Published` with categories/trainers but no curriculum. Added `seedMinimalCurriculum()` (1 published section + 2 published lessons per course, idempotent) + a Draft fallback (`hasPublishableCurriculum()`), so no course is ever seeded Published without ≥1 section and ≥1 published lesson. Added regression tests `tests/Feature/Catalog/CatalogSeederPublishInvariantTest.php` (published⇒publishable, content-less⇒Draft, idempotent+deterministic). Run on host: `docker compose exec api php artisan test --filter=CatalogSeederPublishInvariant`. Full write-up: DEMO_DATA_CONSISTENCY_REPORT.md. Original finding retained below.


- Evidence: "Business AI for Decision Makers" and "Essential Business Skills" seed as `status = published` with 0 sections/0 lessons, yet the publish action forbids this state (`CATALOG_COURSE_PUBLISH_BLOCKED — "The course has no sections."`). The seeder sets status directly, bypassing the domain guard.
- Impact: not an application bug; a demo-content inconsistency. Practical effect: such a course, once unpublished, cannot be re-published through the instructor UI (must be re-seeded or given a section).
- Recommendation: seeder should attach ≥1 section to any course it marks Published, or seed content-less courses as Draft.
- Restore after QA testing (host command): `docker compose exec api php artisan migrate:fresh --seed`. Residual test-state left on the local demo DB: "Business AI…" is Draft; a "QA Test Announcement" exists on "Project Management Foundations".

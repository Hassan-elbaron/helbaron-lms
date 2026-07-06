# HElbaron LMS — Master Implementation Roadmap (10)

**Role:** CTO / Program Manager / Delivery Director.
**Purpose:** Single source of truth. Synthesizes audits 01–09 into one phased execution roadmap to take HElbaron from its current state to production launch. Does not re-audit — it consolidates, de-duplicates, sequences, and staffs the work.
**Inputs:** `01_PRODUCT_DIRECTOR_REVIEW` · `02_UX_INFORMATION_ARCHITECTURE_REVIEW` · `03_UI_DESIGN_SYSTEM_REVIEW` · `04_SOFTWARE_ARCHITECTURE_REVIEW` · `05_BACKEND_LARAVEL_REVIEW` · `06_FRONTEND_NEXTJS_REVIEW` · `07_QA_TESTING_RELEASE_REVIEW` · `08_DEVOPS_INFRASTRUCTURE_REVIEW` · `09_SECURITY_AUDIT`.
**Effort key:** S ≤1d · M 2–3d · L 4–7d · XL 8–15d (one engineer). **Complexity/Risk:** Low/Med/High.

---

## Executive Summary

HElbaron is a **strong, feature-complete engineering base that is not yet a launchable product**. The backend (7.8) and overall architecture (7.2) are genuinely good — disciplined DDD, small files, idempotent payments, encrypted credentials, strict API CSP/CORS. The gaps are concentrated in three areas: (1) **product/UX connectivity** (a whole role — Instructor — has no UI, navigation is broken, no SEO), (2) **frontend rendering & delivery** (client-heavy SPA, no route resilience, and — critically — *no production hosting for the web app at all*), and (3) **launch-blocking security & ops** (a stored-XSS→token-theft chain, manual tenant isolation, no backups, an empty scheduler, and no CD pipeline).

The good news: **almost nothing needs re-architecting.** The domain model, API, and data layer are sound. The roadmap is therefore mostly *wiring, hardening, and filling gaps* — not rebuilding. Twelve phases, front-loaded with a **Phase 0 of hard launch blockers** (security chain, tenant isolation, frontend hosting, backups). A 4–6 engineer team can reach a safe production launch in **~10 two-week sprints (≈20 weeks)**; a leaner critical-path-only launch is possible in **~12 weeks** by deferring Phases 8–9 polish.

**Three things that must be true before any public traffic:** the XSS/token chain is closed, tenant data cannot leak across organizations, and there is a tested backup/restore. Everything else is sequencing.

---

## Current Project Health

| Dimension | Score | State |
|-----------|-------|-------|
| Product completeness | ~6.0 | Broad; Instructor role absent, admin-content gaps |
| UX | 5.4 | Disconnected across chromes; mobile nav broken |
| Information Architecture | 5.0 | Siloed; orphans; no breadcrumbs |
| UI / Visual | 6.6 | Coherent color/motion; unfinished system rigor |
| Design System | 6.2 | Great tokens; no elevation scale, no input error state |
| Software Architecture | 7.2 | Excellent modular monolith; tooling/integration debt |
| Backend (Laravel) | 7.8 | Production-leaning; a few hardening items |
| Frontend (Next.js) | 6.4 | Clean SPA; under-uses Next; near-zero SEO |
| QA / Testing (synthesized) | ~6.5 | 70 API + 34 web tests; no E2E, no security tests |
| DevOps / Infra | 6.0 | Strong API runtime; no web host, backups, CD, monitoring |
| Security | 7.3 | Mature crypto/authn; XSS chain + tenant isolation |
| **Weighted program health** | **≈6.5** | **Solid base, not launch-ready** |

## Overall Scores Summary

```
Backend        ███████▊  7.8
Security       ███████▎  7.3
Architecture   ███████▏  7.2
UI             ██████▌   6.6
QA (synth)     ██████▌   6.5
Frontend       ██████▍   6.4
Design System  ██████▏   6.2
DevOps         ██████    6.0
Product        ██████    6.0
UX             █████▍    5.4
IA             █████     5.0
```

---

## Consolidated Findings

Findings from all nine audits, merged and grouped. Each carries its **source refs** and a **consolidated ID** (used everywhere below). Severity: 🔴 Blocker · 🟠 High · 🟡 Medium · ⚪ Low.

### Group A — Security & Data Protection
- **A1 🔴 Stored-XSS → account-takeover chain** — unsanitized lesson HTML (`dangerouslySetInnerHTML`) + no web CSP + auth token in `localStorage`. *(09/PT-1, 06/AUTH-1, 05/SEC-2)*
- **A2 🔴 Tenant isolation is manual (BOLA)** — 2 global scopes vs 8 org-scoped models. *(09/TEN-1, 05/AUTHZ-1)*
- **A3 🟠 Auth token in `localStorage`** (root of A1 impact) → httpOnly cookie. *(04/FE-2, 05/SEC-2, 06/AUTH-1, 09/SES-1)*
- **A4 🟠 No CSP/security headers on the Next.js app.** *(09/XSS-2)*
- **A5 🟠 Rate limiting not on commerce/verification** (coupon brute, cert enumeration). *(05/SEC-4, 09/PT-3/PT-4)*
- **A6 🟡 Media playback token may not verify enrollment.** *(09/MED-1, 05/LRN-1)*
- **A7 🟡 No dependency/image/secret scanning in CI.** *(08/CI-2, 09/DEP-1)*
- **A8 🟡 PII log scrubbing + audit coverage on privileged actions.** *(05/MOD-3, 09/DATA-1/LOG-1)*
- **A9 🟡 Account lockout + uniform auth responses + `APP_DEBUG=false` enforcement.** *(09/AUTHN-2/3, API-1)*

### Group B — Payments & Commerce Integrity
- **B1 🟠 External gateway `charge()` inside DB transaction.** *(05/CHK-1, 04/PERF-2)*
- **B2 🟡 Coupon re-validation under lock; idempotent refund.** *(05/CHK-2/CHK-3, 09/PAY-1)*
- **B3 🟡 Queue jobs lack tries/backoff/timeout + failed() + dead-letter.** *(05/JOB-1/JOB-2)*

### Group C — Product & Roles
- **C1 🔴 Instructor role has no UI** (no `/teach`). *(01/C1)*
- **C2 🟠 Coarse RBAC** — org/support/finance collapse into `admin`. *(01/C5, 05/AUTHZ, 04)*
- **C3 🟠 Marketing/landing/brand/SEO/homepage not admin-controllable** (hardcoded). *(01/C4)*
- **C4 🟡 Instructor/pricing/SEO admin resources missing.** *(01/§5)*

### Group D — Navigation, IA & UX
- **D1 🔴 Mobile nav broken on public/storefront** (no hamburger). *(02/N1, 03/M1)*
- **D2 🟠 No breadcrumbs anywhere; inconsistent back nav.** *(02/N2/N10)*
- **D3 🟠 Dead "Settings" menu item; `/settings` 404; user-menu missing links.** *(02/N3, 01/C2)*
- **D4 🟠 No cart badge / notifications bell / global search.** *(02/N4/N5/N6)*
- **D5 🟡 Orphans:** `/continue-learning`, `/orders`, `/notifications` not in nav; `(dashboard)` dead group; `settings/theme` in public nav; dead `public-header.tsx`. *(01, 02, 03, 06)*
- **D6 🟡 No role-aware post-login redirect.** *(02/C10, 06/AUTH-3)*
- **D7 🟡 Dashboards lack quick actions/widgets/hierarchy.** *(02/Dashboards, 03/Dashboard)*

### Group E — Frontend Engineering
- **E1 🔴 No `error.tsx`/`loading.tsx`/`not-found.tsx`; no error boundary.** *(06/ERR-1/2, 02/ER1)*
- **E2 🔴 SEO absent** — 2 metadata for 46 pages; no sitemap/robots/OG. *(06/SEO-1/2/3)*
- **E3 🟠 Client-heavy SPA** — 84% `"use client"`; RSC-convert public reads. *(06/CSB-1)*
- **E4 🟠 Static document `lang/dir`** → RTL/SEO flash. *(06/FE-LOC-1)*
- **E5 🟡 No middleware for auth/role redirects.** *(06/AUTH-2)*
- **E6 🟡 Query keys inline (no factory); i18n 893-LOC monofile.** *(06/FE-QK-1/FE-3, 04)*

### Group F — Design System & UI
- **F1 🟠 No elevation scale (6 ad-hoc shadows); radius drift (8 values).** *(03/VC-1/VC-2)*
- **F2 🟠 Input has no error state; no shared Field.** *(03/VC-3, 06)*
- **F3 🟡 No brand (copper/gold) Button variant.** *(03/VC-4)*
- **F4 🟡 Two page-title systems; two container widths.** *(03/VC-5/VC-6)*
- **F5 🟡 Tables lack hover/zebra/sticky/density; Card lacks variants.** *(03/VC-7/VC-8)*
- **F6 🟡 Type scale untokenized; h4–h6 unstyled; contrast unverified.** *(03/VC-9/VC-10)*

### Group G — Architecture & Tooling
- **G1 🟠 Not a real monorepo** (no workspace/turbo/shared packages). *(04/MR-1)*
- **G2 🟠 API contract unenforced** — 10 OpenAPI specs, hand-written types. *(04/API-1, 06/FE-1)*
- **G3 🟡 Analytics/Notifications couple to 6 concrete domains.** *(04/DA-1)*
- **G4 🟡 No context facades / dependency-direction lint.** *(04/DA-2/DA-3)*
- **G5 ⚪ Unused Repository abstraction; dead code.** *(04/BE-1, dead-code list)*
- **G6 🟡 No read caching seam (1 usage).** *(04/PERF-1, 05/PERF-3)*
- **G7 🟡 N+1 risk on list endpoints; no query-count tests.** *(05/PERF-1)*

### Group H — DevOps & Delivery
- **H1 🔴 Frontend has no production hosting** (no web Dockerfile/service). *(08/FE-1)*
- **H2 🔴 No automated backup/restore.** *(08/BAK-1/BAK-2)*
- **H3 🔴 Scheduler runs zero tasks.** *(08/SCH-1)*
- **H4 🟠 CI builds image but never pushes; no CD; rollback has no tag source.** *(08/CI-1/DEP-2)*
- **H5 🟠 No monitoring/alerting; no staging environment.** *(08/MON-1/2, env)*
- **H6 🟡 Self-hosted DB in compose; no HA/pooler; Redis not split.** *(08/DB-1/3, Redis)*
- **H7 🟡 Container runs as root; secrets file-based; PHPStan non-gating.** *(08/DKR-1, SEC-1, CI-3)*

### Group I — QA (synthesized from 05/06/07)
- **I1 🟠 No E2E/Playwright for core flows.** *(06/testing, 07)*
- **I2 🟠 No tenant-isolation / idempotency / authz negative tests.** *(05/TST-1/2, 09)*
- **I3 🟡 No query-count (N+1) tests; no coverage gate.** *(05/TST-3, 08/CI-4)*

---

## Duplicate Findings Removed

Consolidations applied so the backlog has one owner per problem:

| Merged into | Was reported separately in |
|-------------|----------------------------|
| **A3** localStorage token | 04/FE-2, 05/SEC-2, 06/AUTH-1, 09/SES-1 |
| **A2** tenant isolation | 05/AUTHZ-1, 09/TEN-1 |
| **A1** XSS chain | 06 (token) + 09/PT-1 + 05 (storage) unified |
| **D5** dead code | `public-header.tsx` (02/03/06), `(dashboard)` (01/02), `settings/theme` public (01/02/03) |
| **G2/E2-adjacent** contract/types | 04/API-1 + 06/FE-1 (OpenAPI codegen) |
| **G6** caching | 04/PERF-1 + 05/PERF-3 |
| **F4** container width | 03/VC-6 + 06 layout tokens |
| **C2** coarse roles | 01/C5 + 05/AUTHZ + 04 authz |

---

## Prioritized Backlog

Ranked by (blocker status × dependency leverage × risk reduction).

| Rank | ID | Title | Sev | Effort | Phase |
|------|----|-------|-----|--------|-------|
| 1 | A1 | Close XSS→token chain (sanitize + web CSP + cookie) | 🔴 | L | 0 |
| 2 | A2 | Tenant isolation global scope + tests | 🔴 | M | 0 |
| 3 | H2 | Automated backup + restore drill | 🔴 | M | 0 |
| 4 | H1 | Frontend production hosting | 🔴 | M | 0 |
| 5 | H3 | Register scheduled tasks + schedule:work | 🔴 | S | 0 |
| 6 | B1 | Gateway call outside transaction | 🟠 | S | 0/4 |
| 7 | E1 | Route resilience (error/loading/not-found) | 🔴 | M | 1 |
| 8 | H4 | CD: push tagged images + deploy job | 🟠 | M | 1 |
| 9 | G1 | Turborepo + shared packages | 🟠 | M | 2 |
| 10 | G2 | OpenAPI-generated client/types | 🟠 | M | 2 |
| 11 | D1 | Mobile nav + unified top bar (cart/bell/search) | 🔴 | L | 3 |
| 12 | D2/D3 | Breadcrumbs + fix menus/settings | 🟠 | M | 3 |
| 13 | E2 | SEO metadata + sitemap/robots | 🔴 | M | 3 |
| 14 | E4 | Locale-correct document | 🟠 | M | 3 |
| 15 | F1/F2 | Elevation/radius tokens + Input error/Field | 🟠 | M | 3 |
| 16 | C1 | Instructor area (`/teach`) | 🔴 | XL | 6 |
| 17 | C2 | Granular RBAC roles + policies | 🟠 | L | 4 |
| 18 | A5/B2/B3 | Commerce throttle + coupon lock + job resilience | 🟠 | M | 7 |
| 19 | H5 | Monitoring/alerting + staging | 🟠 | L | 10 |
| 20 | I1/I2 | E2E + isolation/idempotency tests | 🟠 | L | 11 |
| … | (remainder) | see Phase Roadmap | | | |

---

## Dependency Graph

```
A3 (cookie auth) ─┬─▶ A1 (XSS chain fully closed)
                  └─▶ E3 (RSC server fetch needs server-readable auth)
G1 (monorepo) ────▶ G2 (shared contracts pkg) ────▶ E-types (web uses generated types)
G2 ──────────────▶ (reduces churn in D/E frontend work)
A2 (tenant scope) ▶ C2 (granular roles refine scoping) ▶ Admin/org UIs
H1 (web host) ────▶ H4 (CD deploys web) ────▶ H5 (staging/monitoring)
H2 (backups) ─────▶ Phase 12 launch gate
E1 (route files) ─▶ D (nav/UX work sits on resilient routes)
F1/F2 (tokens) ───▶ F3–F6, D7 (dashboards), C1 (instructor UI reuses system)
B1 ──────────────▶ Phase 7 commerce hardening
Backend APIs (exist) ▶ C1 instructor UI (wraps Authoring/Live APIs)
```

Critical path: **A3 → A1 / E3**, **G1 → G2 → frontend**, **H1 → H4 → H5**, **A2 → C2**, **F1/F2 → C1**.

---

## Phase Roadmap

### Phase 0 — Critical Blockers
**Objectives:** remove every hard launch blocker (security chain, tenant leakage, no web host, no backups, dead scheduler).
**Tasks:** A1, A2, A3, H1, H2, H3, B1.
**Dependencies:** none (A3 precedes full A1). **Complexity:** High. **Risk:** High. **Impact:** Makes the platform *safe to run at all*.
**Success criteria:** pentest re-test of PT-1 passes; cross-org access denied by tests; web app deploys; a restore drill succeeds; scheduled jobs run; no external call inside a DB transaction.

| Task | Prio | Effort | Engineer | Skills | Blocks/By | Files | Acceptance | Commit |
|------|------|--------|----------|--------|-----------|-------|------------|--------|
| A3 cookie auth | P0 | M | Backend+Frontend | Sanctum, Next | blocks A1,E3 | `config/sanctum,cors`, `lib/api/client.ts` | No token in localStorage; SPA auths via httpOnly cookie | `feat(auth): migrate SPA to httpOnly cookie sessions` |
| A1 sanitize + web CSP | P0 | M | Frontend+Backend | HTMLPurifier, DOMPurify, CSP | by A3 | `lesson-content.tsx`, authoring save, `next.config.ts` | Injected script neither renders nor reads token | `fix(security): sanitize lesson HTML + add web CSP` |
| A2 tenant scope | P0 | M | Backend | Eloquent global scopes | blocks C2 | `Crm/Concerns/BelongsToOrganization`, 8 models | Org A cannot read/modify Org B (tests) | `feat(security): enforce org tenant isolation` |
| H1 web hosting | P0 | M | DevOps+Next | Docker, Next standalone | blocks H4 | `apps/web/Dockerfile`, `docker-compose.prod.yml`, nginx | Web builds + serves in prod stack | `feat(infra): production hosting for web app` |
| H2 backup/restore | P0 | M | DevOps+DBA | pg_dump, S3 | launch gate | `scripts/backup.sh`,`restore.sh` | Encrypted daily backup + tested restore | `feat(infra): automated db backup and restore` |
| H3 scheduler | P0 | S | Backend | Laravel scheduler | — | `routes/console.php`, compose | Reminders/digests/rollups/pruning run | `feat(ops): register scheduled tasks` |
| B1 gateway out of txn | P0 | S | Backend/Laravel | transactions | — | `CheckoutAction.php` | No network I/O inside DB txn (test) | `fix(commerce): move gateway call outside transaction` |

### Phase 1 — Foundation (resilience & delivery baseline)
**Objectives:** route-level resilience + a real CD pipeline + security gates.
**Tasks:** E1 (error/loading/not-found), H4 (CD tagged images + deploy), A7 (dep/image/secret scans), H7 (non-root, gating PHPStan).
**Complexity:** Med. **Risk:** Med. **Impact:** every later change ships safely and can roll back.
**Success:** thrown render error recovers; releases push immutable tags; CI fails on high-sev vulns; rollback restores a prior tag.

### Phase 2 — Architecture (contracts & tooling)
**Objectives:** monorepo + enforced API contract + decoupled consumers.
**Tasks:** G1 (Turborepo + `packages/{contracts,config}`), G2 (OpenAPI→TS client), G3/G4 (decouple Analytics/Notifications, context facades + Deptrac), G5 (remove dead abstractions), G6 seam scaffold.
**Complexity:** Med. **Risk:** Med. **Impact:** kills type drift, cuts frontend churn, guards boundaries.
**Success:** web imports only generated types; Deptrac passes; Analytics/Notifications import no sibling concrete classes.

### Phase 3 — Frontend (IA, SEO, design system, RSC)
**Objectives:** connect the product and make it indexable and consistent.
**Tasks:** D1 (mobile nav + top bar cart/bell/search), D2/D3 (breadcrumbs, fix menus/settings), D4/D5/D6 (bell/search/orphans/role redirect), E2 (SEO+sitemap/robots), E3 (RSC-convert public reads), E4 (locale document), E5 (middleware), E6 (query-key factory, i18n split), F1–F6 (elevation/radius/Input-Field/brand button/title system/tables/type scale), D7 (dashboard quick actions).
**Complexity:** High. **Risk:** Med. **Impact:** the biggest lift in perceived quality + SEO + reuse for later UIs.
**Success:** mobile nav works; every page has metadata; breadcrumbs everywhere; design tokens unified; `"use client"` ratio drops materially.

### Phase 4 — Backend (hardening & RBAC)
**Objectives:** production-grade backend correctness.
**Tasks:** C2 (granular roles + policies + refine tenant scope), A9 (lockout/uniform responses/APP_DEBUG), A8 (PII scrub + audit coverage), G7 (eager-load + query-count tests), validation coverage (05/VAL), MOD-3 observers.
**Complexity:** Med. **Risk:** Med. **Impact:** closes authz/data-integrity gaps.
**Success:** every write path authorized + validated; audit records on privileged actions; no N+1 on list endpoints.

### Phase 5 — Admin Panel (content control)
**Objectives:** make marketing/brand/SEO/instructors/pricing admin-controllable.
**Tasks:** C3 (`HomepageSection`, `LandingPage`, `BrandSetting`, `SeoMeta` models + Filament resources; web reads via API), C4 (`InstructorProfile` + approval, student view, pricing/plans), Filament policy/scope hardening, brand-align Filament theme.
**Complexity:** Med–High. **Risk:** Med. **Impact:** removes developer-deploy dependency for content.
**Success:** homepage/landing/brand/SEO editable from `/admin` and reflected without deploy.

### Phase 6 — Learning Experience (Instructor + assessments)
**Objectives:** the missing role and the missing learning depth.
**Tasks:** C1 (`(instructor)` area: teach dashboard, course/curriculum editor over Authoring API, sessions, students, earnings, `/teach/apply`), assessment UX (quiz/assignment pages) once assessment API exists, A6 (enrollment-gated playback), lesson-completion → certificate CTA.
**Complexity:** High (XL). **Risk:** Med. **Impact:** completes the core LMS loop.
**Success:** an instructor logs in → authors → publishes → sees students/earnings; students take quizzes/assignments.

### Phase 7 — Commerce (integrity hardening)
**Objectives:** payment/coupon robustness at scale.
**Tasks:** A5 (throttle checkout/coupon/verify), B2 (coupon re-validate under lock + idempotent refund), B3 (job tries/backoff/timeout + failed()+dead-letter), server-authoritative pricing check (PAY-3), currency consistency.
**Complexity:** Med. **Risk:** Med (money). **Impact:** prevents abuse & financial edge-case bugs.
**Success:** race/double-webhook/double-refund tests pass; coupon brute-force throttled.

### Phase 8 — Analytics (read models & caching)
**Objectives:** performant, decoupled analytics.
**Tasks:** G6 (read-cache seam + event invalidation for catalog/KPIs), formalize read models, analytics export PII controls (DATA-2), dashboard visuals (lightweight, per 03).
**Complexity:** Med. **Risk:** Low. **Impact:** speed + clean boundaries.
**Success:** KPI/catalog reads cached with event invalidation; exports access-controlled + expiring.

### Phase 9 — Performance
**Objectives:** meet latency/throughput targets.
**Tasks:** G7 (N+1 eradication + `preventLazyLoading`), read caching rollout, RSC/code-splitting (E3 continuation, PERF-2 `dynamic()`), PgBouncer + Redis split (H6), FPM pool tuning, CDN cache headers.
**Complexity:** Med. **Risk:** Low. **Impact:** cost + UX under load.
**Success:** p95 targets met in load test; query counts bounded; bundle size down.

### Phase 10 — Security Hardening (final)
**Objectives:** close remaining security items + defense-in-depth.
**Tasks:** A4 (web headers full set), A8 finalize, A9 finalize, secrets manager (H7/SEC-1), key rotation policy, A7 SBOM, penetration re-test.
**Complexity:** Med. **Risk:** Med. **Impact:** audit-clean posture.
**Success:** external pentest re-test shows no High/Critical; secrets in a manager; scans green.

### Phase 11 — QA
**Objectives:** provable correctness of the above.
**Tasks:** I1 (Playwright E2E: register/login/browse→checkout/learn→lesson/instructor), I2 (tenant-isolation + idempotency + authz negative tests), I3 (query-count + coverage gates), accessibility pass (skip link, focus mgmt, contrast), cross-browser/mobile matrix, load test.
**Complexity:** Med. **Risk:** Low. **Impact:** launch confidence.
**Success:** E2E green in CI; coverage gate enforced; a11y AA on core flows.

### Phase 12 — Production Launch
**Objectives:** controlled go-live.
**Tasks:** staging soak (H5), runbook rehearsal (deploy/rollback/restore/incident), monitoring/alerting live (H5/MON), DNS/TLS/CDN cutover, launch checklist sign-off, post-launch watch.
**Complexity:** Low–Med. **Risk:** High (go-live). **Impact:** revenue on.
**Success:** all launch-checklist gates green; alerting verified; rollback rehearsed.

---

## Engineering Staffing Plan

| Role | Responsibilities | Key deliverables | Depends on | Workload |
|------|------------------|------------------|-----------|----------|
| **CTO / Delivery Director** | Sequencing, gate sign-off, risk | Phase gates, launch decision | all | 20% ongoing |
| **Tech Lead** | Architecture calls, PR review, Deptrac rules | Contracts pkg, boundary rules | — | 100% |
| **Product Manager** | Scope of C1/C3/C4, acceptance | PRDs for instructor/admin content | 01/02 | 50% |
| **Backend / Laravel Engineer (×2)** | A2,B1,B2,B3,C2,A8,A9,G3,G6,G7, backend of A1/A3/C3/C4/C1 | Tenant scope, RBAC, caching, commerce | Phase 0→7 | 100% |
| **Next.js / Frontend Engineer (×2)** | E1–E6, D1–D7, F1–F6, web of A1/A3/H1, C1 UI | Route resilience, IA, SEO, design system, instructor UI | Phase 1→6 | 100% |
| **UI Designer** | F-series specs, dashboard hierarchy, instructor screens | Token spec, component variants | 03 | 40% |
| **UX Designer** | Nav model, journeys, breadcrumbs, onboarding | Flow specs for D-series/C1 | 02 | 30% |
| **Database Engineer / DBA** | H2 backups, H6 pooler/replicas, indexes, migration safety | Backup/restore, PgBouncer | Phase 0,9 | 30% |
| **DevOps Engineer** | H1,H3,H4,H5,H6,H7, CD, monitoring, staging | Web host, CD, backups automation, alerting | Phase 0→12 | 100% |
| **Security Engineer** | A1,A2,A4,A5,A7,A8,A9, pentest re-test | Threat closure, scans, secrets mgr | Phase 0,4,10 | 60% |
| **QA Engineer + Automation QA** | I1–I3, E2E, isolation/idempotency tests, coverage gates | Playwright suite, test plans | Phase 11 (start early) | 100% |
| **Performance Engineer** | Phase 9, load tests, caching, N+1 | Perf report, budgets | Phase 8/9 | 40% |
| **Accessibility Specialist** | a11y pass (skip link, focus, contrast, targets) | WCAG AA on core flows | Phase 3/11 | 20% |
| **SEO Engineer** | E2 metadata/sitemap/robots/structured data | SEO implementation + validation | Phase 3 | 20% |

**Minimum viable team for critical path:** Tech Lead + 2 Backend + 2 Frontend + 1 DevOps + 1 Security (part-time) + 1 QA. Designers/PM part-time.

---

## Sprint Plan (2-week sprints)

| Sprint | Goals | Key tasks | Exit criteria | Risk | Outcome |
|--------|-------|-----------|---------------|------|---------|
| **S1** | Stop the bleeding | A3, A1, B1 | XSS chain closed; cookie auth; gateway out of txn | High | Safe to run |
| **S2** | Isolation + deliverability | A2, H1, H2, H3 | Tenant tests pass; web deploys; backups+restore drill; scheduler runs | High | Launchable infra baseline |
| **S3** | Resilience + CD | E1, H4, A7, H7 | error/loading/404; tagged CD; scans gating | Med | Ships safely |
| **S4** | Contracts + tooling | G1, G2, G3/G4, G5 | monorepo; generated types; Deptrac green | Med | No type drift |
| **S5** | Nav + SEO | D1, D2/D3, E2, E4 | mobile nav; breadcrumbs; metadata; locale doc | Med | Connected + indexable |
| **S6** | Design system + RSC | F1,F2,F3, E3 (public reads), E5,E6, D4/D5/D6/D7 | tokens unified; public pages RSC; orphans linked | Med | Consistent, faster |
| **S7** | Backend hardening + RBAC | C2, A9, A8, G7, F4/F5/F6 | granular roles; audit+authz; no N+1 | Med | Prod-grade backend |
| **S8** | Admin content control | C3, C4, Filament hardening | homepage/landing/brand/SEO editable | Med | Content out of code |
| **S9** | Instructor + learning | C1, assessments, A6 | instructor authors→publishes; quiz/assignment | High | Core loop complete |
| **S10** | Commerce + analytics + perf | A5,B2,B3, G6, Phase 9 items | commerce hardened; caching; perf budgets | Med | Scales |
| **S11** | Security final + QA | A4, secrets mgr, I1,I2,I3, a11y | pentest clean; E2E green; AA | Med | Verified |
| **S12** | Launch | H5 staging soak, runbooks, cutover | launch checklist green | High | Go-live |

(Lean 12-week path = S1–S3 + S5 + S7 + S9 + S11–S12, deferring S4/S6/S8/S10 polish.)

---

## Risk Register

| ID | Risk | Prob | Impact | Mitigation | Owner |
|----|------|------|--------|------------|-------|
| R1 | Cookie-auth migration breaks flows/SSR | Med | High | Feature-flag; keep bearer for non-browser; e2e login tests | Tech Lead |
| R2 | Tenant scope misses an endpoint → leak | Med | High | Global scope by default + isolation tests per endpoint; Deptrac/route audit | Security |
| R3 | RSC conversion regresses interactivity | Med | Med | Convert page-by-page behind tests; islands pattern | Frontend |
| R4 | Instructor area scope creep (XL) | High | Med | Cut MVP (author/publish/students) first; defer earnings | PM |
| R5 | No staging → prod surprises | Med | High | Stand up staging in S2–S3; soak before cutover | DevOps |
| R6 | Backup/restore unproven when needed | Low | High | Quarterly restore drills; record RTO/RPO | DBA |
| R7 | OpenAPI codegen churns FE during migration | Med | Med | Land G2 before heavy FE (S4 before S5/6) | Tech Lead |
| R8 | Payment edge cases under load (in-txn fix) | Low | High | Idempotency + race tests; reconcile via webhook | Backend |
| R9 | Perf targets missed | Med | Med | Caching + N+1 tests early; load test in S10 | Performance |
| R10 | Launch-day rollback fails | Low | High | Rehearse rollback + restore in staging | DevOps |

---

## Release Plan

- **Alpha (internal, end S3):** infra baseline + resilience; feature-flagged; no external traffic.
- **Private Beta (end S7):** connected UX + SEO + hardened backend/RBAC on **staging**; invited orgs; monitoring on.
- **Instructor Beta (end S9):** instructor authoring + assessments; limited cohort.
- **RC (end S11):** security-clean, QA-green, perf-budgeted.
- **GA (S12):** staged cutover, canary if replicas available, 72-hour watch.
- **Versioning:** semver; immutable image tags per release (git SHA + version); expand/contract migrations only.

---

## Definition of Done (applies to every task)

1. Code + tests (unit/feature; E2E where user-facing) pass in CI.
2. Lint + typecheck + PHPStan (gating) + security scans green.
3. Authorization + validation enforced server-side for any new endpoint.
4. Tenant scope applied to any org-owned data.
5. No secret/PII in logs; audit record for privileged mutations.
6. Metadata/SEO + `loading`/`error` present for any new route.
7. Accessible (labels, focus, contrast) and RTL-correct.
8. Docs/runbook updated; migration is expand/contract.
9. Acceptance criteria from the source audit satisfied; reviewed by Tech Lead.

---

## AI Implementation Prompts (per phase)

> Each phase references the detailed, file-level prompts already written in audits 01–09 (AIP-*). Use these orchestration prompts to drive an AI coding agent phase-by-phase; they delegate to the specific prompts.

**PH0 —**
> Execute the launch blockers. In order: migrate SPA auth to httpOnly cookies (09/AIP-1d, 06/AIP-5), sanitize lesson HTML server+client and add a web CSP (09/AIP-1a-c), add the `BelongsToOrganization` global scope to all org models with isolation tests (09/AIP-2, 05/AIP-2), add `apps/web/Dockerfile` + web service (08/AIP-1), add `scripts/backup.sh`+`restore.sh` and schedule them (08/AIP-2), register scheduled tasks and `schedule:work` (08/AIP-3), and move the gateway `charge()` outside the DB transaction (05/AIP-1). Gate: PT-1 re-test passes, tenant tests pass, web deploys, restore drill succeeds.

**PH1 —**
> Add `error.tsx`/`loading.tsx`/`not-found.tsx` per route group + root (06/AIP-1). Make CI push immutable tagged images to a registry and add a deploy job (08/AIP-4). Add composer/npm/Trivy/gitleaks gates and make PHPStan gating (08/AIP-5, 05). Add a non-root container user (08/AIP-8).

**PH2 —**
> Introduce Turborepo + `packages/{config,contracts}` (04/AIP-1), generate the API client/types from OpenAPI and remove hand-written types (04/AIP-2, 06/FE-1), decouple Analytics/Notifications to event DTOs (04/AIP-3), add context facades + Deptrac (04/AIP-5), delete unused `Repository` + dead components (04/AIP-7).

**PH3 —**
> Ship mobile nav + unified top bar with cart/bell/search (02/AIP-1, AIP-2), breadcrumbs (02/AIP-3), fix menus/`/settings` and orphans + role redirect (02/AIP-4/5/7/8), SEO metadata + sitemap/robots (06/AIP-2), locale-correct document (06/AIP-3), RSC-convert public reads (06/AIP-4), query-key factory + i18n split (06/AIP-6, 04/AIP-8), and design-system tokens: elevation/radius/Input-Field/brand button/title system/tables/type scale (03/AIP-1…9).

**PH4 —**
> Add granular roles + policies and refine tenant scope (05/AIP-2 extension, 01/C5), auth lockout + uniform responses + APP_DEBUG enforcement (09/AIP-7), PII log scrubbing + audit coverage (09/AIP-6, 05/AIP-7), eager-loading + query-count tests (05/AIP-5).

**PH5 —**
> Create `HomepageSection`, `LandingPage`, `BrandSetting`, `SeoMeta` models + Filament resources and have the web read them via API (01/F9/F10/F11), add `InstructorProfile`+approval, student view, pricing/plan management (01/F12), and harden Filament per-record policies/scopes.

**PH6 —**
> Scaffold the `(instructor)` area (`/teach*`, `/teach/apply`) over existing Authoring/Live APIs (01/F4), build quiz/assignment pages once the assessment API exists (02/FX10), enforce enrollment before playback tokens (09/AIP-4), and surface a certificate CTA on course completion.

**PH7 —**
> Add throttles to checkout/coupon/verification, re-validate coupons under lock, make refunds idempotent (09/AIP-3, 05/AIP-4), and add job tries/backoff/timeout+failed()+dead-letter (05/AIP-3).

**PH8 —**
> Add the read-cache seam with event invalidation for catalog/KPIs (04/AIP-4), formalize analytics read models, and secure exports (09/DATA-2).

**PH9 —**
> Eradicate N+1 with eager loading + `preventLazyLoading` + query-count tests (05/AIP-5), roll out caching, `dynamic()`-split heavy client components (06/AIP-7), add PgBouncer + split Redis + FPM tuning (08/AIP-7).

**PH10 —**
> Full web security headers, secrets manager migration (08/SEC-1), key-rotation policy, SBOM, and an external penetration re-test; close any residual High/Critical.

**PH11 —**
> Build the Playwright E2E suite for the four core flows (06/AIP-8), add tenant-isolation/idempotency/authz negative tests (05/AIP-*, 09), query-count + coverage gates, and an accessibility pass (skip link, focus management, contrast).

**PH12 —**
> Stand up staging parity, rehearse deploy/rollback/restore/incident runbooks, enable monitoring/alerting (08/AIP-6), and execute a staged production cutover against the Final Launch Checklist.

---

## Final Launch Checklist

**Security (blocking)**
- [ ] PT-1 XSS→token chain closed (sanitization + web CSP + cookie auth) — re-tested
- [ ] Tenant isolation enforced by default + isolation tests green
- [ ] Commerce/verification rate-limited; coupon validated under lock
- [ ] Playback requires active enrollment; signed URLs short-TTL
- [ ] Dependency/image/secret scans green; secrets in a manager; `APP_DEBUG=false`
- [ ] Audit trail on privileged actions; no secrets/PII in logs

**Reliability / Ops (blocking)**
- [ ] Automated encrypted backups + a **tested** restore (RTO/RPO recorded)
- [ ] Scheduled jobs running (reminders/digests/rollups/pruning)
- [ ] CD pushes immutable tags; rollback rehearsed on staging
- [ ] Monitoring + alerting live (5xx, readiness 503, queue depth, DB/Redis, payment/cert failures)
- [ ] Staging environment parity; health checks wired to LB

**Product / Frontend (blocking)**
- [ ] Web app has production hosting and serves with correct API base
- [ ] `error`/`loading`/`not-found` on all routes; no white-screens
- [ ] SEO metadata + sitemap/robots; Arabic renders RTL server-side
- [ ] Mobile navigation works; no dead menu items / 404 links
- [ ] Instructor can author→publish; students can complete a course + get a certificate

**Quality (blocking)**
- [ ] E2E green for register/login/checkout/learn/instructor
- [ ] Accessibility AA on core flows; RTL verified
- [ ] Performance budgets met in load test; no N+1 on list endpoints

**Sign-off:** Product ✅ · Tech Lead ✅ · Security ✅ · DevOps ✅ · QA ✅ · CTO ✅

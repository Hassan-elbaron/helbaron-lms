# Helbaron LMS

Helbaron LMS is a bilingual (Arabic/English) enterprise Learning Management System built as a Laravel 12 modular-monolith REST API (`/api/v1`) with a custom Next.js 15 frontend, a Filament v4 admin panel, PostgreSQL, Redis/Horizon, S3+CloudFront, and Mux. A functional v1 exists at tag `1.0.0-rc.1` covering ten domains (Identity, Catalog, Authoring, Learning, Commerce, CRM, Certification, Live, Analytics, Notifications). On top of that v1, the team has authored a complete enterprise **redesign** (five architecture blueprints) and an **execution program** (master plan, backlog, binding execution rules, ADRs), and has begun executing it: **Sprint 0** (architecture-fitness tooling) and **Sprint 1** (multi-tenancy foundation, Epic A2) are implemented in code. This document is the single source of truth for where the repository actually stands today — every claim below is derived from files in the repository, and anything that cannot be confirmed from the repository is marked **"Not verifiable from repository."**

> Scope note: this file was created without running the toolchain (no PHP/Composer/Node execution was performed while writing it). Statements about *design and code presence* are verified by inspecting files; statements about *runtime results* (tests passing, gates green, coverage %) are explicitly marked as not verifiable here.

---

# Current Project Phase

**Foundation.**

Why (evidence-based):

- A working v1 exists (`VERSION` = `1.0.0-rc.1`; 93 migrations, 81 Eloquent models, 72 backend Pest test files, 190 web Vitest files, 53 Next.js pages, Docker dev+prod, CI). That places the project past "Planning" and "Architecture."
- However, the team has explicitly pivoted to a redesign-driven rebuild of the platform spine. The `docs/redesign/*` blueprints define an enterprise target, and `docs/redesign/99_IMPLEMENTATION_MASTER_PLAN.md` sequences it into Phases A–G. **Only Phase A work has begun**: Sprint 0 (fitness tooling) and Sprint 1 (multi-tenancy) are done (`docs/implementation/reports/SPRINT0_*`, `SPRINT1_*`).
- The enterprise contexts the redesign centers on are **not built**: `app/Platform/Administration`, `app/Platform/Media`, `app/Platform/AI`, `app/Platform/Search`, `app/Contexts/Instructor`, `app/Contexts/Organization` directories do not exist. `app/Platform/Integration` exists but contains only 4 contract stubs (no implementation).
- The architecture-fitness gates configured in Sprint 0 are now **operational** (2026-07-11): `deptrac/deptrac` and `rector/rector` are in `apps/api/composer.json` require-dev with a regenerated lock; `deptrac.baseline.yaml` is populated (92 baselined violations, gate green on new code); the PHPStan baseline was regenerated against the refactored tree. See docs/reviews/ENTERPRISE_IMPLEMENTATION_REVIEW_AND_FIX_REPORT.md.

The project is therefore laying the *foundation* (governance + tenancy) of the enterprise platform on top of a v1 codebase. It is not yet in "Core Development" of the redesigned contexts, not in "Hardening" (target contexts unbuilt), and not "Production Ready" (open security items + unbuilt spine + unverified gates), despite the README labelling `1.0.0-rc.1`.

---

# Project Health

Scores are 0–100, derived from repository evidence. Each score states its basis.

| Area | Score | Basis |
|------|:---:|-------|
| **Architecture** | 72 | Exceptional *design* maturity: 5 redesign blueprints, 20 ADRs (`docs/adr/INDEX.md`), a dependency matrix, and a Deptrac ruleset. Enforcement is now operational (Deptrac installed + baseline populated, 2026-07-11), but most target contexts remain unbuilt. Design ~95, realised ~50. |
| **Backend** | 70 | Real modular monolith: Domains (Authoring, Catalog, Certification, Crm, Live) + Contexts (Analytics, Commerce, Learning) + Platform (Identity, Integration, Notifications, Shared); Actions/Services/Policies per context; 93 migrations. Known coupling debt documented (audits 04/05; redesign TD register) is only partly addressed. |
| **Frontend** | 68 | Next.js 15 App Router, 53 pages across route groups, i18n (AR/EN), TanStack Query, design tokens. Gaps: auth token in localStorage (security audit), no offline/multi-device, E2E only scaffolded (1 spec). |
| **Database** | 64 | 93 migrations, per-context schemas, soft deletes, casts. Tenant isolation now applied to 8 org-owned models but **composite `(organization_id, …)` indexes are recommended, not created** (A2-S03 report); no `tenants` table (lifecycle is value-objects only). |
| **Security** | 55 | Present: Sanctum + MFA, spatie RBAC + policies, security headers/CSP, named rate limiters, tenant isolation on 8 models with admin bypass. **Open (per `docs/audits/09_SECURITY_AUDIT.md` / `PRODUCTION_AUDIT.md`, not yet remediated): auth token in localStorage; payment-gateway call inside DB transaction / webhook idempotency; secrets in env.** These are scheduled for Sprint 2 (A6), not done. |
| **Testing** | 62 | 72 backend Pest files + 190 web Vitest files + new tenancy suites (scope, leakage, lifecycle, events). But coverage is not gated, E2E is a single Playwright+axe smoke, and **whether the suite currently passes is Not verifiable from repository** (not run here; contexts were relocated). |
| **DevOps** | 70 | Docker dev + prod, `docker-compose.prod.yml`, CI (`ci.yml`: `api`, `architecture`, `web`, `image` jobs) + `adr-validation.yml`, deploy/rollback scripts, health endpoints. Gaps: Deptrac/Rector deps not installed so the `architecture` job cannot pass yet; blue/green manual; no K8s manifests. |
| **Documentation** | 92 | Outstanding: 5 redesign blueprints, master plan, execution backlog, binding execution rules (`101`), 10 sprint reports, 20 ADRs, 9 audit reports, refactor logs, brand identity, README, CHANGELOG. The strongest asset in the repository. |
| **UI/UX** | 70 | Brand system (`docs/HElbaron-Brand-Identity.md`), 53 pages, marketing/landing, accessibility wired via axe (scaffold). UX/IA audit exists (`docs/audits/02`, `03`). Depth of applied a11y beyond the smoke test: Not verifiable from repository. |
| **Product** | 72 | Clear scope and prioritisation: product-director review (`docs/audits/01`), master roadmap (`docs/audits/10`), execution backlog with epics/stories. No live product metrics in repo. |
| **Marketing** | 45 | Brand identity doc + landing/marketing pages exist. No campaign, analytics, or go-to-market execution artifacts in the repository. Largely **Not verifiable from repository.** |
| **Commercial Readiness** | 55 | Commerce domain built (cart, pricing, coupons, orders, contracts, Stripe gateway). But tenant billing config, plan/entitlement tiers, and provisioning are foundation-only or planned. |
| **Production Readiness** | 50 | README asserts `1.0.0-rc.1`, but: open security items (above), unbuilt enterprise spine, empty Deptrac baseline, and unverified test/gate runs. A v1 could plausibly ship, but the *documented enterprise target* is far from production. |
| **Overall** | 65 | Weighted blend: elite documentation + a real v1 + a well-governed, just-started foundation, offset by unremediated security items, unbuilt target contexts, and gates not yet operational. |

---

# Current Reality

Classification is per repository evidence. "Foundation Only" = abstractions/contracts/scaffold present, no working implementation. "Planned" = defined in design docs, no code.

## Implemented (working code present)
- **Architecture (modular monolith):** `app/{Domains,Contexts,Platform}` structure, provider ordering, per-context Actions/Services/Policies.
- **Identity:** users, auth (Sanctum), MFA, RBAC (spatie), OTP, devices, sessions.
- **Catalog:** courses, categories, taxonomy, publish state.
- **Authoring:** sections, lessons, lesson media, publish-guard integration with Catalog.
- **Learning (v1):** enrollments, progress, sessions, bookmarks/notes, playback tokens.
- **Commerce (v1):** products, pricing, coupons, cart, orders, contracts, Stripe gateway.
- **CRM:** leads, companies, org membership, consulting, seats, timeline/notes/tasks/tags.
- **Certification:** templates, issuance, verification, auto-generate listener.
- **Live:** live courses/sessions, attendance, recordings, providers.
- **Analytics (v1):** metric definitions, dashboards, reports, exports, event subscriber.
- **Notifications:** templates, channels/providers, workflow engine, subscriber.
- **Authentication / Authorization:** Sanctum tokens + MFA; spatie roles + per-aggregate policies.
- **Frontend (v1):** 53 pages across route groups (auth, learning, commerce, crm, org, analytics, marketing), i18n AR/EN, TanStack Query.
- **Deployment (baseline):** Docker dev + prod, compose files, deploy/rollback scripts, health/readiness/liveness endpoints.

## Partially Implemented
- **DDD / Contexts:** contexts exist and are separated, but boundaries are **not enforced operationally** (Deptrac ruleset written; dep not installed; baseline empty). Learning/Commerce/Analytics relocated to `app/Contexts`; Catalog/Authoring/Certification/Crm/Live still under `app/Domains`.
- **Tenancy:** resolution abstraction + global `TenantScope` + `BelongsToTenant` + re-entrant bypass + admin-bypass policy are implemented and applied to **8 org-owned CRM models** (`Company, OrganizationMember, Department, Team, SeatPool, ConsultingRequest, ConsultingProject, BillingProfile`), with a cross-tenant leakage test suite. Not applied to indirect CRM models or non-CRM tenant data; composite indexes not created; runtime pass status Not verifiable here.
  - **Launch tenancy model = SINGLE TENANT (decision, 2026-07-11).** Repository evidence: no `tenants` table, no tenant persistence/provisioning workflow (value-objects only), no frontend tenant/workspace switcher UX, and tenant scoping covers only the 8 CRM/organization models above — the core LMS (Commerce/Catalog/Learning/Certification/Notifications/Analytics/Live) is deliberately **not** tenant-scoped. `config/tenancy.php` itself states it is "foundation only; enforcement is a later story." HElbaron therefore ships as a **single academy**; "Organizations" is a B2B sub-feature (orgs purchase seats and consulting), not platform multi-tenancy. The tenancy trait/scope/context remain as frozen foundation for a possible future multi-tenant SaaS and are harmless to the single-tenant launch (a single default org). Multi-tenant launch would require the deferred work in `docs/redesign/05` + composite indexes + isolating the non-CRM domains.
- **Filament admin:** resources + data-map panel present; README states a "Filament v4 resource pass" is pending.
- **Testing:** substantial unit/integration tests exist; coverage ungated; E2E scaffold only.
- **CI/CD:** pipelines defined; the `architecture` (Deptrac) job cannot pass until deps are installed and the baseline generated.
- **Monitoring:** health endpoints + JSON/correlated logging present; full observability (metrics/tracing) not present.
- **Media:** Mux/CloudFront/S3 playback is embedded in Learning (works), but there is **no Media Platform context** with ports.
- **Search:** Postgres-based lookups embedded per context; **no Search Platform port**.

## Foundation Only (abstractions/contracts, no implementation)
- **Tenant Provisioning / lifecycle:** `TenantStatus` state machine + value objects (`Tenant`, `TenantLimits`, `TenantUsage`, `TenantBranding`, `TenantDomains`, `TenantSettings`) + ports (`TenantRepository`, `TenantProvisioner`, `TenantUsageTracker`, lifecycle/read/publisher contracts). No persistence, no workflow, no `tenants` table.
- **Integration Platform:** `app/Platform/Integration/Contracts` = `EventBus`, `Outbox`, `WebhookPublisher`, `MessageBroker` interfaces only (4 stubs). No implementation, no messaging.
- **Tenant events:** 11 immutable event DTOs + marker (no publisher implementation bound beyond `CurrentTenantProvider`).
- **Organization:** exists today only inside CRM; the standalone context is design-only, but tenancy foundation prepares for it.

## Planned (design docs only, no code)
- **Administration context** (config/flags/capabilities/audit-center/tenant-provisioning/ops/branding) — `docs/redesign/05`.
- **Instructor context** split from Catalog/Identity — `docs/redesign/03`.
- **Media / AI / Search Platforms** as ports/adapters — `docs/redesign/05`.
- **Content versioning, Progress-Engine-v2, LRS, assessments, gamification, offline/sync** — `docs/redesign/04`.
- **Capabilities vs permissions vs flags, outbox-backed guaranteed events, Analytics repoint** — `docs/redesign/05`.

## Deferred (explicitly postponed in reports)
- Composite tenant indexes (A2-S03 → later migration).
- Indirect CRM model isolation and non-CRM tenant data isolation (needs schema/parent scoping).
- Real-model integration leakage tests (A2-S04+).

## Blocked
- **Sprint-0/1 gate operation is blocked on local steps** (not code): install `deptrac/deptrac` + `rector/rector`, run `composer require --dev`, generate Deptrac + PHPStan baselines, `npm install` to sync web dev-deps, then run `php artisan test`. Until then the `architecture` CI job and the boundary guarantees are inert. (Documented in every Sprint 0/1 report.)

## Unknown / Not verifiable from repository
- Whether the full test suite currently passes (not executed here; contexts were relocated).
- Actual test coverage percentage.
- Whether `1.0.0-rc.1` has been deployed anywhere.
- Marketing/commercial execution status beyond documents.
- Whether the latest commit has been pushed to the remote (local commit `f3432fd` exists; push requires credentials outside this environment).

---

# Production Blockers

Ranked by severity × proximity to shipping the documented enterprise target. Blockers 1–3 are security/correctness and derive from the audits; 4–8 are readiness gaps.

1. **Auth token stored in browser localStorage (XSS → token theft).** Source: `docs/audits/09_SECURITY_AUDIT.md`. Impact: account-takeover risk on any XSS. Remediation is scheduled (Sprint 2 / A6, httpOnly cookies) but **not implemented**.
2. **Payment gateway call inside a DB transaction + webhook idempotency.** Source: audits `05`/`09` and redesign ADR-16. Impact: risk of inconsistent/duplicate financial state and lost/duplicated entitlement. Not remediated (Sprint 2 C1 / A5.2).
3. **Architecture-fitness gates not operational.** `deptrac`/`rector` absent from `composer.json`; `deptrac.baseline.yaml` empty. Impact: bounded-context boundaries are unenforced in CI, so the redesign can silently erode. Fix is a local install + baseline generation (Blocked item above).
4. **Tenant isolation is partial and unverified at runtime.** Only 8 CRM models are scoped; user-owned and indirect tenant data are not; composite indexes are missing; the leakage suite has not been confirmed green here. Impact: potential cross-tenant exposure for unscoped data at scale.
5. **Enterprise spine unbuilt.** Administration, Media/AI/Search/Integration implementations, and Instructor/Organization splits are design-only. Impact: the multi-tenant/enterprise value the redesign promises is not yet deliverable.
6. **Test-suite pass status + coverage unknown.** Not verifiable from repository. Impact: cannot assert regression safety for a release.
7. **Filament v4 resource pass pending** (README). Impact: admin operations may be incomplete/inconsistent.
8. **Lockfiles not synced with new dev-deps** (`composer.lock` vs new `deptrac`/`rector`; `package-lock.json` vs new web dev-deps). Impact: `composer install` / `npm ci` in CI fall back or fail until synced.

---

# Critical Technical Debt

Only real, currently-present debt (not future enhancements). Cross-referenced to the audits / redesign TD register.

- **TD-1 — Learning reads Authoring/Catalog Eloquent models directly** for business logic (progress math, prerequisites). Coupling; blocks independent evolution. (`docs/redesign/04`.)
- **TD-2 — Synchronous full-course progress recompute on every write** (O(lessons) on the request path). Scalability. (`docs/redesign/04`.)
- **TD-4 — Auth token in localStorage** (also Blocker 1). Security.
- **TD-5 — Gateway-in-transaction / webhook idempotency** (also Blocker 2). Correctness/security.
- **TD-6 — Media providers embedded in Learning** (Mux/CloudFront/S3), not behind a Media port. Coupling; raw storage ids risk.
- **TD-8 — Analytics subscribes to concrete domain events** rather than published projections. Coupling.
- **Governance not operational** — Deptrac dep uninstalled + empty baseline; custom PHPStan arch rules not exercised. The controls exist but do nothing yet.
- **Partial/inconsistent tenant scoping** — the historical manual-scoping risk (TEN-1) is mostly replaced by the global scope for 8 models, but the remaining tenant-owned data is unscoped and indexes are missing.
- **Lockfile drift** (composer.lock / package-lock vs new dev-deps).

Note: much of TD-1/2/6/8 is *addressed in design* by the redesign but **not yet in code**; it remains debt in the running system.

---

# Recommended Execution Order

Exactly what should happen after Sprint 1, per `docs/redesign/100_EXECUTION_BACKLOG.md` and the sprint reports. Do not skip steps.

1. **Close Sprint 1 operationally (local, ~30–60 min):** `composer require --dev deptrac/deptrac:^2.0 rector/rector:^2.0`; generate the Deptrac baseline (`vendor/bin/deptrac analyse --formatter=baseline`) and the PHPStan baseline (`--generate-baseline`); `npm install` in `apps/web`; **run `php artisan test`, `pint --test`, `phpstan`, `deptrac`, `npm run lint/typecheck/e2e`**; commit the regenerated baselines + lockfiles. This makes the Sprint-0 gates real and proves no regression from Sprint 1 (tenant enforcement on the 8 models).
2. **Enable branch protection on `main`** requiring `api`, `architecture`, `web`, and the ADR check. Push the pending commit(s) to the remote.
3. **Sprint 2 — P0 security + eventing backbone (A4 + A6):** transactional outbox + DLQ + idempotency (ADR-16); httpOnly-cookie auth (Blocker 1); move gateway calls out of DB transactions + idempotent webhooks (Blocker 2); externalise secrets. These are the highest-severity blockers and are independent of feature work.
4. **Sprint 3 — Administration context (A3):** config/flags, capabilities (capability→permission→flag), Audit Center, Ops Console, providers/secrets registry, tenant provisioning implementation against a new `tenants` table (+ the deferred composite indexes), and multi-tenant enforcement completion (adopt tenancy on remaining tenant-owned data with the leakage suite as gate).
5. **Sprint 4 — Platform capability ports (A5) + backend relocations (C9/C10):** Media/AI/Search/Integration behind ports (moves embedded media out of Learning — TD-6); relocate Catalog/Live to `Contexts`, gated on tests + Deptrac.
6. **Phase B — Core LMS (Sprints 5–10):** content ports + versioning (TD-1), progress-engine-v2 projector (TD-2), assessment execution, LRS, gamification/paths/mastery, offline/multi-device, learning frontend.
7. **Phase C–D:** Commerce hardening (entitlement/outbox), Organization + Instructor splits, SSO/white-label/multi-panel, Analytics repoint (TD-8).
8. **Phase E–G:** AI, Marketplace, Global — only on the stabilised spine.

Rule (from `101_EXECUTION_RULES.md`): every step is additive, reversible, behind a capability/flag by default, and gated on Deptrac/PHPStan/tests before merge.

---

# Repository Reading Order

Read in this exact order before touching the repository:

1. `README.md` — what the system is and its stack (note: labels `1.0.0-rc.1`; read this document for the true status).
2. `PROJECT_STATUS.md` — **this file**; the single source of truth for current reality.
3. `docs/implementation/101_EXECUTION_RULES.md` — the binding execution contract; no code may be written without complying.
4. `docs/redesign/99_IMPLEMENTATION_MASTER_PLAN.md` — phases, WBS, milestones, timeline.
5. `docs/redesign/100_EXECUTION_BACKLOG.md` — the sprint-by-sprint executable backlog (single source for what to build next).
6. `docs/redesign/01`…`05` — the five architecture blueprints (Catalog; CRM/Organization; Instructor/Authoring; Learning; Administration/Enterprise).
7. `docs/adr/INDEX.md` — the 20 Architecture Decision Records + their implementation status/sprint targets.
8. `docs/audits/` — the current-state reviews (`01` product … `09` security, `10` roadmap, `PRODUCTION_AUDIT`).
9. `docs/implementation/reports/SPRINT0_*` then `SPRINT1_*` — what has actually been executed and how to finish it (run steps).
10. `docs/refactor/` — the backend/route relocation logs (Platform extraction, context moves).
11. Code: `apps/api/app/Platform/Shared/Tenancy/**` (the newest, most load-bearing foundation) → then the context you are working in.

---

# Documentation Map

| Document | Purpose | Primary audience |
|----------|---------|------------------|
| `README.md` | Stack, domains, quick orientation | Everyone (first contact) |
| `PROJECT_STATUS.md` (this) | True current status, health, blockers, next steps | Everyone before touching the repo |
| `docs/implementation/101_EXECUTION_RULES.md` | Mandatory, binding rules for every implementation task | Engineers, AI agents, reviewers |
| `docs/redesign/99_IMPLEMENTATION_MASTER_PLAN.md` | Phased plan, WBS, milestones, timeline | Architects, TPMs, execs |
| `docs/redesign/100_EXECUTION_BACKLOG.md` | Sprint/epic/story/task backlog, import-ready | Engineers, PMs, delivery leads |
| `docs/redesign/01_CATALOG_DOMAIN_REDESIGN.md` | Catalog target architecture | Backend, architects |
| `docs/redesign/02_CRM_ORGANIZATION_DOMAIN_REDESIGN.md` | CRM ↔ Organization split target | Backend, architects |
| `docs/redesign/03_INSTRUCTOR_AUTHORING_DOMAIN_REDESIGN.md` | Instructor/Authoring target | Backend, architects |
| `docs/redesign/04_LEARNING_DOMAIN_REDESIGN.md` | Learning target (ports, LRS, offline, assessments) | Backend, architects |
| `docs/redesign/05_ADMINISTRATION_ENTERPRISE_BLUEPRINT.md` | Administration + platform-wide integration + ADRs | Architects, execs, DevOps, security |
| `docs/adr/INDEX.md` | 20 ADRs with status + sprint targets | Everyone touching architecture |
| `docs/audits/01`…`10`, `PRODUCTION_AUDIT.md` | Independent reviews (product, UX, UI, architecture, backend, frontend, DevOps, security) | Domain owners, QA, security |
| `docs/implementation/SPRINT_0_EXECUTION_PLAN.md` | Sprint 0 plan | Engineers, delivery leads |
| `docs/implementation/reports/SPRINT0_*` | Sprint 0 outcomes + local run steps | Engineers, QA |
| `docs/implementation/reports/SPRINT1_A2_S0[1-5]_REPORT.md` | Sprint 1 (tenancy) outcomes + run steps | Engineers, QA, security |
| `docs/implementation/reports/SPRINT0_FINAL_REVIEW.md` | Sprint 0 closeout + Go/No-Go | Delivery leads, execs |
| `docs/refactor/*` | Route/context relocation logs | Backend, architects |
| `docs/HElbaron-Brand-Identity.md` | Brand system | UI/UX, marketing |
| `docs/HElbaron-QA-Issues.md` | Recorded QA issues | QA, engineers |
| `CHANGELOG.md`, `RELEASE_NOTES.md`, `VERSION` | Release history/version | Everyone |
| `config/architecture/adr-watch.yaml` | Paths that trigger the ADR check | Engineers, DevOps |

---

# Current Risks

## Technical
- Fitness gates not operational (Deptrac/PHPStan) → boundaries can erode before enforcement is switched on.
- Partial tenant isolation + missing composite indexes → cross-tenant exposure and/or slow tenant queries at scale.
- Large unbuilt gap between the redesign target and the running v1 (TD-1/2/6/8) → sustained coupling and scalability debt until Phase B lands.
- Test pass/coverage unknown here → regression risk on any change until the suite is run and gated.

## Business
- The README markets `1.0.0-rc.1` / "production ready" while the enterprise target is early-foundation; expectation mismatch with stakeholders/customers.
- Long redesign runway (master plan: ~9–12 months to enterprise-ready through Phase D) → time-to-value risk if scope isn't gated by exit criteria.

## Operational
- Sprint delivery depends on manual local steps (install deps, generate baselines, run tests) done consistently on each machine; drift risk.
- Blue/green + rollback are scripted but not automated/K8s-native; deployment operability at scale is unproven (Not verifiable from repository whether ever deployed).
- Single-maintainer knowledge concentration risk (mitigated by strong docs/ADRs).

## Commercial
- Billing/entitlement/plan tiers and tenant provisioning are foundation-only → cannot commercially onboard tenants self-serve yet.
- Security blockers (token storage, gateway-in-tx) must close before handling real customer money/data at scale.
- Marketing/GTM execution is not evidenced in the repository (Not verifiable from repository).

---

# Executive Recommendation

**In one line:** the project has a working v1 and *exceptional* planning/governance; the right move now is to make the just-built foundation real and safe before building any new features — do not start feature work until the gates are green and the P0 security items are closed.

**What the team should do next (in order):**

1. **Finish Sprint 1 operationally this week.** The Sprint-0/1 code is in place but inert until three local actions happen: install `deptrac`/`rector`, generate the Deptrac + PHPStan baselines, and `npm install`; then run the full gate suite (`php artisan test`, `pint`, `phpstan`, `deptrac`, web `lint`/`typecheck`/`e2e`) and commit the baselines + lockfiles. This converts the architecture-fitness and tenancy work from "written" to "enforced and proven," and is the single highest-leverage, lowest-cost step. Push the pending commit and turn on branch protection.

2. **Run Sprint 2 (security + eventing) before anything else.** Close the three P0 blockers — httpOnly-cookie auth, gateway-outside-transaction + idempotent webhooks, and the transactional outbox — plus secrets externalisation. These are independent of feature scope and are the items that would actually stop a production launch.

3. **Then Administration + full tenancy (Sprint 3).** Implement tenant provisioning against a real `tenants` table, add the deferred composite indexes, complete tenant adoption on the remaining tenant-owned data with the leakage suite as a permanent gate, and stand up capabilities/flags/audit. This is where multi-tenant enterprise value becomes deliverable.

4. **Correct the external narrative.** Update stakeholder-facing messaging so the `1.0.0-rc.1` label reflects reality: a solid v1 plus an early-foundation enterprise rebuild — not a finished enterprise product. This protects trust and sets correct expectations for the ~Phase-A→D runway.

5. **Hold the line on governance.** Per `101_EXECUTION_RULES.md`: one story at a time, additive and reversible, behind capability/flags, gated on Deptrac/PHPStan/tests, ADR-referenced. The documentation discipline here is the project's biggest asset — keep every future change traceable to a backlog task and an ADR.

**Bottom line:** the architecture is sound and the plan is excellent; execution risk (not design risk) dominates. Do the ~1 day of operational closure to make the gates real, spend the next sprint closing the security blockers, and only then resume building. Everything needed to do this — rules, backlog, ADRs, reports — is already in `docs/`.

---

## Validation

- Every section above is derived from files in this repository (structure, counts, configs, redesign/audit/report/ADR contents). Where a claim could not be confirmed from the repository, it is marked **"Not verifiable from repository"** (test pass status, coverage %, deployment status, marketing/commercial execution, and remote-push state).
- No existing document was modified. No additional files were created. Only `PROJECT_STATUS.md` was written, at the repository root.

---

# Architecture Progress Dashboard

Progress % reflects **realised code** against the redesign target (not design maturity). Owner is role-based per the master-plan team model; **actual person-level ownership is Not verifiable from repository** (git shows a single committer, `Hassan Elbaron <marketing@seenshow.com>`).

| Context | Current State | Progress % | Current Sprint | Next Sprint | Remaining Work | Risk | Owner (role) |
|---------|---------------|:---:|:---:|:---:|---------------|:---:|--------------|
| **Shared (Platform)** | Implemented | 85 | Sprint 1 (done) | Sprint 2 | Outbox/idempotency primitives; keep as stable kernel | Low | Solution Architect |
| **Identity** | Implemented | 80 | — | Sprint 3 | IdentityContracts ports burn-down; SSO (D3) | Medium | Backend |
| **Tenancy (isolation)** | Partially Implemented | 65 | Sprint 1 (done) | Sprint 3 | Adopt remaining tenant-owned models; composite indexes; verify leakage green | High | Backend + SA |
| **Tenant Provisioning** | Foundation Only | 25 | — | Sprint 3 (A3) | `tenants` table + repository/provisioner impl + workflows | High | Backend |
| **Administration** | Not Started (Planned) | 5 | — | Sprint 3 (A3) | Whole context: config/flags/capabilities/audit/ops/branding | High | Backend + SA |
| **Identity Auth/Authz** | Implemented | 85 | — | Sprint 2 (cookies) | httpOnly-cookie auth (A6) | High | Backend + FE |
| **Catalog** | Implemented (v1) | 70 | — | Sprint 5 (B1) | CurriculumReadPort; content versioning; relocate to Contexts (D4) | Medium | Backend |
| **Authoring** | Implemented (v1) | 65 | — | Sprint 5–7 | Definition ports; versioning; assessment defs | Medium | Backend |
| **Learning** | Implemented (v1) | 55 | — | Sprint 5–10 (B) | Ports; progress-v2 projector; LRS; assessments; offline | High | Backend + FE |
| **Instructor** | Not Started (Planned) | 5 | — | Sprint 11 (D2) | Split from Catalog/Identity; TeachingAuthority | Medium | Backend + SA |
| **Commerce** | Implemented (v1) | 65 | — | Sprint 6 (C1) | Exactly-once entitlement; gateway-outside-tx; refund policy | High | Backend |
| **CRM** | Implemented | 75 | — | Sprint 11 (D1) | Split Organization out; timeline stays CRM | Medium | Backend |
| **Organization** | Foundation Only | 20 | — | Sprint 11 (D1) | Standalone context; org roles/capabilities/lifecycle | Medium | Backend + SA |
| **Certification** | Implemented (v1) | 70 | — | Sprint 8 | Credential-reference model; recert | Low | Backend |
| **Live** | Implemented (v1) | 70 | — | Sprint 4 (C10) | Relocate to Contexts | Low | Backend |
| **Analytics** | Implemented (v1) | 55 | — | Sprint 12 (D5) | Consume published projections (repoint) | Medium | Backend |
| **Notifications** | Implemented (v1) | 70 | — | later | Provider config via Administration | Low | Backend |
| **Media Platform** | Foundation Only (embedded) | 20 | — | Sprint 4 (A5) | Extract PlaybackPort/MediaPort from Learning | Medium | Backend |
| **Search Platform** | Foundation Only (embedded) | 15 | — | Sprint 4 (A5) | SearchPort; per-context indexes | Low | Backend |
| **AI Platform** | Not Started (Planned) | 0 | — | Sprint 13 (E) | AIProvider port; human-in-the-loop | Low | Backend + SA |
| **Integration Platform** | Foundation Only (4 contracts) | 15 | — | Sprint 2/4 | Outbox/EventBus/Webhook impl | High | Backend + DO |
| **Frontend (web)** | Implemented (v1) | 65 | — | Sprint 10 (B7) | Cookie auth; learning UI v2; offline; E2E breadth | Medium | Frontend |
| **Filament (admin)** | Partially Implemented | 60 | — | Sprint 3/12 | v4 resource pass; instructor/org panels; white-label | Medium | Backend + FE |
| **Testing** | Partially Implemented | 60 | — | continuous | Coverage gate; E2E breadth; run/verify suites | Medium | QA |
| **CI/CD + Governance** | Partially Implemented | 55 | Sprint 0 (done) | close now | Install deptrac/rector; generate baselines; branch protection | High | DevOps + SA |
| **Deployment/Monitoring** | Foundation | 55 | — | later | Blue/green automation; observability stack | Medium | DevOps |

---

# ADR Executive Summary

The 15 highest-impact decisions (full set + fields in `docs/adr/INDEX.md`). "Current Status" is realised-in-code status.

| ADR | Decision | Reason | Current Status | Target Sprint | Link |
|-----|----------|--------|----------------|:---:|------|
| ADR-01 | Modular monolith over microservices | Velocity + strong consistency, one team | Implemented | — | `docs/adr/INDEX.md#adr-01` |
| ADR-02 | Bounded contexts, single-writer ownership | Avoid cross-domain coupling | In progress (enforcement pending) | Continuous | `docs/adr/INDEX.md#adr-02` |
| ADR-03 | Event-driven integration, events as DTOs | Decouple, resilience, replay | In progress (outbox pending) | Sprint 2 (A4) | `docs/adr/INDEX.md#adr-03` |
| ADR-04 | Filament is UI only | One source of business truth | Implemented (enforced by rule) | Continuous | `docs/adr/INDEX.md#adr-04` |
| ADR-05 | Administration as a Platform context | Operate platform without owning domains | Not started | Sprint 3 (A3) | `docs/adr/INDEX.md#adr-05` |
| ADR-06 | Capability vs Permission vs Flag | Correct multi-tenant gating | Not started | Sprint 3 (A3) | `docs/adr/INDEX.md#adr-06` |
| ADR-07 | Row-level multi-tenancy via global scope | Isolation by construction (TEN-1) | Partially (8 models) | Sprint 1–3 (A2) | `docs/adr/INDEX.md#adr-07` |
| ADR-08 | Media owns bytes; contexts own refs | One place for signing/security | Not started | Sprint 4 (A5) | `docs/adr/INDEX.md#adr-08` |
| ADR-09 | Content versioning (copy-on-write + pin) | Editing must not corrupt history | Not started | Sprint 5 (B1) | `docs/adr/INDEX.md#adr-09` |
| ADR-10 | Progress derived by projector (off write path) | Scalability of progress | Not started | Sprint 6 (B2) | `docs/adr/INDEX.md#adr-10` |
| ADR-11 | Authoring owns definitions; Learning owns attempts | Content vs execution split | Not started | Sprint 5–7 | `docs/adr/INDEX.md#adr-11` |
| ADR-13 | LRS + xAPI/SCORM/cmi5 ledger | Audit/replay/interop | Not started | Sprint 8 (B4) | `docs/adr/INDEX.md#adr-13` |
| ADR-16 | Integration Platform = single external I/O boundary | Security/retry/audit; gateway outside tx | Foundation (contracts) | Sprint 2/4 | `docs/adr/INDEX.md#adr-16` |
| ADR-19 | Deptrac + custom PHPStan for fitness | Boundaries can't erode silently (TD-7) | Implemented (not operational) | Sprint 0 (close now) | `docs/adr/INDEX.md#adr-19` |
| ADR-20 | Identity exposes a contracts seam | Contexts must not couple to Identity impl | Foundation (Deptrac split) | Sprint 1+ | `docs/adr/INDEX.md#adr-20` |

---

# Sprint Dashboard

Derived from `docs/implementation/reports/*` and `docs/redesign/100_EXECUTION_BACKLOG.md`.

- **Current Sprint:** Sprint 1 — Epic **A2 (Multi-Tenancy & Isolation)** — code complete (all 5 stories implemented); **awaiting operational closure** (local gate run + push).
- **Current Story:** none active. Last delivered: **A2-S05** (tenant events + platform contracts). Next actionable: **operational close of Sprint 1**, then **A4/A6 (Sprint 2)**.
- **Completed Stories:** Sprint 0 — A1-S01, A1-S02, A1-S03, A1-S04 (Deptrac, PHPStan L6 + rules, ESLint/Playwright, ADR validation). Sprint 1 — A2-S01, A2-S02, A2-S03, A2-S04, A2-S05.
- **Remaining Stories (near-term, per backlog):** A2 close-out (deps/baselines/tests); Sprint 2 — A4-S01…S03 (outbox/DLQ/versioning), A6-S01…S03 (cookie auth, secrets, impersonation); Sprint 3 — A3 (Administration) + A2 tenancy completion.
- **Current Epic:** A2 (done, pending closure) → next epic **A4 + A6** (Sprint 2).
- **Current Milestone:** M2 (Tenant Isolation) reached in code; **M3 (Secure Foundation) not started**.
- **Next Milestone:** **M3 — Secure Foundation** (httpOnly cookies, Integration Platform, outbox+DLQ) — Sprint 2. (M1 Fitness Gate: reached in code, pending operational closure.)

---

# Immediate Next Action

- **What should be done now:** Operationally close Sprint 1 — from `apps/api`: `composer require --dev deptrac/deptrac:^2.0 rector/rector:^2.0`; generate the Deptrac baseline and PHPStan baseline; from `apps/web`: `npm install`; then run the full gate suite (`vendor/bin/pint --test`, `vendor/bin/phpstan analyse`, `vendor/bin/deptrac analyse`, `php artisan test`, and web `npm run lint`/`typecheck`/`e2e`); commit the regenerated baselines + lockfiles and **push commit `f3432fd`** to `origin/main`.
- **Who should do it:** DevOps + Backend, with Solution Architect signing off the gate results.
- **Which document to read first:** `docs/implementation/reports/SPRINT1_A2_S05_REPORT.md` ("Sprint 1 readiness for closure") and `SPRINT0_FINAL_REVIEW.md` (the exact commands + recommendations).
- **Which branch:** `main` for the operational closure/push; then a short-lived feature branch (e.g. `feat/a4-outbox`) per story for Sprint 2 (trunk-based, per `101_EXECUTION_RULES.md`).
- **Which Story is currently active:** none — Sprint 1 is code-complete; the active work item is the **operational close-out task** above, immediately followed by **A4-S01 (transactional outbox)** or **A6-S01 (httpOnly-cookie auth)** in Sprint 2.
- **Blockers to resolve first (in order):** (1) install `deptrac`/`rector` + generate baselines (makes the `architecture` CI job pass); (2) sync `composer.lock` + `package-lock.json`; (3) run `php artisan test` to confirm no regression from tenant enforcement on the 8 CRM models; (4) push the pending commit + enable branch protection. Only after these should Sprint 2 (the P0 security work) begin.

---

# Repository Navigation

**First 10 minutes after cloning — open these, in order:**

1. **Minute 0–1:** `README.md` — stack + domains (note it labels `1.0.0-rc.1`).
2. **Minute 1–3:** `PROJECT_STATUS.md` (this file) — the true status; read "Current Project Phase", "Immediate Next Action", "Production Blockers".
3. **Minute 3–4:** `docs/implementation/101_EXECUTION_RULES.md` — the binding rules you must follow before writing any code.
4. **Minute 4–6:** `docs/redesign/100_EXECUTION_BACKLOG.md` — find the current sprint/epic and the next story.
5. **Minute 6–7:** `docs/adr/INDEX.md` — skim the 20 ADRs + statuses.
6. **Minute 7–8:** `docs/implementation/reports/SPRINT1_A2_S05_REPORT.md` — what just landed + how to close it.
7. **Minute 8–10:** Code — open `apps/api/app/Platform/Shared/Tenancy/` (newest, most load-bearing) and the redesign for the context you'll work in (`docs/redesign/0X_*`).

**Where things live:** API = `apps/api` (Laravel; contexts in `app/{Domains,Contexts,Platform}`); Web = `apps/web` (Next.js; route groups in `src/app/(group)`); Docs = `docs/{redesign,implementation,adr,audits,refactor}`; Governance config = `apps/api/deptrac.yaml`, `config/architecture/adr-watch.yaml`; CI = `.github/workflows/`.

---

# Executive Snapshot

**Helbaron LMS — one-page snapshot (for investors, technical leads, product owners).**

**What it is.** A bilingual (AR/EN) enterprise LMS: Laravel 12 modular-monolith API + Next.js 15 frontend + Filament admin, on PostgreSQL/Redis/S3/CloudFront/Mux. A functional v1 (`1.0.0-rc.1`) spans ten domains (identity, catalog, authoring, learning, commerce, CRM, certification, live, analytics, notifications).

**Where it stands.** The project is in the **Foundation** phase of an enterprise rebuild. A working v1 exists; on top of it the team has produced an unusually complete architecture-and-execution corpus (5 redesign blueprints, a phased master plan, a sprint-by-sprint backlog, a binding execution contract, 20 ADRs, 9 audits, 10 sprint reports) and has executed the first two sprints in code: **Sprint 0** installed automated architecture-fitness controls (Deptrac + PHPStan boundary rules, ADR gate), and **Sprint 1** built the multi-tenancy foundation (tenant resolution, global isolation scope applied to 8 org-owned models with a cross-tenant leakage test suite, tenant lifecycle value-objects/ports, and tenant event DTOs + platform contracts).

**Strengths.** Elite documentation and governance; a real, testable v1 (93 migrations, 81 models, ~262 test files); clean bounded-context structure; every change traceable to a backlog task and an ADR; additive, reversible delivery discipline.

**Gaps / risks (honest).** The controls are written but **not yet operational** (Deptrac/Rector not installed, baseline empty); three P0 security items remain open (auth token in localStorage; payment gateway inside a DB transaction / webhook idempotency; secrets in env); tenant isolation is partial (8 models, indexes deferred) and unverified at runtime here; and the enterprise contexts the redesign centers on (Administration, Media/AI/Search/Integration implementations, Instructor/Organization splits) are still design-only. Whether the test suite currently passes and whether anything is deployed are **Not verifiable from repository**.

**What happens next.** (1) ~1 day of operational closure to make the gates real and prove no regression, then push + branch protection. (2) One sprint (Sprint 2) to close the P0 security blockers via cookie auth + transactional outbox + gateway-outside-transaction. (3) Administration + full tenancy (Sprint 3), unlocking self-serve enterprise onboarding. The master plan estimates ~9–12 months to enterprise-ready through Phase D with the recommended team.

**Bottom line.** The design is sound and the plan is excellent; the dominant risk is **execution discipline, not architecture**. The immediate, low-cost, high-leverage move is to finish Sprint 1 operationally and close the security blockers before building anything new — everything needed to do so already exists in `docs/`.

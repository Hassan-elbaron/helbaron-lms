# Enterprise Architecture Review — V2

_CoreLMS · Laravel 12 API (`apps/api`) + Next.js 15 web (`apps/web`) · Fresh full review · July 2026_

> **Baseline note.** There is no `ENTERPRISE_REVIEW_V1.md` in the repository. The "Review V1" baseline used for all comparisons in this document is the committed audit suite `docs/audits/01…10` (its consolidated score table lives in `docs/audits/10_MASTER_IMPLEMENTATION_ROADMAP.md`) plus `docs/audits/09_SECURITY_AUDIT.md` and `docs/audits/08_DEVOPS_INFRASTRUCTURE_REVIEW.md`. Every finding below was re-derived from the current repository state, not carried over. Where a V1 issue is fixed, it is explicitly acknowledged and not re-listed as a defect. Runtime gates (PHPUnit/Pest, PHPStan, Deptrac, Pint) could not be executed in this environment — where a claim depends on execution it is marked **Not verifiable from repository**, and the analysis relies on source evidence (file:line), config, and git state instead.

---

# Executive Summary

Since V1 the team executed a disciplined, genuinely valuable **internal decoupling program** on the backend: an Identity contracts/ports/adapters layer (Actor, `UserLookupPort`, `CurrentUserPort`, `UserRef` DTO), a Curriculum read-port with immutable DTOs, and a Media playback port. The concrete `App\Platform\Identity\Models\User` model is now fully encapsulated inside the Identity context for all production code (test fixtures and one documented event seam excepted), Learning→Curriculum import coupling fell from ~31 to 8, and the media-playback authorization hole flagged in V1 is closed. This is real, grep-verifiable maintainability and extensibility work, and it is the correct direction.

However — and this is the central, brutally honest finding of V2 — **none of the V1 launch blockers moved.** The stored-XSS→token-theft chain is still fully live on the frontend (unsanitized `dangerouslySetInnerHTML` fed by API HTML + Sanctum bearer token in `localStorage` + no web CSP). The core LMS domain models (Course, Order, Enrollment, Certificate) still have no tenant scoping — the tenancy *mechanism* was built but adopted only on 8 CRM models. The Next.js app still has **no production hosting** (no web Dockerfile, no compose service). There is still no automated backup/restore, the scheduler is still empty, there is still no CD/registry push, and still no monitoring or alerting. Public pages still have almost no SEO and are ~74% client components.

Worse, the review surfaced a **new regression**: the CI `architecture` (Deptrac) job and the Rector step invoke `vendor/bin/deptrac` and `vendor/bin/rector`, but neither `deptrac/deptrac` nor `rector/rector` is in `apps/api/composer.json` / `composer.lock` / `vendor/`. The Deptrac baseline is empty. So the flagship "architecture fitness gate" is not merely inert — it would hard-fail on the first clean `composer install`, and no boundary rule has ever actually been enforced by tooling. Boundary compliance to date rests entirely on manual, prose-documented refactors.

**Net:** the codebase is a stronger, cleaner engineering *base* than at V1, but it is no closer to being a shippable *product*. Overall movement is roughly **+1 point** on a 100-scale. The repository's own `PROJECT_STATUS.md` says the same thing honestly ("Architecture: design ~95, realised ~50; governance not operational").

---

# Repository Score

**Overall: 66 / 100** (V1 ≈ 65). A strong backend core dragged down by unaddressed launch-blocking security, frontend-delivery, and operational gaps, plus a broken fitness gate.

| Major area | Score /100 | One-line basis |
|---|---|---|
| Software Architecture (design) | 75 | Ports/adapters + DDD decoupling are excellent on paper and largely realized in Identity/Curriculum/Media |
| DDD boundaries (realized in code) | 78 | `User` encapsulated; residual Analytics/Notifications event coupling + Enrollment→Catalog relations |
| Architecture governance / Deptrac fitness | 38 | Ruleset well-written but tool not installed, baseline empty, CI job would fail |
| Ports & Adapters implementation | 85 | Real contracts, thin adapters, correct DI binding via providers |
| Backend (Laravel) | 78 | Strong validation/indexes/eager-loading; media-auth fixed; payments/rate-limit/audit still open |
| Security | 73 | Solid crypto/authn + media auth; XSS→token chain and core tenant isolation still open |
| Payments & commerce integrity | 68 | Idempotent webhook is exemplary; gateway call inside txn, refund not lock-guarded, coupon TOCTOU |
| Data / DB design | 82 | 87 migrations, FKs + hot-path indexes present and correct |
| Frontend (Next.js) | 64 | Route error/loading boundaries present; SEO/RSC/middleware/hosting/XSS all still open |
| UX | 54 | Unchanged since V1 (no frontend commits) |
| Information Architecture | 50 | Unchanged since V1 |
| UI / Visual | 66 | Unchanged since V1 |
| Design System | 62 | Unchanged since V1 |
| Accessibility | 50 | Real axe+Playwright checks, but only 3 of 53 routes; no skip links; sparse ARIA |
| QA / Testing | 67 | +e2e w/ axe, +tenancy isolation test, +architecture test dir; still no query-count/security tests |
| DevOps / Infra | 60 | PHPStan now blocking (+) offset by broken Deptrac CI job (−); blockers unchanged |
| CI / CD | 52 | Build-only, no push/registry, no deploy; broken arch job; no scanning |
| Observability / Ops | 45 | Solid health probes; zero monitoring/alerting/backup/scheduler |
| Documentation | 74 | Vast, well-structured corpus; but demonstrable doc-vs-reality drift |
| Product completeness | 62 | Instructor `/teach` area now exists; admin-controllable marketing/SEO still absent |
| Enterprise readiness | 55 | Core sound; launch blockers unmet |

---

# Comparison With Review V1

Scores normalized to /100 (V1 audit suite reported /10; ×10 here). "Delta" is V2 − V1.

| Category | Previous (V1) | Current (V2) | Delta | Reason |
|---|---|---|---|---|
| Software Architecture | 72 | 75 | +3 | Identity/Curriculum/Media ports realized; DTOs; `User` encapsulated. Offset by broken governance gate + unfinished `Domains`/`Contexts` naming migration |
| DDD boundaries | 85 | 78→(area) 88 | +3 | Concrete cross-context model imports largely removed; residual event coupling remains. (Listed as its own area above at 78 for *realized* strictness; boundary-design intent ≈88) |
| Backend (Laravel) | 78 | 78 | 0 | Media-playback authorization fixed; but payments-in-transaction, rate-limiting, and audit-trail backlog untouched |
| Security | 73 | 73 | 0 | Media auth solid; tenancy mechanism scaffolded — but XSS→token chain, core-model tenant scoping, rate limiting, and audit all still open |
| Frontend (Next.js) | 64 | 64 | 0 | No frontend commits since V1. Route error/loading boundaries are present (V1's E1 not reproduced), but SEO, RSC conversion, middleware, hosting, and the XSS sink are unchanged |
| UX | 54 | 54 | 0 | Frontend untouched since V1 |
| Information Architecture | 50 | 50 | 0 | Frontend untouched since V1 |
| UI / Visual | 66 | 66 | 0 | Frontend untouched since V1 |
| Design System | 62 | 62 | 0 | Frontend untouched since V1 |
| QA / Testing | 65 | 67 | +2 | Added Playwright e2e with axe, a tenancy cross-leakage test, and an architecture test directory; still no query-count or security-regression tests |
| DevOps / Infra | 60 | 60 | 0 | PHPStan is now a blocking gate (+), but a new broken Deptrac/Rector CI job (−) and all V1 ops blockers remain |
| Security (web headers/CSP) | 30 | 30 | 0 | Still no CSP or security headers on the Next.js app |
| Dependency security / scanning | 40 | 40 | 0 | Still no `composer audit`/`npm audit`/Dependabot/Trivy/gitleaks in CI |
| Tenant isolation | 50 | 55 | +5 | Reusable `TenantScope` + `BelongsToTenant` + `ResolveTenant` middleware + isolation test built; adopted only on 8 CRM models, not core LMS |
| Product completeness | 60 | 62 | +2 | Instructor `(instructor)/teach/*` UI now exists (V1 C1 partially addressed) |
| **Weighted overall** | **≈65** | **≈66** | **+1** | Real backend-internal gains; zero movement on launch blockers; one CI regression |

---

# Improvements Achieved

Verified from current source; V1 issues genuinely resolved or advanced.

- **Identity fully decoupled (Phases 1–3C).** `Actor` interface + `CurrentUserPort`/`UserLookupPort`/`UserPermissionPort`/`UserRolePort` + `UserRef` DTO, thin adapters, DI-bound in `IdentityServiceProvider`. 18 policies + 1 gate migrated to `Actor`; the last two `belongsToMany(User)` relations (`Course::trainers()`, `LiveSession::trainers()`) replaced with context-local pivot read models resolved via `UserLookupPort::refsByIds()`. Zero non-Identity production imports of the concrete `User` (test fixtures + `UserRegistered` event payload excepted).
- **Curriculum read-port.** `CurriculumReadPort` + immutable `CourseRef`/`SectionRef`/`LessonRef` DTOs; Learning→Curriculum `use`-imports reduced from ~31 to 8 (remaining are cross-context integration seams and fixtures).
- **Media decoupled.** `MediaAssetPort` (Authoring) and `PlaybackPort` (new `Platform/Media`) extracted; `LearningMediaService` no longer touches storage/provider internals.
- **Media playback authorization closed (V1 A6/MED-1).** `LessonPlayerController` calls `assertAccessByUserId(...)` (active enrollment + prerequisite check, throws 403) *before* any Mux/CloudFront signed URL is generated. Confirmed at source.
- **PHPStan is now a blocking CI gate (V1 CI-3).** `ci.yml` runs `phpstan analyse` with no `continue-on-error` / `|| echo`.
- **Instructor UI exists (V1 C1, partial).** `apps/web/src/app/(instructor)/teach/*` pages are present (previously "no `/teach`").
- **Route error/loading boundaries present (V1 E1, partial).** 10 `error.tsx` + 10 `loading.tsx` (per route group) + root `not-found.tsx` delegating to shared route components.
- **Tenancy mechanism built (V1 A2, mechanism only).** `TenantScope`, `BelongsToTenant`, `ResolveTenant` middleware, and `CrossTenantLeakageTest` are solid and correct in isolation.
- **Data layer confirmed strong.** 87 migrations; FKs via `foreignId()->constrained()`; hot-path composite indexes on `orders`, `enrollments`, `certificates`. Validation centralized in 31 Form Requests with zero inline controller validation.
- **Runtime foundations confirmed solid.** Real liveness/readiness endpoints probing Postgres + Redis; Horizon supervisors configured; S3 + CloudFront + Mux signer classes implemented (not stubs).

---

# Remaining Critical Issues

Launch-blocking. All independently re-verified in current source.

1. **Stored-XSS → token-theft chain is fully live.** `apps/web/src/components/learning/lesson-content.tsx:99-103` renders API-sourced lesson HTML via `dangerouslySetInnerHTML` with **no sanitizer** (no DOMPurify/sanitize-html in `package.json`; the code comment concedes it). The Sanctum bearer token sits in plain `localStorage` (`apps/web/src/lib/api/client.ts:4-14`), and there is **no web CSP** (`next.config.ts` has no `headers()`, no `middleware.ts`). Instructor-authored HTML → every learner's browser → `localStorage.getItem("helbaron.token")` exfiltration. This is the identical V1 #1 blocker, unmitigated.
2. **Core LMS data is not tenant-isolated.** Only 8 CRM models carry `organization_id` / `BelongsToTenant`; `Order`, `Enrollment`, `Certificate`, `Course`, `LiveSession` have no tenant column or scope, and `ResolveTenant` is intentionally non-throwing. _Severity is conditional:_ if the platform is intended as multi-tenant SaaS, this is a data-isolation blocker; if "organization" is only a B2B CRM/seat construct over a single-tenant LMS, it is by-design. **Not fully verifiable from repository** — product intent must be confirmed. Flagged critical pending that confirmation.
3. **The Next.js app has no production hosting.** No `apps/web/Dockerfile`; no `web` service in `docker-compose.prod.yml` (or dev). The frontend cannot be deployed by the provided infrastructure at all. Identical to V1 H1/FE-1.
4. **No automated backup/restore.** No `backup.sh`/`restore.sh`, no `spatie/laravel-backup`, no backup sidecar; DR is prose-only in `docs/ops/DISASTER_RECOVERY_GUIDE.md`. Postgres/Redis on local named volumes. Restore is untested. Identical to V1 H2.
5. **Architecture fitness gate is broken, not just inert.** `ci.yml` `architecture` job runs `vendor/bin/deptrac` (hard gate) and a Rector step, but neither package is in `composer.json`/`composer.lock`/`vendor/`; `deptrac.baseline.yaml` is empty. A clean `composer install` → CI job fails on "command not found." No boundary rule has ever been machine-enforced. (Runtime confirmation: **Not verifiable from repository**, but package absence is verifiable in `composer.json`/`composer.lock`.)

---

# High Priority Issues

1. **External gateway calls inside DB transactions.** `CheckoutAction` calls `$this->gateway->charge(...)` inside `$this->transaction()` while holding a coupon `lockForUpdate()`; `RefundOrderAction` calls `$this->gateway->refund(...)` inside a transaction. A slow/hung Stripe call holds DB locks and risks connection-pool exhaustion. (V1 B1, open.)
2. **Refund is not idempotent/lock-guarded.** `RefundOrderAction` checks `status !== Paid` on an unlocked read before charging the gateway — no `lockForUpdate()`, no dedup — unlike the exemplary `ProcessWebhookAction` (row lock + unique `event_id`). Double-refund race is possible. (V1 B2, open.)
3. **Coupon TOCTOU at checkout.** The coupon is validated at apply-time only; `CheckoutAction`/`CartService.totals()` use the pre-lock coupon relation and never re-check `isExhausted()`/window/active after locking. An exhausted/expired coupon can still apply. (V1 B2, open.)
4. **Rate limiting only on auth.** The only `RateLimiter::for(...)` definitions are 4 identity limiters. Commerce (`checkout`, coupon via `POST /cart`) and the **public unauthenticated** certificate-verification endpoint (`GET certificates/verify/{code}`) are unthrottled — coupon brute-force and certificate enumeration are open. No global `throttle:api`. (V1 A5, open.)
5. **No privileged-action audit trail.** `HasAudit` only stamps `created_by`/`updated_by`; there is no audit table/model and no activity-log package. Role/permission changes, refunds, enrollment grant/revoke, and certificate revoke are not recorded with actor + before/after. (V1 A8/MOD-3, open.)
6. **No CD.** CI `image` job builds with `push: false`, tag `helbaron-api:ci`, no registry login, no SHA/semver tag, no deploy job. `rollback.sh` has no durable tag source to roll back to. (V1 H4, open.)
7. **No monitoring or alerting; no staging.** No Sentry/Prometheus/APM/uptime/alert rules anywhere in the repo; only two compose files (dev/prod), no staging. Incidents would go unnoticed. (V1 H5/MON, open.)
8. **Empty scheduler running in production.** `routes/console.php` registers zero tasks, no `withSchedule` closure, yet `docker-compose.prod.yml` runs a `scheduler` service looping `schedule:run` forever over nothing. (V1 H3, open.)
9. **Near-zero SEO on public pages + client-heavy delivery.** ~10 of 53 pages define metadata (mostly gated instructor pages); the money pages (`courses`, `courses/[public_id]`) have none and are `"use client"`. No `sitemap.ts`/`robots.ts`/OG. ~125 of 168 `.tsx` (74%) are client components. No `middleware.ts` for auth/role/locale at the edge. (V1 E2/E3/E5, open.)

---

# Medium Priority Issues

1. **Residual cross-context event coupling.** `Analytics/Listeners/MetricEventSubscriber` and `Notifications/Listeners/NotificationEventSubscriber` import concrete `Events` from Certification, Commerce, CRM, Learning, Live — violating the Deptrac ruleset as written (each is allowed only `Shared + IdentityContracts`). Self-labelled TD-8. A real (unbaselined) violation a running gate would flag.
2. **`Domains` vs `Contexts` naming split is an unfinished migration.** Learning/Commerce/Analytics moved to `app/Contexts`; Catalog/Authoring/Certification/CRM/Live remain under `app/Domains`. Inconsistent import surface and cognitive load.
3. **Filament composition-root violations.** `PlatformOverview` widget still imports `Course`, `Lead`, `LiveSession`, `Order`, `Enrollment` across contexts (Deptrac comments name it as an accepted existing violation).
4. **`Enrollment::course()`/`user()` cross-context relations retained.** Consumed by Certification/Notifications listeners and Filament — documented irreducible-within-scope debt.
5. **One of two queue jobs lacks resilience.** `DeliverNotificationJob` has `tries`/`backoff`/`failed()` (no `$timeout`); `ProcessExportJob` has none of them and rethrows on failure.
6. **No query-count / N+1 regression tests.** Eager loading is currently good, but zero `assertDatabaseCount`/query-count assertions exist, so regressions won't be caught.
7. **`global-error.tsx` missing.** An error thrown in `app/layout.tsx`/`providers.tsx` (outside route groups) renders Next's unstyled crash screen.
8. **Static `lang`/`dir`.** `<html lang dir>` is fixed to `defaultLocale` ("en"/"ltr"); Arabic/RTL users get a first-paint `dir="ltr"` flash; locale switch is client-only. (V1 E4.)
9. **Accessibility is narrow.** axe runs on 3 of 53 routes; `eslint-plugin-jsx-a11y` only transitive; no skip links; sparse ARIA outside marketing.
10. **`env:validate` not wired into deploy/CI.** The command correctly rejects `APP_DEBUG=true` in production, but nothing in `deploy.sh`/`rollback.sh`/`ci.yml` invokes it.
11. **Single shared Redis for cache/queue/session.** No logical separation; a cache flush can affect jobs/sessions.

---

# Low Priority Issues

1. **Dead abstractions.** `Platform/Shared/Contracts/Repository.php` has zero implementers; `Platform/Integration/Contracts/*` (EventBus/MessageBroker/Outbox/WebhookPublisher) are stubs with no implementations.
2. **Single-use caching.** Exactly one `Cache::remember` (`Analytics/KpiEngine`); no read-cache seam/port.
3. **No OpenAPI→types generation.** 10 hand-authored specs; frontend types hand-written; drift risk.
4. **No real monorepo tooling.** No `turbo.json`/`pnpm-workspace.yaml`/root workspaces; apps linked only by compose/CI.
5. **`.env.example` ships `APP_DEBUG=true`** (acceptable for a template, worth a comment).
6. **API container runs as root.** `apps/api/Dockerfile` sets no `USER`; only two dirs are `chown`ed.
7. **Git history is 7 squashed commits (3 days); latest possibly unpushed** — real development history is not observable; `PROJECT_STATUS.md` notes the last commit may not be pushed to a remote.
8. **i18n dictionary is an 893-line dual-locale file** — maintainability, not correctness.

---

# Technical Debt

**Architecture**
- Governance gate non-functional: `deptrac`/`rector` absent from `composer.json`; empty Deptrac baseline; 4 custom PHPStan architecture rules authored but unverified in execution.
- Unfinished `Domains`→`Contexts` migration; residual Analytics/Notifications event coupling; Filament cross-context model imports; retained `Enrollment` cross-context relations.
- Redesigned enterprise contexts (Administration, AI, Search, Instructor, Organization) exist only as empty scaffolding per `PROJECT_STATUS.md`.
- Dead `Repository` contract and stub `Integration` messaging contracts.

**Infrastructure**
- No web Dockerfile / prod hosting for Next.js. API container runs as root. No CD/registry push. No staging. Single shared Redis. No image/dependency/secret scanning.

**Application**
- Payment gateway calls inside transactions; non-idempotent refund; coupon TOCTOU. Rate limiting only on auth. No audit trail. `ProcessExportJob` lacks retry/timeout/dead-letter. Unsanitized lesson HTML sink. Token in `localStorage`.

**Documentation**
- Doc-vs-reality drift: `docs/audits/PRODUCTION_AUDIT.md` claims `composer audit` + `npm audit` are "wired" in CI — they are not. Multiple implementation reports assert Deptrac/PHPStan runs that are marked "Not verifiable from repository." The doc corpus is large and well-structured but partly aspirational; readers must treat "done/wired" claims skeptically.

**Testing**
- No query-count/N+1 regression tests; no security-regression tests (XSS sink, token storage, headers untested); tenancy isolation tested only against a synthetic model, not real domain models; no query-count assertions anywhere; e2e is a single smoke spec.

**Operations**
- Empty scheduler in a running scheduler container; no automated/tested backup-restore; no monitoring/alerting; `env:validate` not enforced in the pipeline; deploy/rollback lack a real post-deploy smoke test and a durable rollback tag source.

---

# Enterprise Readiness

**Production readiness: ≈55%.**

The backend application core is above-average for a pre-launch codebase (clean DDD, strong validation, correct indexes, idempotent webhook, real health probes, Horizon/S3/CloudFront wired, media authorization enforced). But enterprise production requires the perimeter and operations to be sound, and there the repository is materially incomplete: a live XSS→token chain, no web hosting, no backups, no CD, no monitoring/alerting, an empty scheduler, unthrottled sensitive endpoints, no privileged-action audit, and a fitness gate that would fail CI. The 55% reflects a solid engine in a vehicle with no brakes, no airbags, and no way to drive it off the lot.

---

# Release Readiness

**Can this repository safely ship to enterprise production today? No.**

The three conditions V1 declared mandatory before any public traffic — (1) close the XSS/token chain, (2) guarantee tenant data cannot leak, (3) have a tested backup/restore — are all still unmet. Independently of the tenancy intent question, shipping is blocked by at least: the unsanitized lesson-HTML sink combined with a token in `localStorage` and no CSP (account-takeover chain); the absence of any way to deploy the frontend; the absence of automated, tested backups; and the absence of CD and monitoring (no safe deploy, no incident visibility). Any one of these is disqualifying for enterprise use; all are present simultaneously. The decoupling work, while excellent, did not touch this list. **Do not release.**

---

# Top 25 Remaining Improvements

Ranked by ROI (impact ÷ effort). Effort: S ≤1d · M 2–3d · L 4–7d · XL 8–15d (one engineer). Owner roles drawn from the reviewing panel.

| # | Improvement | Impact | Complexity | Priority | Effort | Owner role |
|---|---|---|---|---|---|---|
| 1 | Sanitize lesson HTML server-side (and/or DOMPurify at the sink) before render | Critical (closes XSS half of the chain) | Low | P0 | S | Principal Security Architect |
| 2 | Add CSP + security headers to Next.js (`next.config` `headers()` / middleware) | Critical (defense-in-depth for the sink) | Low | P0 | S | Principal Frontend Architect |
| 3 | Install `deptrac` + `rector`, generate the baseline, make the CI arch job real | High (unblocks CI, activates governance) | Low | P0 | S | Principal Staff Engineer |
| 4 | Throttle commerce + certificate-verification endpoints (named limiters) | High (stops brute-force/enumeration) | Low | P0 | S | Principal Backend Engineer |
| 5 | Add `apps/web` production Dockerfile + `web` compose service | Critical (makes frontend deployable) | Low–Med | P0 | M | Principal DevOps Architect |
| 6 | Automate DB backup + restore (script or `spatie/laravel-backup`) + restore drill | Critical (data safety) | Med | P0 | M | Principal Infrastructure Architect |
| 7 | Move Sanctum token to httpOnly, Secure, SameSite cookie | Critical (closes token-theft half) | Med | P0 | M | Principal Security Architect |
| 8 | Move gateway `charge()`/`refund()` outside DB transactions | High (DB pool stability) | Med | P1 | M | Principal Backend Engineer |
| 9 | Make refund lock-guarded + idempotent (mirror webhook flow) | High (prevents double refund) | Low–Med | P1 | S | Principal Backend Engineer |
| 10 | Re-validate coupon under lock at checkout (close TOCTOU) | High (revenue integrity) | Low | P1 | S | Principal Backend Engineer |
| 11 | CD: push immutable SHA/semver image to a registry; wire deploy trigger | High (safe releases + real rollback) | Med | P1 | M | Principal DevOps Architect |
| 12 | Monitoring + alerting (error tracking, uptime, 5xx/queue/readiness alerts) | High (operability) | Med | P1 | L | Principal Platform Engineer |
| 13 | Privileged-action audit trail (actor + before/after) for role/refund/enroll/cert | High (compliance/forensics) | Med | P1 | M | Principal Compliance Architect |
| 14 | Populate scheduler (token/OTP prune, retries, digests, health self-checks) | Med–High (correctness of async) | Low | P1 | S | Principal Backend Engineer |
| 15 | SEO: `generateMetadata` on public pages + `sitemap.ts`/`robots.ts`/OG | High (organic reach) | Med | P1 | M | Principal Frontend Architect |
| 16 | Convert public read pages to RSC / server data-fetch | High (SEO + TTFB) | Med–High | P2 | L | Principal Frontend Architect |
| 17 | Confirm tenancy intent; if SaaS, add `organization_id`+scope to core models | Critical-if-SaaS | High | P0/P2 | XL | Principal Solution Architect |
| 18 | Add `middleware.ts` for edge auth/role gating + locale detection | Med–High (security + RTL) | Med | P2 | M | Principal Frontend Architect |
| 19 | Dependency/image/secret scanning in CI (composer audit, npm audit, Trivy, gitleaks, Dependabot) | Med–High (supply chain) | Low | P1 | S | Principal DevOps Architect |
| 20 | Query-count/N+1 regression tests on list endpoints | Med (perf durability) | Low–Med | P2 | M | Principal QA Architect |
| 21 | `$timeout` + `failed()` + retry/backoff on `ProcessExportJob` | Med (async reliability) | Low | P2 | S | Principal Backend Engineer |
| 22 | Resolve Analytics/Notifications event coupling via published projections | Med (extraction readiness) | Med–High | P2 | L | Principal DDD Architect |
| 23 | Finish `Domains`→`Contexts` naming unification | Med (DX/consistency) | Med | P3 | M | Principal Laravel Architect |
| 24 | Dynamic `lang`/`dir` + `global-error.tsx` + skip links + broaden axe coverage | Med (a11y/i18n/resilience) | Low–Med | P2 | M | Principal Accessibility Expert |
| 25 | Non-root API container user + wire `env:validate` into deploy/CI | Low–Med (hardening) | Low | P3 | S | Principal Infrastructure Architect |

---

# Final Verdict

**Would I approve this repository for enterprise production? No — not in its current state.**

I want to be precise and fair, because the trajectory matters. The engineering *discipline* on display since V1 is real and, frankly, better than most teams achieve: a proper hexagonal contracts layer, immutable cross-context DTOs, a concrete-model that is now genuinely encapsulated behind ports, and a self-critical `PROJECT_STATUS.md` that refuses to oversell itself. If the question were "is this a well-architected foundation a competent team can finish?", the answer is an emphatic yes, and almost nothing needs re-architecting.

But approval for *enterprise production* is a different bar, and the repository fails it on multiple independent axes at once. A stored-XSS sink feeds unsanitized instructor HTML into every learner's browser while the session token sits in `localStorage` behind no CSP — a textbook account-takeover chain that was the #1 blocker at V1 and is untouched. The frontend has no production hosting at all, so the product literally cannot be deployed as delivered. There is no tested backup/restore, no CD, no monitoring, and an empty scheduler running in a scheduler container. Sensitive endpoints (checkout, coupons, certificate verification) are unthrottled, privileged actions are unaudited, and payment refunds are not idempotent. And the one mechanism meant to protect the celebrated architecture — the Deptrac fitness gate — is not installed, has an empty baseline, and would fail CI on a clean install, meaning every boundary claim rests on manual grep rather than enforcement.

The decoupling program improved the codebase's *inside*. It did not touch its *perimeter* or its *operations*, which is where enterprise risk actually lives. Overall movement since V1 is about one point, and one new regression (the broken CI gate) was introduced. My recommendation: treat Top-25 items #1–#7 as a hard gate (roughly one to two focused sprints), confirm the multi-tenancy product intent (#17), then re-review. Until the XSS/token chain is closed, the frontend is deployable, backups are automated and tested, and CD + monitoring exist, this repository should not carry enterprise production traffic.

_Validation of runtime gates (Pest, PHPStan, Deptrac, Pint, `env:validate`): **Not verifiable from repository** in this environment. All architectural, security, and configuration findings above are derived from source files, configuration, dependency manifests, and git state, with file paths cited in the underlying evidence._

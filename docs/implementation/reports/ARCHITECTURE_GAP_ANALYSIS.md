# Architecture Gap Analysis — Intended vs. Actual

> Definitive architectural gap analysis before Sprint 2. Evidence-based only: every claim is derived from repository files (structure, `use`-import scans, configs, redesign/ADR/report contents). Statements that cannot be confirmed from the repository are marked **"Not verifiable from repository."**
>
> Method note: cross-context coupling figures below are **static `use`-import counts** taken from the backend source. A precise Deptrac violation count requires running Deptrac (dep not installed, baseline empty) and is **Not verifiable from repository**. No toolchain was executed while writing this report.

---

# Executive Summary

The repository contains a **working v1** (`VERSION` = `1.0.0-rc.1`) — a Laravel 12 modular monolith of ten domains with a Next.js 15 frontend, Filament admin, 93 migrations, 81 models, and ~262 test files — plus an **exceptionally complete enterprise redesign** (5 blueprints, master plan, backlog, 20 ADRs) and two executed sprints (Sprint 0 fitness tooling, Sprint 1 multi-tenancy foundation).

The gap is between the **intended** ports-and-events architecture (single-writer contexts, cross-context access only via ports/published events, projector-based read models, platform capabilities as ports, capability-gated multi-tenancy) and the **actual** v1, which integrates largely by **direct cross-context Eloquent model access**. Static scans confirm the redesign's central debt items in code: **Learning references Authoring/Catalog models 41 times** (`Lesson` ×22, `Course` ×8, `Section` ×6, `LessonMedia` ×5), **Authoring references `Catalog\Models\Course` ×14**, and **Analytics subscribes to concrete domain events** (TD-8). The redesign's cross-context ports (`CurriculumReadPort`, `AssessmentDefinitionPort`, `EntitlementPort`, `PlaybackPort`) are **absent**; CQRS is effectively **absent** (0 command/query buses, 1 projector/read-model file); and the enterprise contexts the redesign centers on — **Administration, Media, AI, Search platforms, and the Instructor/Organization splits — do not exist in code** (only 4 Integration contract stubs + tenancy foundation).

Sprint 0's fitness controls (Deptrac + PHPStan boundary rules) are written but **not operational** (deps uninstalled, baseline empty), so none of the above is currently enforced. Multi-tenancy is real but **partial** (global scope on 8 CRM models, unverified at runtime, indexes deferred). Frontend is ahead of backend in one dimension: `(instructor)` and `(organization)` route groups exist with no corresponding backend contexts.

**Verdict:** the design is sound and the v1 is a legitimate base, but the realised architecture is early-**Foundation** against the enterprise target. The correct move is **Go with Conditions**: make the fitness gates operational and close the P0 security items (Sprint 2) before building the ported contexts.

---

# Architecture Compliance Score

Scores are 0–100 for **realised-vs-intended** architecture (not design maturity, which is ~95). Each cites evidence.

| Dimension | Score | Evidence |
|-----------|:---:|----------|
| **DDD** | 68 | Real bounded contexts with Actions/Services/Policies; but boundaries are leaky (see below). |
| **Bounded Contexts** | 62 | Contexts separated under `app/{Domains,Contexts,Platform}`, yet 40+ cross-context model imports break single-writer ownership. |
| **Dependency Direction** | 52 | Upward/lateral coupling: Learning→Authoring/Catalog, Authoring→Catalog, Commerce→Learning. Deptrac rule (contexts → Shared + IdentityContracts only) is defined but **not enforced** (baseline empty). |
| **Ports & Adapters** | 45 | Provider-abstraction contracts exist per context (13 `Contracts/` dirs: e.g. PaymentGateway, PdfGenerator, PlaybackTokenProvider) + new tenancy ports. But the redesign's **cross-context** ports (Curriculum/AssessmentDefinition/Entitlement/Playback) are absent. |
| **CQRS** | 10 | 0 CommandBus/QueryBus/handlers found; writes and reads share Eloquent models. Not an intended pattern in v1. |
| **Events** | 58 | Domain events + subscribers exist (Certification←Learning, Notifications←many). No transactional outbox; Analytics binds to concrete events (TD-8); event-DTO purity Not verifiable from repository. |
| **Read Models** | 15 | 1 projector/read-model file found (Analytics subscriber); dashboards/progress computed live. Redesign's projectors not built. |
| **Multi Tenancy** | 62 | Global `TenantScope` + `BelongsToTenant` on 8 CRM models + bypass policy + leakage suite. Partial (non-CRM tenant data unscoped), indexes deferred, runtime pass Not verifiable. |
| **Identity** | 82 | Sanctum + MFA + spatie RBAC + policies; `IdentityContracts` seam started (ADR-20); bypass policy implemented. |
| **Administration** | 5 | No `app/Platform/Administration`. Design-only (ADR-05/06). |
| **Media Platform** | 20 | Mux/CloudFront/S3 playback embedded in Learning (`LearningMediaService`); no `PlaybackPort`/Media context. |
| **Search Platform** | 15 | Per-context DB lookups; no `SearchPort`/Search context. |
| **AI Platform** | 0 | No code; design-only (ADR-14). |
| **Integration Platform** | 15 | `app/Platform/Integration/Contracts` = 4 interface stubs (EventBus/Outbox/WebhookPublisher/MessageBroker). No implementation. |
| **Frontend Architecture** | 64 | Route groups + ESLint boundary config; TanStack Query; i18n. Auth token in localStorage (confirmed); `(instructor)`/`(organization)` groups exist without backend contexts. |
| **Filament Architecture** | 62 | 24 resource classes; data-map panel (no-branch discovery); "UI only" discipline. v4 resource pass pending (README). |
| **Infrastructure** | 66 | Docker dev+prod, CI (`api`/`architecture`/`web`/`image`) + `adr-validation`, health endpoints, deploy/rollback scripts. `architecture` job cannot pass until deps installed. |
| **Testing Architecture** | 56 | 72 Pest + 190 Vitest + tenancy suites. No CQRS/arch tests operational; coverage ungated; E2E = 1 smoke; suite pass Not verifiable from repository. |
| **Overall** | 50 | Realised architecture is early-Foundation vs the enterprise target; strong contexts + tenancy start, offset by pervasive model coupling, absent ports/CQRS/read-models, and unbuilt platform contexts. |

---

# Context-by-Context Gap Analysis

Estimated remaining work maps to the backlog (`docs/redesign/100_EXECUTION_BACKLOG.md`); calendar figures there are program-level, so remaining work is expressed in relative size.

### Catalog
- **Current:** Implemented v1 (courses, categories, taxonomy, publish state). No inbound coupling (0 cross-context model imports into Catalog).
- **Target:** Central reference/published-language context with `CurriculumReadPort`, course versioning, editions; relocated to `Contexts`.
- **Completed:** models, actions, HTTP, Filament (2 resources), publish-guard contract.
- **Missing:** CurriculumReadPort adapter, content versioning, relocation (D4).
- **Debt:** consumed by others via direct model access (see Learning).
- **Blocking:** none.
- **Remaining:** Medium. **Risk:** Medium. **Priority:** High (unblocks Learning ports).

### Authoring
- **Current:** Implemented v1 (sections, lessons, media, publish-guard). Couples to `Catalog\Models\Course` ×14.
- **Target:** owns definitions + versioning + assessment definitions behind ports; content lifecycle.
- **Completed:** models, services, publish-guard integration.
- **Missing:** AssessmentDefinitionPort, versioning, content workflows.
- **Debt:** 14 direct Course refs; no versioning.
- **Blocking:** none. **Remaining:** Large. **Risk:** Medium. **Priority:** High.

### Learning
- **Current:** Implemented v1 but the **most coupled** context: 41 direct imports of Authoring/Catalog models; `ProgressService`/`LessonAccessService`/`ContinueLearningService` query `Section::`/`Lesson::` and read `$lesson->media`/`$lesson->prerequisites()` directly; media providers embedded.
- **Target:** pure execution context behind CurriculumReadPort/AssessmentDefinitionPort/EntitlementPort/PlaybackPort; projector progress-v2; LRS; assessments; offline.
- **Completed:** enrollment/progress/session/engagement/playback v1.
- **Missing:** all ports, projector, LRS, assessments, gamification, offline.
- **Debt:** TD-1 (content-model coupling), TD-2 (sync recompute), TD-6 (embedded media).
- **Blocking:** depends on Catalog/Authoring/Media ports first.
- **Remaining:** Very large. **Risk:** High. **Priority:** High (Phase B core).

### Commerce
- **Current:** Implemented v1 (cart, pricing, coupons, orders, contracts, Stripe). Couples to `Catalog\Models\Course` ×3, `Learning\Models\Enrollment` ×1. No `DB::transaction` located in a scan of `app/Contexts/Commerce` (the audit's gateway-in-transaction claim is **not located here**; treat as audit-flagged, Not verifiable in this scan).
- **Target:** exactly-once entitlement via outbox; gateway outside tx; EntitlementPort; tenant billing config.
- **Missing:** outbox-backed grant, EntitlementPort, refund/revoke policy.
- **Debt:** direct course/enrollment refs; webhook idempotency Not verifiable here.
- **Blocking:** needs A4 outbox. **Remaining:** Medium. **Risk:** High. **Priority:** High.

### CRM
- **Current:** Implemented; 8 org-owned models now tenant-scoped (Sprint 1). No cross-context model imports.
- **Target:** split Organization out; CRM keeps sales record/timeline.
- **Missing:** Organization split; org roles/capabilities.
- **Debt:** Organization tangled inside CRM (TD-9).
- **Blocking:** none. **Remaining:** Medium. **Risk:** Medium. **Priority:** Medium (Sprint 11).

### Organization
- **Current:** Foundation Only — lives inside CRM; tenancy VOs/ports prepare the split.
- **Target:** standalone context (tenant business entity, roles, capabilities, lifecycle).
- **Missing:** the entire context. **Remaining:** Large. **Risk:** Medium. **Priority:** Medium.

### Instructor
- **Current:** Not Started (backend); teacher data lives in Catalog/Identity. Frontend has an `(instructor)` route group with no backend context.
- **Target:** own context + TeachingAuthority port; owns zero lesson data.
- **Missing:** the entire context. **Remaining:** Large. **Risk:** Medium. **Priority:** Medium (D2).

### Certification
- **Current:** Implemented v1 (templates, issuance, verification, auto-generate on `CourseCompleted`). Couples to `Catalog\Models\Course` ×3; subscribes to `Learning\Events\CourseCompleted`.
- **Target:** issues credentials; Learning stores references; recert.
- **Missing:** CertificateReference model in Learning; recert flow.
- **Debt:** 3 direct Course refs. **Remaining:** Small–Medium. **Risk:** Low. **Priority:** Medium.

### Live
- **Current:** Implemented v1; couples to `Catalog\Models\Course` ×1; emits session events.
- **Target:** relocate to Contexts; attendance→Learning.
- **Missing:** relocation (C10). **Remaining:** Small. **Risk:** Low. **Priority:** Low.

### Analytics
- **Current:** Implemented v1; **subscribes to concrete domain events** of Learning/Commerce/Live/Crm/Certification (event-import scan). No model coupling.
- **Target:** read-only; consume **published projections** (ADR-18).
- **Missing:** projection consumption; repoint subscriber.
- **Debt:** TD-8. **Remaining:** Medium. **Risk:** Medium. **Priority:** Medium (D5).

### Notifications
- **Current:** Implemented v1 (templates, channels, workflow engine, subscriber to many events).
- **Target:** delivery only; provider config via Administration.
- **Missing:** Administration-driven provider config. **Remaining:** Small. **Risk:** Low. **Priority:** Low.

### Identity (Platform)
- **Current:** Implemented (auth, MFA, RBAC, OTP, devices); `IdentityContracts` seam + role-based tenancy-bypass policy.
- **Target:** contracts-only surface to contexts; SSO.
- **Missing:** SSO; contexts' `User`-model usage burn-down (baselined).
- **Debt:** contexts import `Identity\Models\User` (ADR-20 target). **Remaining:** Medium. **Risk:** Medium. **Priority:** Medium.

### Administration / Media / AI / Search (Platform)
- **Current:** Not Started / embedded. **Target:** dedicated contexts/ports. **Missing:** everything. **Remaining:** Very large. **Risk:** High (Administration/Integration), Medium (Media/Search), Low (AI). **Priority:** Administration High (Sprint 3), Media/Search High (Sprint 4), AI Low (Sprint 13).

### Integration (Platform)
- **Current:** Foundation Only — 4 contract stubs. **Target:** outbox/eventbus/webhook impl. **Missing:** all implementation. **Remaining:** Medium. **Risk:** High (guaranteed events depend on it). **Priority:** High (Sprint 2/4).

---

# Cross Context Violations

Measured by static `use`-import scan of `apps/api/app`. Under the intended rule (a context may depend only on **Shared** and **IdentityContracts**), all of the below violate the target architecture. Precise Deptrac counts are **Not verifiable from repository** (Deptrac not run).

| # | Source | Destination | Reason | Severity | Recommended fix |
|---|--------|-------------|--------|:---:|-----------------|
| 1 | Learning | `Authoring\Models\Lesson` (×22), `Section` (×6), `LessonMedia` (×5) | Reads content structure + media directly (TD-1/TD-6) | **Critical** | `CurriculumReadPort` + `PlaybackPort` (Sprint 4/5) |
| 2 | Learning | `Catalog\Models\Course` (×8) | Reads course directly for progress/access | High | `CurriculumReadPort` (course ref by id+version) |
| 3 | Authoring | `Catalog\Models\Course` (×14) | Direct course model access | High | Reference Course by id; publish via existing guard port |
| 4 | Commerce | `Catalog\Models\Course` (×3), `Learning\Models\Enrollment` (×1) | Direct course + enrollment access | High | `EntitlementPort`; course ref |
| 5 | Certification | `Catalog\Models\Course` (×3) | Direct course access | Medium | course ref |
| 6 | Live | `Catalog\Models\Course` (×1) | Direct course access | Low | course ref |
| 7 | Analytics | concrete `Events\*` of Learning/Commerce/Live/Crm/Certification | Subscribes to internal events, not published projections (TD-8) | High | Consume `LearningAnalyticsProjection` / published projections (ADR-18) |
| 8 | Certification / Commerce / Notifications | others' `Events\*` (e.g. `Learning\Events\CourseCompleted`) | Cross-context event subscription (published language) | Medium | Move event DTOs to a Shared published-language package OR relax Deptrac for `Events` namespace + keep DTOs |
| 9 | Kernel (`app/Providers`, `app/Filament`) | context Models (Commerce/Learning/Catalog/Crm/Live/Notifications) | Dashboard widget + provider wiring read context models directly | Medium | Read models for the widget; keep composition-root wiring |
| 10 | All contexts | `Identity\Models\User` | Depend on Identity implementation, not contracts (ADR-20) | Medium | Identity ports; burn baseline down |
| 11 | Frontend | `(instructor)` / `(organization)` route groups | Feature slices with no backend context (design-ahead) | Low | Land backend contexts (D1/D2) or gate the routes |

Aggregate: **~70+ cross-context model/event import sites** across contexts (Learning alone = 41 model imports). All are absorbed into the Deptrac baseline in Sprint 0's design and are intended to burn down through Phases A–D.

---

# Platform Layer Analysis

| Platform component | State | Evidence |
|--------------------|-------|----------|
| **Shared** | **Implemented** | `app/Platform/Shared` (base classes, VOs, traits, tenancy, Blueprint macros, providers). The stable kernel. |
| **Identity** | **Implemented** | auth/MFA/RBAC/OTP/devices + `Contracts` + tenancy-bypass policy. |
| **Integration** | **Foundation** | `app/Platform/Integration/Contracts` = 4 interface stubs only; no implementation. |
| **Notifications** | **Implemented** | templates/channels/workflow/subscriber. |
| **Administration** | **Missing** | no `app/Platform/Administration` directory. |
| **Media** | **Missing** (as a platform) / embedded in Learning | `LearningMediaService` + `Playback/*` providers live inside Learning. |
| **Search** | **Missing** (as a platform) / embedded | per-context DB queries; no `SearchPort`. |
| **AI** | **Missing** | no code. |

---

# Enterprise Readiness Matrix

| Capability | Status | Evidence / gap |
|------------|--------|----------------|
| **White Label** | Missing (Foundation) | `TenantBranding` VO only; no brand resolver/panels wired. |
| **SSO** | Missing | no SSO provider code (design D3). |
| **RBAC** | **Supported** | spatie roles + per-aggregate policies. |
| **Audit** | Partially Supported | Identity audit logging exists; no cross-context Audit Center (A3). |
| **Capabilities** | Missing | no capability system (ADR-06). |
| **Feature Flags** | Partially Supported | `config/features.php` exists; no per-tenant capability/flag runtime. |
| **Branding** | Foundation | `TenantBranding` VO; no application. |
| **Provisioning** | Foundation | lifecycle VOs + ports; no `tenants` table / workflow. |
| **Marketplace** | Missing | design-only (Phase F). |
| **Localization** | **Supported** | AR/EN i18n + RTL, `src/lib/i18n`, dictionaries. |
| **Offline** | Missing | design-only (B6). |
| **LRS** | Missing | design-only (B4). |
| **AI** | Missing | design-only (E). |
| **Analytics** | Partially Supported | v1 dashboards/reports/exports; not projection-based (ADR-18 pending). |
| **Commerce** | **Supported (v1)** | cart/pricing/coupons/orders/contracts + Stripe; entitlement hardening pending (C1). |

---

# Top 25 Architecture Risks

Ranked by severity × likelihood × proximity to shipping the target.

1. **Fitness gates not operational** — Deptrac/Rector uninstalled, baseline empty → boundaries unenforced; all coupling can grow silently.
2. **Auth token in localStorage** (confirmed `web/src/lib/api/client.ts`) → XSS token theft.
3. **Payment gateway/webhook correctness** (audit-flagged; gateway-in-tx not located here) → financial/entitlement inconsistency risk; webhook idempotency Not verifiable.
4. **Learning↔content coupling (41 imports)** → cannot evolve content or scale progress independently (TD-1/TD-2).
5. **No transactional outbox** → money/entitlement/credential events can be lost/duplicated.
6. **Administration context absent** → no capabilities, audit center, provisioning, ops → no enterprise operability.
7. **Multi-tenancy partial + unverified** → cross-tenant exposure for unscoped (non-CRM) data.
8. **Analytics couples to concrete events (TD-8)** → BI breaks on domain internals changes.
9. **Media embedded in Learning (TD-6)** → raw storage-id leakage risk; no single signing boundary.
10. **CQRS/read-models effectively absent** → live cross-context joins; dashboard/progress performance ceiling.
11. **Composite tenant indexes deferred** → slow tenant-scoped queries at scale.
12. **Test-suite pass status unknown** (Not verifiable) → no regression assurance for a release.
13. **Lockfile drift** (composer/npm vs new dev-deps) → CI install failures.
14. **Integration Platform unimplemented** → outbox/eventbus/webhook are contracts only.
15. **Identity-implementation coupling (ADR-20)** → contexts import `User` model; brittle to identity changes.
16. **Frontend ahead of backend** (`instructor`/`organization` groups without contexts) → dead/half-wired UX.
17. **Filament v4 resource pass pending** → admin operability gaps.
18. **Secrets in env** (audit) → weaker secret hygiene/rotation.
19. **No capability/permission/flag layering** → cannot gate enterprise features per tenant.
20. **Event-DTO purity unverified** → risk of Eloquent crossing boundaries in events.
21. **Organization/Instructor splits pending** → ownership tangles persist (TD-9).
22. **Observability incomplete** (health/logs only) → limited production diagnosis.
23. **Blue/green + rollback manual** → riskier deploys at scale.
24. **Backend relocation incomplete** (Catalog/Authoring/Certification/Live still under `Domains`) → taxonomy inconsistency, larger future diffs.
25. **Single-committer bus factor** (git) → knowledge concentration (mitigated by strong docs).

---

# Technical Debt Ranking

**Critical** (blocks a safe release / enterprise correctness):
- Fitness gates not operational (Deptrac/PHPStan) — governance inert.
- Auth token in localStorage (TD-4) — confirmed.
- No transactional outbox for guaranteed events.
- Payment/webhook correctness (TD-5) — audit-flagged; verify.

**High** (structural coupling / scalability):
- Learning↔Authoring/Catalog model coupling (TD-1) — 41 imports.
- Synchronous progress recompute (TD-2).
- Media embedded in Learning (TD-6).
- Analytics concrete-event coupling (TD-8).
- Partial tenancy + missing indexes.

**Medium**:
- Identity-implementation coupling (ADR-20 burn-down).
- Authoring→Catalog Course ×14, Commerce/Certification/Live→Course.
- Kernel/dashboard widget reads context models.
- Backend relocation incomplete (Catalog/Authoring/Certification/Live under `Domains`).
- CQRS/read-models absent (per redesign target).

**Low**:
- Lockfile drift.
- Frontend `instructor`/`organization` groups without backend.
- Filament v4 resource pass pending.

---

# Recommended Execution Order

Exactly as `docs/redesign/100_EXECUTION_BACKLOG.md` sequences it, matched to the measured reality above.

1. **Operational close of Sprint 1 (local, ~1 day):** install `deptrac`/`rector`; generate Deptrac + PHPStan baselines (which will capture the ~70 measured cross-context imports as the burn-down ledger); `npm install`; run `pint`/`phpstan`/`deptrac`/`php artisan test`/web `lint`/`typecheck`/`e2e`; commit baselines + lockfiles; push; enable branch protection. **This is the precondition for everything** — without it the gates that would prevent further erosion do nothing.
2. **Sprint 2 — A4 + A6 (P0):** transactional outbox + DLQ + idempotency; httpOnly-cookie auth (Risk 2); gateway-outside-transaction + idempotent webhooks (Risk 3); secrets externalisation. Closes the top security/correctness risks.
3. **Sprint 3 — A3 Administration + tenancy completion:** capabilities/flags/audit-center/ops; tenant provisioning against a `tenants` table; add deferred composite indexes; adopt tenancy on remaining tenant-owned data (leakage suite as gate).
4. **Sprint 4 — A5 platform ports + relocations:** Media/Search/Integration behind ports (extract media from Learning — Risk 9); relocate Catalog/Live to `Contexts` (C9/C10), gated on tests + Deptrac.
5. **Phase B (Sprints 5–10):** CurriculumReadPort/AssessmentDefinitionPort/EntitlementPort + content versioning (kills TD-1); progress-v2 projector (TD-2); assessments; LRS; gamification; offline; learning frontend.
6. **Phase C–D:** Commerce entitlement hardening; Organization + Instructor splits (align backend with the existing frontend groups); SSO/white-label/multi-panel; Analytics repoint (TD-8).
7. **Phase E–G:** AI, Marketplace, Global — only on the stabilised spine.

Non-negotiable per `101_EXECUTION_RULES.md`: one story at a time, additive/reversible, flag-gated, Deptrac/PHPStan/tests green before merge, ADR-referenced.

---

# Final Recommendation

**Go with Conditions.**

Justification (from evidence): the architecture is well-designed and the v1 is a real, testable base — but the realised system is early-Foundation against the enterprise target, and the controls that would keep it on track are **written but off**. Building new ported contexts now, on top of unenforced boundaries and open P0 security items, would compound debt.

**Conditions to proceed (all verifiable, ~1 sprint + 1 day):**
1. Make the fitness gates operational and green (install deps, generate baselines, run all gates), commit baselines/lockfiles, push, and require the `api`/`architecture`/`web`/ADR checks on `main`.
2. Confirm the test suite passes and the Sprint-1 tenancy leakage suite is green (currently Not verifiable from repository).
3. Complete Sprint 2 (A4 outbox + A6 security: cookie auth, gateway-outside-tx, secrets) before any Phase-B feature work.
4. Correct external messaging so `1.0.0-rc.1` is understood as "solid v1 + early enterprise-foundation," not a finished enterprise product.

Meet these four conditions and the program is a **Go** for Sprint 2 → Administration → Phase B, on the schedule in the master plan. The dominant risk remains **execution discipline, not architecture.**

---

## Validation

- All findings are derived from repository evidence: static `use`-import scans of `apps/api/app`, directory/structure inspection, config files, and the contents of `docs/redesign/*`, `docs/adr/INDEX.md`, `PROJECT_STATUS.md`, and the sprint reports.
- Items that could not be confirmed from the repository are marked **"Not verifiable from repository"** (test pass status, coverage %, precise Deptrac violation counts, gateway-in-transaction presence, webhook idempotency, deployment status, event-DTO purity).
- No existing file was modified. Only `docs/implementation/reports/ARCHITECTURE_GAP_ANALYSIS.md` was created.

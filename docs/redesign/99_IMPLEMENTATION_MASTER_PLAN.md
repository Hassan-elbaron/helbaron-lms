# Implementation Master Plan (Final Phase)

> Execution blueprint. No application code, no architecture changes, no API/DB changes here — this document turns redesigns 01–05 into an executable engineering roadmap.
> Reads and builds on: `01_CATALOG` · `02_CRM_ORGANIZATION` · `03_INSTRUCTOR_AUTHORING` · `04_LEARNING` · `05_ADMINISTRATION_ENTERPRISE`.

---

# Executive Summary

The architecture is complete and internally consistent across five redesign documents. What remains is **execution**: transforming a working-but-narrow modular monolith into the multi-tenant, event-driven, offline-capable, AI-assisted platform the redesigns describe — **without a rewrite**. Every step in this plan is **additive and reversible**; the live system keeps running while capability is layered in behind ports, flags, and projections.

The codebase today is healthy foundation, not greenfield and not legacy: Laravel 12 with ten bounded contexts + a Platform layer, 100 migrations, ~68 backend test files; Next.js 15 with ~190 frontend test files; PHPStan (larastan) + Pint + Pest + a CI workflow already wired. It is missing the enterprise spine the redesigns define — Administration context, capability system, tenant-isolation-by-construction, transactional outbox, content versioning, Learning ports/LRS/attempts/offline, Organization/Instructor context splits, and architecture-fitness enforcement (Deptrac).

This plan sequences that work into **7 phases (A–G)**, **~18 epics**, a Work Breakdown Structure down to subtasks, a single recommended execution order, milestones with acceptance criteria, a team model, repository/quality/testing/CI-CD strategy, risks, success metrics, a multi-level Definition of Done, and three timeline scenarios. The non-negotiable spine is **Phase A (Platform Foundation)** — capabilities, tenant isolation, outbox, and Deptrac — because every later phase depends on it and because it closes the known security-audit findings.

**Bottom line:** ship Phase A first (foundation + security fixes), then Core LMS (Learning redesign), then monetization and enterprise, then AI/marketplace/global. Recommended timeline to enterprise-ready (through Phase D): **~9–12 months** with the recommended team.

---

# Current Project State

Maturity scored 1–5 (1 = absent, 3 = works but narrow, 5 = target). Scores are grounded in the actual repository.

| Dimension | Score | Evidence | Gap to target |
|-----------|:---:|----------|---------------|
| **Backend** | 3.5 | Laravel 12; 10 contexts + Platform (Shared/Identity/Notifications); 100 migrations; Actions/Services/Policies per context; Filament data-map panel | missing ports for content/media/entitlement; synchronous progress recompute; no outbox; no Administration context |
| **Frontend** | 3.5 | Next.js 15 App Router; route-groups restructure done; ~190 test files; i18n (en/ar, RTL); TanStack Query; design tokens | no offline/multi-device; token-in-localStorage; no E2E; assessment/gamification UIs absent |
| **Database** | 3 | 100 migrations; per-context schemas; soft deletes, casts | tenant isolation is manual `where` (risk); no version-keyed content; no LRS/attempt tables; no read-model tables |
| **DDD maturity** | 3.5 | real bounded contexts; events; Actions; publish-guard port (Catalog↔Authoring) | cross-context reads via foreign Eloquent models (Learning→Authoring/Catalog); definitions vs execution not yet split for assessments |
| **Architecture maturity** | 3 | modular monolith; provider ordering; Platform extraction (Shared/Identity/Notifications); Learning/Commerce/Analytics moved to `Contexts` | **no Deptrac** (boundaries unenforced); Media/AI/Search/Integration embedded not ported; Analytics couples to concrete domain events |
| **Security maturity** | 3 | Sanctum; MFA on admin; policies; security headers/CSP; env validation; `.env*` gitignored; named rate limiters | **token in localStorage** (XSS chain); **manual tenant scoping** (TEN-1); **webhook processing risk / gateway-in-transaction**; secrets in env not vault |
| **Performance maturity** | 2.5 | caching present; queues/Horizon; indexes | O(lessons) synchronous progress recompute on write; no read models for dashboards; live cross-context joins |
| **Testing maturity** | 3 | Pest (~68 files) + Vitest (~190 files); factories/seeders; contract tests for gateways/signed URLs | **no E2E** (no Playwright); no architecture tests; no performance/load tests; coverage not gated |
| **DevOps maturity** | 3.5 | Docker dev + prod; `docker-compose.prod.yml`; CI (`ci.yml`); deploy/rollback scripts; health endpoints; backups scripted | single CI workflow (no arch-validation stage); no blue/green automation; no K8s manifests; no DLQ tooling surfaced |
| **Technical debt** | — | see register below | manageable, mostly "missing abstraction" not "wrong code" |

## Technical debt register (from audits + redesigns)

| ID | Debt | Severity | Redesign fix |
|----|------|:---:|--------------|
| TD-1 | Learning reads Authoring/Catalog models directly for logic | High | 04: CurriculumReadPort/AssessmentDefinitionPort |
| TD-2 | Synchronous full-course progress recompute on write | High | 04/ADR-10: projector off write path |
| TD-3 | Manual per-query tenant scoping (TEN-1) | High (security) | 05/ADR-07: global scope |
| TD-4 | Web auth token in localStorage (XSS→theft) | High (security) | 05: httpOnly cookies |
| TD-5 | Payment gateway call inside DB transaction; webhook idempotency | High (security/consistency) | 05/ADR-16: Integration Platform, outbox |
| TD-6 | Media playback providers embedded in Learning | Medium | 04/ADR-08: PlaybackPort |
| TD-7 | No Deptrac — boundaries unenforced | Medium | this plan: adopt Deptrac in CI |
| TD-8 | Analytics subscribes to concrete domain events | Medium | 05/ADR-18: consume published projections |
| TD-9 | Organization tangled in CRM; Instructor tangled in Catalog/Identity | Medium | 02/03: context splits |
| TD-10 | No E2E / architecture / load tests | Medium | this plan: testing strategy |
| TD-11 | Assessments/gamification/paths/offline entirely absent | Feature gap | 03/04: new modules |

---

# Work Breakdown Structure (WBS)

Epics map to roadmap phases (05). Complexity: S/M/L/XL. Priority: P0 (blocker) → P3. Risk: Low/Med/High. Dependencies reference other epics.

## PHASE A — Platform Foundation

### EPIC A1 — Architecture Fitness & Tooling  ·  Complexity M · Priority P0 · Risk Low · Deps: none
- **Feature A1.1 — Deptrac boundary enforcement**
  - Tasks: define layers (Domains/Contexts/Platform) + ruleset from 05 dependency matrix; baseline current violations; wire into CI (fail on new violations).
    - Subtasks: install deptrac; author `deptrac.yaml`; classify each context; encode forbidden deps (no cross-context `Models`, Analytics read-only, Filament no-logic); generate baseline; CI stage.
- **Feature A1.2 — Static analysis hardening**: raise PHPStan level incrementally; shrink baseline; add Rector (dry-run) for consistency; Pint enforced.
- **Feature A1.3 — Frontend fitness**: ESLint boundaries plugin (import rules per route-group); `tsc --noEmit` gate; add Playwright scaffold.
- **Feature A1.4 — ADR validation**: ADR index + lightweight lint (every PR touching architecture links an ADR).

### EPIC A2 — Multi-Tenancy & Isolation  ·  XL · P0 · High · Deps: A1
- **A2.1 Tenant model & global scope**: introduce tenant context resolution (host/user/org); global scope + policy on tenant-scoped models; remove manual `where org_id`.
  - Subtasks: tenant resolver middleware; `BelongsToTenant` trait/scope; audit every tenant-scoped model; migration-safe backfill; regression tests proving cross-tenant denial.
- **A2.2 Tenant provisioning (Administration)**: provision/suspend/limits; usage metering hooks; `TenantProvisioned/Suspended` events (outbox).
- **A2.3 Isolation test harness**: automated cross-tenant leakage tests as a permanent CI gate.

### EPIC A3 — Administration Context  ·  L · P0 · Med · Deps: A2
- **A3.1 Settings & Global Config** (+ `ConfigPort`).
- **A3.2 Feature Flags & Capabilities** (+ `CapabilityPort`; capability→permission checks at entry points, default-on).
- **A3.3 Audit Center**: consume `*Audited` events; retention policies; search/export.
- **A3.4 Providers & Secrets registry** (`SecretsPort`; encrypted provider configs; API keys).
- **A3.5 Ops Console**: queues/jobs/scheduler/health/maintenance-mode/alerts + DLQ surface.
- **A3.6 Branding & multi-brand config** (feeds Filament + web white-label).

### EPIC A4 — Eventing Backbone (Outbox + DLQ)  ·  L · P0 · High · Deps: A1
- **A4.1 Transactional outbox** for guaranteed events (money/entitlement/credentials/tenant): write event+state in one tx; relay publisher.
- **A4.2 Idempotency store & consumer dedupe** (eventId/clientMutationId).
- **A4.3 DLQ + retry policy** per event class; Ops Console visibility; alerts.
- **A4.4 Event versioning convention** (additive v1/v2 payloads).

### EPIC A5 — Platform Capability Ports  ·  L · P0 · Med · Deps: A3
- **A5.1 Media Platform** behind `PlaybackPort`/`MediaPort` (wrap existing Mux/CloudFront/S3; move Learning's embedded providers out — TD-6).
- **A5.2 Integration Platform** (`WebhookPort`/`OutboxPort`; move gateway calls out of DB tx — TD-5; HMAC verify; idempotent inbound).
- **A5.3 Search Platform** (`SearchPort`; wrap Postgres FTS; per-context index ownership).
- **A5.4 AI Platform** (`AIProvider` port + safety/moderation + cost governance; no autonomous action).

### EPIC A6 — Security Hardening  ·  M · P0 · High · Deps: A2
- **A6.1 Auth token → httpOnly cookies** (TD-4) across Next.js + Sanctum.
- **A6.2 Webhook security & idempotency** (TD-5) via Integration Platform.
- **A6.3 Secrets externalization** (env → Vault/SSM path).
- **A6.4 Impersonation** (Administration-initiated, Identity-enforced, audited, time-boxed).

## PHASE B — Core LMS (Learning Redesign)

### EPIC B1 — Content Ports & Versioning  ·  XL · P1 · High · Deps: A5
- **B1.1 CurriculumReadPort** (Catalog/Authoring adapter over existing reads — TD-1).
- **B1.2 AssessmentDefinitionPort** (Authoring).
- **B1.3 Content versioning** (copy-on-write; version pinning; major-no-auto-propagate — ADR-09).
- **B1.4 EntitlementPort** (Commerce/Catalog; replace `is_preview`/paid logic in Learning).

### EPIC B2 — Progress Engine v2  ·  L · P1 · Med · Deps: B1, A4
- **B2.1 Version-keyed curriculum snapshot cache.**
- **B2.2 Projector-based rollup** off the write path (TD-2, ADR-10); `CourseProgressView`.
- **B2.3 Read models**: Dashboard, ContinueLearning, Gradebook (rebuildable from ledger).

### EPIC B3 — Assessment Execution  ·  XL · P1 · High · Deps: B1
- Attempts/submissions/grades aggregates; state machine; version pinning; auto/manual/peer grading; retries/late; online-only exams. (TD-11)

### EPIC B4 — Learning Record Store & Analytics Projection  ·  L · P2 · Med · Deps: B2
- Append-only experience-event ledger; xAPI mapping; SCORM/cmi5 normalization; GDPR crypto-shred; publish `LearningAnalyticsProjection`.

### EPIC B5 — Gamification, Paths, Mastery  ·  L · P2 · Med · Deps: B2
- Event-sourced XP/badges/streaks/leaderboards (materialized); path execution; competency/skill mastery from evidence.

### EPIC B6 — Offline & Multi-Device Sync  ·  XL · P2 · High · Deps: B2, B3
- clientMutationId idempotency; version vectors; per-aggregate merge; sync push/pull; conflict quarantine; offline media licenses. (ADR-15)

### EPIC B7 — Learning Frontend  ·  L · P2 · Med · Deps: B2–B6
- Player + progress + attempts UI; dashboard from read models; offline shell; accessibility profile application.

## PHASE C — Commerce

### EPIC C1 — Entitlement & Payments Hardening  ·  L · P1 · High · Deps: A4, A5, B1
- Guaranteed `OrderPaid→EnrollmentGranted` (outbox); refund→revoke policy; gateway outside tx; idempotent webhooks; tenant billing config.

### EPIC C2 — Commerce Frontend & Ops  ·  M · P2 · Med · Deps: C1
- Checkout/orders/contracts UI parity; billing config admin; dunning/retry surfaces.

## PHASE D — Enterprise

### EPIC D1 — Organization Context Split  ·  XL · P1 · High · Deps: A2, A3 · (redesign 02, chunk C11)
- Split Organization from CRM (folder+namespace move, no schema/API change); org roles/capabilities; org audit trail; consulting request vs delivery.

### EPIC D2 — Instructor Context Split  ·  L · P2 · Med · Deps: A3 · (redesign 03)
- Extract Instructor from Catalog/Identity; `TeachingAuthority` port; teaching assignments/revenue refs/reviews; **owns zero lesson data**.

### EPIC D3 — SSO, White-Label, Multi-Panel  ·  L · P2 · Med · Deps: A3, A6
- SSO providers (Administration config); Filament instructor/org panels; brand resolution end-to-end.

### EPIC D4 — Backend Relocation Chunks  ·  M · P1 · Med · Deps: A1(Deptrac) · (chunks C9/C10)
- C9 Catalog→Contexts; C10 Live→Contexts; Filament map one-line updates; each gated on `php artisan test` green + Deptrac.

### EPIC D5 — Analytics Repoint  ·  M · P2 · Low · Deps: B4 · (ADR-18)
- Consume published projections instead of concrete domain events; remove coupling.

## PHASE E — AI  ·  Deps: A5(AI), B4
- **E1** Authoring AI generation behind approval gate · **E2** Adaptive engine (`AdaptivePolicyPort`, rules→ML) · **E3** AI tutoring signals · **E4** AI analytics narratives. All human-in-the-loop, capability-gated, audited.

## PHASE F — Marketplace  ·  Deps: C, D, E
- **F1** instructor marketplace + discovery · **F2** revenue sharing · **F3** external/partner instructors · **F4** sandboxed UI/Filament plugins · **F5** content licensing (copy/fork).

## PHASE G — Global Expansion  ·  Deps: all
- **G1** full i18n/l10n + RTL completion · **G2** data residency per region · **G3** regional providers · **G4** compliance tooling (residency/erasure/consent) · **G5** multi-region deployment + DR.

---

# Implementation Order

**The only recommended execution order** (top-to-bottom); items on the same line are parallelizable.

```
0. Prereq (always-on): A1 tooling + Deptrac baseline  ── gate every later PR
1. A2 Tenancy/isolation            (P0, blocks everything multi-tenant)
2. A4 Outbox/DLQ  ∥  A6 Security fixes (TD-4/TD-5)     (parallel; both P0)
3. A3 Administration context        (needs A2)
4. A5 Platform capability ports     (needs A3)  ──►  D4 relocation chunks C9/C10 can run in parallel here (needs A1)
5. B1 Content ports & versioning    (needs A5)
6. B2 Progress Engine v2  ∥  C1 Entitlement/payments hardening  (B2 needs B1+A4; C1 needs A4+A5+B1)
7. B3 Assessment execution          (needs B1)
8. B4 LRS/projection  ∥  B5 Gamification/paths/mastery          (need B2)
9. B6 Offline/multi-device          (needs B2+B3)
10. B7 Learning frontend  ∥  C2 Commerce frontend               (need their backends)
11. D1 Organization split  ∥  D2 Instructor split               (need A2/A3; independent of each other)
12. D3 SSO/white-label/multi-panel  ∥  D5 Analytics repoint      (D3 needs A3/A6; D5 needs B4)
13. E (AI)                          (needs A5-AI + B4)
14. F (Marketplace)                 (needs C+D+E)
15. G (Global)                      (needs all)
```

**Prerequisites, hard rules:**
- Nothing in B/C/D starts before **A2 isolation** and the **Deptrac gate (A1)** are green.
- **A4 outbox** precedes any guaranteed-delivery work (C1 entitlement, Certification issuance).
- **B1 ports** precede B2/B3 (Learning must not read content models once B starts).
- Context **splits (D1/D2)** and **relocations (D4)** are folder+namespace moves gated on tests + Deptrac — schedule in low-churn windows.
- Security fixes (A6) are P0 and must not wait for a phase boundary.

**Parallelizable at any time (low coupling):** A1 tooling, documentation, test-backfill, frontend design-system work, i18n string extraction.

---

# Milestones

| Milestone | Goals | Key deliverables | Acceptance criteria |
|-----------|-------|------------------|---------------------|
| **M1 — Fitness Gate** | boundaries enforced; security-fix track opened | Deptrac in CI (baseline); PHPStan level bump; Playwright scaffold | CI fails on new boundary violation; static analysis green; ADR-link check active |
| **M2 — Tenant Isolation** | isolation by construction | global scope on all tenant models; cross-tenant leakage tests | no manual `org_id where` remains; leakage suite green in CI; TEN-1 closed |
| **M3 — Secure Foundation** | close security-audit findings | httpOnly-cookie auth; Integration Platform; outbox+DLQ | TD-4/TD-5 closed; guaranteed events survive worker kill; webhook idempotent + verified |
| **M4 — Administration Live** | operator cockpit + capabilities | Administration context; CapabilityPort/ConfigPort/SecretsPort; Audit Center; Ops Console | capabilities gate a feature per tenant; audit search/export works; maintenance mode broadcast |
| **M5 — Content Ports & Versioning** | Learning decoupled from content models | Curriculum/AssessmentDefinition/Entitlement ports; content versioning | Deptrac: Learning has zero logic-dep on Authoring/Catalog models; attempts pin versions |
| **M6 — Learning Core v2** | scalable progress + assessments | Progress Engine v2 (projector); assessment execution; read-model dashboards | progress write O(1) on request path; dashboards rebuildable; grade stable across re-version |
| **M7 — LRS + Engagement** | ledger, gamification, paths, mastery | LRS + xAPI export; gamification; paths; mastery | any projection drop-and-replay verified; XP idempotent; competency from evidence only |
| **M8 — Offline & Multi-Device** | offline-first learning | sync push/pull; merge; offline media | completion never regresses on merge; conflicts quarantined; exams online-only enforced |
| **M9 — Commerce Hardened** | reliable monetization | guaranteed entitlement; refund/revoke; billing config | paid enrollment exactly-once; financial events retained; gateway outside tx |
| **M10 — Enterprise** | multi-tenant enterprise features | Organization + Instructor splits; SSO; white-label; C9/C10 relocations | Instructor owns zero lesson data; SSO live; per-brand panels; Deptrac green post-split |
| **M11 — AI** | human-in-the-loop AI | authoring gen (gated); adaptive (advisory); AI analytics | no AI output to learners/grades without human approval; capability-gated; audited |
| **M12 — Marketplace** | open ecosystem | marketplace; revenue share; sandboxed plugins | external instructors safe; payouts correct; plugins presentation-only |
| **MFinal — Global GA** | global, compliant, multi-region | i18n complete; data residency; multi-region DR | residency honored per region; localized E2E; DR drill passes RPO/RTO |

---

# Team Structure

Recommended steady-state team for the **recommended** timeline (scale down for aggressive-single-squad, up for parallel phases).

| Role | Count | Focus |
|------|:---:|-------|
| **Solution/Chief Architect** | 1 | owns redesigns 01–05, ADRs, Deptrac ruleset, reviews every boundary-touching PR, arbitration |
| **Backend Engineers (Laravel/DDD)** | 3–4 | contexts, ports, outbox, Administration, Learning core, Commerce; 1 senior per phase-critical epic |
| **Frontend Engineers (Next.js/TS)** | 2–3 | learning player, offline shell, dashboards, admin panels parity, white-label |
| **QA / SDET** | 1–2 | Pest/Vitest, Playwright E2E, isolation & merge test harnesses, performance/load |
| **DevOps / Platform** | 1 | CI/CD stages, blue/green, K8s readiness, Horizon scaling, DLQ/observability, backups/DR |
| **UI/UX Designer** | 1 | assessment/gamification/offline flows, accessibility, brand system |
| **Product Owner** | 1 | phase scope, capability/flag rollout, tenant/enterprise priorities, exit-criteria sign-off |
| **Technical Writer** | 0.5–1 | API/OpenAPI docs, runbooks, ADR upkeep, migration guides |

**Squad shape:** two streams — **Platform stream** (A + capability ports + DevOps) and **Learning stream** (B + frontend). Commerce/Enterprise/AI pulled in as their prerequisites land. The Architect is shared and gates both.

---

# Repository Strategy

- **Branching model:** trunk-based with short-lived feature branches; `main` always releasable; release via tags. Long/risky refactors (context splits, isolation retrofit) behind **feature flags**, merged small and often (never a multi-week branch — proven painful with the bracket-route/PowerShell incidents).
- **Commit conventions:** Conventional Commits (`feat:`, `fix:`, `refactor:`, `chore:`, `test:`, `docs:`), scope = context (`feat(learning): …`); reference epic/task id; ASCII-only in scripts (documented gotcha).
- **Release strategy:** semantic versioning (currently `1.0.0-rc.1`); release train per milestone; changelog generated from commits; expand-and-contract migrations only (never destructive in one release).
- **Migration strategy:** every DB migration is **expand → migrate → contract** across two releases; never rename/drop in the same deploy as the code that stops using it; backfills are online/batched; each context split is folder+namespace move (PSR-4, `composer dump-autoload`) gated on tests — no schema change.
- **Feature flag strategy:** capabilities (tenant) + flags (operational) from Administration; every new subsystem ships **flag-off**, enabled per-tenant progressively; flags are removed once a feature is GA (no permanent flag debt).

---

# Quality Gates

| Gate | Checks (all must pass) |
|------|------------------------|
| **Before merge (PR)** | Pint + ESLint/Prettier clean · PHPStan + `tsc` clean · **Deptrac no new violation** · unit+integration green · coverage not decreased · ADR linked if architecture touched · no secrets (scanner) · migration is expand-safe |
| **Before release (staging tag)** | full test suite (unit/integration/architecture) · E2E smoke green · OpenAPI diff reviewed (no breaking) · performance budget met · security scan (deps + SAST) · isolation/leakage suite green · changelog updated |
| **Before production (promote)** | staging soak passed · blue/green health green · DB migration dry-run on prod copy · rollback rehearsed · DLQ empty · on-call + runbook ready · feature flags default-off · capability grants reviewed |

---

# Testing Strategy

| Layer | Tooling | Scope & rules |
|-------|---------|---------------|
| **Unit** | Pest (PHP), Vitest (TS) | domain logic, VOs, services, pure functions; fast, isolated; factories/fakes |
| **Integration** | Pest + Laravel, Vitest + msw | context boundaries via **ports** (test the port, mock the adapter); DB migrations; event handlers/projectors |
| **Architecture** | Deptrac + PHPStan custom rules + ESLint boundaries | boundaries from 05 matrix; "no cross-context model dep", "Analytics read-only", "Filament no logic", route-group import rules — **CI-enforced** |
| **E2E** | Playwright (to add) | critical journeys: enroll→learn→complete→certify; checkout→entitlement; offline→sync; admin capability toggle; per-tenant isolation |
| **Performance** | k6/Artillery + query profiling | progress-write O(1) proof; dashboard read latency; leaderboard materialization; N+1 guards; load at target concurrency |
| **Security** | SAST + dependency scan + auth/webhook tests | token storage, tenant leakage, webhook signature/idempotency, rate limits, impersonation audit |
| **Accessibility** | axe + manual + Accessibility quality gate | WCAG 2.1 AA; captions/transcripts availability; assessment accommodations honored; keyboard/screen-reader |

**Coverage policy:** ratchet, not a fixed number — coverage may not decrease on a PR; new domain logic requires tests; critical paths (money, entitlement, grades, isolation) require E2E + integration.

---

# CI/CD Pipeline

```
PR pipeline:
  ├─ format      (pint --test, prettier --check, eslint)
  ├─ static      (phpstan, tsc --noEmit)
  ├─ architecture(deptrac, eslint-boundaries, ADR-link check)   ← fail on new violation
  ├─ test        (pest, vitest)  →  coverage (ratchet gate)
  ├─ security    (dependency scan, SAST, secret scan)
  └─ build       (api image, web build, OpenAPI generate + diff)

main → staging:
  ├─ full suite + E2E smoke (Playwright)
  ├─ performance budget
  ├─ migrate (expand) on staging + soak
  └─ tag release

staging → production (manual promote):
  ├─ migrate dry-run on prod copy
  ├─ blue/green deploy green + health
  ├─ smoke on green → switch LB
  ├─ keep blue warm (rollback window)
  └─ post-deploy: DLQ watch, error-rate SLO
```

**Rollback:** previous image + blue stack = instant revert; expand-and-contract guarantees the old code runs against the new schema; rollback scripts already exist and are rehearsed pre-promote.

---

# Architecture Validation

| Tool | Role | Gate |
|------|------|------|
| **Deptrac** | context/layer boundaries from the 05 dependency matrix | **PR-blocking** on new violation; baseline burns down |
| **PHPStan (larastan)** | type safety, null-safety, dead code; custom rules for "no cross-context model use" | PR-blocking; level ratchets up; baseline shrinks |
| **Pint** | PHP code style | PR-blocking (`--test`) |
| **ESLint (+ boundaries)** | TS/React style + route-group import rules | PR-blocking |
| **TypeScript** | `tsc --noEmit` strict | PR-blocking |
| **OpenAPI** | generate spec; diff for breaking changes; REST-only contract | release-blocking on breaking diff |
| **ADR validation** | every architecture-touching PR references an ADR; ADRs stay current | PR check |

This is the enforcement layer that keeps the redesign real: without Deptrac (TD-7), boundaries erode silently — adopting it in M1 is the single highest-leverage step.

---

# Risks

| # | Risk | Type | Likelihood | Impact | Mitigation |
|---|------|------|:---:|:---:|-----------|
| R1 | Tenant-isolation retrofit misses a query → cross-tenant leak | Security | Med | Critical | global scope (not manual); leakage test suite as permanent gate; Deptrac + code review |
| R2 | Big-bang context split breaks tests/build | Technical | Med | High | small flagged moves; folder+namespace only; gate each on tests+Deptrac; low-churn windows |
| R3 | Outbox/eventing adds latency or duplicate side-effects | Operational | Med | High | idempotency by eventId/clientMutationId; DLQ + alerts; load-test before enabling guaranteed path |
| R4 | Offline merge conflicts corrupt learner state | Technical | Med | High | deterministic per-aggregate merge; completion monotonic; conflict quarantine; extensive property tests |
| R5 | Scope creep across 7 phases; never "done" | Business | High | High | strict phase exit criteria; flag-off by default; PO owns cut lines; Phase A→D is the enterprise MVP |
| R6 | Payment/entitlement inconsistency | Security/Business | Low | Critical | outbox exactly-once; gateway outside tx; reconciliation job; financial-event retention |
| R7 | AI produces unsafe/incorrect content reaching learners | Security/Trust | Low | High | human-in-the-loop hard gate; capability-gated; audited; no AI→grade path |
| R8 | Team/context-knowledge concentration (bus factor) | Operational | Med | Med | ADRs + runbooks + technical writer; pairing; architect reviews |
| R9 | Execution environment friction (Windows/PowerShell/sandbox) | Operational | High | Low | ASCII-only scripts; Docker-based verify; documented gotchas; prefer CI over local scripts |
| R10 | Performance regressions under multi-tenant load | Performance | Med | Med | read models; version-keyed cache; k6 budgets in CI; replica routing |

---

# Success Metrics

| Category | Metric | Target |
|----------|--------|--------|
| **Engineering** | Deptrac violations | 0 new; baseline → 0 by end of Phase D |
| | PHPStan level / baseline | level ↑, baseline ↓ each milestone |
| | Progress-write latency | O(1) on request path; p95 < 100ms |
| | Test coverage (critical paths) | E2E + integration on money/entitlement/grades/isolation; ratchet elsewhere |
| | Read-model rebuild | any projection drop-and-replay < defined SLA |
| **Business** | Time-to-enterprise-ready | Phase A–D shipped in recommended window |
| | Multi-tenant onboarding | self-serve tenant provision + capability grant |
| | Feature adoption | assessments/gamification/offline enabled per tenant progressively |
| | Credential integrity | 100% issued-from-verified-completion |
| **Operations** | Guaranteed-event delivery | 100% (outbox); DLQ size ~0 steady state |
| | Deploy safety | blue/green with rehearsed rollback; MTTR < target |
| | Isolation incidents | 0 cross-tenant leaks |
| | Availability / DR | SLO met; DR drill passes RPO/RTO |

---

# Definition of Done

| Level | Done when |
|-------|-----------|
| **Task** | code + tests written; PR gates green (format/static/**deptrac**/test/coverage/security); reviewed; behind flag if new subsystem; no secret; docstring/OpenAPI updated |
| **Feature** | all tasks done; integration tests via ports; feature flag wired; acceptance criteria met; docs/runbook updated; demoed |
| **Epic** | all features done; architecture tests green (boundaries honored); performance budget met; ADRs current; PO sign-off; flag-rollout plan defined |
| **Project (per phase)** | milestone acceptance criteria met; E2E for phase journeys green; security/isolation gates green; migration expand-and-contract complete; rollback rehearsed; capability/flag defaults set; retro done |

---

# Estimated Timeline

Assumes the recommended team (≈2 squads + shared architect). Ranges, not commitments — gated by exit criteria, not dates.

| Phase | Conservative | Recommended | Aggressive |
|-------|:---:|:---:|:---:|
| A — Platform Foundation (M1–M4) | 4 mo | 3 mo | 2 mo |
| B — Core LMS (M5–M8) | 6 mo | 4 mo | 3 mo |
| C — Commerce (M9) | 2 mo | 1.5 mo | 1 mo |
| D — Enterprise (M10) | 4 mo | 3 mo | 2 mo |
| **Subtotal to Enterprise-ready (A–D)** | **~16 mo** | **~11.5 mo** | **~8 mo** |
| E — AI (M11) | 3 mo | 2 mo | 1.5 mo |
| F — Marketplace (M12) | 3 mo | 2 mo | 1.5 mo |
| G — Global (MFinal) | 4 mo | 3 mo | 2 mo |
| **Total to Global GA** | **~26 mo** | **~18.5 mo** | **~13 mo** |

Phases overlap in the recommended/aggressive scenarios (Platform and Learning streams run partly in parallel), which is why the subtotal is less than the sum of sequential worst cases. **Aggressive** assumes no discovery surprises and a fully staffed team — treat it as a floor, not a plan.

---

# Final Recommendation

**Execute in strict phase order, foundation first, flags everywhere, boundaries enforced from day one.**

1. **Start with Phase A, this quarter.** Adopt Deptrac (M1) before writing any new feature — it is the cheapest, highest-leverage move and the only thing keeping the redesign from eroding. In parallel, close the three known security findings (token storage, webhook/gateway-in-tx, manual tenant scoping) because they are P0 and independent of feature work.
2. **Treat Phase A–D as the enterprise MVP.** That is the ~9–12 month goal: a multi-tenant, secure, event-driven platform with the Learning redesign, hardened Commerce, and the Organization/Instructor splits. Everything after (AI, Marketplace, Global) is upside layered on a stable spine.
3. **Never big-bang.** Every context split and every subsystem ships behind a capability/flag, off by default, enabled per-tenant. Migrations are expand-and-contract. This is validated by our own execution history: small, gated, reversible steps succeeded; long branches and locale/encoding surprises did not.
4. **Let exit criteria — not dates — gate promotion.** The milestone acceptance criteria in this plan are the contract. Green gates promote; red gates hold.
5. **Keep the architect in the loop on every boundary-touching change,** and keep ADRs current. The five redesigns are the source of truth; this plan is how they become real.

The architecture is sound and complete. The remaining risk is execution discipline, not design. This plan is implementation-ready: pick up **A1 (Deptrac + fitness) and A6 (security fixes)** first, then proceed down the Implementation Order.

---

## Appendix — Redesign → Epic traceability

| Redesign decision | Epic(s) |
|-------------------|---------|
| 01 Catalog: publish guard, course versions, editions | B1.3, B1.1 |
| 02 CRM/Organization split, capabilities, tenant flags | D1, A3.2 |
| 03 Instructor/Authoring split, TeachingAuthority, content versioning, AI gate | D2, B1.3, E1 |
| 04 Learning: ports, progress projector, LRS, assessments, gamification, offline, accessibility | B1–B7 |
| 05 Administration, Filament multi-panel, capabilities, outbox, security, Analytics repoint, ADRs | A2–A6, D3, D5 |
| Backend chunks C9/C10/C11 (relocation + Crm split) | D4, D1 |
| Audit findings (TEN-1, token, webhook-in-tx, Deptrac absent) | A2, A6, A1 |

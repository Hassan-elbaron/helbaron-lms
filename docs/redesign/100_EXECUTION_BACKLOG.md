# Engineering Execution Backlog (Single Source of Truth)

> Execution artifact only. No application code, no architecture/API/DB changes. This converts redesigns 01–05 + the Master Plan (99) into a sequential, dependency-aware, import-ready backlog.
> Source of truth: `01_CATALOG` · `02_CRM_ORGANIZATION` · `03_INSTRUCTOR_AUTHORING` · `04_LEARNING` · `05_ADMINISTRATION_ENTERPRISE` · `99_IMPLEMENTATION_MASTER_PLAN`.

---

## How to use this document (import guide)

- **Hierarchy:** Sprint → Epic → Story → Task. IDs are stable and tool-agnostic.
- **IDs:** Epics use Master-Plan ids (`A1`…`G5`). Stories = `<EPIC>-S##`. Tasks = `<EPIC>-T##`.
- **Jira/Azure/Linear/ClickUp/GitHub Projects:** map Epic→Epic/Parent, Story→Story/Issue, Task→Sub-task. Story Points are Fibonacci (1,2,3,5,8,13). Hours are per-task engineering estimates.
- **Owners** use role codes: **SA** Solution Architect · **BE** Backend · **FE** Frontend · **DO** DevOps · **QA** QA/SDET · **UX** UI/UX · **PO** Product Owner · **TW** Technical Writer.
- **Priority:** P0 (blocker) → P3 (nice-to-have).

### Canonical PR Checklist (applies to EVERY task; task rows list only additions/specifics)

```
[ ] Architecture respected (matches redesign 01–05 + relevant ADR)
[ ] DDD boundary respected (single-writer ownership; no logic leak)
[ ] Ports only for cross-context access (no direct cross-context Model use)
[ ] No direct cross-context dependency (Deptrac clean)
[ ] Tenant isolation preserved (global scope; no manual org_id where)
[ ] Tests added/updated (unit + integration; E2E if critical path)
[ ] Static analysis passes (PHPStan / tsc --noEmit)
[ ] Deptrac passes (no new violation; baseline not increased)
[ ] PHPStan passes (level not lowered; baseline not grown)
[ ] Frontend lint passes (ESLint + Prettier)
[ ] Coverage not decreased (ratchet)
[ ] Feature behind capability/flag if new subsystem (default-off)
[ ] Migration is expand-and-contract (no destructive single-step)
[ ] No secret committed (scanner clean)
[ ] OpenAPI regenerated + no breaking diff
[ ] Documentation updated (README/runbook/OpenAPI)
[ ] ADR added/updated if an architectural decision changed
[ ] Conventional Commit message with epic/task id + ASCII-only scripts
```

### Canonical Story Test Taxonomy (applies to EVERY story; story blocks list concrete cases)

Each story defines: **Acceptance Tests** (behavioral), **Unit**, **Integration** (port/adapter + DB), **Architecture** (Deptrac/PHPStan rule), **E2E** (Playwright, if user-facing/critical), **Performance** (budget, if hot path), **Security** (authz/isolation/secret), **Accessibility** (WCAG, if UI). "n/a" is stated explicitly where a layer does not apply (e.g., no UI → no a11y).

### Global sprint cadence

2-week sprints, recommended team (2 streams + shared SA). Sprint 0 is a hardening/tooling sprint. Sprints may run parallel epics where the Implementation Order allows; parallel epics are noted in each sprint header.

---

# SPRINT 0 — Architecture Fitness & Tooling

- **Sprint Goal:** make boundaries and quality **enforced by CI** before any feature work; open the security-fix track.
- **Duration:** 2 weeks.
- **Dependencies:** none (foundational).
- **Risks:** Deptrac baseline large; false-positive boundary rules slow PRs (mitigate: baseline current violations, tune rules with SA).
- **Definition of Done:** CI fails on new boundary violation; PHPStan/Pint/ESLint/tsc gates active; Playwright scaffold runs one smoke test.
- **Exit Criteria:** M1 (Fitness Gate) acceptance met — Deptrac in CI (baseline), static analysis green, ADR-link check active.

## EPIC A1 — Architecture Fitness & Tooling
- **Epic ID:** A1 · **Name:** Architecture Fitness & Tooling
- **Objective:** enforce the 05 dependency matrix and code quality automatically.
- **Business Value:** protects the entire redesign from silent erosion; lowest-cost, highest-leverage control.
- **Architecture Reference:** 05 Dependency Matrix; 99 Architecture Validation; ADR-01/02/03.
- **Dependencies:** none. · **Priority:** P0 · **Risk:** Low · **Estimated Story Points:** 34.

### Story A1-S01 — Deptrac boundary enforcement in CI
- **Title:** Enforce context/layer boundaries with Deptrac.
- **Description:** Introduce Deptrac with a ruleset encoding Domains/Contexts/Platform layers and the forbidden dependencies from redesign 05; baseline existing violations; block new ones in CI.
- **Business Reason:** boundaries are currently unenforced (TD-7); without this, ownership rules decay.
- **Acceptance Criteria:** (1) `deptrac.yaml` defines every context as a layer; (2) forbidden rules encode "no cross-context Models for logic", "Analytics read-only", "Filament no logic", "Platform capabilities depend on no domain"; (3) baseline captures current state; (4) CI stage fails on any new violation.
- **Definition of Done:** Story DoD + CI stage visible on PRs; baseline file committed; SA sign-off on ruleset.
- **Priority:** P0 · **Story Points:** 13 · **Owner:** SA+BE · **Dependencies:** none.
- **Tests:** **Acceptance:** intentionally-violating branch fails CI. **Unit:** n/a. **Integration:** deptrac run in CI container. **Architecture:** the ruleset itself. **E2E/Perf/Sec/A11y:** n/a.

Tasks:
| Task | Description | Related | Impl. notes | Blocking | Hrs | Cx | Risk |
|------|-------------|---------|-------------|----------|:--:|:--:|:--:|
| A1-T01 | Install Deptrac; scaffold `deptrac.yaml` | A1-S01 | composer require-dev; layer per `app/{Domains,Contexts,Platform}/*` | — | 4 | M | Low |
| A1-T02 | Encode forbidden-dependency ruleset from 05 matrix | A1-S01 | collector by namespace; rules per matrix; SA review | A1-T01 | 8 | M | Med |
| A1-T03 | Generate + commit baseline | A1-S01 | `deptrac --formatter=baseline` | A1-T02 | 2 | S | Low |
| A1-T04 | Add CI `architecture` stage (fail on new) | A1-S01 | wire into `ci.yml`; cache vendor | A1-T03 | 3 | S | Low |
- **PR Checklist (all tasks):** Standard PR Checklist. *Specifics:* A1-T02 add "ruleset reviewed against 05 matrix line-by-line by SA"; A1-T04 add "CI red on seeded violation proven".

### Story A1-S02 — Static analysis hardening (PHPStan/Pint/Rector)
- **Title:** Raise PHP static-analysis floor.
- **Description:** Bump PHPStan level incrementally, shrink the baseline, add a "no cross-context Model use" custom rule, enforce Pint, add Rector in dry-run.
- **Business Reason:** type/null safety + a machine check that complements Deptrac.
- **Acceptance Criteria:** level raised ≥1 with baseline not grown; custom rule flags a cross-context `use`; Pint `--test` gate; Rector dry-run reports (non-blocking).
- **Definition of Done:** Story DoD + gates in CI.
- **Priority:** P0 · **Story Points:** 8 · **Owner:** BE · **Dependencies:** A1-T04.
- **Tests:** **Acceptance:** seeded cross-context use fails PHPStan. **Architecture:** custom rule. Others n/a.

Tasks:
| Task | Description | Related | Impl. notes | Blocking | Hrs | Cx | Risk |
|------|-------------|---------|-------------|----------|:--:|:--:|:--:|
| A1-T05 | Bump PHPStan level; reconcile baseline | A1-S02 | one level at a time | A1-T04 | 6 | M | Low |
| A1-T06 | Custom PHPStan rule: no cross-context Model use | A1-S02 | complements Deptrac at type level | A1-T05 | 6 | M | Med |
| A1-T07 | Rector dry-run stage (non-blocking) | A1-S02 | report only | A1-T05 | 3 | S | Low |

### Story A1-S03 — Frontend fitness + Playwright scaffold
- **Title:** Enforce TS/ESLint boundaries; scaffold E2E.
- **Description:** Add ESLint import-boundary rules per route-group, strict `tsc --noEmit` gate, and a Playwright harness with one smoke journey.
- **Business Reason:** frontend has no E2E (TD-10); route-group boundaries need enforcement.
- **Acceptance Criteria:** disallowed cross-group import fails lint; `tsc` strict in CI; Playwright runs `home→login` smoke headless in CI.
- **Definition of Done:** Story DoD + green CI.
- **Priority:** P1 · **Story Points:** 8 · **Owner:** FE+QA · **Dependencies:** A1-T04.
- **Tests:** **Acceptance:** seeded bad import fails. **E2E:** smoke journey. **A11y:** axe wired (report). Others n/a.

Tasks:
| Task | Description | Related | Impl. notes | Blocking | Hrs | Cx | Risk |
|------|-------------|---------|-------------|----------|:--:|:--:|:--:|
| A1-T08 | ESLint boundaries plugin + rules | A1-S03 | map to `(auth)/(learning)/...` groups | — | 5 | M | Low |
| A1-T09 | Strict `tsc --noEmit` CI gate | A1-S03 | — | — | 2 | S | Low |
| A1-T10 | Playwright scaffold + 1 smoke + axe wiring | A1-S03 | CI headless; trace on fail | A1-T09 | 6 | M | Low |

### Story A1-S04 — ADR validation check
- **Title:** Require ADR link on architecture PRs.
- **Description:** Lightweight CI check: PRs touching `app/**/Providers`, ports, or `deptrac.yaml` must reference an ADR id.
- **Business Reason:** keep ADRs (18 in blueprint) current; traceability.
- **Acceptance Criteria:** PR without ADR link on an architecture path fails the check; ADR index page lists all ADRs.
- **Definition of Done:** Story DoD.
- **Priority:** P2 · **Story Points:** 5 · **Owner:** SA+DO · **Dependencies:** A1-T04.
- **Tests:** **Acceptance:** architecture PR without ADR fails. Others n/a.

Tasks:
| Task | Description | Related | Impl. notes | Blocking | Hrs | Cx | Risk |
|------|-------------|---------|-------------|----------|:--:|:--:|:--:|
| A1-T11 | ADR-link CI check (path-triggered) | A1-S04 | danger/custom script | — | 4 | S | Low |
| A1-T12 | Publish ADR index (from 05) | A1-S04 | TW; link from README | — | 3 | S | Low |

### Sprint 0 checklists
- **Release Checklist:** all A1 gates green on `main`; baseline files committed; CI docs updated (TW).
- **Rollback Checklist:** CI stages are additive — disable the new stage flag to revert; no runtime impact.
- **Deployment Checklist:** none (tooling only; no runtime deploy).
- **Monitoring Checklist:** track CI stage duration + failure rate; alert if `architecture` stage flaps.
- **Team Allocation:** SA (ruleset/ADR), BE×1 (PHPStan/Deptrac), FE×1 (ESLint/Playwright), DO (CI wiring), QA (smoke), TW (docs). PO sign-off on M1.

---

# SPRINT 1 — Multi-Tenancy & Isolation

- **Sprint Goal:** tenant isolation **by construction**; eliminate manual scoping (TEN-1).
- **Duration:** 2 weeks (may spill to 3 given XL — plan a buffer).
- **Dependencies:** A1 (Deptrac gate to protect the refactor).
- **Risks:** a missed query → cross-tenant leak (Critical). Mitigate: global scope not manual; permanent leakage suite; SA review of every tenant-scoped model.
- **Definition of Done:** every tenant-scoped model carries a global scope; leakage suite green; no manual `org_id where` remains.
- **Exit Criteria:** M2 (Tenant Isolation) met; TEN-1 closed.

## EPIC A2 — Multi-Tenancy & Isolation
- **Epic ID:** A2 · **Name:** Multi-Tenancy & Isolation
- **Objective:** enforce row-level tenant isolation globally + tenant provisioning envelope.
- **Business Value:** enterprise prerequisite; closes the highest-severity security finding.
- **Architecture Reference:** 05 Security (Org/Tenant isolation), ADR-07; 99 TD-3.
- **Dependencies:** A1. · **Priority:** P0 · **Risk:** High · **Estimated Story Points:** 42.

### Story A2-S01 — Tenant context resolution
- **Title:** Resolve tenant per request (host/user/org).
- **Description:** Middleware resolves the active tenant from host/subdomain, authenticated user's org, or explicit context; exposes it to the container for scopes/policies.
- **Business Reason:** every isolation rule needs one authoritative "current tenant".
- **Acceptance Criteria:** resolver sets tenant for web+API+queue jobs; unresolved tenant on a tenant-scoped route → 403; jobs carry tenant context across the queue.
- **Definition of Done:** Story DoD + integration tests for web/API/queue.
- **Priority:** P0 · **Story Points:** 8 · **Owner:** BE+SA · **Dependencies:** A1.
- **Tests:** **Acceptance:** request without tenant on scoped route denied. **Unit:** resolver precedence. **Integration:** queue job retains tenant. **Security:** spoofed host rejected. **Architecture:** resolver in Platform, not a domain. **E2E:** login → tenant-scoped page loads own data. **Perf/A11y:** n/a.

Tasks:
| Task | Description | Related | Impl. notes | Blocking | Hrs | Cx | Risk |
|------|-------------|---------|-------------|----------|:--:|:--:|:--:|
| A2-T01 | TenantContext service + middleware | A2-S01 | container-scoped; from host/user/org | — | 8 | M | Med |
| A2-T02 | Propagate tenant into queued jobs | A2-S01 | middleware on dispatch/handle | A2-T01 | 6 | M | Med |
| A2-T03 | Deny path for unresolved tenant | A2-S01 | 403 + audit | A2-T01 | 3 | S | Low |
- **PR specifics:** add "resolver has no domain dependency (Deptrac)"; "queue propagation tested with a real job".

### Story A2-S02 — Global tenant scope + policy
- **Title:** Apply `BelongsToTenant` global scope to every tenant-scoped model.
- **Description:** Add a global scope + trait so tenant-scoped models auto-filter by tenant; add policy checks; remove manual `where org_id`.
- **Business Reason:** isolation by construction (ADR-07) instead of remembering a `where`.
- **Acceptance Criteria:** listed tenant-scoped models filtered automatically; a query without the scope cannot see other tenants in tests; zero manual `org_id` filters remain (grep gate).
- **Definition of Done:** Story DoD + leakage suite green.
- **Priority:** P0 · **Story Points:** 13 · **Owner:** BE · **Dependencies:** A2-S01.
- **Tests:** **Acceptance:** cross-tenant read returns empty. **Unit:** scope SQL. **Integration:** per-model. **Security:** leakage suite across all scoped models. **Architecture:** grep "no manual org_id where". **Perf:** scope adds indexed predicate (no full scan). **E2E:** two-tenant isolation journey. **A11y:** n/a.

Tasks:
| Task | Description | Related | Impl. notes | Blocking | Hrs | Cx | Risk |
|------|-------------|---------|-------------|----------|:--:|:--:|:--:|
| A2-T04 | `BelongsToTenant` trait + global scope | A2-S02 | opt-in per model | A2-T01 | 6 | M | Med |
| A2-T05 | Apply to every tenant-scoped model | A2-S02 | inventory first; SA review list | A2-T04 | 10 | L | High |
| A2-T06 | Remove manual org_id filters | A2-S02 | grep + replace; keep index | A2-T05 | 6 | M | Med |
| A2-T07 | Tenant index review (composite) | A2-S02 | ensure (tenant_id, …) indexes | A2-T05 | 4 | M | Med |

### Story A2-S03 — Cross-tenant leakage test harness
- **Title:** Permanent isolation regression suite.
- **Description:** Seed two tenants; assert every list/detail/mutation endpoint denies cross-tenant access; run in CI as a blocking gate.
- **Business Reason:** isolation must stay proven forever, not just once.
- **Acceptance Criteria:** suite covers every tenant-scoped resource; wired as blocking CI gate; a deliberately un-scoped model fails the suite.
- **Definition of Done:** Story DoD + CI gate.
- **Priority:** P0 · **Story Points:** 8 · **Owner:** QA · **Dependencies:** A2-S02.
- **Tests:** **Security/Acceptance:** the suite itself. Others n/a.

Tasks:
| Task | Description | Related | Impl. notes | Blocking | Hrs | Cx | Risk |
|------|-------------|---------|-------------|----------|:--:|:--:|:--:|
| A2-T08 | Two-tenant fixtures + factory helpers | A2-S03 | reusable | A2-T05 | 5 | M | Low |
| A2-T09 | Leakage assertions per resource | A2-S03 | matrix over endpoints | A2-T08 | 8 | L | Med |
| A2-T10 | Wire as blocking CI gate | A2-S03 | tag suite; required check | A2-T09 | 2 | S | Low |

### Story A2-S04 — Tenant provisioning (Administration slice)
- **Title:** Provision / suspend tenants with limits.
- **Description:** Minimal Administration-owned tenant lifecycle: create/suspend, usage limits, `TenantProvisioned/Suspended` events (via outbox once A4 lands; sync-safe until then).
- **Business Reason:** tenants are the unit of enterprise onboarding + isolation envelope.
- **Acceptance Criteria:** provision creates a tenant with limits; suspend blocks access; events emitted; actions are audited.
- **Definition of Done:** Story DoD + Filament admin action delegates to a domain Action.
- **Priority:** P1 · **Story Points:** 8 · **Owner:** BE · **Dependencies:** A2-S02.
- **Tests:** **Acceptance:** suspended tenant denied. **Integration:** event emitted + audited. **Security:** only super-admin provisions. **E2E:** admin provisions tenant. **Architecture:** Filament delegates (no logic). **A11y:** admin form axe. **Perf:** n/a.

Tasks:
| Task | Description | Related | Impl. notes | Blocking | Hrs | Cx | Risk |
|------|-------------|---------|-------------|----------|:--:|:--:|:--:|
| A2-T11 | Tenant + tenant_limits schema (expand) | A2-S04 | new tables; no change to domain tables | A2-T05 | 6 | M | Med |
| A2-T12 | Provision/Suspend Actions + events | A2-S04 | audited; outbox-ready | A2-T11 | 6 | M | Med |
| A2-T13 | Filament Tenant resource (delegates) | A2-S04 | thin wrapper | A2-T12 | 5 | M | Low |

### Sprint 1 checklists
- **Release Checklist:** leakage suite green; grep gate (no manual org_id) green; expand migrations applied on staging; two-tenant smoke passed.
- **Rollback Checklist:** global scope is additive (trait) — feature-flag scope activation per model; revert flag if a regression; contract migration deferred to next release.
- **Deployment Checklist:** run expand migration (tenant tables + indexes); backfill tenant_id where needed (online batch); verify indexes present; enable scope flag per model gradually.
- **Monitoring Checklist:** watch query latency (new predicate), 403 rate on scoped routes, any cross-tenant assertion failures in prod canary, slow-query log for missing indexes.
- **Team Allocation:** BE×2 (scope, provisioning), SA (model inventory review), QA (leakage suite), DO (migration/backfill), PO (tenant limits policy), TW (isolation runbook).

---

# SPRINT 2 — Eventing Backbone (Outbox/DLQ)  ∥  Security Hardening

- **Sprint Goal:** guaranteed event delivery + close the remaining P0 security findings.
- **Duration:** 2 weeks. · **Parallel epics:** A4 (BE stream) ∥ A6 (BE+FE+DO).
- **Dependencies:** A1; A4 depends on nothing further; A6 depends on A2 (tenant) for scoped-cookie context.
- **Risks:** outbox duplicate side-effects; cookie auth breaking existing SPA sessions. Mitigate: idempotency by eventId; staged cookie rollout behind flag with fallback.
- **Definition of Done:** guaranteed events survive worker kill; webhooks idempotent + verified; web auth on httpOnly cookies behind flag.
- **Exit Criteria:** M3 (Secure Foundation) met; TD-4 + TD-5 closed.

## EPIC A4 — Eventing Backbone (Outbox + DLQ)
- **Epic ID:** A4 · **Objective:** exactly-once-effect delivery for critical events; DLQ + retry + versioning.
- **Business Value:** money/entitlement/credential events can never be lost or double-applied.
- **Architecture Reference:** 05 Event Map (guaranteed-delivery/outbox), ADR-03/16; 99 TD-5.
- **Dependencies:** A1. · **Priority:** P0 · **Risk:** High · **Story Points:** 34.

### Story A4-S01 — Transactional outbox
- **Title:** Write event + state in one transaction; relay publishes.
- **Description:** Guaranteed events are written to an outbox table inside the domain transaction; a relay worker publishes to the queue and marks sent.
- **Business Reason:** prevents lost entitlement/credential/financial events on crash.
- **Acceptance Criteria:** killing the worker mid-publish loses no event; relay is idempotent; ordering per aggregate preserved.
- **Definition of Done:** Story DoD + chaos test (kill worker) green.
- **Priority:** P0 · **Story Points:** 13 · **Owner:** BE+SA · **Dependencies:** A1.
- **Tests:** **Acceptance:** worker-kill loses nothing. **Unit:** relay dedupe. **Integration:** tx rollback drops outbox row. **Performance:** relay throughput budget. **Security:** outbox not tenant-leaking. **Architecture:** outbox in Platform. **E2E/A11y:** n/a.

Tasks:
| Task | Description | Related | Impl. notes | Blocking | Hrs | Cx | Risk |
|------|-------------|---------|-------------|----------|:--:|:--:|:--:|
| A4-T01 | Outbox table (expand) + writer | A4-S01 | same-tx insert | — | 6 | M | Med |
| A4-T02 | Relay worker (publish + mark) | A4-S01 | idempotent; backoff | A4-T01 | 8 | L | High |
| A4-T03 | Chaos test: kill relay mid-flight | A4-S01 | assert no loss/dupe-effect | A4-T02 | 5 | M | Med |

### Story A4-S02 — Idempotency + consumer dedupe
- **Title:** Stable eventId + consumer-side dedupe store.
- **Description:** Every event carries an id; consumers record processed ids to guarantee at-most-once effect.
- **Business Reason:** at-least-once delivery + dedupe = exactly-once effect.
- **Acceptance Criteria:** replaying an event is a no-op; dedupe store TTLّd; clientMutationId supported for learner writes.
- **Definition of Done:** Story DoD.
- **Priority:** P0 · **Story Points:** 8 · **Owner:** BE · **Dependencies:** A4-S01.
- **Tests:** **Acceptance:** double-deliver = single effect. **Unit/Integration** per consumer. Others n/a.

Tasks:
| Task | Description | Related | Impl. notes | Blocking | Hrs | Cx | Risk |
|------|-------------|---------|-------------|----------|:--:|:--:|:--:|
| A4-T04 | Idempotency-key store | A4-S02 | Redis/DB; TTL | A4-T01 | 5 | M | Med |
| A4-T05 | Consumer dedupe middleware | A4-S02 | wrap listeners | A4-T04 | 6 | M | Med |

### Story A4-S03 — DLQ, retry policy, event versioning
- **Title:** Dead-letter + retry tiers + additive versioning.
- **Description:** Per-event-class retry/backoff; exhausted → DLQ surfaced in Ops Console; payload schema versioned additively.
- **Business Reason:** operational resilience + safe schema evolution.
- **Acceptance Criteria:** poison event lands in DLQ with context; retry tiers per class; a v2 payload coexists with v1 consumers.
- **Definition of Done:** Story DoD.
- **Priority:** P1 · **Story Points:** 8 · **Owner:** BE+DO · **Dependencies:** A4-S01.
- **Tests:** **Acceptance:** poison → DLQ. **Integration:** retry counts. **Architecture:** version compatibility test. Others n/a.

Tasks:
| Task | Description | Related | Impl. notes | Blocking | Hrs | Cx | Risk |
|------|-------------|---------|-------------|----------|:--:|:--:|:--:|
| A4-T06 | Retry/backoff tiers per event class | A4-S03 | config-driven | A4-T02 | 5 | M | Med |
| A4-T07 | DLQ store + Ops Console feed | A4-S03 | surfaced in A3.5 later | A4-T06 | 5 | M | Med |
| A4-T08 | Event payload versioning convention | A4-S03 | v1/v2 additive | — | 4 | S | Low |

## EPIC A6 — Security Hardening
- **Epic ID:** A6 · **Objective:** close TD-4/TD-5; externalize secrets; audited impersonation.
- **Business Value:** removes the highest-likelihood breach vectors before enterprise onboarding.
- **Architecture Reference:** 05 Security Architecture; 99 TD-4/TD-5.
- **Dependencies:** A2, A5(Integration slice for webhooks — sequenced so webhook work lands with A5.2; here do cookie + secrets + impersonation, webhook idempotency stub). · **Priority:** P0 · **Risk:** High · **Story Points:** 26.

### Story A6-S01 — Auth token → httpOnly cookies
- **Title:** Move web auth off localStorage to httpOnly cookies.
- **Description:** Switch Next.js + Sanctum to httpOnly, SameSite cookies; CSRF handling; behind a rollout flag with fallback.
- **Business Reason:** closes the XSS→token-theft chain (TD-4).
- **Acceptance Criteria:** no token in localStorage; auth works via cookie; CSRF enforced; logout clears cookie; flag allows staged rollout.
- **Definition of Done:** Story DoD + security test.
- **Priority:** P0 · **Story Points:** 8 · **Owner:** FE+BE · **Dependencies:** A2.
- **Tests:** **Security/Acceptance:** localStorage has no token; XSS cannot read auth. **Integration:** cookie auth round-trip. **E2E:** login/logout journey. **A11y:** login form axe. **Unit:** CSRF util. **Perf:** n/a.

Tasks:
| Task | Description | Related | Impl. notes | Blocking | Hrs | Cx | Risk |
|------|-------------|---------|-------------|----------|:--:|:--:|:--:|
| A6-T01 | Sanctum cookie auth config + CSRF | A6-S01 | SameSite; secure | — | 6 | M | Med |
| A6-T02 | Next.js auth client → cookies (flagged) | A6-S01 | remove localStorage token | A6-T01 | 8 | L | High |
| A6-T03 | Logout/session-invalidation flow | A6-S01 | clear cookie server-side | A6-T02 | 4 | S | Low |

### Story A6-S02 — Secrets externalization
- **Title:** Move secrets from env to a secret store path.
- **Description:** Introduce a `SecretsPort` abstraction; wire provider secrets through Administration config; document Vault/SSM migration.
- **Business Reason:** reduce secret sprawl; enable rotation.
- **Acceptance Criteria:** provider secrets referenced via port; no plaintext secret in repo/logs; rotation documented.
- **Definition of Done:** Story DoD.
- **Priority:** P1 · **Story Points:** 5 · **Owner:** DO+BE · **Dependencies:** A2.
- **Tests:** **Security:** secret scan clean; port returns from store. Others n/a.

Tasks:
| Task | Description | Related | Impl. notes | Blocking | Hrs | Cx | Risk |
|------|-------------|---------|-------------|----------|:--:|:--:|:--:|
| A6-T04 | SecretsPort + env adapter (default) | A6-S02 | Vault/SSM adapter later | — | 5 | M | Low |
| A6-T05 | Route provider secrets via port | A6-S02 | Stripe/Mux/mail | A6-T04 | 5 | M | Med |

### Story A6-S03 — Audited impersonation
- **Title:** Safe, time-boxed impersonation.
- **Description:** Administration-initiated, Identity-enforced impersonation with banner, time-box, full audit, and financial-action block.
- **Business Reason:** support/ops need it; must be safe + auditable.
- **Acceptance Criteria:** impersonation logged (start/end); banner shown; expires; blocked for financial actions.
- **Definition of Done:** Story DoD.
- **Priority:** P2 · **Story Points:** 5 · **Owner:** BE+FE · **Dependencies:** A2.
- **Tests:** **Security/Acceptance:** start/end audited; financial action denied. **E2E:** impersonate → banner → expire. **A11y:** banner. Others n/a.

Tasks:
| Task | Description | Related | Impl. notes | Blocking | Hrs | Cx | Risk |
|------|-------------|---------|-------------|----------|:--:|:--:|:--:|
| A6-T06 | Impersonation Action + audit events | A6-S03 | time-box; block finance | — | 6 | M | Med |
| A6-T07 | Impersonation banner (web) | A6-S03 | persistent + exit | A6-T06 | 4 | S | Low |

### Sprint 2 checklists
- **Release Checklist:** outbox chaos test green; dedupe verified; cookie-auth flag ready (default-off in prod); secret scan clean; impersonation audited.
- **Rollback Checklist:** cookie auth behind flag → revert to token path instantly; outbox relay can be paused (events buffer in table); secrets adapter falls back to env.
- **Deployment Checklist:** expand outbox + idempotency + dedupe tables; deploy relay worker (scaled 1→N); enable cookie flag on staging then canary tenant; rotate any exposed secret.
- **Monitoring Checklist:** outbox backlog size, relay lag, DLQ size, dedupe hit rate, auth failure rate post-cookie, 401/419 (CSRF) spikes.
- **Team Allocation:** BE×2 (outbox/dedupe/impersonation), FE×1 (cookie client/banner), DO (relay deploy, secrets), QA (chaos + security suite), SA (event versioning), PO (rollout gating), TW (security runbook).

---

# SPRINT 3 — Administration Context

- **Sprint Goal:** stand up the operator cockpit — config, capabilities, audit center, ops console, providers, branding.
- **Duration:** 2 weeks. · **Dependencies:** A2 (tenant), A4 (events/DLQ feed).
- **Risks:** capability checks added platform-wide could block flows if misconfigured. Mitigate: default-on capabilities, staged enforcement.
- **Definition of Done:** capabilities gate a feature per tenant; audit center searchable; ops console shows queues/DLQ/health; providers configurable.
- **Exit Criteria:** M4 (Administration Live) met.

## EPIC A3 — Administration Context
- **Epic ID:** A3 · **Objective:** platform operation surface without owning domain data.
- **Business Value:** enterprise operability, per-tenant entitlement, auditability.
- **Architecture Reference:** 05 Administration Boundary + Filament Architecture; ADR-05/06.
- **Dependencies:** A2, A4. · **Priority:** P0 · **Risk:** Med · **Story Points:** 42.

### Story A3-S01 — Settings & Global Config (ConfigPort)
- **Description:** Central settings store + `ConfigPort` consumed by contexts; environment profiles.
- **Business Reason:** one place for global/system configuration.
- **Acceptance Criteria:** contexts read config via port; changes audited; environment profile selectable.
- **DoD:** Story DoD. **Priority:** P1 · **SP:** 8 · **Owner:** BE · **Deps:** A2.
- **Tests:** Acceptance (config change reflected), Integration (port), Security (only admin writes), Architecture (port in Platform). Others n/a.

| Task | Description | Related | Impl. notes | Blocking | Hrs | Cx | Risk |
|------|-------------|---------|-------------|----------|:--:|:--:|:--:|
| A3-T01 | Settings schema + service | A3-S01 | typed settings | — | 6 | M | Low |
| A3-T02 | ConfigPort + adapter | A3-S01 | cache-backed | A3-T01 | 5 | M | Low |
| A3-T03 | Environment profiles | A3-S01 | dev/stage/prod | A3-T01 | 4 | S | Low |

### Story A3-S02 — Feature Flags & Capabilities (CapabilityPort)
- **Description:** Flag definitions + per-tenant capability grants; capability→permission checks at entry points (default-on).
- **Business Reason:** distinguish tenant entitlement (capability) from user authz (permission) from operational toggle (flag) — ADR-06.
- **Acceptance Criteria:** a capability gates a whole feature per tenant; flags toggle operationally; checks layered capability→permission; defaults safe.
- **DoD:** Story DoD. **Priority:** P0 · **SP:** 13 · **Owner:** BE+SA · **Deps:** A3-S01.
- **Tests:** Acceptance (disabled capability hides feature), Unit (precedence), Integration (entry-point checks), Security (no bypass), Architecture (CapabilityPort). E2E (admin toggles capability). Perf/A11y n/a.

| Task | Description | Related | Impl. notes | Blocking | Hrs | Cx | Risk |
|------|-------------|---------|-------------|----------|:--:|:--:|:--:|
| A3-T04 | Flag + capability schema | A3-S02 | per-tenant grants | — | 6 | M | Med |
| A3-T05 | CapabilityPort + check helper | A3-S02 | capability→permission | A3-T04 | 6 | M | Med |
| A3-T06 | Wire checks at feature entry points | A3-S02 | default-on; audited | A3-T05 | 8 | L | Med |
| A3-T07 | FeatureFlagChanged/CapabilityGranted events | A3-S02 | via outbox | A3-T04 | 4 | S | Low |

### Story A3-S03 — Audit Center
- **Description:** Aggregate `*Audited` events from all contexts; retention policies; search + export.
- **Business Reason:** single auditable trail (security + compliance).
- **Acceptance Criteria:** every state-change audited event lands in the center; searchable by actor/entity/time; export works; retention per policy.
- **DoD:** Story DoD. **Priority:** P1 · **SP:** 8 · **Owner:** BE+QA · **Deps:** A4.
- **Tests:** Acceptance (event captured + searchable), Integration (consumer), Security (access restricted), Architecture (read-only consumer). Others n/a.

| Task | Description | Related | Impl. notes | Blocking | Hrs | Cx | Risk |
|------|-------------|---------|-------------|----------|:--:|:--:|:--:|
| A3-T08 | Audit index schema + consumer | A3-S03 | subscribes `*Audited` | A4-T05 | 6 | M | Med |
| A3-T09 | Search + export UI (Filament page) | A3-S03 | read-only | A3-T08 | 6 | M | Low |
| A3-T10 | Retention policy jobs | A3-S03 | per class (24mo–7yr) | A3-T08 | 4 | S | Low |

### Story A3-S04 — Ops Console
- **Description:** Surface queues/jobs/scheduler/health/maintenance-mode/alerts + DLQ (from A4).
- **Business Reason:** operability + incident response.
- **Acceptance Criteria:** console shows queue depth, failed jobs, DLQ, health, scheduler; maintenance mode toggles + broadcasts.
- **DoD:** Story DoD. **Priority:** P1 · **SP:** 8 · **Owner:** DO+BE · **Deps:** A4.
- **Tests:** Acceptance (maintenance broadcast; DLQ visible), Integration (health feeds), Security (admin-only). E2E (toggle maintenance). Others n/a.

| Task | Description | Related | Impl. notes | Blocking | Hrs | Cx | Risk |
|------|-------------|---------|-------------|----------|:--:|:--:|:--:|
| A3-T11 | Ops dashboard page (Horizon/health/DLQ) | A3-S04 | read + actions delegate | A4-T07 | 6 | M | Low |
| A3-T12 | Maintenance mode toggle + broadcast | A3-S04 | MaintenanceModeChanged | — | 4 | S | Low |
| A3-T13 | Alerts wiring | A3-S04 | thresholds → notify | A3-T11 | 4 | S | Low |

### Story A3-S05 — Providers, Secrets & Branding
- **Description:** Provider registry (Media/AI/Email/SMS/SSO) with encrypted configs; API keys; multi-brand/theme config.
- **Business Reason:** configure integrations + white-label without deploys.
- **Acceptance Criteria:** provider config CRUD (secrets encrypted via SecretsPort); API keys managed; brand resolves per host/tenant.
- **DoD:** Story DoD. **Priority:** P2 · **SP:** 5 · **Owner:** BE · **Deps:** A6-S02.
- **Tests:** Acceptance (brand resolves; provider configured), Security (secrets encrypted, not logged), Integration (port). A11y (admin forms). Others n/a.

| Task | Description | Related | Impl. notes | Blocking | Hrs | Cx | Risk |
|------|-------------|---------|-------------|----------|:--:|:--:|:--:|
| A3-T14 | Provider config registry (encrypted) | A3-S05 | via SecretsPort | A6-T04 | 6 | M | Med |
| A3-T15 | API key management | A3-S05 | scoped keys | A3-T14 | 4 | S | Low |
| A3-T16 | Brand/theme config + resolver | A3-S05 | host/tenant → brand | A3-T02 | 5 | M | Low |

### Sprint 3 checklists
- **Release Checklist:** capabilities default-on verified; audit center receiving from all contexts; ops console live; provider/brand config working on staging.
- **Rollback Checklist:** capability enforcement behind a master flag (enforce/observe); revert to observe-only; Administration tables additive.
- **Deployment Checklist:** expand admin tables; seed default capabilities (all on); deploy audit consumer; verify DLQ feed; set brand config for existing tenants.
- **Monitoring Checklist:** capability-denied rate (watch for accidental blocks), audit ingestion lag, ops console load, maintenance-mode broadcast receipt.
- **Team Allocation:** BE×2 (config/capabilities/audit/providers), DO (ops console/alerts), QA (audit + capability suites), SA (capability model), UX (admin pages), PO (capability catalog), TW (ops runbook).

---

# SPRINT 4 — Platform Capability Ports  ∥  Backend Relocation Chunks

- **Sprint Goal:** formalize Media/AI/Search/Integration behind ports; relocate Catalog & Live to `Contexts` under the Deptrac gate.
- **Duration:** 2 weeks. · **Parallel epics:** A5 (Platform stream) ∥ D4 (refactor stream).
- **Dependencies:** A3 (provider config), A1 (Deptrac gate for D4).
- **Risks:** moving embedded media providers may regress playback; relocation may break imports. Mitigate: port wraps existing impl (no behavior change); relocation is folder+namespace move gated on tests+Deptrac in low-churn window.
- **Definition of Done:** contexts consume Media/AI/Search/Integration only via ports; Catalog/Live under `Contexts`; all tests + Deptrac green.
- **Exit Criteria:** capability ports in place (part of M5 prerequisites); C9/C10 relocations merged.

## EPIC A5 — Platform Capability Ports
- **Epic ID:** A5 · **Objective:** Media/AI/Search/Integration as ports over existing implementations.
- **Business Value:** severs embedded coupling; one place for media signing, external I/O, search, AI safety.
- **Architecture Reference:** 05 Platform Integration (capabilities) + ADR-08/16; 99 TD-5/TD-6.
- **Dependencies:** A3. · **Priority:** P0 · **Risk:** Med · **Story Points:** 34.

### Story A5-S01 — Media Platform behind PlaybackPort/MediaPort
- **Description:** Wrap existing Mux/CloudFront/S3 providers behind `PlaybackPort` (playback tokens) and `MediaPort` (upload/refs); move Learning's embedded providers out (TD-6). Behavior identical.
- **Business Reason:** bytes belong to Media; contexts hold refs (ADR-08).
- **Acceptance Criteria:** Learning obtains playback via `PlaybackPort`; no raw `s3_key`/`mux_asset_id` leaves Media; playback behavior unchanged; Deptrac shows Learning no longer imports playback providers.
- **DoD:** Story DoD. **Priority:** P0 · **SP:** 13 · **Owner:** BE+SA · **Deps:** A3.
- **Tests:** Acceptance (signed token issued, expires), Unit (token TTL), Integration (port+adapter), Security (no raw id leaks), Architecture (Deptrac: Learning→Media via port only), Performance (token issue latency). E2E (play a lesson). A11y n/a.

| Task | Description | Related | Impl. notes | Blocking | Hrs | Cx | Risk |
|------|-------------|---------|-------------|----------|:--:|:--:|:--:|
| A5-T01 | Define PlaybackPort/MediaPort interfaces | A5-S01 | in Platform | — | 4 | S | Low |
| A5-T02 | Adapter over existing Mux/CloudFront/S3 | A5-S01 | wrap, no rewrite | A5-T01 | 8 | M | Med |
| A5-T03 | Repoint Learning media usage to port | A5-S01 | remove embedded providers | A5-T02 | 8 | L | Med |
| A5-T04 | MediaAssetReady/Failed events | A5-S01 | async | A5-T02 | 4 | S | Low |

### Story A5-S02 — Integration Platform (WebhookPort/OutboxPort)
- **Description:** Single external-I/O boundary; move gateway calls out of DB transactions (TD-5); HMAC verification + idempotent inbound webhooks.
- **Business Reason:** one place for security/retry/audit of third-party I/O (ADR-16).
- **Acceptance Criteria:** payment/webhook I/O flows through Integration; gateway call never inside a DB tx; inbound webhook verified + idempotent (`firstOrCreate(event_id)`).
- **DoD:** Story DoD. **Priority:** P0 · **SP:** 8 · **Owner:** BE · **Deps:** A4.
- **Tests:** Security (signature verified; replay idempotent), Integration (outbox delivery), Acceptance (gateway outside tx). Perf (webhook throughput). Others n/a.

| Task | Description | Related | Impl. notes | Blocking | Hrs | Cx | Risk |
|------|-------------|---------|-------------|----------|:--:|:--:|:--:|
| A5-T05 | WebhookPort + HMAC verify + idempotent inbound | A5-S02 | reuse Stripe sig | A4-T04 | 6 | M | Med |
| A5-T06 | Move gateway calls outside DB tx | A5-S02 | reorder; compensate on fail | A5-T05 | 6 | M | High |
| A5-T07 | Outbound webhook delivery + DLQ | A5-S02 | retry tiers | A4-T07 | 5 | M | Med |

### Story A5-S03 — Search & AI Platform ports
- **Description:** `SearchPort` (wrap Postgres FTS; per-context index ownership) and `AIProvider` port (safety/moderation, cost governance, human-in-the-loop).
- **Business Reason:** pluggable search engine; safe AI behind one abstraction.
- **Acceptance Criteria:** each context queries its index via `SearchPort`; AI calls go through `AIProvider` with moderation; no autonomous AI action.
- **DoD:** Story DoD. **Priority:** P1 · **SP:** 8 · **Owner:** BE+SA · **Deps:** A3.
- **Tests:** Integration (search port; AI port), Security (AI moderation gate; no autonomous action), Architecture (ports in Platform). Others n/a.

| Task | Description | Related | Impl. notes | Blocking | Hrs | Cx | Risk |
|------|-------------|---------|-------------|----------|:--:|:--:|:--:|
| A5-T08 | SearchPort + Postgres FTS adapter | A5-S03 | per-context index | — | 6 | M | Low |
| A5-T09 | AIProvider port + safety/cost governance | A5-S03 | human-in-loop enforced | A3-T14 | 6 | M | Med |

## EPIC D4 — Backend Relocation Chunks (C9/C10)
- **Epic ID:** D4 · **Objective:** move Catalog (C9) and Live (C10) from `Domains` to `Contexts`; update Filament map one line each.
- **Business Value:** completes the context taxonomy consistency (Contexts vs Platform); no behavior change.
- **Architecture Reference:** 99 chunks C9/C10; 05 data-map Filament (no branches).
- **Dependencies:** A1 (Deptrac + tests as the gate). · **Priority:** P1 · **Risk:** Med · **Story Points:** 13.

### Story D4-S01 — Relocate Catalog → Contexts (C9)
- **Description:** Folder + namespace move `App\Domains\Catalog → App\Contexts\Catalog`; repo-wide reference rewrite; Filament `RESOURCE_PATHS` one-line update; no schema/API/URL change.
- **Business Reason:** taxonomy consistency; smaller future diffs.
- **Acceptance Criteria:** `php artisan test` green; Deptrac green; `route:list` URIs unchanged; `/admin` shows Catalog resources; zero `App\Domains\Catalog` references remain.
- **DoD:** Story DoD + relocation report. **Priority:** P1 · **SP:** 8 · **Owner:** BE+SA · **Deps:** A1.
- **Tests:** Acceptance (tests+routes unchanged), Architecture (Deptrac; no remaining old ns). Others n/a.

| Task | Description | Related | Impl. notes | Blocking | Hrs | Cx | Risk |
|------|-------------|---------|-------------|----------|:--:|:--:|:--:|
| D4-T01 | Move Catalog tree + rewrite refs | D4-S01 | git mv; literal replace; `\\?\` long paths | — | 6 | M | Med |
| D4-T02 | Update Filament map + providers | D4-S01 | one map line; bootstrap/providers | D4-T01 | 2 | S | Low |
| D4-T03 | Verify (test/route/dump-autoload) + report | D4-S01 | Docker verify | D4-T02 | 3 | S | Low |

### Story D4-S02 — Relocate Live → Contexts (C10)
- **Description:** Same procedure for `App\Domains\Live → App\Contexts\Live`.
- **Business Reason:** taxonomy consistency.
- **Acceptance Criteria:** as C9 for Live; live-session tests green; Filament Live resources present.
- **DoD:** Story DoD + report. **Priority:** P2 · **SP:** 5 · **Owner:** BE · **Deps:** D4-S01.
- **Tests:** Acceptance + Architecture as C9. Others n/a.

| Task | Description | Related | Impl. notes | Blocking | Hrs | Cx | Risk |
|------|-------------|---------|-------------|----------|:--:|:--:|:--:|
| D4-T04 | Move Live tree + rewrite refs | D4-S02 | as D4-T01 | D4-T03 | 5 | M | Med |
| D4-T05 | Update map + verify + report | D4-S02 | as D4-T02/03 | D4-T04 | 3 | S | Low |

### Sprint 4 checklists
- **Release Checklist:** playback behavior unchanged (regression pack); webhook idempotency proven; Catalog/Live relocation reports attached; all tests + Deptrac green.
- **Rollback Checklist:** ports wrap existing impl → revert repoint (A5-T03) via flag; relocations are pure moves → `git revert` the move commit restores prior namespace.
- **Deployment Checklist:** deploy with feature parity (no schema change); run `composer dump-autoload`; verify `/admin` resources; canary playback + webhook.
- **Monitoring Checklist:** playback error/latency, webhook verify-fail rate, DLQ, 404/route drift after relocation.
- **Team Allocation:** BE×2 (ports; relocations), SA (port review + relocation gate), DO (deploy/verify), QA (playback + webhook regression), TW (relocation reports). PO tracks parity.

---

# SPRINT 5 — Content Ports & Versioning (Learning decoupling)

- **Sprint Goal:** Learning reads content **only via ports**; introduce content versioning + version pinning.
- **Duration:** 3 weeks (XL). · **Dependencies:** A5 (Media port), A1 (Deptrac).
- **Risks:** behavior drift when replacing direct model reads; versioning migration. Mitigate: ports wrap existing reads first (parity), then add versioning additively.
- **Definition of Done:** Deptrac shows Learning has zero logic-dependency on Authoring/Catalog models; attempts/progress consume a version-pinned snapshot.
- **Exit Criteria:** M5 (Content Ports & Versioning) met.

## EPIC B1 — Content Ports & Versioning
- **Epic ID:** B1 · **Objective:** CurriculumReadPort, AssessmentDefinitionPort, EntitlementPort + content versioning.
- **Business Value:** the core decoupling that makes Learning scalable and safe to evolve.
- **Architecture Reference:** 04 (ports, ADR-09/ADR-11), 01 (versioning), 05 EntitlementPort.
- **Dependencies:** A5. · **Priority:** P1 · **Risk:** High · **Story Points:** 42.

### Story B1-S01 — CurriculumReadPort (parity adapter)
- **Description:** Define `CurriculumReadPort` returning `{lessonRef, sectionRef, order, weight, isPreview, prereqRefs}` for a `CourseRef`; implement as an adapter over existing Catalog/Authoring reads. Repoint `ProgressService`/`LessonAccessService`/`ContinueLearningService` to it (TD-1).
- **Business Reason:** removes Learning's direct model coupling to content.
- **Acceptance Criteria:** progress/next-lesson/prereq logic reads via the port; identical results to before (parity tests); Deptrac denies Learning→Authoring/Catalog models.
- **DoD:** Story DoD. **Priority:** P1 · **SP:** 13 · **Owner:** BE+SA · **Deps:** A5.
- **Tests:** Acceptance (parity vs current), Unit (mapping), Integration (adapter over real reads), Architecture (Deptrac denial), Performance (snapshot fetch cached). E2E (learn page unchanged). A11y n/a.

| Task | Description | Related | Impl. notes | Blocking | Hrs | Cx | Risk |
|------|-------------|---------|-------------|----------|:--:|:--:|:--:|
| B1-T01 | Define CurriculumReadPort DTOs | B1-S01 | version-aware | — | 5 | M | Med |
| B1-T02 | Adapter over Catalog/Authoring reads | B1-S01 | parity first | B1-T01 | 8 | L | Med |
| B1-T03 | Repoint Learning services to port | B1-S01 | Progress/Access/Continue | B1-T02 | 8 | L | High |
| B1-T04 | Deptrac rule: Learning no content-model dep | B1-S01 | tighten baseline | B1-T03 | 3 | S | Low |

### Story B1-S02 — AssessmentDefinitionPort + EntitlementPort
- **Description:** Ports for Authoring assessment definitions and Commerce/Catalog entitlement; replace `is_preview`/paid logic inside Learning.
- **Business Reason:** definitions vs execution split (ADR-11); entitlement owned by Commerce/Catalog.
- **Acceptance Criteria:** Learning fetches definitions + entitlement decisions via ports; no content/entitlement rule authored inside Learning.
- **DoD:** Story DoD. **Priority:** P1 · **SP:** 13 · **Owner:** BE · **Deps:** B1-S01.
- **Tests:** Acceptance (access decision via port), Integration (both ports), Security (entitlement enforced), Architecture (Deptrac). Others n/a.

| Task | Description | Related | Impl. notes | Blocking | Hrs | Cx | Risk |
|------|-------------|---------|-------------|----------|:--:|:--:|:--:|
| B1-T05 | AssessmentDefinitionPort + adapter | B1-S02 | version-pinned | B1-T01 | 8 | L | Med |
| B1-T06 | EntitlementPort + adapter | B1-S02 | replace is_preview logic | B1-T02 | 6 | M | Med |
| B1-T07 | Repoint LessonAccessService | B1-S02 | port-only decisions | B1-T06 | 5 | M | Med |

### Story B1-S03 — Content versioning + pinning
- **Description:** Copy-on-write lesson/assessment versions; references pin a version; majors never auto-propagate (ADR-09).
- **Business Reason:** editing published content must not corrupt learner history.
- **Acceptance Criteria:** editing a published lesson creates a new version; a course pins a version; a major bump requires re-pin; attempts record the version they ran against.
- **DoD:** Story DoD. **Priority:** P1 · **SP:** 13 · **Owner:** BE+SA · **Deps:** B1-S01.
- **Tests:** Acceptance (edit → new version; pin stable), Unit (semver rules), Integration (propagation policy), Architecture (version-keyed cache), Performance (immutable snapshot cache hit). Others n/a.

| Task | Description | Related | Impl. notes | Blocking | Hrs | Cx | Risk |
|------|-------------|---------|-------------|----------|:--:|:--:|:--:|
| B1-T08 | Version schema (expand) + copy-on-write | B1-S03 | new version tables | B1-T01 | 8 | L | High |
| B1-T09 | Version pinning on references | B1-S03 | constraint {id,version} | B1-T08 | 6 | M | Med |
| B1-T10 | Propagation policy (patch/minor/major) | B1-S03 | major = manual re-pin | B1-T09 | 6 | M | Med |

### Sprint 5 checklists
- **Release Checklist:** parity suite green (identical progress/access results); Deptrac denial active; versioning migration expand-only; snapshot cache validated.
- **Rollback Checklist:** ports wrap existing reads → revert repoint via flag; versioning additive (old unversioned path preserved until contract release).
- **Deployment Checklist:** expand version tables; backfill v1 for existing content (online); enable port-read flag; verify learn/progress parity on canary.
- **Monitoring Checklist:** progress/access result diffs (parity canary), snapshot cache hit rate, version-table growth, port latency.
- **Team Allocation:** BE×3 (ports; versioning), SA (port contracts + Deptrac), QA (parity + versioning suites), DO (migration/backfill), TW (port + versioning docs), PO (version policy).

---

# SPRINT 6 — Progress Engine v2  ∥  Commerce Entitlement Hardening

- **Sprint Goal:** move progress rollup off the write path; make paid-enrollment exactly-once.
- **Duration:** 2 weeks. · **Parallel epics:** B2 (Learning stream) ∥ C1 (Commerce stream).
- **Dependencies:** B1 (ports/versioning), A4 (outbox); C1 also needs A5 (Integration).
- **Risks:** projector lag surprises users; refund/entitlement race. Mitigate: near-real-time projector + optimistic UI; outbox exactly-once + reconciliation.
- **Definition of Done:** progress write O(1) on request path; dashboards from read models; OrderPaid→EnrollmentGranted exactly-once.
- **Exit Criteria:** M6 (Learning Core v2, progress portion) + M9 (Commerce hardened) partially met.

## EPIC B2 — Progress Engine v2
- **Epic ID:** B2 · **Objective:** projector-based rollups + rebuildable read models.
- **Business Value:** scalability fix (TD-2) and the base for dashboards/leaderboards.
- **Architecture Reference:** 04 Progress Engine, ADR-10.
- **Dependencies:** B1, A4. · **Priority:** P1 · **Risk:** Med · **Story Points:** 34.

### Story B2-S01 — Version-keyed curriculum snapshot cache
- **Description:** Cache `{lessonRef,order,weight,isPreview,prereqRefs}` keyed by `CourseRef(id,version)` (immutable per version).
- **Business Reason:** O(1) progress math against a cached snapshot.
- **Acceptance Criteria:** snapshot cached per version; invalidated by `CourseVersionPublished`/`LessonPublished`; progress uses snapshot, not live queries.
- **DoD:** Story DoD. **Priority:** P1 · **SP:** 8 · **Owner:** BE · **Deps:** B1.
- **Tests:** Acceptance (progress uses cache), Integration (invalidation on publish), Performance (cache hit; no content query on write). Others n/a.

| Task | Description | Related | Impl. notes | Blocking | Hrs | Cx | Risk |
|------|-------------|---------|-------------|----------|:--:|:--:|:--:|
| B2-T01 | Snapshot builder + version cache | B2-S01 | Redis; version key | B1-T09 | 6 | M | Med |
| B2-T02 | Invalidate on publish events | B2-S01 | subscribe Catalog/Authoring | B2-T01 | 4 | S | Low |

### Story B2-S02 — Projector-based rollup off write path
- **Description:** Progress writes emit `ProgressUpdated`; a projector recomputes section/course rollups against the snapshot; `CourseProgressView` read model.
- **Business Reason:** removes O(lessons) synchronous recompute (ADR-10).
- **Acceptance Criteria:** progress write returns without full recompute; rollups eventually consistent; completion still idempotent; view rebuildable.
- **DoD:** Story DoD. **Priority:** P1 · **SP:** 13 · **Owner:** BE+SA · **Deps:** B2-S01, A4.
- **Tests:** Acceptance (write O(1); rollup correct), Unit (calculator), Integration (projector), Performance (write p95 budget), Architecture (rebuild-from-events). Others n/a.

| Task | Description | Related | Impl. notes | Blocking | Hrs | Cx | Risk |
|------|-------------|---------|-------------|----------|:--:|:--:|:--:|
| B2-T03 | ProgressCalculator (pure) | B2-S02 | weighted % | B2-T01 | 5 | M | Low |
| B2-T04 | Rollup projector on ProgressUpdated | B2-S02 | off request path | B2-T03 | 8 | L | Med |
| B2-T05 | CourseProgressView read model | B2-S02 | rebuildable | B2-T04 | 5 | M | Low |

### Story B2-S03 — Dashboard / ContinueLearning / Gradebook read models
- **Description:** Rebuildable read models feeding `/me/dashboard`, `/continue-learning`, gradebook.
- **Business Reason:** cheap reads; offline snapshots; removes live cross-context joins.
- **Acceptance Criteria:** views served from read models; drop-and-replay rebuilds them; `/continue-learning` parity maintained.
- **DoD:** Story DoD. **Priority:** P2 · **SP:** 13 · **Owner:** BE · **Deps:** B2-S02.
- **Tests:** Acceptance (views correct), Integration (projector), Architecture (rebuild), Performance (read latency). Others n/a.

| Task | Description | Related | Impl. notes | Blocking | Hrs | Cx | Risk |
|------|-------------|---------|-------------|----------|:--:|:--:|:--:|
| B2-T06 | Dashboard projector + view | B2-S03 | subscribes many events | B2-T04 | 8 | L | Med |
| B2-T07 | ContinueLearning + Gradebook views | B2-S03 | rebuildable | B2-T06 | 6 | M | Low |

## EPIC C1 — Entitlement & Payments Hardening
- **Epic ID:** C1 · **Objective:** guaranteed entitlement; refund→revoke; gateway outside tx; tenant billing config.
- **Business Value:** reliable monetization; no lost/double entitlement.
- **Architecture Reference:** 05 Commerce + ADR-16; 99 Phase C.
- **Dependencies:** A4, A5, B1. · **Priority:** P1 · **Risk:** High · **Story Points:** 26.

### Story C1-S01 — Guaranteed OrderPaid → EnrollmentGranted
- **Description:** Emit `OrderPaid` and grant entitlement via the outbox (exactly-once effect); Learning consumes to grant enrollment.
- **Business Reason:** paid learners must always get access, once.
- **Acceptance Criteria:** double webhook = single enrollment; crash mid-grant recovers; entitlement idempotent.
- **DoD:** Story DoD. **Priority:** P1 · **SP:** 13 · **Owner:** BE · **Deps:** A4, B1.
- **Tests:** Acceptance (double-pay single grant), Integration (outbox path), Security (idempotent webhook), Performance (grant latency), E2E (buy → access). Others n/a.

| Task | Description | Related | Impl. notes | Blocking | Hrs | Cx | Risk |
|------|-------------|---------|-------------|----------|:--:|:--:|:--:|
| C1-T01 | OrderPaid via outbox | C1-S01 | guaranteed | A4-T02 | 6 | M | Med |
| C1-T02 | EntitlementPort grant consumer (Learning) | C1-S01 | idempotent | C1-T01 | 6 | M | Med |
| C1-T03 | Reconciliation job (paid-but-not-granted) | C1-S01 | safety net | C1-T02 | 5 | M | Med |

### Story C1-S02 — Refund→revoke + gateway-outside-tx + billing config
- **Description:** Refund policy revokes/adjusts entitlement; ensure gateway calls are outside DB transactions (TD-5); per-tenant billing config.
- **Business Reason:** consistency + the known transaction-safety fix.
- **Acceptance Criteria:** refund triggers policy; no gateway call inside a DB tx (test asserts); tenant billing config drives checkout.
- **DoD:** Story DoD. **Priority:** P2 · **SP:** 13 · **Owner:** BE · **Deps:** A5, C1-S01.
- **Tests:** Acceptance (refund → revoke policy), Security (no gateway-in-tx), Integration (billing config). Others n/a.

| Task | Description | Related | Impl. notes | Blocking | Hrs | Cx | Risk |
|------|-------------|---------|-------------|----------|:--:|:--:|:--:|
| C1-T04 | Refund→revoke policy + event | C1-S02 | configurable | C1-T02 | 6 | M | Med |
| C1-T05 | Gateway calls outside DB tx | C1-S02 | reorder + compensate | A5-T06 | 6 | M | High |
| C1-T06 | Tenant billing config | C1-S02 | from Administration | A3-T02 | 5 | M | Low |

### Sprint 6 checklists
- **Release Checklist:** progress-write p95 budget met; dashboards from read models; exactly-once entitlement proven (double-webhook test); no gateway-in-tx (test).
- **Rollback Checklist:** projector behind flag → fall back to synchronous recompute; entitlement grant path revertible to prior direct grant; refund policy flag.
- **Deployment Checklist:** expand read-model + outbox consumer tables; deploy projectors + relay; enable projector flag on canary; verify parity + entitlement.
- **Monitoring Checklist:** progress-write latency, projector lag, dashboard read latency, entitlement grant success, reconciliation hits, refund policy actions.
- **Team Allocation:** BE×3 (B2 projectors; C1 entitlement), SA (projector + outbox review), QA (parity + payments suites), DO (workers), PO (billing config), TW (docs).

---

# SPRINT 7 — Assessment Execution

- **Sprint Goal:** run Authoring-defined assessments; own attempts/grades in Learning.
- **Duration:** 3 weeks (XL). · **Dependencies:** B1 (definition port + versioning).
- **Risks:** grade correctness across versions; exam integrity. Mitigate: attempt pins version; exams online-only; extensive grading tests.
- **Definition of Done:** attempts pin definition version; auto/manual/peer grading; retries/late; exams online-only.
- **Exit Criteria:** M6 (Learning Core v2, assessment portion) met.

## EPIC B3 — Assessment Execution
- **Epic ID:** B3 · **Objective:** attempts, submissions, grades aggregates + grading engine.
- **Business Value:** closes the biggest feature gap (TD-11); enables graded learning + certification exams.
- **Architecture Reference:** 04 Assessment Execution, 03 Assessment Framework, ADR-11.
- **Dependencies:** B1. · **Priority:** P1 · **Risk:** High · **Story Points:** 42.

### Story B3-S01 — Attempt lifecycle + version pinning
- **Description:** `AssessmentAttempt` aggregate with state machine (Created→InProgress→Submitted→Graded→Expired), pinning `AssessmentDefinitionRef(id,version)` and recording the exact question set/seed.
- **Business Reason:** stable grades across content re-versioning.
- **Acceptance Criteria:** attempt pins a version; no edits after Submitted; timeout → Expired; recorded seed reproduces the question set.
- **DoD:** Story DoD. **Priority:** P1 · **SP:** 13 · **Owner:** BE+SA · **Deps:** B1.
- **Tests:** Acceptance (pin + no-edit-after-submit), Unit (state machine), Integration (definition port), Security (no answer tamper), Performance (attempt start latency). E2E (take a quiz). A11y (quiz UI later in B7). 

| Task | Description | Related | Impl. notes | Blocking | Hrs | Cx | Risk |
|------|-------------|---------|-------------|----------|:--:|:--:|:--:|
| B3-T01 | Attempt + response schema (expand) | B3-S01 | pins version | B1-T05 | 8 | L | Med |
| B3-T02 | Attempt state machine + StartAttempt | B3-S01 | seed recorded | B3-T01 | 8 | L | Med |
| B3-T03 | SaveDraft/Submit + immutability | B3-S01 | no post-submit edit | B3-T02 | 6 | M | Med |

### Story B3-S02 — Grading engine (auto/manual/peer) + policies
- **Description:** Auto-grade against definition; manual grading over rubric; peer review; passing/retry/late policies; emit `AssessmentGraded/Passed/Failed`.
- **Business Reason:** full assessment matrix from redesign 03/04.
- **Acceptance Criteria:** auto-grade correct per definition; manual override recorded; peer flow works; late/retry honored; passed feeds Certification/Mastery.
- **DoD:** Story DoD. **Priority:** P1 · **SP:** 13 · **Owner:** BE · **Deps:** B3-S01.
- **Tests:** Acceptance (each grading mode), Unit (grader), Integration (events to Certification/Mastery), Security (grade authz). Others n/a.

| Task | Description | Related | Impl. notes | Blocking | Hrs | Cx | Risk |
|------|-------------|---------|-------------|----------|:--:|:--:|:--:|
| B3-T04 | AttemptGrader (auto) | B3-S02 | per definition | B3-T02 | 8 | L | Med |
| B3-T05 | Manual grade + rubric override | B3-S02 | emits ManualGradeRecorded | B3-T04 | 6 | M | Med |
| B3-T06 | Peer review flow | B3-S02 | reviewer assignment | B3-T04 | 6 | M | Med |
| B3-T07 | Passing/retry/late policies | B3-S02 | config per type | B3-T04 | 5 | M | Low |

### Story B3-S03 — Assignment submissions + gradebook
- **Description:** `AssignmentSubmission` (artifacts as AssetRefs), grade, `GradebookView`; exams online-only enforcement.
- **Business Reason:** assignments + a unified gradebook.
- **Acceptance Criteria:** submission with artifacts; grade recorded; gradebook read model; exams cannot start offline.
- **DoD:** Story DoD. **Priority:** P2 · **SP:** 13 · **Owner:** BE · **Deps:** B3-S02.
- **Tests:** Acceptance (submit+grade; exam online-only), Integration (gradebook projector), Security (artifact access), Performance (gradebook read). Others n/a.

| Task | Description | Related | Impl. notes | Blocking | Hrs | Cx | Risk |
|------|-------------|---------|-------------|----------|:--:|:--:|:--:|
| B3-T08 | Submission schema + SubmitAssignment | B3-S03 | AssetRefs via Media | B3-T01 | 6 | M | Med |
| B3-T09 | Gradebook projector + view | B3-S03 | rebuildable | B3-T05 | 6 | M | Low |
| B3-T10 | Exam online-only guard | B3-S03 | integrity | B3-T02 | 3 | S | Low |

### Sprint 7 checklists
- **Release Checklist:** grading correctness suite green (all modes); version-pin stability proven; exam online-only enforced; gradebook rebuildable.
- **Rollback Checklist:** assessment behind capability (default-off) → disable per tenant; additive tables.
- **Deployment Checklist:** expand attempt/submission/gradebook tables; deploy grading projector; enable assessment capability on pilot tenant.
- **Monitoring Checklist:** attempt start/submit rates, grading latency, failed auto-grades, gradebook lag, exam-offline-attempt denials.
- **Team Allocation:** BE×3 (attempts; grading; assignments), SA (state machine/versioning), QA (grading matrix suite), PO (assessment policies), UX (grading UI spec for B7), TW (assessment docs).

---

# SPRINT 8 — Learning Record Store  ∥  Gamification / Paths / Mastery

- **Sprint Goal:** append-only learning ledger + engagement/progression systems.
- **Duration:** 2 weeks. · **Parallel epics:** B4 (ledger) ∥ B5 (gamification/paths/mastery). Both depend on B2.
- **Dependencies:** B2 (read-model/event base), B3 (evidence from attempts).
- **Risks:** ledger growth/retention; leaderboard hot-path. Mitigate: hot/cold retention + crypto-shred; materialized leaderboards.
- **Definition of Done:** any projection drop-and-replay from LRS; XP idempotent; competency from evidence only.
- **Exit Criteria:** M7 (LRS + Engagement) met.

## EPIC B4 — Learning Record Store & Analytics Projection
- **Epic ID:** B4 · **Objective:** experience-event ledger + xAPI/SCORM/cmi5 + published analytics projection.
- **Business Value:** auditability, replay, standards interop, decoupled analytics.
- **Architecture Reference:** 04 LRS refinement, ADR-13/18.
- **Dependencies:** B2. · **Priority:** P2 · **Risk:** Med · **Story Points:** 26.

### Story B4-S01 — Experience-event ledger + replay
- **Description:** Append-only ledger as source of truth; rebuild any read model by replay; audit metadata (actor/device/time/command).
- **Acceptance Criteria:** every Learning command writes an experience event; drop-and-replay reconstructs dashboards/gradebook/leaderboard; audit query answerable.
- **Business Reason:** the backbone for audit/replay/analytics.
- **DoD:** Story DoD. **Priority:** P2 · **SP:** 13 · **Owner:** BE+SA · **Deps:** B2.
- **Tests:** Acceptance (replay rebuild), Integration (event capture), Architecture (rebuildable), Performance (append throughput), Security (pseudonymized actor). Others n/a.

| Task | Description | Related | Impl. notes | Blocking | Hrs | Cx | Risk |
|------|-------------|---------|-------------|----------|:--:|:--:|:--:|
| B4-T01 | Ledger schema (append-only) + writer | B4-S01 | hot/cold | B2-T04 | 8 | L | Med |
| B4-T02 | Replay/rebuild command | B4-S01 | projection rebuild | B4-T01 | 6 | M | Med |
| B4-T03 | GDPR crypto-shred of actor key | B4-S01 | erasure w/ evidence retained | B4-T01 | 5 | M | Med |

### Story B4-S02 — xAPI/SCORM/cmi5 + published analytics projection
- **Description:** Map events to xAPI; normalize SCORM/cmi5 runtime into events; publish `LearningAnalyticsProjection` for Analytics (ADR-18); `LrsExportPort`.
- **Acceptance Criteria:** xAPI statements produced; SCORM cmi.* normalized; Analytics consumes the projection (not concrete events); external LRS export works.
- **Business Reason:** interop + analytics decoupling.
- **DoD:** Story DoD. **Priority:** P2 · **SP:** 13 · **Owner:** BE · **Deps:** B4-S01.
- **Tests:** Acceptance (xAPI/SCORM mapping), Integration (projection consumed), Architecture (Analytics reads projection only). Others n/a.

| Task | Description | Related | Impl. notes | Blocking | Hrs | Cx | Risk |
|------|-------------|---------|-------------|----------|:--:|:--:|:--:|
| B4-T04 | xAPI statement mapping + LrsExportPort | B4-S02 | IRI from refs | B4-T01 | 6 | M | Med |
| B4-T05 | SCORM/cmi5 normalization | B4-S02 | cmi.* → events | B4-T01 | 6 | M | Med |
| B4-T06 | Publish LearningAnalyticsProjection | B4-S02 | Analytics consumes | B4-T02 | 5 | M | Low |

## EPIC B5 — Gamification, Paths, Mastery
- **Epic ID:** B5 · **Objective:** XP/badges/streaks/leaderboards, path execution, competency/skill mastery.
- **Business Value:** engagement + progression + enterprise competency tracking.
- **Architecture Reference:** 04 Gamification/Paths/Competency, 04 refinement (Competency framework).
- **Dependencies:** B2. · **Priority:** P2 · **Risk:** Med · **Story Points:** 34.

### Story B5-S01 — Event-sourced gamification
- **Description:** XP ledger (idempotent by sourceEventId), levels, badges, daily/weekly streaks, materialized leaderboards, challenges/rewards.
- **Acceptance Criteria:** XP additive + idempotent; leaderboards materialized (no live scan); streaks recomputed from activity; rewards recorded (money delegated to Commerce).
- **Business Reason:** motivation, offline-safe.
- **DoD:** Story DoD. **Priority:** P2 · **SP:** 13 · **Owner:** BE · **Deps:** B2.
- **Tests:** Acceptance (XP idempotent; leaderboard correct), Unit (streak calc), Integration (events), Performance (leaderboard read), Security (scope). Others n/a.

| Task | Description | Related | Impl. notes | Blocking | Hrs | Cx | Risk |
|------|-------------|---------|-------------|----------|:--:|:--:|:--:|
| B5-T01 | XP ledger + level function | B5-S01 | idempotent | B2-T04 | 6 | M | Low |
| B5-T02 | Badges + streaks rules engine | B5-S01 | subscribes events | B5-T01 | 6 | M | Med |
| B5-T03 | Materialized leaderboards | B5-S01 | scope-based | B5-T01 | 6 | M | Med |

### Story B5-S02 — Path execution + Mastery framework
- **Description:** `PathEnrollment` execution; competency/skill mastery from `EvidenceRef` (attempts), decay + recertification signals; sync with Certification.
- **Acceptance Criteria:** path steps advance monotonically; mastery derived only from evidence; decay downgrades; `CompetencyAttained` feeds Certification.
- **Business Reason:** journeys + enterprise competency tracking.
- **DoD:** Story DoD. **Priority:** P2 · **SP:** 13 · **Owner:** BE+SA · **Deps:** B3, B5-S01.
- **Tests:** Acceptance (mastery from evidence; path advance), Integration (Certification sync), Architecture (no hand-set mastery). Others n/a.

| Task | Description | Related | Impl. notes | Blocking | Hrs | Cx | Risk |
|------|-------------|---------|-------------|----------|:--:|:--:|:--:|
| B5-T04 | PathEnrollment execution | B5-S02 | monotonic | B2-T04 | 6 | M | Med |
| B5-T05 | Mastery from evidence + decay | B5-S02 | scheduled recompute | B3-T04 | 8 | L | Med |
| B5-T06 | Competency↔Certification sync | B5-S02 | events + refs | B5-T05 | 5 | M | Low |

### Sprint 8 checklists
- **Release Checklist:** replay rebuild verified; xAPI export validated; Analytics on projection; XP idempotent + leaderboard materialized; mastery-from-evidence enforced.
- **Rollback Checklist:** gamification/paths behind capability (default-off); LRS additive; Analytics can temporarily read prior events (dual-path) during cutover.
- **Deployment Checklist:** expand ledger + gamification + path + mastery tables; deploy projectors; enable capabilities on pilot; verify replay.
- **Monitoring Checklist:** ledger growth/retention jobs, replay duration, leaderboard refresh lag, XP dedupe rate, mastery recompute time.
- **Team Allocation:** BE×3 (LRS; gamification; paths/mastery), SA (ledger + analytics decoupling), QA (replay + idempotency), DO (retention/cold storage), PO (gamification rules), TW (LRS/xAPI docs).

---

# SPRINT 9 — Offline & Multi-Device Sync

- **Sprint Goal:** offline-first learning with deterministic multi-device merge.
- **Duration:** 3 weeks (XL). · **Dependencies:** B2 (progress/read models), B3 (attempts).
- **Risks:** merge corrupting learner state. Mitigate: deterministic per-aggregate merge, completion monotonic, conflict quarantine, property-based tests.
- **Definition of Done:** offline lessons/progress/notes/(practice+graded) assessments; sync push/pull idempotent; completion never regresses; conflicts quarantined; exams online-only.
- **Exit Criteria:** M8 (Offline & Multi-Device) met.

## EPIC B6 — Offline & Multi-Device Sync
- **Epic ID:** B6 · **Objective:** idempotent offline writes + version-vectored multi-device sync + merge.
- **Business Value:** mobile/field learning; seamless device switching.
- **Architecture Reference:** 04 Offline/Sync/Conflict, ADR-15.
- **Dependencies:** B2, B3. · **Priority:** P2 · **Risk:** High · **Story Points:** 42.

### Story B6-S01 — Idempotent sync engine (push/pull)
- **Description:** `POST /sync/push` + `GET /sync/pull` with `clientMutationId` idempotency and version vectors; durable queue + retry.
- **Acceptance Criteria:** replayed batch = single effect; pull returns changes since cursor; retries safe.
- **Business Reason:** durable offline UX.
- **DoD:** Story DoD. **Priority:** P2 · **SP:** 13 · **Owner:** BE · **Deps:** B2.
- **Tests:** Acceptance (replay = single effect), Integration (push/pull cursor), Security (tenant/user scope), Performance (batch throughput), E2E (offline→reconnect). Others n/a.

| Task | Description | Related | Impl. notes | Blocking | Hrs | Cx | Risk |
|------|-------------|---------|-------------|----------|:--:|:--:|:--:|
| B6-T01 | Sync schema (envelope/queue/state) | B6-S01 | clientMutationId | B2-T04 | 8 | L | Med |
| B6-T02 | Push/pull endpoints + idempotency | B6-S01 | version vectors | B6-T01 | 8 | L | High |
| B6-T03 | Retry/queue drain | B6-S01 | backoff; DLQ on poison | B6-T02 | 5 | M | Med |

### Story B6-S02 — Deterministic merge + conflict handling
- **Description:** Per-aggregate `MergeResolver` (progress max/monotonic, session latest-checkpoint, notes LWW, XP commutative, attempt single-writer); quarantine unresolvable conflicts.
- **Acceptance Criteria:** completion never regresses; resume = latest checkpoint; unresolvable → `SyncConflictDetected` quarantine (not dropped).
- **Business Reason:** safe multi-device without data loss.
- **DoD:** Story DoD. **Priority:** P2 · **SP:** 13 · **Owner:** BE+SA · **Deps:** B6-S01.
- **Tests:** Acceptance (merge per aggregate), Unit (each policy; property-based), Integration (conflict quarantine), Architecture (monotonic completion). Others n/a.

| Task | Description | Related | Impl. notes | Blocking | Hrs | Cx | Risk |
|------|-------------|---------|-------------|----------|:--:|:--:|:--:|
| B6-T04 | MergeResolver per aggregate | B6-S02 | deterministic | B6-T02 | 8 | L | High |
| B6-T05 | Conflict quarantine + surface | B6-S02 | ConflictRecord | B6-T04 | 6 | M | Med |
| B6-T06 | Resume-priority policy | B6-S02 | device tiebreak | B6-T04 | 5 | M | Med |

### Story B6-S03 — Offline media + accessibility bundle
- **Description:** Offline media licenses via `PlaybackPort`; package captions/transcripts/AD per `AccessibilityProfile`; practice/graded attempts offline (exams excluded).
- **Acceptance Criteria:** lessons playable offline with required a11y tracks; offline attempts sync back; exams blocked offline.
- **Business Reason:** true offline learning incl. accessibility.
- **DoD:** Story DoD. **Priority:** P3 · **SP:** 13 · **Owner:** BE+FE · **Deps:** A5, B3, B6-S01.
- **Tests:** Acceptance (offline play + a11y), Integration (offline attempt sync), Security (license expiry), Accessibility (offline captions/transcripts), E2E (airplane-mode journey). Others n/a.

| Task | Description | Related | Impl. notes | Blocking | Hrs | Cx | Risk |
|------|-------------|---------|-------------|----------|:--:|:--:|:--:|
| B6-T07 | Offline media license (PlaybackPort) | B6-S03 | includes a11y tracks | A5-T02 | 6 | M | Med |
| B6-T08 | Offline attempt queue (practice/graded) | B6-S03 | exam guard | B3-T02 | 6 | M | Med |
| B6-T09 | Offline shell + sync UI (web) | B6-S03 | queue status | B6-T02 | 8 | L | Med |

### Sprint 9 checklists
- **Release Checklist:** merge property tests green; completion-never-regresses proven; conflict quarantine works; exams offline-blocked; offline a11y verified.
- **Rollback Checklist:** offline behind capability (default-off); online path unaffected (empty version vector special case).
- **Deployment Checklist:** expand sync tables; deploy sync endpoints + resolver; enable offline capability on pilot; verify airplane-mode E2E.
- **Monitoring Checklist:** sync push/pull rates, conflict/quarantine counts, merge errors, offline-license issuance, retry-queue depth.
- **Team Allocation:** BE×2 (sync engine; merge), FE×1 (offline shell), SA (merge policies), QA (property-based + E2E), DO (queue scaling), PO (offline scope), TW (offline/sync runbook).

---

# SPRINT 10 — Learning Frontend  ∥  Commerce Frontend & Ops

- **Sprint Goal:** ship the learner-facing UI for the new backends; complete commerce UI + billing ops.
- **Duration:** 2 weeks. · **Parallel epics:** B7 (FE learning) ∥ C2 (FE commerce).
- **Dependencies:** B2–B6 (learning backends), C1 (commerce backend).
- **Risks:** UI outpacing backend readiness; accessibility gaps. Mitigate: read-model-driven views; a11y gate in CI.
- **Definition of Done:** dashboard/player/attempts/offline UI live from read models; checkout/orders/billing UI parity; a11y AA on new screens.
- **Exit Criteria:** M6 (frontend portion) + M9 (commerce UI) met.

## EPIC B7 — Learning Frontend
- **Epic ID:** B7 · **Objective:** player + progress + attempts + dashboard + offline shell + accessibility application.
- **Business Value:** the actual learner experience for the redesigned core.
- **Architecture Reference:** 04 (read models, AccessibilityProfile), 06 frontend review.
- **Dependencies:** B2–B6. · **Priority:** P2 · **Risk:** Med · **Story Points:** 34.

### Story B7-S01 — Dashboard & continue-learning from read models
- **Description:** `/me/dashboard` + `/continue-learning` consuming read models; optimistic progress; skeleton/empty/error states.
- **Acceptance Criteria:** views hydrate from read models; resume works cross-device; states covered.
- **Business Reason:** cheap, fast learner home.
- **DoD:** Story DoD. **Priority:** P2 · **SP:** 8 · **Owner:** FE · **Deps:** B2.
- **Tests:** Acceptance (renders read model), Unit (hooks), Integration (msw), E2E (dashboard→resume), Accessibility (axe AA), Performance (TTI budget). Others n/a.

| Task | Description | Related | Impl. notes | Blocking | Hrs | Cx | Risk |
|------|-------------|---------|-------------|----------|:--:|:--:|:--:|
| B7-T01 | Dashboard page + hooks | B7-S01 | TanStack Query | — | 8 | M | Low |
| B7-T02 | Continue-learning + resume | B7-S01 | cross-device | B7-T01 | 5 | M | Low |

### Story B7-S02 — Player + assessment + gamification UI
- **Description:** Lesson player (signed playback), attempt/quiz/assignment UI, gamification widgets (XP/badges/streaks/leaderboard).
- **Acceptance Criteria:** player plays via token; attempts submit/save; gamification reflects events; captions/transcripts available.
- **Business Reason:** the interactive learning surface.
- **DoD:** Story DoD. **Priority:** P2 · **SP:** 13 · **Owner:** FE+UX · **Deps:** B3, B5.
- **Tests:** Acceptance (play/attempt/gamify), E2E (take graded quiz), Accessibility (player + quiz AA), Security (no raw media id in client), Integration (msw). Others n/a.

| Task | Description | Related | Impl. notes | Blocking | Hrs | Cx | Risk |
|------|-------------|---------|-------------|----------|:--:|:--:|:--:|
| B7-T03 | Player w/ signed playback + captions | B7-S02 | PlaybackPort token | B7-T01 | 8 | L | Med |
| B7-T04 | Attempt/quiz/assignment UI | B7-S02 | draft/submit | B7-T03 | 8 | L | Med |
| B7-T05 | Gamification widgets | B7-S02 | XP/badges/leaderboard | B7-T01 | 6 | M | Low |

### Story B7-S03 — Offline shell + AccessibilityProfile application
- **Description:** Offline UI (queue status/sync), apply `AccessibilityProfile` (captions/dyslexia/contrast/reduced-motion/extra-time).
- **Acceptance Criteria:** offline mode usable; profile persists + syncs; assessment accommodations applied.
- **Business Reason:** offline + inclusive learning.
- **DoD:** Story DoD. **Priority:** P3 · **SP:** 13 · **Owner:** FE+UX · **Deps:** B6.
- **Tests:** Acceptance (offline + profile), Accessibility (AA + accommodations), E2E (airplane-mode), Integration (sync UI). Others n/a.

| Task | Description | Related | Impl. notes | Blocking | Hrs | Cx | Risk |
|------|-------------|---------|-------------|----------|:--:|:--:|:--:|
| B7-T06 | Offline shell + sync status UI | B7-S03 | queue indicator | B7-T03 | 8 | L | Med |
| B7-T07 | AccessibilityProfile settings + apply | B7-S03 | synced VO | B7-T04 | 6 | M | Low |

## EPIC C2 — Commerce Frontend & Ops
- **Epic ID:** C2 · **Objective:** checkout/orders/contracts parity + billing config admin + dunning surfaces.
- **Business Value:** complete purchase experience + operable billing.
- **Architecture Reference:** 05 Commerce; 06 frontend review.
- **Dependencies:** C1. · **Priority:** P2 · **Risk:** Med · **Story Points:** 16.

### Story C2-S01 — Checkout/orders/contracts UI parity
- **Description:** Ensure UI reflects hardened entitlement flow; order/contract states; refund status.
- **Acceptance Criteria:** buy→access reflected; order/contract/refund states shown; errors handled.
- **Business Reason:** trustworthy purchase UX.
- **DoD:** Story DoD. **Priority:** P2 · **SP:** 8 · **Owner:** FE · **Deps:** C1.
- **Tests:** Acceptance (states render), E2E (checkout→access), Accessibility (checkout AA), Security (no card data in app), Integration (msw). Others n/a.

| Task | Description | Related | Impl. notes | Blocking | Hrs | Cx | Risk |
|------|-------------|---------|-------------|----------|:--:|:--:|:--:|
| C2-T01 | Checkout/orders/contracts states | C2-S01 | entitlement-aware | — | 6 | M | Low |
| C2-T02 | Refund/dunning status surfaces | C2-S01 | read-only | C2-T01 | 5 | M | Low |

### Story C2-S02 — Billing config admin (Filament)
- **Description:** Tenant billing configuration UI delegating to Commerce/Administration Actions.
- **Acceptance Criteria:** per-tenant billing config editable; changes audited; delegates (no logic in Filament).
- **Business Reason:** operable enterprise billing.
- **DoD:** Story DoD. **Priority:** P3 · **SP:** 8 · **Owner:** FE+BE · **Deps:** C1-S02.
- **Tests:** Acceptance (config saved+audited), Architecture (Filament delegates), Accessibility (admin form). Others n/a.

| Task | Description | Related | Impl. notes | Blocking | Hrs | Cx | Risk |
|------|-------------|---------|-------------|----------|:--:|:--:|:--:|
| C2-T03 | Billing config Filament page | C2-S02 | delegates to Action | — | 6 | M | Low |

### Sprint 10 checklists
- **Release Checklist:** a11y AA on all new screens; E2E learner + checkout journeys green; no raw media/card data in client bundle.
- **Rollback Checklist:** new UI behind route flags; revert to prior pages; backend unaffected.
- **Deployment Checklist:** web build + deploy; enable learning/commerce UI flags on pilot tenant; verify E2E on canary.
- **Monitoring Checklist:** TTI/LCP budgets, JS error rate, player start failures, checkout conversion/error, a11y violations (axe CI).
- **Team Allocation:** FE×3 (learning; commerce; offline/a11y), UX (player/quiz/accommodations), QA (E2E + axe), BE×1 (billing action), PO (parity), TW (help docs).

---

# SPRINT 11 — Organization Split  ∥  Instructor Split

- **Sprint Goal:** extract Organization from CRM and Instructor from Catalog/Identity (folder+namespace moves, no schema/API change).
- **Duration:** 3 weeks. · **Parallel epics:** D1 (Organization) ∥ D2 (Instructor). Independent of each other.
- **Dependencies:** A2 (tenant), A3 (capabilities), A1 (Deptrac gate).
- **Risks:** split breaks references/tests. Mitigate: gated moves; Deptrac + full test suite per chunk; low-churn window.
- **Definition of Done:** Organization + Instructor are distinct contexts; Instructor owns zero lesson data; all tests + Deptrac green; APIs/URLs unchanged.
- **Exit Criteria:** M10 (Enterprise, splits portion) met.

## EPIC D1 — Organization Context Split
- **Epic ID:** D1 · **Objective:** split Organization (tenant business entity) from CRM (sales record) per redesign 02 (chunk C11).
- **Business Value:** clean tenant/relationship ownership; enterprise org features.
- **Architecture Reference:** 02 CRM/Organization redesign; 99 chunk C11.
- **Dependencies:** A2, A3. · **Priority:** P1 · **Risk:** High · **Story Points:** 34.

### Story D1-S01 — Extract Organization aggregate + ownership
- **Description:** Move org entity/membership/roles/lifecycle/audit + consulting-request into an Organization context; CRM keeps sales record/timeline/consulting-delivery.
- **Acceptance Criteria:** Organization owns tenant business entity + membership; CRM references org; timeline stays CRM-only; no schema/API change; tests + Deptrac green.
- **Business Reason:** correct ownership (02) + enterprise org management.
- **DoD:** Story DoD + split report. **Priority:** P1 · **SP:** 21 · **Owner:** BE+SA · **Deps:** A2.
- **Tests:** Acceptance (ownership honored; APIs unchanged), Architecture (Deptrac; CRM→Org via ref), Integration (events), Security (org isolation). Others n/a.

| Task | Description | Related | Impl. notes | Blocking | Hrs | Cx | Risk |
|------|-------------|---------|-------------|----------|:--:|:--:|:--:|
| D1-T01 | Carve Organization namespace/folders | D1-S01 | move org models/services | — | 10 | L | High |
| D1-T02 | Repoint CRM to org refs (ACL) | D1-S01 | no direct model use | D1-T01 | 8 | L | High |
| D1-T03 | Org roles/capabilities + audit trail | D1-S01 | own audit | D1-T01 | 6 | M | Med |
| D1-T04 | Verify (tests/routes/Deptrac) + report | D1-S01 | Docker verify | D1-T02 | 4 | S | Med |

### Story D1-S02 — Org capabilities + templates + lifecycle
- **Description:** OrganizationType as template-only; capabilities as runtime authority; org lifecycle; consulting request vs delivery seam.
- **Acceptance Criteria:** org capabilities gate org features; type is template; lifecycle transitions audited; consulting request (Org) vs delivery (CRM) separated.
- **Business Reason:** matches 02 refinements.
- **DoD:** Story DoD. **Priority:** P2 · **SP:** 13 · **Owner:** BE · **Deps:** D1-S01, A3.
- **Tests:** Acceptance (capability gates; lifecycle), Integration (events), Architecture (template vs capability). Others n/a.

| Task | Description | Related | Impl. notes | Blocking | Hrs | Cx | Risk |
|------|-------------|---------|-------------|----------|:--:|:--:|:--:|
| D1-T05 | Org capability model + templates | D1-S02 | runtime authority | A3-T05 | 6 | M | Med |
| D1-T06 | Org lifecycle + audit | D1-S02 | transitions | D1-T03 | 5 | M | Low |
| D1-T07 | Consulting request↔delivery seam | D1-S02 | Org req, CRM delivery | D1-T02 | 5 | M | Low |

## EPIC D2 — Instructor Context Split
- **Epic ID:** D2 · **Objective:** extract Instructor from Catalog/Identity; `TeachingAuthority` port; owns zero lesson data.
- **Business Value:** correct teacher ownership; instructor features (teams/revenue/reviews).
- **Architecture Reference:** 03 Instructor/Authoring redesign.
- **Dependencies:** A3. · **Priority:** P2 · **Risk:** Med · **Story Points:** 26.

### Story D2-S01 — Instructor context + TeachingAuthority
- **Description:** Instructor profile (ref to Identity), assignments, schedules, revenue refs, reviews, availability, teams; provide `TeachingAuthority` consumed by Authoring/Catalog.
- **Acceptance Criteria:** Instructor owns teachers not content; `TeachingAuthority` authorizes Authoring edits; no lesson data in Instructor; tests + Deptrac green.
- **Business Reason:** the 03 boundary "Instructor owns teachers, Authoring owns content".
- **DoD:** Story DoD + report. **Priority:** P2 · **SP:** 13 · **Owner:** BE+SA · **Deps:** A3.
- **Tests:** Acceptance (authority gates authoring; zero lesson data), Architecture (Deptrac), Integration (events), Security (teaching authz). Others n/a.

| Task | Description | Related | Impl. notes | Blocking | Hrs | Cx | Risk |
|------|-------------|---------|-------------|----------|:--:|:--:|:--:|
| D2-T01 | Instructor namespace + profile (Identity ref) | D2-S01 | no lesson data | — | 8 | L | Med |
| D2-T02 | TeachingAuthority port (Authoring consumes) | D2-S01 | authorize edits | D2-T01 | 6 | M | Med |
| D2-T03 | Assignments/schedules/reviews/teams | D2-S01 | teaching relation | D2-T01 | 8 | L | Med |

### Story D2-S02 — Instructor revenue refs + analytics
- **Description:** Teaching revenue references (Commerce), teaching analytics (from Learning/Analytics), instructor brand.
- **Acceptance Criteria:** revenue shown as refs (not owned); teaching analytics via projection; brand configurable.
- **Business Reason:** instructor economics + insight without owning money/content.
- **DoD:** Story DoD. **Priority:** P3 · **SP:** 13 · **Owner:** BE · **Deps:** D2-S01, B4.
- **Tests:** Acceptance (revenue refs; analytics), Architecture (no Commerce/Learning internals), Integration (projection). Others n/a.

| Task | Description | Related | Impl. notes | Blocking | Hrs | Cx | Risk |
|------|-------------|---------|-------------|----------|:--:|:--:|:--:|
| D2-T04 | Teaching revenue refs (Commerce) | D2-S02 | refs only | D2-T01 | 6 | M | Low |
| D2-T05 | Teaching analytics from projection | D2-S02 | Learning projection | B4-T06 | 6 | M | Low |

### Sprint 11 checklists
- **Release Checklist:** both split reports attached; tests + Deptrac green post-split; APIs/URLs unchanged; org + teaching isolation verified.
- **Rollback Checklist:** splits are pure moves → `git revert` restores prior namespace; capability features behind flags.
- **Deployment Checklist:** deploy with `composer dump-autoload`; verify `/admin` org+instructor resources; canary org/instructor flows.
- **Monitoring Checklist:** post-split route/error drift, org isolation assertions, teaching-authority denials, reference-resolution latency.
- **Team Allocation:** BE×3 (Org split; Instructor split), SA (both split gates + ACLs), QA (regression + isolation), DO (deploy/verify), PO (org/instructor scope), TW (split reports).

---

# SPRINT 12 — SSO / White-Label / Multi-Panel  ∥  Analytics Repoint

- **Sprint Goal:** enterprise access (SSO), white-label + instructor/org panels; decouple Analytics onto projections.
- **Duration:** 2 weeks. · **Parallel epics:** D3 (SSO/branding) ∥ D5 (Analytics repoint).
- **Dependencies:** A3 (branding/providers), A6 (auth), B4 (projection for D5).
- **Risks:** SSO edge cases; brand resolution errors. Mitigate: provider config + fallbacks; per-host tests.
- **Definition of Done:** SSO live; per-brand panels; Analytics consumes published projections (no concrete-event coupling).
- **Exit Criteria:** M10 (Enterprise) fully met.

## EPIC D3 — SSO, White-Label, Multi-Panel
- **Epic ID:** D3 · **Objective:** SSO providers; Filament instructor/org panels; end-to-end brand resolution.
- **Business Value:** enterprise onboarding + multi-brand operation.
- **Architecture Reference:** 05 Filament multi-panel + white-label + SSO; ADR-04.
- **Dependencies:** A3, A6. · **Priority:** P2 · **Risk:** Med · **Story Points:** 26.

### Story D3-S01 — SSO providers
- **Description:** SSO (SAML/OIDC) provider config in Administration; Identity enforces; JIT provisioning to tenant.
- **Acceptance Criteria:** SSO login works per configured provider; JIT user maps to correct tenant/org; fallback to local auth.
- **Business Reason:** enterprise access requirement.
- **DoD:** Story DoD. **Priority:** P2 · **SP:** 13 · **Owner:** BE+DO · **Deps:** A3, A6.
- **Tests:** Acceptance (SSO login; JIT), Security (assertion validation; tenant mapping), Integration (provider config), E2E (SSO journey). Others n/a.

| Task | Description | Related | Impl. notes | Blocking | Hrs | Cx | Risk |
|------|-------------|---------|-------------|----------|:--:|:--:|:--:|
| D3-T01 | SSO provider config (Administration) | D3-S01 | SAML/OIDC | A3-T14 | 6 | M | Med |
| D3-T02 | Identity SSO enforcement + JIT | D3-S01 | tenant/org map | D3-T01 | 8 | L | Med |

### Story D3-S02 — Multi-panel + white-label
- **Description:** Instructor + Org Filament panels (own guard/discovery map); brand resolution across panels + web + emails/certs.
- **Acceptance Criteria:** instructor/org panels scoped by tenant; brand resolves per host; panels render correct brand; no cross-tenant rows.
- **Business Reason:** operator + tenant self-service under a brand.
- **DoD:** Story DoD. **Priority:** P2 · **SP:** 13 · **Owner:** BE+FE · **Deps:** A3, D1, D2.
- **Tests:** Acceptance (panel scope; brand render), Security (panel tenant isolation), Architecture (data-map discovery, Filament delegates), Accessibility (panels AA). Others n/a.

| Task | Description | Related | Impl. notes | Blocking | Hrs | Cx | Risk |
|------|-------------|---------|-------------|----------|:--:|:--:|:--:|
| D3-T03 | Instructor + Org PanelProviders | D3-S02 | own RESOURCE_PATHS map | D2-T01 | 8 | L | Med |
| D3-T04 | Brand resolution end-to-end | D3-S02 | host/tenant→brand | A3-T16 | 6 | M | Low |

## EPIC D5 — Analytics Repoint
- **Epic ID:** D5 · **Objective:** Analytics consumes published projections, not concrete domain events (ADR-18).
- **Business Value:** decoupled, stable BI.
- **Architecture Reference:** 05 Analytics; 04 LearningAnalyticsProjection; 99 TD-8.
- **Dependencies:** B4. · **Priority:** P2 · **Risk:** Low · **Story Points:** 13.

### Story D5-S01 — Repoint MetricEventSubscriber to projections
- **Description:** Replace concrete-domain-event subscriptions with consumption of published projections; dual-run then cut over.
- **Acceptance Criteria:** Analytics reads published projections; no subscription to concrete internal domain events; metric parity vs before.
- **Business Reason:** removes coupling (TD-8).
- **DoD:** Story DoD. **Priority:** P2 · **SP:** 13 · **Owner:** BE · **Deps:** B4-S02.
- **Tests:** Acceptance (metric parity), Architecture (Deptrac: Analytics reads projections only), Integration (projection consume). Others n/a.

| Task | Description | Related | Impl. notes | Blocking | Hrs | Cx | Risk |
|------|-------------|---------|-------------|----------|:--:|:--:|:--:|
| D5-T01 | Consume published projections | D5-S01 | dual-run | B4-T06 | 6 | M | Med |
| D5-T02 | Remove concrete-event subscriptions | D5-S01 | after parity | D5-T01 | 4 | S | Low |
| D5-T03 | Deptrac rule: Analytics read-only | D5-S01 | tighten | D5-T02 | 3 | S | Low |

### Sprint 12 checklists
- **Release Checklist:** SSO journeys green; brand renders per host; panels tenant-isolated; Analytics parity + decoupled (Deptrac).
- **Rollback Checklist:** SSO fallback to local auth; new panels behind flags; Analytics dual-run allows revert to event subscriptions.
- **Deployment Checklist:** configure SSO/brand per tenant; deploy panels; enable Analytics projection consumption; verify parity + SSO on canary.
- **Monitoring Checklist:** SSO success/fail, JIT mappings, brand-resolution errors, panel isolation, Analytics metric parity drift.
- **Team Allocation:** BE×2 (SSO; Analytics repoint), FE×1 (panels/brand), DO (SSO infra), QA (SSO + parity), SA (decoupling gate), PO (enterprise access), TW (SSO/brand docs).

---

# SPRINT 13 — AI Platform (human-in-the-loop)

- **Sprint Goal:** ship authoring AI, adaptive engine, tutoring signals, AI analytics — all gated + audited.
- **Duration:** 3 weeks. · **Dependencies:** A5-AI, B4 (evidence/LRS).
- **Risks:** unsafe/incorrect AI output; cost. Mitigate: hard human-approval gate; capability-gating; moderation + cost governance.
- **Definition of Done:** no AI output reaches learners/grades without human approval; AI capability-gated; every AI decision audited + overridable.
- **Exit Criteria:** M11 (AI) met.

## EPIC E — AI Platform
- **Epic ID:** E · **Objective:** authoring generation (gated), adaptive engine (advisory), tutoring signals, AI analytics.
- **Business Value:** productivity + personalization without autonomy risk.
- **Architecture Reference:** 03 Authoring AI, 04 Adaptive Engine, 05 AI Platform; ADR-14.
- **Dependencies:** A5, B4. · **Priority:** P2 · **Risk:** Med · **Story Points:** 42.

### Story E-S01 — Authoring AI generation behind approval gate
- **Description:** AI content suggestions via `AIProvider`; every output is a suggestion requiring human approval before publish.
- **Acceptance Criteria:** AI never publishes; suggestions enter as review items; approval logged; capability-gated per tenant.
- **Business Reason:** authoring productivity, safely.
- **DoD:** Story DoD. **Priority:** P2 · **SP:** 13 · **Owner:** BE+SA · **Deps:** A5.
- **Tests:** Acceptance (no auto-publish; approval logged), Security (moderation; capability gate), Integration (AIProvider), Architecture (human-in-loop). Others n/a.

| Task | Description | Related | Impl. notes | Blocking | Hrs | Cx | Risk |
|------|-------------|---------|-------------|----------|:--:|:--:|:--:|
| E-T01 | AI generation suggestions (Authoring) | E-S01 | via AIProvider | A5-T09 | 8 | L | Med |
| E-T02 | Human approval gate + audit | E-S01 | no AI→learner | E-T01 | 6 | M | Med |

### Story E-S02 — Adaptive engine (advisory) + tutoring signals
- **Description:** `AdaptivePolicyPort` (rules first); personalized sequencing/gaps/difficulty as advisory suggestions; AI tutoring signal payloads; human override logged.
- **Acceptance Criteria:** recommendations are advisory + overridable; every decision emits `AdaptiveRecommendationMade`; override recorded; capability-gated.
- **Business Reason:** personalization from owned evidence.
- **DoD:** Story DoD. **Priority:** P2 · **SP:** 13 · **Owner:** BE · **Deps:** B4, E-S01.
- **Tests:** Acceptance (advisory + override), Unit (policy), Integration (decision events), Security (no autonomous action). Others n/a.

| Task | Description | Related | Impl. notes | Blocking | Hrs | Cx | Risk |
|------|-------------|---------|-------------|----------|:--:|:--:|:--:|
| E-T03 | AdaptivePolicyPort + rules impl | E-S02 | ML later | B4-T02 | 8 | L | Med |
| E-T04 | Recommendations + override logging | E-S02 | auditable | E-T03 | 6 | M | Low |
| E-T05 | AI tutoring signal payloads | E-S02 | via AIProvider | E-T03 | 5 | M | Med |

### Story E-S03 — AI analytics narratives
- **Description:** On-demand AI narratives/anomaly summaries over Analytics projections; advisory only.
- **Acceptance Criteria:** narratives generated on request; clearly advisory; cost-governed; capability-gated.
- **Business Reason:** insight acceleration.
- **DoD:** Story DoD. **Priority:** P3 · **SP:** 8 · **Owner:** BE · **Deps:** D5, E-S02.
- **Tests:** Acceptance (narrative advisory), Security (cost/moderation), Integration (projection input). Others n/a.

| Task | Description | Related | Impl. notes | Blocking | Hrs | Cx | Risk |
|------|-------------|---------|-------------|----------|:--:|:--:|:--:|
| E-T06 | AI analytics narrative service | E-S03 | on-demand | D5-T01 | 6 | M | Low |

### Sprint 13 checklists
- **Release Checklist:** no-AI-to-learner-without-approval proven; capability gates verified; moderation + cost governance active; overrides audited.
- **Rollback Checklist:** all AI behind capability (default-off) → disable per tenant; AIProvider circuit-breaker.
- **Deployment Checklist:** configure AI provider/secrets; enable AI capability on pilot; verify approval gate + cost caps.
- **Monitoring Checklist:** AI cost per tenant, moderation blocks, approval/override rates, recommendation acceptance, provider latency/errors.
- **Team Allocation:** BE×2 (authoring AI; adaptive), SA (safety/human-in-loop), QA (safety + gate suites), DO (provider/cost infra), PO (AI capability policy), TW (AI usage docs).

---

# SPRINT 14 — Marketplace

- **Sprint Goal:** open the platform — marketplace, revenue share, external instructors, sandboxed plugins, content licensing.
- **Duration:** 3 weeks. · **Dependencies:** C (revenue), D (org/instructor), E (AI/quality review).
- **Risks:** content trust; payout complexity; plugin security. Mitigate: quality gates + AI review; reconciliation; plugin sandbox (presentation + published APIs only).
- **Definition of Done:** external instructors onboard safely; revenue share correct; plugins presentation-only.
- **Exit Criteria:** M12 (Marketplace) met.

## EPIC F — Marketplace
- **Epic ID:** F · **Objective:** marketplace listing/discovery, revenue sharing, external/partner instructors, sandboxed plugins, content licensing.
- **Business Value:** ecosystem growth + new revenue.
- **Architecture Reference:** 03 instructor future evolution, 03 content composition (copy/fork), 05 Filament plugin strategy.
- **Dependencies:** C, D, E. · **Priority:** P3 · **Risk:** Med · **Story Points:** 42.

### Story F-S01 — Marketplace listing + discovery
- **Description:** Listing/discovery over Catalog with marketplace metadata; quality-gated publish.
- **Acceptance Criteria:** courses listable to marketplace; discovery works; only quality-gated content lists.
- **Business Reason:** ecosystem supply/demand.
- **DoD:** Story DoD. **Priority:** P3 · **SP:** 13 · **Owner:** BE+FE · **Deps:** D.
- **Tests:** Acceptance (list/discover), Integration (Catalog + quality gate), Security (tenant scope), E2E (browse marketplace). Others n/a.

| Task | Description | Related | Impl. notes | Blocking | Hrs | Cx | Risk |
|------|-------------|---------|-------------|----------|:--:|:--:|:--:|
| F-T01 | Marketplace metadata + listing | F-S01 | over Catalog | — | 8 | L | Med |
| F-T02 | Discovery UI | F-S01 | search/browse | F-T01 | 6 | M | Low |

### Story F-S02 — Revenue sharing + external/partner instructors
- **Description:** Revenue-share model (Commerce); external/partner instructor onboarding via Instructor context; payout refs.
- **Acceptance Criteria:** revenue split computed + auditable; external instructors onboard with limited scope; payouts referenced (money via Commerce).
- **Business Reason:** creator economy.
- **DoD:** Story DoD. **Priority:** P3 · **SP:** 13 · **Owner:** BE · **Deps:** C, D2.
- **Tests:** Acceptance (split correct), Security (payout authz; reconciliation), Integration (Commerce), Architecture (Instructor no content). Others n/a.

| Task | Description | Related | Impl. notes | Blocking | Hrs | Cx | Risk |
|------|-------------|---------|-------------|----------|:--:|:--:|:--:|
| F-T03 | Revenue-share model + reconciliation | F-S02 | Commerce | C1-T02 | 8 | L | Med |
| F-T04 | External/partner instructor onboarding | F-S02 | scoped | D2-T01 | 6 | M | Med |

### Story F-S03 — Sandboxed plugins + content licensing
- **Description:** UI/Filament plugins sandboxed to presentation + published APIs; content licensing via copy/fork (03 composition).
- **Acceptance Criteria:** plugins cannot access business logic/tables; licensing uses copy/fork with lineage; forbidden capabilities blocked.
- **Business Reason:** safe extensibility + content reuse.
- **DoD:** Story DoD. **Priority:** P3 · **SP:** 16 · **Owner:** BE+SA · **Deps:** E.
- **Tests:** Acceptance (plugin sandbox; license copy/fork), Security (no logic sink), Architecture (presentation-only). Others n/a.

| Task | Description | Related | Impl. notes | Blocking | Hrs | Cx | Risk |
|------|-------------|---------|-------------|----------|:--:|:--:|:--:|
| F-T05 | Plugin sandbox contract | F-S03 | presentation + APIs only | — | 8 | L | High |
| F-T06 | Content licensing (copy/fork) | F-S03 | lineage per 03 | — | 8 | L | Med |

### Sprint 14 checklists
- **Release Checklist:** marketplace listings quality-gated; revenue split reconciled; plugin sandbox enforced; licensing copy/fork verified.
- **Rollback Checklist:** marketplace behind capability (default-off); plugin loading flag; revenue-share config revertible.
- **Deployment Checklist:** enable marketplace capability on pilot; deploy plugin sandbox; verify payouts on test tenants.
- **Monitoring Checklist:** marketplace conversions, revenue-share reconciliation diffs, plugin errors/violations, licensing operations.
- **Team Allocation:** BE×2 (marketplace/revenue; plugins/licensing), FE×1 (discovery), SA (plugin sandbox), QA (security + reconciliation), PO (marketplace policy), TW (partner docs).

---

# SPRINT 15 — Global Expansion

- **Sprint Goal:** i18n/l10n completion, data residency, regional providers, compliance, multi-region DR.
- **Duration:** 3 weeks. · **Dependencies:** all prior phases.
- **Risks:** residency complexity; regional compliance; latency. Mitigate: per-region storage/providers; compliance tooling; multi-region DR drills.
- **Definition of Done:** data residency honored per tenant/region; localized end-to-end; multi-region DR proven.
- **Exit Criteria:** MFinal (Global GA) met.

## EPIC G — Global Expansion
- **Epic ID:** G · **Objective:** i18n/l10n, residency, regional providers, compliance, multi-region deployment.
- **Business Value:** global market reach + compliance.
- **Architecture Reference:** 05 Deployment (multi-region), 04 accessibility/localization, GDPR (LRS).
- **Dependencies:** all. · **Priority:** P3 · **Risk:** Med · **Story Points:** 42.

### Story G-S01 — Full i18n/l10n + RTL
- **Description:** Complete content + UI localization, RTL parity (en/ar already partial), locale-complete quality gate.
- **Acceptance Criteria:** UI + content localizable; RTL correct; localization-completeness gate blocks incomplete locales per policy.
- **DoD:** Story DoD. **Priority:** P3 · **SP:** 13 · **Owner:** FE+TW · **Deps:** B7.
- **Tests:** Acceptance (locale switch; RTL), Accessibility (RTL AA), E2E (localized journey), Integration (locale gate). Others n/a.

| Task | Description | Related | Impl. notes | Blocking | Hrs | Cx | Risk |
|------|-------------|---------|-------------|----------|:--:|:--:|:--:|
| G-T01 | Localization completion + RTL parity | G-S01 | extend existing i18n | — | 10 | L | Med |
| G-T02 | Locale-completeness quality gate | G-S01 | per policy | G-T01 | 5 | M | Low |

### Story G-S02 — Data residency + regional providers
- **Description:** Per-region storage/media/data residency; regional payment/SMS providers via Administration.
- **Acceptance Criteria:** tenant data stays in configured region; regional providers selected per tenant; residency honored end-to-end.
- **DoD:** Story DoD. **Priority:** P3 · **SP:** 13 · **Owner:** DO+BE · **Deps:** A3, A5.
- **Tests:** Acceptance (residency honored), Security (no cross-region leak), Integration (regional providers). Others n/a.

| Task | Description | Related | Impl. notes | Blocking | Hrs | Cx | Risk |
|------|-------------|---------|-------------|----------|:--:|:--:|:--:|
| G-T03 | Per-region storage/media routing | G-S02 | residency config | A5-T02 | 8 | L | High |
| G-T04 | Regional providers config | G-S02 | via Administration | A3-T14 | 6 | M | Med |

### Story G-S03 — Compliance tooling + multi-region DR
- **Description:** Residency/erasure/consent tooling; multi-region deployment (active-passive/active-active) + DR drills.
- **Acceptance Criteria:** erasure/consent operations work (crypto-shred honored); multi-region deploy; DR drill meets RPO/RTO.
- **DoD:** Story DoD. **Priority:** P3 · **SP:** 16 · **Owner:** DO+BE · **Deps:** B4, G-S02.
- **Tests:** Acceptance (erasure/consent), Security (compliance), Integration (multi-region), Performance (cross-region latency), DR (drill). Others n/a.

| Task | Description | Related | Impl. notes | Blocking | Hrs | Cx | Risk |
|------|-------------|---------|-------------|----------|:--:|:--:|:--:|
| G-T05 | Compliance tooling (erasure/consent) | G-S03 | crypto-shred | B4-T03 | 8 | L | Med |
| G-T06 | Multi-region deploy + DR drill | G-S03 | active-passive first | G-T03 | 10 | L | High |

### Sprint 15 checklists
- **Release Checklist:** localized E2E green; residency honored (tests); DR drill passed RPO/RTO; compliance operations verified.
- **Rollback Checklist:** regional routing behind config; multi-region active-passive allows failback; localization additive.
- **Deployment Checklist:** provision regional storage/providers; deploy multi-region; run DR drill; enable residency per tenant.
- **Monitoring Checklist:** cross-region latency, residency violations (0 target), DR replication lag, erasure/consent operations, locale coverage.
- **Team Allocation:** DO×1 (multi-region/DR), BE×1 (residency/compliance), FE×1 (i18n/RTL), TW (localization/compliance docs), QA (DR + compliance), SA (residency architecture), PO (market priorities).

---

# PROJECT DASHBOARD

## Overall progress model

Baseline at start = 0%. Progress is **story-point-weighted** across the total backlog (≈ **476 SP** across 16 sprints). Track "% SP done" per sprint; the table is the burndown reference (points remaining after each sprint if fully delivered).

| Sprint | Epic(s) | SP in sprint | Cumulative SP | % complete | SP remaining |
|:--:|-----|:--:|:--:|:--:|:--:|
| 0 | A1 | 34 | 34 | 7% | 442 |
| 1 | A2 | 42 | 76 | 16% | 400 |
| 2 | A4 + A6 | 60 | 136 | 29% | 340 |
| 3 | A3 | 42 | 178 | 37% | 298 |
| 4 | A5 + D4 | 47 | 225 | 47% | 251 |
| 5 | B1 | 42 | 267 | 56% | 209 |
| 6 | B2 + C1 | 60 | 327 | 69% | 149 |
| 7 | B3 | 42 | 369 | 78% | 107 |
| 8 | B4 + B5 | 60 | 429 | 90% | 47 |
| 9 | B6 | 42 | 471 | 99% | 5 |
| 10 | B7 + C2 | 50 | 521 | — | — |
| 11 | D1 + D2 | 60 | 581 | — | — |
| 12 | D3 + D5 | 39 | 620 | — | — |
| 13 | E | 42 | 662 | — | — |
| 14 | F | 42 | 704 | — | — |
| 15 | G | 42 | 746 | — | — |

> Two views: **Enterprise-MVP burndown** (Sprints 0–12, the A–D scope, ≈620 SP; "% complete" column above tracks the A–B critical spine to Sprint 9) and **Full-GA burndown** (through Sprint 15, ≈746 SP). Use the platform's velocity chart; assume ~40–60 SP/sprint at the recommended team size (2 streams).

## Sprint burndown model (ideal vs tracking)

- **Ideal line:** total SP ÷ number of sprints, drawn from start (476/… for MVP spine; 746 for full).
- **Tracking:** subtract delivered SP each sprint; a sprint that carries work over pushes the remaining line above ideal.
- **Alert rule:** if remaining line is >1.5 sprints above ideal for two consecutive sprints → PO re-scopes the next sprint (cut P3 stories first).
- **Parallel sprints (2,4,6,8,10,11,12) carry ~50–60 SP** — staff both streams or split across 3 weeks; do not attempt a single-squad 2-week burn on those.

## Epic dependency graph

```
A1 ─┬─► A2 ─┬─► A3 ─┬─► A5 ─┬─► B1 ─┬─► B2 ─┬─► B3 ─► B6 ─► B7
    │       │       │      │       │       │
    │       │       │      │       │       └─► B4 ─► B5 ─► (E)
    │       │       │      │       │
    │       │       │      │       └─► C1 ─► C2
    │       │       │      └─► D4 (C9/C10, needs only A1 gate + tests)
    │       │       └─► D1, D2 ─► D3
    │       └─► A6 (parallel; needs A2)
    └─► A4 ─► A5/B2/C1 (outbox consumers)

D5 needs B4.   E needs A5+B4.   F needs C+D+E.   G needs all.
```

## Critical path (longest dependency chain)

```
A1 → A2 → A3 → A5 → B1 → B2 → B3 → B6 → B7 → (D3 for enterprise GA)
```
This is the **spine**; slippage here slips the program. A4 and A6 run beside A2/A3 but must both land before C1/entitlement and before enterprise. Everything on the critical path is P0/P1 — protect it: no P3 work steals critical-path capacity.

## Parallel work streams

| Stream | Owns | Sprints | Notes |
|--------|------|---------|-------|
| **Platform** | A1, A2, A3, A4, A5, A6, D3, D4 | 0–4, 11–12 | foundation + capability ports + relocations + SSO/branding |
| **Learning** | B1–B7 | 5–10 | the core redesign; longest stream |
| **Commerce** | C1, C2 | 6, 10 | pulled in when entitlement prerequisites land |
| **Enterprise** | D1, D2, D5 | 11–12 | context splits + analytics decoupling |
| **AI/Marketplace/Global** | E, F, G | 13–15 | upside on a stable spine |
| **Quality (cross-cutting)** | Deptrac, leakage, E2E, perf, a11y | all | permanent gates, every sprint |

## Risk register (live)

| ID | Risk | Type | Sev | Owner | Trigger metric | Mitigation | Status |
|----|------|------|:--:|-------|----------------|------------|--------|
| RR-1 | Cross-tenant leak | Security | Crit | SA | leakage suite fail | global scope + permanent gate | Open (A2) |
| RR-2 | Big-bang split breaks build | Technical | High | SA | test/Deptrac red | gated moves, low-churn window | Open (D1/D2/D4) |
| RR-3 | Outbox duplicate effects | Ops | High | BE | dupe-effect assertion | idempotency + dedupe | Open (A4) |
| RR-4 | Offline merge corruption | Technical | High | SA | conflict quarantine spike | deterministic merge + property tests | Open (B6) |
| RR-5 | Scope creep across A–G | Business | High | PO | remaining-line drift | phase exit criteria; flags off | Open |
| RR-6 | Payment/entitlement mismatch | Sec/Biz | Crit | BE | reconciliation hits | outbox + reconcile job | Open (C1) |
| RR-7 | Unsafe AI to learners | Trust | High | SA | approval-gate bypass | hard human gate; capability | Open (E) |
| RR-8 | Bus factor / knowledge silo | Ops | Med | PO | review bottleneck | ADRs, runbooks, pairing | Open |
| RR-9 | Windows/PowerShell/env friction | Ops | Low | DO | script parse errors | ASCII-only, Docker verify, CI-first | Mitigated |
| RR-10 | Multi-tenant perf regression | Perf | Med | DO | p95 budget breach | read models, cache, k6 gate | Open |
| RR-11 | Data residency violation | Compliance | High | DO | residency assertion | per-region routing + tests | Open (G) |

## Decision log (traceable to ADRs in blueprint 05)

| # | Decision | ADR | Sprint enacted |
|---|----------|-----|:--:|
| DL-1 | Deptrac enforces boundaries in CI | ADR-01/02 | 0 |
| DL-2 | Tenant isolation via global scope | ADR-07 | 1 |
| DL-3 | Transactional outbox for guaranteed events | ADR-03/16 | 2 |
| DL-4 | httpOnly-cookie auth | 05 Security | 2 |
| DL-5 | Administration as Platform context; capability≠permission≠flag | ADR-05/06 | 3 |
| DL-6 | Media/AI/Search/Integration as ports | ADR-08/16 | 4 |
| DL-7 | Catalog/Live relocated to Contexts | 99 C9/C10 | 4 |
| DL-8 | Content ports + versioning; definitions vs execution | ADR-09/11 | 5 |
| DL-9 | Progress projector off write path | ADR-10 | 6 |
| DL-10 | Exactly-once entitlement; gateway outside tx | ADR-16 | 6 |
| DL-11 | LRS ledger + xAPI/SCORM/cmi5 | ADR-13 | 8 |
| DL-12 | Offline idempotent + deterministic merge | ADR-15 | 9 |
| DL-13 | Organization/Instructor context splits | 02/03 | 11 |
| DL-14 | Analytics consumes projections | ADR-18 | 12 |
| DL-15 | AI human-in-the-loop behind port | ADR-14 | 13 |

## Architecture compliance checklist (per-PR + per-release)

```
[ ] Single-writer ownership honored (no context owns another's fact)
[ ] Cross-context access via port/ref only (Deptrac green)
[ ] No context depends on another's Models for logic
[ ] Analytics remains read-only across contexts
[ ] Filament contains no business logic (delegates to Actions/Services)
[ ] Tenant isolation via global scope (no manual org_id where)
[ ] Events are DTOs (no Eloquent crosses a boundary)
[ ] Guaranteed events use the outbox (money/entitlement/credential/tenant)
[ ] Media bytes behind PlaybackPort/MediaPort (refs only in contexts)
[ ] External I/O via Integration Platform (gateway outside DB tx)
[ ] AI output gated by human approval + capability
[ ] Content changes are versioned; references pin versions
[ ] Capability → permission → flag layering enforced at entry points
[ ] Migration is expand-and-contract; rollback rehearsed
[ ] ADR referenced for any architectural change
```

## Team allocation matrix (role load by phase)

| Phase / Sprints | SA | BE | FE | DO | QA | UX | PO | TW |
|-----------------|:--:|:--:|:--:|:--:|:--:|:--:|:--:|:--:|
| A (0–4) | ●●● | ●●●● | ●● | ●●● | ●●● | ● | ●● | ●● |
| B (5–10) | ●●● | ●●●● | ●●● | ●● | ●●● | ●● | ●● | ●● |
| C (6,10) | ● | ●●● | ●● | ● | ●● | ● | ●● | ● |
| D (11–12) | ●●● | ●●● | ●● | ●● | ●● | ● | ●● | ●● |
| E (13) | ●●● | ●●● | ● | ●● | ●● | ● | ●● | ●● |
| F (14) | ●● | ●●● | ●● | ● | ●● | ● | ●● | ●● |
| G (15) | ●● | ●● | ●● | ●●● | ●● | ● | ●● | ●●● |

(● = light, ●●● = heavy load; SA is shared across streams and reviews every boundary-touching PR.)

## Import-readiness notes

- **CSV/JSON export:** each Epic/Story/Task row here maps 1:1 to a ticket. Suggested columns: `id, type, parent_id, title, description, priority, story_points|hours, owner_role, dependencies, sprint, acceptance_criteria, dod`.
- **Labels:** apply `epic:<ID>`, `phase:<A–G>`, `stream:<Platform|Learning|Commerce|Enterprise|AI|Marketplace|Global>`, `risk:<Low|Med|High>`.
- **Boards:** one board per stream; a program board tracks the critical path (A1→A2→A3→A5→B1→B2→B3→B6→B7→D3).
- **Definition of Ready (per story before a sprint):** dependencies delivered or stubbed; acceptance criteria agreed; capability/flag decided; test plan noted; owner assigned.

## Final note

This backlog is complete, sequential, dependency-aware, and architecture-aware across all epics A1–G5 from the Master Plan. It is import-ready for Jira / GitHub Projects / Azure DevOps / Linear / ClickUp. Execute top-to-bottom: **Sprint 0 (A1 fitness) and Sprint 2's A6 security fixes are the non-negotiable openers**; the critical path is the spine; everything else is scheduled around it. No placeholders, no TODOs — every epic has stories, tasks, PR checklists, tests, and per-sprint release/rollback/deploy/monitoring checklists.

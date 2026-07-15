# Architecture Decision Records — Index

> The authoritative list of architectural decisions for the HElbaron LMS. Decisions ADR-01…ADR-18 were made during the redesign (see `docs/redesign/05_ADMINISTRATION_ENTERPRISE_BLUEPRINT.md` "Architecture Decision Records"); ADR-19…ADR-20 were made during Sprint 0 execution. This index is enforced by the ADR-reference check (`scripts/adr-link-check.sh` + `config/architecture/adr-watch.yaml` + `.github/workflows/adr-validation.yml`): any architecture-sensitive PR must cite an `ADR-XX` here or add a new ADR.
>
> **Status** legend: **Accepted** (in force) · **Proposed** · **Superseded**. **Implementation Status** legend: **Implemented** · **In progress** · **Foundation** (abstraction/scaffold only) · **Not started**. **Sprint Target** maps to the Execution Backlog (`docs/redesign/100_EXECUTION_BACKLOG.md`).

## Summary table

| ID | Title | Status | Impl. Status | Sprint Target |
|----|-------|--------|--------------|---------------|
| ADR-01 | Modular monolith over microservices | Accepted | Implemented | — (foundational) |
| ADR-02 | Bounded contexts with single-writer ownership | Accepted | In progress | Continuous (A1 enforce; burn-down thru Phase D) |
| ADR-03 | Event-driven integration (events as DTOs) | Accepted | In progress | Sprint 2 (A4 outbox) |
| ADR-04 | Filament as UI only | Accepted | Implemented (enforced A1) | Continuous |
| ADR-05 | Administration as a Platform context | Accepted | Not started | Sprint 3 (A3) |
| ADR-06 | Capability vs Permission vs Feature Flag | Accepted | Not started | Sprint 3 (A3) |
| ADR-07 | Row-level multi-tenancy via global scope | Accepted | Foundation (A2-S01) | Sprint 1 (A2) |
| ADR-08 | Media Platform owns bytes; contexts own refs | Accepted | Not started | Sprint 4 (A5) |
| ADR-09 | Content versioning (copy-on-write + pinning) | Accepted | Not started | Sprint 5 (B1) |
| ADR-10 | Progress derived by projector, off the write path | Accepted | Not started | Sprint 6 (B2) |
| ADR-11 | Authoring owns definitions; Learning owns attempts | Accepted | Not started | Sprint 5-7 (B1/B3) |
| ADR-12 | Certification issues credentials; Learning stores references | Accepted | Not started | Sprint 8 (B4/Certification) |
| ADR-13 | LRS + xAPI/SCORM/cmi5 as Learning's ledger | Accepted | Not started | Sprint 8 (B4) |
| ADR-14 | AI is human-in-the-loop behind a port | Accepted | Not started | Sprint 13 (E) |
| ADR-15 | Offline-first with idempotent, mergeable writes | Accepted | Not started | Sprint 9 (B6) |
| ADR-16 | Integration Platform as the single external I/O boundary | Accepted | Not started | Sprint 2/4 (A4/A5) |
| ADR-17 | REST-only, versioned, Sanctum-authenticated API | Accepted | Implemented | — |
| ADR-18 | Analytics is read-only; consumes published projections | Accepted | Not started | Sprint 12 (D5) |
| ADR-19 | Adopt Deptrac + custom PHPStan rules for architecture fitness | Accepted | Implemented (Sprint 0) | — |
| ADR-20 | Identity exposes a contracts seam; contexts depend on IdentityContracts only | Accepted | Foundation (Deptrac split Sprint 0) | Sprint 1+ (Identity ports; burn-down) |

---

## ADR-01 — Modular monolith over microservices
- **Status:** Accepted
- **Implementation Status:** Implemented (baseline architecture in place).
- **Sprint Target:** — (foundational).
- **Context:** One team, an evolving domain, and a need for velocity plus strong transactional consistency.
- **Decision:** Build a Laravel 12 modular monolith of bounded contexts (`App\Domains\*`, `App\Contexts\*`, `App\Platform\*`) integrating via events + ports.
- **Affected Contexts:** all.
- **Dependencies:** none (foundational).
- **Superseded By:** —
- **Related ADRs:** ADR-02, ADR-03.

## ADR-02 — Bounded contexts with single-writer ownership
- **Status:** Accepted
- **Implementation Status:** In progress (contexts exist; boundary enforcement via Deptrac from Sprint 0; coupling burns down through Phase D).
- **Sprint Target:** Continuous (A1 enforcement; burn-down thru Phase D).
- **Context:** Avoid the "everything reaches into everything" LMS trap.
- **Decision:** Each fact has exactly one owning context; others hold references; cross-context reads go through read models/ports.
- **Affected Contexts:** all.
- **Dependencies:** ADR-01.
- **Superseded By:** —
- **Related ADRs:** ADR-03, ADR-08, ADR-11, ADR-12, ADR-18, ADR-20.

## ADR-03 — Event-driven integration (events as DTOs)
- **Status:** Accepted
- **Implementation Status:** In progress (domain events exist; transactional outbox pending).
- **Sprint Target:** Sprint 2 (A4 outbox/DLQ).
- **Context:** Decouple producers from consumers; enable resilience and replay.
- **Decision:** Domain events are DTOs on the queue; no Eloquent crosses a boundary; guaranteed events use a transactional outbox.
- **Affected Contexts:** all.
- **Dependencies:** ADR-01, ADR-02.
- **Superseded By:** —
- **Related ADRs:** ADR-16, ADR-18.

## ADR-04 — Filament as UI only
- **Status:** Accepted
- **Implementation Status:** Implemented (discipline in place; enforced by the A1 PHPStan rule).
- **Sprint Target:** Continuous.
- **Context:** The admin console must not fork business rules.
- **Decision:** Filament resources/pages/actions delegate to domain Actions/Services; no business logic in the panel; resource discovery via a data map (no conditional branches).
- **Affected Contexts:** Platform/Administration + every resource-owning context.
- **Dependencies:** ADR-02.
- **Superseded By:** —
- **Related ADRs:** ADR-05, ADR-19.

## ADR-05 — Administration as a Platform context (not a super-domain)
- **Status:** Accepted
- **Implementation Status:** Not started.
- **Sprint Target:** Sprint 3 (A3 Administration context).
- **Context:** Platform operation vs domain data must not blur.
- **Decision:** Administration owns config/flags/capabilities/tenants/providers/ops/audit and never owns domain business data; it operates other contexts via ports/flags/events.
- **Affected Contexts:** Administration, Platform.
- **Dependencies:** ADR-01, ADR-02.
- **Superseded By:** —
- **Related ADRs:** ADR-06, ADR-16.

## ADR-06 — Capability vs Permission vs Feature Flag
- **Status:** Accepted
- **Implementation Status:** Not started.
- **Sprint Target:** Sprint 3 (A3 capabilities/flags).
- **Context:** Three different "can this happen?" questions were conflated.
- **Decision:** Capability = tenant feature entitlement (Administration); Permission = user authorization (Identity); Flag = operational toggle (Administration). Checked capability -> permission at each entry point.
- **Affected Contexts:** Administration, Identity, all.
- **Dependencies:** ADR-05, ADR-07.
- **Superseded By:** —
- **Related ADRs:** ADR-14.

## ADR-07 — Row-level multi-tenancy via global scope
- **Status:** Accepted
- **Implementation Status:** Foundation (tenant-resolution abstraction introduced in Sprint 1 / A2-S01; the global scope + enforcement land in A2-S02).
- **Sprint Target:** Sprint 1 (A2 Multi-Tenancy & Isolation).
- **Context:** Manual `where org_id` is error-prone (audit finding TEN-1).
- **Decision:** Enforce tenant isolation with a global scope + policy on every tenant-scoped model; no manual scoping.
- **Affected Contexts:** all tenant-scoped.
- **Dependencies:** ADR-02.
- **Superseded By:** —
- **Related ADRs:** ADR-05, ADR-06, ADR-20.

## ADR-08 — Media Platform owns bytes; contexts own refs
- **Status:** Accepted
- **Implementation Status:** Not started.
- **Sprint Target:** Sprint 4 (A5 capability ports).
- **Context:** Playback providers were embedded in Learning.
- **Decision:** Media Platform owns upload/transcode/delivery behind `PlaybackPort`/`MediaPort`; contexts store asset refs and request signed tokens.
- **Affected Contexts:** Media, Learning, Authoring.
- **Dependencies:** ADR-02.
- **Superseded By:** —
- **Related ADRs:** ADR-16.

## ADR-09 — Content versioning with copy-on-write + version pinning
- **Status:** Accepted
- **Implementation Status:** Not started.
- **Sprint Target:** Sprint 5 (B1 content ports & versioning).
- **Context:** Editing published content must not corrupt learner history.
- **Decision:** Lessons/assessments are versioned; references pin a version; major bumps never auto-propagate; attempts record the version they ran against.
- **Affected Contexts:** Catalog, Authoring, Learning.
- **Dependencies:** ADR-02, ADR-11.
- **Superseded By:** —
- **Related ADRs:** ADR-10, ADR-11.

## ADR-10 — Progress derived by projector, off the write path
- **Status:** Accepted
- **Implementation Status:** Not started.
- **Sprint Target:** Sprint 6 (B2 Progress Engine v2).
- **Context:** Synchronous full-course recompute on every progress write does not scale.
- **Decision:** Progress writes emit events; rollups are computed by projectors against a version-keyed curriculum snapshot; read models serve reads.
- **Affected Contexts:** Learning.
- **Dependencies:** ADR-03, ADR-09.
- **Superseded By:** —
- **Related ADRs:** ADR-13.

## ADR-11 — Authoring owns definitions; Learning owns attempts
- **Status:** Accepted
- **Implementation Status:** Not started.
- **Sprint Target:** Sprint 5-7 (B1 ports / B3 assessment execution).
- **Context:** Assessments blur content vs execution.
- **Decision:** Authoring defines quizzes/assignments/exams; Learning owns every attempt/grade; the attempt pins the definition version.
- **Affected Contexts:** Authoring, Learning.
- **Dependencies:** ADR-02, ADR-09.
- **Superseded By:** —
- **Related ADRs:** ADR-12, ADR-13.

## ADR-12 — Certification issues credentials; Learning stores references
- **Status:** Accepted
- **Implementation Status:** Not started.
- **Sprint Target:** Sprint 8 (B4/B5 + Certification integration).
- **Context:** Who owns the credential.
- **Decision:** Learning proves completion/mastery via events; Certification decides and issues; Learning stores a `CertificateReference`.
- **Affected Contexts:** Certification, Learning.
- **Dependencies:** ADR-02, ADR-03.
- **Superseded By:** —
- **Related ADRs:** ADR-11, ADR-13.

## ADR-13 — LRS + xAPI/SCORM/cmi5 as Learning's ledger
- **Status:** Accepted
- **Implementation Status:** Not started.
- **Sprint Target:** Sprint 8 (B4 Learning Record Store).
- **Context:** Need an auditable, replayable, standards-compatible learning history.
- **Decision:** An append-only experience-event ledger is the source of truth; xAPI mapping; SCORM/cmi5 normalized into events; GDPR erasure via crypto-shredding.
- **Affected Contexts:** Learning, Analytics.
- **Dependencies:** ADR-03, ADR-10.
- **Superseded By:** —
- **Related ADRs:** ADR-18.

## ADR-14 — AI is human-in-the-loop behind a port
- **Status:** Accepted
- **Implementation Status:** Not started.
- **Sprint Target:** Sprint 13 (E AI Platform).
- **Context:** AI must assist, not act autonomously on learners/grades.
- **Decision:** AI Platform sits behind `AIProvider`/`AdaptivePolicyPort`; every AI output is a suggestion requiring human approval; gated by tenant capability; all decisions audited.
- **Affected Contexts:** AI, Authoring, Learning, Analytics.
- **Dependencies:** ADR-06, ADR-16.
- **Superseded By:** —
- **Related ADRs:** ADR-18.

## ADR-15 — Offline-first with idempotent, mergeable writes
- **Status:** Accepted
- **Implementation Status:** Not started.
- **Sprint Target:** Sprint 9 (B6 Offline & Multi-Device Sync).
- **Context:** Mobile/offline and multi-device are first-class.
- **Decision:** Every learner-state write carries a `clientMutationId`; version vectors + per-aggregate deterministic merge; completion never regresses; exams are online-only.
- **Affected Contexts:** Learning.
- **Dependencies:** ADR-03, ADR-10.
- **Superseded By:** —
- **Related ADRs:** ADR-13.

## ADR-16 — Integration Platform as the single external I/O boundary
- **Status:** Accepted
- **Implementation Status:** Not started.
- **Sprint Target:** Sprint 2/4 (A4 outbox / A5 Integration Platform).
- **Context:** External calls (payments, calendar, SSO, webhooks) need one security/retry/audit point.
- **Decision:** All third-party I/O flows through the Integration Platform (outbox, idempotency, retry, DLQ, signature verification); gateway calls never inside a DB transaction.
- **Affected Contexts:** Integration, Commerce, Identity, Live, Notifications.
- **Dependencies:** ADR-03.
- **Superseded By:** —
- **Related ADRs:** ADR-08, ADR-14.

## ADR-17 — REST-only, versioned, Sanctum-authenticated API
- **Status:** Accepted
- **Implementation Status:** Implemented (REST `/api/v1` + Sanctum in place).
- **Sprint Target:** — (done; additive evolution only).
- **Context:** A stable client contract for the Next.js app + integrations.
- **Decision:** REST under `/api/v1`, Sanctum auth, policy-enforced, idempotency keys on mutations; media via signed tokens; additive changes only.
- **Affected Contexts:** all HTTP-exposing contexts.
- **Dependencies:** ADR-01.
- **Superseded By:** —
- **Related ADRs:** ADR-08.

## ADR-18 — Analytics is read-only; consumes published projections
- **Status:** Accepted
- **Implementation Status:** Not started (Analytics currently subscribes to concrete domain events — TD-8).
- **Sprint Target:** Sprint 12 (D5 Analytics repoint).
- **Context:** BI must not couple to every domain's internals.
- **Decision:** Analytics consumes contexts' published projections (esp. Learning's `LearningAnalyticsProjection`), never writes domain data, never binds to concrete internal events.
- **Affected Contexts:** Analytics, all publishers.
- **Dependencies:** ADR-02, ADR-03, ADR-13.
- **Superseded By:** —
- **Related ADRs:** ADR-13.

## ADR-19 — Adopt Deptrac + custom PHPStan rules for architecture fitness
- **Status:** Accepted
- **Implementation Status:** Implemented (Sprint 0 / A1: Deptrac + 4 custom rules + ADR check, all wired to CI).
- **Sprint Target:** — (done; baselines burn down over Phases A-D).
- **Context:** Boundaries were unenforced (TD-7); without automated fitness functions the redesign erodes silently.
- **Decision:** Enforce the dependency matrix with Deptrac (baseline-backed, blocking in CI) and complement it at the AST/type level with four custom PHPStan rules (no cross-context Model use, no cross-context Eloquent access, no business logic in Filament resources, no business logic in controllers). This ADR-reference check is part of that fitness system.
- **Affected Contexts:** all (tooling/governance).
- **Dependencies:** ADR-02, ADR-04.
- **Superseded By:** —
- **Related ADRs:** ADR-20.

## ADR-20 — Identity exposes a contracts seam; contexts depend on IdentityContracts only
- **Status:** Accepted
- **Implementation Status:** Foundation (Deptrac split Identity vs IdentityContracts in Sprint 0; contexts' direct `User`-model usage baselined; Identity ports + burn-down are future work).
- **Sprint Target:** Sprint 1+ (Identity ports introduced as tenancy/identity work proceeds; baseline burns down).
- **Context:** Contexts referenced the Identity implementation directly (e.g. `App\Platform\Identity\Models\User`), coupling them to Identity internals.
- **Decision:** Split Identity into implementation vs `IdentityContracts` (ports). Every context/capability/kernel layer may depend on `IdentityContracts` only, never the Identity implementation; current direct usages are baselined and burn down as Identity ports land. Enforced by Deptrac.
- **Affected Contexts:** Identity, all.
- **Dependencies:** ADR-02, ADR-19.
- **Superseded By:** —
- **Related ADRs:** ADR-06, ADR-07, ADR-19.

---

## Adding a new ADR

1. Append a new `## ADR-NN — Title` section here with all fields (Status, Implementation Status, Sprint Target, Context, Decision, Affected Contexts, Dependencies, Superseded By, Related ADRs) and a summary-table row.
2. Reference `ADR-NN` in the PR description (or the fact that this file changed satisfies the ADR-reference check automatically).
3. If a decision replaces an earlier one, set the old ADR's **Status** to `Superseded` and its **Superseded By** to the new id.
4. Keep **Implementation Status** current as the decision moves Not started -> Foundation -> In progress -> Implemented.

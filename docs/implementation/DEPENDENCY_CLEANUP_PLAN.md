# Backend Dependency Cleanup Plan

> Plan only — no code, API, schema, or behavior changes. Goal: eliminate every architectural boundary violation **gradually, without breaking production**, converting direct cross-context Eloquent access into ports/published events per the redesign and ADRs. Every dependency below is derived from a static `use`-import scan of `apps/api/app`; a precise Deptrac count requires running Deptrac (dep not installed, baseline empty) and is **Not verifiable from repository**. No toolchain was executed while writing this document.

---

# Executive Summary

The intended architecture (`docs/redesign/*`, `docs/adr/INDEX.md`, ADR-02) is single-writer bounded contexts that may depend **only on `Shared` and `IdentityContracts`**; all cross-context access goes through **ports or published events**. The actual backend integrates largely by **direct cross-context Eloquent model access**. A file-level scan finds:

- **Learning → Authoring/Catalog: 41 model import sites** (`Authoring\Models\Lesson`, `Section`, `LessonMedia`; `Catalog\Models\Course`) + 1 `Catalog\Enums\CourseStatus`. This is the largest and most critical cluster (TD-1/TD-6).
- **Authoring → `Catalog\Models\Course`: 14**.
- **Commerce → `Catalog\Models\Course` (3) + `Learning\Models\Enrollment` (1) + `Learning\Enums\EnrollmentSource` (1)**.
- **Certification → `Catalog\Models\Course` (3) + `Learning\Events\CourseCompleted` (2)**.
- **Live → `Catalog\Models\Course` (1)**.
- **Analytics → 6 concrete domain Events** (OrderPaid, CourseCompleted, UserEnrolled, CertificateIssued, ConsultingRequestCreated, SessionCompleted) — the `MetricEventSubscriber` (TD-8).
- **All contexts → `Identity\Models\User`: 69 files** (ADR-20 target).
- **CRM and Catalog: zero outbound cross-context imports** (clean).

The redesign's cross-context ports (`CurriculumReadPort`, `AssessmentDefinitionPort`, `EntitlementPort`, `PlaybackPort`) **do not exist**; the only ports present are provider abstractions (`CoursePublishGuard`, `PaymentGateway`, `PdfGenerator`, `PlaybackTokenProvider`, provider contracts) — and even `Learning\Contracts\PlaybackTokenProvider` **leaks** `Authoring\Models\LessonMedia`.

This plan classifies every dependency, maps each removable one to a future port, and burns them down sprint-by-sprint (Sprint 2 → Phase D) using **expand-and-contract** so production never breaks: introduce a port as a thin adapter over the *existing* reads (parity, zero behavior change), repoint consumers, then delete the direct import. The controlling gate is the Deptrac baseline (generated at Sprint-1 close) which freezes today's violations and fails any *new* one; each sprint shrinks it.

---

# Dependency Inventory

All cross-context dependencies (outbound), grouped by owning context. "Sites" = distinct file→target import occurrences from the scan.

## Learning (41 model + 1 enum) — the critical cluster
- → `Authoring\Models\Lesson` (production: `ProgressService`, `LessonAccessService`, `LearningMediaService`, `ContinueLearningService`; Actions `ToggleBookmark`, `UpsertLessonNote`, `RecordLessonProgress`; Controllers `Bookmark`, `LessonPlayer`, `LessonProgress`, `Note`; Resources `ContinueLearning`, `LearnLessonItem`; Models `LearningSession`, `LessonBookmark`, `LessonNote`, `LessonProgress`; Events `LessonCompleted`, `LessonProgressRecorded`) + dev-only (`LessonNoteFactory`, `LessonProgressFactory`, `LearningSeeder`).
- → `Authoring\Models\Section` (`ProgressService`, `LessonAccessService`, `ContinueLearningService`, `LessonPlayerController`, `LearnSectionResource`; dev-only `LearningSeeder`).
- → `Authoring\Models\LessonMedia` (`Contracts\PlaybackTokenProvider`; `Playback\Providers\{CloudFront,Fake,Mux,S3}PlaybackTokenProvider`) — 5 sites (TD-6, media).
- → `Catalog\Models\Course` (`EnrollInCourseAction`, `GrantEnrollmentAction`, `EnrollmentController`, `LearnController`, `Enrollment` model, `LearningSession` model; dev-only `EnrollmentFactory`, `LearningSeeder`).
- → `Catalog\Enums\CourseStatus` (`EnrollInCourseAction`).

## Catalog
- **None.** No outbound cross-context imports (it is a source-of-truth context).

## Authoring (14)
- → `Catalog\Models\Course` ×14 (course-attached content operations).

## Commerce (5)
- → `Catalog\Models\Course` ×3; → `Learning\Models\Enrollment` ×1; → `Learning\Enums\EnrollmentSource` ×1.

## CRM
- **None.** No outbound cross-context imports (8 org-owned models now tenant-scoped).

## Certification (5)
- → `Catalog\Models\Course` ×3; → `Learning\Events\CourseCompleted` ×2 (auto-generate listener — published-language).

## Live (1)
- → `Catalog\Models\Course` ×1.

## Analytics (6 — events, TD-8)
- → `Commerce\Events\OrderPaid`, `Learning\Events\CourseCompleted`, `Learning\Events\UserEnrolled`, `Certification\Events\CertificateIssued`, `Crm\Events\ConsultingRequestCreated`, `Live\Events\SessionCompleted` (the `MetricEventSubscriber`).

## Identity (inbound, 69)
- **69 files across all contexts import `Identity\Models\User`** (models, actions, controllers, factories). ADR-20 target: depend on `IdentityContracts`, not the implementation.

## Platform
- **Shared:** depended on by all — **Allowed** by design.
- **Integration:** 4 contract stubs; no inbound/outbound context coupling yet.
- **Notifications:** subscribes to domain events (published-language) — inventoried under "events" (Not exhaustively listed here; scan shows Notifications imports many `Events\*`).

---

# Dependency Classification

| Dependency | Class | Rationale |
|------------|-------|-----------|
| Any context → `Shared`, `IdentityContracts`, framework | **Allowed** | The intended kernel dependencies (ADR-02/20). |
| Intra-context imports | **Allowed** | Within a single owner. |
| Authoring → `Catalog\Contracts\CoursePublishGuard` (port, already exists) | **Allowed** | Correct inbound-port usage (the redesign's model). |
| Cross-context **event subscriptions** (Certification←`Learning\Events\CourseCompleted`; Analytics←6 events; Notifications←events; Commerce←events) | **Temporarily Allowed** | Published-language integration; keep until an event-DTO package in `Shared` (or an explicit Deptrac allowance for `Events`) is decided. Analytics's is additionally **Deprecated** in favour of published projections (ADR-18). |
| Factories/Seeders referencing foreign models (`EnrollmentFactory`, `LessonProgressFactory`, `LessonNoteFactory`, `LearningSeeder`, `EnrollmentFactory`) | **Temporarily Allowed** | Dev/test-only; low production risk; refactor last (or via test fixtures/ports). |
| `Learning\Contracts\PlaybackTokenProvider` + `Playback\Providers\*` → `Authoring\Models\LessonMedia` | **Deprecated** | Media belongs to a Media Platform behind `PlaybackPort`/`MediaPort` (ADR-08); this whole subtree moves out of Learning. |
| Learning production code → `Authoring\Models\{Lesson,Section}` (services/actions/controllers/resources/models/events) | **Must Remove** | TD-1; replace with `CurriculumReadPort`. |
| Learning production code → `Catalog\Models\Course` + `Catalog\Enums\CourseStatus` | **Must Remove** | Replace with `CurriculumReadPort` (course ref) / `EntitlementPort`. |
| Authoring → `Catalog\Models\Course` (×14) | **Must Remove** | Reference Course by id/ref; publish via existing guard port. |
| Commerce → `Catalog\Models\Course`, `Learning\Models\Enrollment`, `Learning\Enums\EnrollmentSource` | **Must Remove** | Replace with course ref + `EntitlementPort` (grant to Learning). |
| Certification → `Catalog\Models\Course` (×3) | **Must Remove** | Course ref. |
| Live → `Catalog\Models\Course` (×1) | **Must Remove** | Course ref. |
| Analytics → concrete Events (×6) | **Must Remove** (Deprecated) | Consume published projections (ADR-18). |
| All contexts → `Identity\Models\User` (×69) | **Must Remove** (gradual) | Depend on `IdentityContracts` / a `UserRef` (ADR-20). |
| `CurriculumReadPort`, `AssessmentDefinitionPort`, `EntitlementPort`, `PlaybackPort`, `MediaPort`, `SearchPort`, `AIProvider`, Integration `Outbox/EventBus/WebhookPublisher` | **Future Port** | Not yet present; introduced per the burn-down. |

---

# Port Replacement Plan

Each removable dependency and the port that replaces it. Migration strategy is **expand-and-contract**: (1) introduce the port + a parity adapter over the *current* reads, (2) repoint consumers behind a flag, (3) delete the direct import + tighten Deptrac. Owner = the context that **implements** the port.

| Current dependency | Future port | Owner (implements) | Target sprint | Migration strategy | Risk |
|--------------------|-------------|--------------------|:---:|--------------------|:---:|
| Learning → `Authoring\Models\{Lesson,Section}` (curriculum/prereqs) | `CurriculumReadPort` (DTO: lessonRef, sectionRef, order, weight, isPreview, prereqRefs) | Catalog/Authoring | Sprint 5 (B1) | Adapter wraps existing `Section::`/`Lesson::` reads → parity tests → repoint `ProgressService`/`LessonAccessService`/`ContinueLearningService`/controllers/resources → delete imports | High |
| Learning → `Catalog\Models\Course` + `CourseStatus` | `CurriculumReadPort` (course ref) + `EntitlementPort` (enrollable/preview decision) | Catalog / Commerce | Sprint 5–6 | Replace `is_preview`/status logic with entitlement decision; course ref by id | High |
| Learning `Playback*` → `Authoring\Models\LessonMedia` | `PlaybackPort` / `MediaPort` (AssetRef) | Media Platform | Sprint 4 (A5) | Move `Playback/*` + `LearningMediaService` behind Media ports; Learning holds asset refs | Medium |
| Authoring → `Catalog\Models\Course` (×14) | course reference (id) + existing `CoursePublishGuard` | Catalog | Sprint 5 (B1) | Reference Course by id; keep publish-readiness via the existing guard port | Medium |
| Commerce → `Catalog\Models\Course` | course reference | Catalog | Sprint 6 (C1) | Course ref by id in pricing/product | Medium |
| Commerce → `Learning\Models\Enrollment` + `EnrollmentSource` | `EntitlementPort` (grant) | Commerce provides, Learning consumes | Sprint 6 (C1) | Emit `OrderPaid` → grant via port (outbox-backed) instead of writing Enrollment directly | High |
| Certification → `Catalog\Models\Course` (×3) | course reference | Catalog | Sprint 8 | Course ref; certificate stores ref | Low |
| Live → `Catalog\Models\Course` (×1) | course reference | Catalog | Sprint 4 (C10 relocation) | Course ref | Low |
| Analytics → concrete Events (×6) | published `LearningAnalyticsProjection` + projections | Learning/others publish | Sprint 12 (D5) | Dual-run: consume projections, then remove concrete-event subscriptions | Medium |
| All contexts → `Identity\Models\User` (×69) | `IdentityContracts` (UserRef / lookup port) | Identity | Sprint 3+ (burn-down) | Introduce `UserRef` + read port; migrate file-by-file; baseline shrinks | Medium |
| Cross-context event subscriptions (published-language) | Shared **event-DTO package** (or Deptrac allowance for `Events`) | Shared / SA decision | Sprint 2 (decision), Sprint 4 (impl) | Decide policy; move event DTOs to `Shared\...\Events` or whitelist the `Events` seam | Medium |

---

# Burn-down Plan

Sprint-by-sprint elimination. Each step is additive/reversible and gated on Deptrac + tests (per `101_EXECUTION_RULES.md`).

**Sprint 1 close (precondition):** generate the Deptrac baseline — this **freezes** today's ~70 peer-context import sites + 69 Identity\User sites + event subscriptions as the burn-down ledger; from here **new** violations fail. No removals yet.

**Sprint 2 (A4 + A6):** no context-coupling removal (security/outbox focus). Decisions only: (a) event-DTO policy (Shared package vs Deptrac `Events` allowance); (b) confirm the baseline is committed and enforced. Deprecate the Learning `Playback*`→`LessonMedia` subtree (mark for A5). **Forbidden peer-context deps unchanged; new = 0.**

**Sprint 3 (A3 Administration + tenancy):** begin **Identity\User burn-down** where cheap (introduce `IdentityContracts` `UserRef`/lookup; migrate the highest-churn contexts). Target: reduce Identity\User sites by ~30–40%. No content-port work yet.

**Sprint 4 (A5 ports + relocations):** introduce **PlaybackPort/MediaPort** → remove Learning's 5 `LessonMedia` imports + `LearningMediaService` media reads → **Learning media coupling = 0**. Relocate Live/Catalog to `Contexts` (C9/C10) — Live→Course ref cleaned during move. Introduce `SearchPort` (no coupling change).

**Sprint 5 (B1 content ports + versioning):** introduce **CurriculumReadPort** (+ `EntitlementPort` scaffold) → repoint Learning `ProgressService`/`LessonAccessService`/`ContinueLearningService`/controllers/resources/models off `Authoring\Models\{Lesson,Section}` and `Catalog\Models\Course` → remove ~35 Learning production sites. Remove Authoring→Course (×14) via course ref. **Biggest single drop.**

**Sprint 6 (C1 Commerce):** `EntitlementPort` grant path (outbox) → remove Commerce→`Learning\Models\Enrollment`/`EnrollmentSource` and Commerce→Course. **Commerce peer-context deps = 0.**

**Sprint 7–8 (B3/B4 + Certification):** remove Certification→Course (ref) and, when the LRS/projection lands, prepare Analytics repoint. Convert Learning/Certification event subscriptions to the chosen event-DTO policy.

**Sprint 11 (D1/D2):** Organization + Instructor splits — align with existing frontend `(organization)`/`(instructor)` groups; no new cross-context model coupling introduced (ports only).

**Sprint 12 (D5 Analytics repoint):** consume published projections → remove Analytics's 6 concrete-event imports. **Analytics event coupling = 0.**

**Phase D close:** finish Identity\User burn-down (all contexts on `IdentityContracts`). **Target: zero forbidden peer-context model/enum dependencies; cross-context communication only via ports + event DTOs.**

---

# Safe Refactoring Rules

Mandatory for every dependency-removal PR (extends `101_EXECUTION_RULES.md`):

1. **Expand-and-contract, never rip-and-replace.** Introduce the port + a parity adapter over the *existing* reads first; prove identical results with parity tests; only then repoint consumers; only then delete the direct import.
2. **One dependency cluster per PR.** e.g. "Learning→LessonMedia via PlaybackPort" is one PR; don't mix with Course removal. Small, reviewable, reversible.
3. **No behavior/API/schema change.** The port returns the same data the direct read returned; endpoints and payloads are byte-identical (OpenAPI diff must be empty).
4. **Ports return DTOs/refs, never foreign Eloquent models.** A consumer may hold `courseRef`/`lessonRef`, never a `Course`/`Lesson` instance for business logic.
5. **Deptrac baseline only shrinks.** Each PR removes the corresponding baseline entries; growing the baseline is forbidden. CI fails on any new violation.
6. **Behind a flag when risk is High.** Port-vs-direct read is flag-switchable during migration; revert = flip the flag.
7. **Tests first at the seam.** Test the port contract (mock the adapter); keep the existing feature tests green as the regression guard.
8. **Events are DTOs.** When converting event subscriptions, the event payload carries scalars/VOs/refs — never an Eloquent model.
9. **Dev-only coupling (factories/seeders) refactored last** and may use test-only fixtures; never let a production path depend on a factory's foreign import.
10. **ADR-referenced.** Every port PR cites the ADR it realises (ADR-08/09/11/16/18/20) and updates `docs/adr/INDEX.md` status.

---

# Dependency Dashboard

Counts are **cross-context import sites to peer domain contexts** (excluding `Shared`/`IdentityContracts`), from the scan. Two lanes tracked separately: **(A) peer-context model/enum coupling**, **(B) Identity\User coupling**, **(C) cross-context event subscriptions**. Projections assume the burn-down above; they are targets, not guarantees.

| Metric | Current (baseline) | After Sprint 2 | After Sprint 3 | After Sprint 4 | Expected Final (Phase D) |
|--------|:---:|:---:|:---:|:---:|:---:|
| **(A) Peer-context model/enum sites** | ~66 (Learning 42, Authoring 14, Commerce 5, Certification 3, Live 1, +Catalog/CRM 0) | ~66 (frozen; new=0) | ~66 | ~60 (Learning media −5, Live −1) | **0** |
| — of which **Learning** | 42 | 42 | 42 | 37 (−media 5) | 0 (Sprint 5) |
| — of which **Authoring** | 14 | 14 | 14 | 14 | 0 (Sprint 5) |
| — of which **Commerce** | 5 | 5 | 5 | 5 | 0 (Sprint 6) |
| **(B) `Identity\Models\User` sites (files)** | 69 | 69 | ~45 | ~40 | **0** |
| **(C) Cross-context event subscriptions** | Analytics 6 + Certification 2 + Notifications/Commerce (many) | policy decided | unchanged | event-DTO package | **Allowed via DTOs** (Analytics concrete → 0 by Sprint 12) |
| **New (post-baseline) violations** | 0 | **0 (enforced)** | 0 | 0 | 0 |

Interpretation: Sprint 2/3 mostly *freeze and enforce* (plus Identity burn-down start); the large mechanical drop lands in **Sprint 4 (media) and Sprint 5 (curriculum)**, with Commerce (6), Analytics (12), and Identity (Phase D) closing the remainder to **zero forbidden peer-context dependencies**.

---

# Final Recommendation

**Proceed — but gate the cleanup behind an operational, enforced baseline, and sequence the mechanical removals with the ports that own them.**

1. **Do not remove any dependency until the Deptrac baseline is generated, committed, and enforced** (Sprint-1 close). Without it, removals are unmeasured and new violations can slip in. This is the single precondition.
2. **Do not hand-remove couplings ahead of their port.** The 66 peer-context sites are symptoms; the cure is the port that owns the data. Introduce `PlaybackPort` (Sprint 4) and `CurriculumReadPort`/`EntitlementPort` (Sprint 5–6) as parity adapters, then delete the imports — this removes ~48 of the 66 sites safely.
3. **Treat event subscriptions as a policy decision, not a bug.** Decide in Sprint 2 whether published events live as DTOs in `Shared` or the `Events` seam is explicitly allowed in Deptrac; then Analytics repoints to projections (ADR-18) by Sprint 12.
4. **Burn down `Identity\Models\User` (69 files) gradually** via `IdentityContracts`, starting Sprint 3 — it is the largest single lane and the lowest-risk to migrate incrementally.
5. **Hold the invariant:** the baseline only shrinks; every PR is expand-and-contract, no behavior/API/schema change, ADR-referenced, Deptrac+tests green.

Executed this way, the backend reaches **zero forbidden peer-context dependencies by end of Phase D** with **no production breakage** — each removal is a parity-tested, reversible, flag-gated step behind a port that already returns identical data. The design and the plan are sound; the risk is sequencing discipline, which the baseline + these rules enforce.

---

## Validation

- Every dependency, count, and file reference is derived from a static `use`-import scan of `apps/api/app` and the contents of `PROJECT_STATUS.md`, `ARCHITECTURE_GAP_ANALYSIS.md`, `docs/adr/INDEX.md`, and `docs/redesign/*`.
- Items that cannot be confirmed from the repository are marked **"Not verifiable from repository"** (precise Deptrac violation counts; whether tests pass; the full list of Notifications/Commerce event subscriptions; runtime behavior).
- No code, API, schema, or existing document was modified. Only `docs/implementation/DEPENDENCY_CLEANUP_PLAN.md` was created.

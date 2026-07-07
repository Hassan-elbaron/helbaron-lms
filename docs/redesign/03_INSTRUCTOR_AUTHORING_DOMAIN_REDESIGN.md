# Instructor & Authoring Domain Redesign (Phase 3) — Architecture Only

**Role:** Principal Domain Architect. **Type:** documentation only — no code, no moves, no namespace/API/DB changes.
**Grounding:** current `Authoring` domain as built (Sections, Lessons, LessonMedia, Curriculum tree/validator/publish-guard, publish state, admin API + Filament) verified in audits 04/05 and refactor 07A; **Instructor has no backend domain today** — instructors are `Identity` users with `role=instructor` plus Catalog's `course_trainer` assignment, and the `/teach` web area is placeholder-only (refactor 5E).
**Thesis:** **Instructor owns teachers; Authoring owns content. Instructor NEVER owns lessons.** These are two independent bounded contexts that interact only via contracts and events, consistent with Phase 1 (Catalog owns the Course; Authoring implements Catalog's `CoursePublishGuard`) and Phase 2 (capabilities/permissions layering).

---

## Executive Summary

Today "authoring" is an admin-only content module and "instructor" is an implicit role scattered across Identity (the user) and Catalog (`course_trainer`). This conflates two very different responsibilities: **managing the people who teach** (their profile, assignments, schedule, revenue, reviews, availability, teams) versus **managing the content they produce** (structure, lessons, versions, assessments, media, publishing). The redesign creates:

- **Instructor** — the teacher-management context: instructor profile (a reference over an Identity user), teaching assignments / course ownership, teaching permissions & schedule, teaching analytics, revenue (references Commerce), reviews, availability, branding, teams, settings, integrations. It owns the **teaching relationship**, never the content.
- **Authoring** — the content system: course structure, sections, lessons (with **versioning**), lesson assets/metadata/transcripts/captions, assessments (quizzes/assignments), resources/attachments/SCORM, AI generation, content validation, and the publishing/review/approval workflows. It owns the **content**, and answers Catalog's publish-readiness port.

They meet at the seam: Instructor's `TeachingAssignment` says *who may edit which course*; Authoring consults a `TeachingAuthority` inbound port (implemented by Instructor/Catalog) to authorize content edits — Authoring never reads Instructor internals, Instructor never reads Lesson models. Content-readiness flows to Catalog (Phase 1) via `CoursePublishGuard`; teaching/revenue/review facts flow to Instructor via events.

Outcome: instructors get a real product (`/teach`), content gains proper versioning/review/approval and an AI pipeline with human-in-the-loop, and the two concerns scale independently (instructor marketplace/teams vs. content assessments/SCORM/AI).

---

## Current Problems

1. **Instructor concept is homeless.** No Instructor domain exists; "instructor" = `Identity.role` + `Catalog.course_trainer`. There is nowhere to own teaching assignments, revenue, reviews, availability, or teams. *(refactor 01/C1)*
2. **Authoring is admin-only, not instructor-enabled.** `LessonAdminController`/`SectionAdminController` + Filament assume an admin; there is no policy path for an instructor to edit **their** course's content. *(refactor 01)*
3. **Wrong/absent ownership of assessments.** Quizzes and assignments do not exist (only a `quiz_placeholder` `LessonType`); the learning journey ends at video (audit 06). Assessment ownership is undefined — it belongs to **Authoring**.
4. **No content versioning.** Lessons are mutable in place; there is no working-draft vs published version, no rollback, no parallel-edit/conflict handling — risky once instructors edit live courses. *(aligns with Catalog Phase 1 versioning)*
5. **Media ownership blur.** `LessonMedia` couples content to storage; per Phase 1 Asset Ownership, **Media Platform owns bytes**, Authoring owns **asset references**.
6. **Publish-readiness coupling is one-directional but informal.** `CurriculumPublishGuard` binds to Catalog's `CoursePublishGuard` (good seed), but the review/approval workflow and the events around readiness are not formalized.
7. **Cross-course leakage guarded but ad hoc.** `CrossCourseReferenceException`/`PrerequisiteCycleException` exist, but there is no explicit aggregate boundary making a Lesson belong to exactly one Course's curriculum.
8. **Scalability blocked.** Instructor teams, assistant/guest/external/partner instructors, revenue sharing, instructor marketplace, and an AI authoring pipeline cannot be added cleanly while "instructor" is a role and "authoring" is an admin CRUD.

---

## Instructor Boundary

**Mission:** Own the **people who teach** and their teaching relationship to courses — profile, assignments, permissions, schedule, revenue, reputation, availability, teams — without owning any content.

**Instructor OWNS:** Instructor Profile · Instructor Identity Reference (→ Identity userId) · Instructor Dashboard (read models) · Teaching Assignments · Course Ownership (teaching side) · Teaching Permissions · Teaching Schedule · Teaching Analytics (read models) · Instructor Revenue (→ Commerce refs) · Instructor Reviews · Instructor Notifications (→ Notifications) · Instructor Certificates (teaching credentials, → Certification refs) · Instructor Availability · Instructor Branding · Instructor Team · Instructor Settings · Instructor Integrations.

**Instructor does NOT own:** lessons/sections/assessments/media (Authoring); course definition/slug/lifecycle (Catalog); user identity/auth (Identity); money/payouts (Commerce owns; Instructor holds revenue *references/shares*); enrollment/progress (Learning; consumed for analytics by id).

---

## Authoring Boundary

**Mission:** Own the **content** of a course — its structure, lessons, versions, assessments, media references, and the workflows that take content from draft to published — and answer Catalog's publish-readiness.

**Authoring OWNS:** Course Structure · Sections · Lessons · Lesson Ordering · Lesson Drafts · Lesson Publishing · Lesson Versioning · Lesson Assets (refs) · Lesson Metadata · Lesson Transcripts · Lesson Captions · Lesson Assessments · Quizzes · Assignments · Resources · Attachments · SCORM · Lesson AI Generation · Content Validation · Publishing Workflow · Review Workflow · Approval Workflow.

**Authoring does NOT own:** who teaches (Instructor); course marketing/lifecycle/slug (Catalog); media bytes/transcoding (Media Platform; Authoring holds asset ids); enrollment/grading-at-scale/attempts (Learning owns learner *attempts*; Authoring owns the *definition* of a quiz/assignment); certificates (Certification).

---

## Context Ownership

| Concern | Owner | Cross-context interaction |
|--------|-------|---------------------------|
| Teacher profile / assignment / schedule | **Instructor** | `TeachingAuthority` port to Authoring; events |
| Course structure / lessons / versions | **Authoring** | consults `TeachingAuthority`; emits content events |
| Assessment *definition* (quiz/assignment) | **Authoring** | Learning consumes definition by id |
| Assessment *attempts / grading / submission* | **Learning** | reads Authoring definition; owns learner data |
| Course definition / publish decision | **Catalog** (Phase 1) | Authoring answers `CoursePublishGuard`; Catalog emits `CoursePublished` |
| Media bytes / transcode / captions processing | **Media Platform** | Authoring holds asset ids; subscribes to `MediaAssetReady` |
| Course ownership (marketing/trainer list) | **Catalog** (`course_trainer`) | Instructor is the write-side of teaching assignment; Catalog renders trainers |
| Revenue / payouts | **Commerce** | Instructor holds revenue-share config + refs |
| Instructor reviews / reputation | **Instructor** | fed by Learning completion + student ratings (events) |
| Notifications to instructors | **Notifications** | Instructor emits events; Notifications delivers |

---

## Entities

### Instructor context
- **InstructorProfile** *(aggregate root)* — `instructor_public_id`, `userId` (Identity ref), display name, bio/branding, headline, expertise, `status` (active|suspended), `type` (staff|assistant|guest|external|partner), `settings`.
- **TeachingAssignment** *(aggregate root)* — `courseId` (Catalog ref), `instructorId`, `role` (lead|co|assistant|guest), `scope` (edit|deliver|view), `since`, `until?`.
- **InstructorTeam** — a group of instructors under a lead/owner (teamId, members, roles).
- **TeachingSchedule** / **Availability** — bookable slots, timezone, blackout.
- **InstructorRevenueShare** — `{ instructorId, courseId?, sharePct, source }` (references Commerce payouts).
- **InstructorReview** — student review of the instructor (rating, text, verified-enrollment flag).
- **InstructorCredential** — teaching certificate/qualification (→ Certification ref).
- **InstructorIntegration** / **InstructorSetting**.

### Authoring context
- **Curriculum** *(aggregate root, keyed by courseId)* — the ordered tree of Sections + Lesson placements + publish state of the structure.
- **Section** — grouping within a curriculum (title, order, publish state).
- **Lesson** *(aggregate root)* — `lesson_public_id`, `courseId`, `sectionId`, `type` (video|article|external_link|quiz|assignment|scorm), current `publishState`, `position`, prerequisites, metadata, **versions**.
- **LessonVersion** — immutable snapshot of a lesson's content (body/media refs/metadata) with `state` (draft|in_review|approved|published|archived).
- **LessonMedia** — asset **reference** (assetId, role, variants) — bytes in Media Platform.
- **Transcript / Caption** — asset references + locale.
- **Assessment** *(aggregate root)* — `Quiz` or `Assignment` definition (belongs to a lesson/section).
- **Question** — quiz question (type, options, correct answer, scoring) — Authoring-owned; learner **attempts** are Learning's.
- **AssignmentSpec** — instructions, rubric, submission type, due policy.
- **Resource / Attachment** — downloadable asset reference.
- **ScormPackage** — SCORM manifest reference (packaged in Media Platform).
- **AIGenerationJob** — a content-generation task + its suggestion output (pending human approval).
- **ContentValidation** — validation result for a version.

---

## Aggregates

| Context | Aggregate | Root | Key invariants | Boundary |
|---------|-----------|------|----------------|----------|
| Instructor | **InstructorProfile** | InstructorProfile | one profile per userId; status transitions | profile + settings/branding |
| Instructor | **TeachingAssignment** | TeachingAssignment | one lead per course; assignment references existing course + instructor; scope legal | assignment |
| Instructor | **InstructorTeam** | InstructorTeam | members are instructors; lead is a member | team + memberships |
| Authoring | **Curriculum** | Curriculum(courseId) | a lesson belongs to exactly one curriculum; no cross-course references; prerequisite graph acyclic; publish requires readiness | curriculum + sections + lesson placements |
| Authoring | **Lesson** | Lesson | at most one published version; one working draft; version state machine | lesson + its versions + media refs |
| Authoring | **Assessment** | Assessment | questions belong to one assessment; scoring valid | assessment + questions |

Cross-aggregate + cross-context references are **by id only**.

---

## Value Objects

**Instructor:** `InstructorType` (staff|assistant|guest|external|partner), `TeachingRole` (lead|co|assistant|guest), `AssignmentScope` (edit|deliver|view), `RevenueShare(pct, source)`, `AvailabilitySlot`, `Rating(value, count)`, `InstructorStatus`.
**Authoring:** `LessonType` (*exists*), `PublishState`/`ContentState` (draft|editing|in_review|approved|scheduled|published|archived|deprecated|deleted), `LessonVersionRef(lessonId, version)`, `CurriculumVersionRef(courseId, version)`, `Position`, `PrerequisiteRule`, `AssetRef(assetId, role)`, `LocalizedText` (shared VO with Catalog Phase 1), `Rubric`, `QuestionScore`, `ValidationResult`.

---

## Commands

### Instructor
`CreateInstructorProfile` · `UpdateInstructorProfile/Branding/Settings` · `SuspendInstructor` · `AssignTeaching(courseId, instructorId, role, scope)` · `RemoveTeaching` · `ChangeTeachingRole` · `CreateInstructorTeam` · `AddTeamMember` · `SetAvailability` · `ConfigureRevenueShare` · `PublishInstructorReviewResponse` · `ConnectInstructorIntegration`.

### Authoring
`CreateSection` · `UpdateSection` · `ReorderSections` · `CreateLesson` · `UpdateLessonDraft` · `ReorderLessons` · `SetLessonPrerequisites` · `TogglePreview` · `UpsertLessonMedia` · `AddTranscript/Caption` · `CreateQuiz` · `AddQuestion` · `CreateAssignment(spec)` · `AttachResource` · `UploadScormPackage` · `SubmitForReview` · `Review(approve|request-changes)` · `Approve` · `SchedulePublish(at)` · `PublishLessonVersion` · `PublishCurriculum` · `ArchiveLesson` · `DeprecateLesson` · `RollbackLessonVersion` · `RequestAIGeneration(kind)` · `AcceptAISuggestion` · `RejectAISuggestion` · `ValidateContent`.

Every command → application service → transaction → event(s); authorized by policy + permission + (for Authoring writes) the `TeachingAuthority` port.

---

## Queries

**Instructor:** `GetInstructorProfile(id)` · `GetMyCourses(instructorId)` · `GetTeachingSchedule` · `GetInstructorRevenue(instructorId, period)` · `GetInstructorReviews(instructorId)` · `GetInstructorDashboard(instructorId)` · `GetTeamMembers(teamId)`.
**Authoring:** `GetCurriculumTree(courseId)` *(exists)* · `GetLesson(id)` · `GetLessonVersions(lessonId)` · `GetDraft(lessonId)` · `GetReviewQueue(courseId)` · `GetAssessment(id)` · `GetPublishReadiness(courseId)` · `GetAIGenerationSuggestions(lessonId)`.

Queries return **read models**, never Eloquent aggregates.

---

## Events

### Instructor events
`InstructorProfileCreated` · `InstructorSuspended` · `InstructorAssigned` (teaching) · `InstructorRemoved` · `TeachingRoleChanged` · `InstructorTeamCreated` · `TeamMemberAdded` · `AvailabilitySet` · `RevenueShareConfigured` · `InstructorReviewReceived`.

### Authoring events
`SectionCreated/Updated` · `LessonCreated` · `LessonUpdated` · `LessonSubmittedForReview` · `LessonReviewed` · `LessonApproved` · `LessonScheduled` · `LessonPublished` · `LessonVersionPublished` · `LessonRolledBack` · `LessonArchived` · `LessonDeprecated` · `CurriculumReordered` · `CurriculumPublished` · `QuizPublished` · `AssignmentPublished` · `ScormPackageReady` · `CourseReadyForPublish` · `AIGenerationCompleted` · `AISuggestionAccepted`.

All events carry **DTOs (ids + primitives)**; Learning/Catalog/Analytics/Notifications consume without importing Authoring/Instructor.

---

## Read Models

**Instructor:** `InstructorCard` · `MyCoursesRow` (courseId, title, status, students, rating) · `TeachingScheduleView` · `RevenueSummary` (from Commerce) · `ReviewFeed` · `InstructorDashboardTiles` (students, revenue, ratings, pending reviews).
**Authoring:** `CurriculumTreeView` · `LessonEditorState` (working draft) · `ReviewQueueRow` · `PublishReadinessReport` · `AssessmentView` · `AISuggestionCard` · `VersionHistory`.

Maintained by projectors on each context's own events; the cacheable surface.

---

## Services

**Instructor:** `InstructorProfileService` · `TeachingAssignmentService` (assign/remove/role) · `InstructorTeamService` · `AvailabilityService` · `RevenueShareService` (composes Commerce payout refs) · `InstructorReviewService` · `TeachingAnalyticsService` (composes Learning/Catalog by id).
**Authoring:** `CurriculumService` (tree/reorder — from `CurriculumTreeService`) · `CurriculumValidator` *(exists)* · `LessonService` · `LessonVersioningService` · `AssessmentService` · `PublishingWorkflowService` · `ReviewWorkflowService` · `ApprovalWorkflowService` · `CompletionEstimationService` *(exists)* · `ContentValidationService` · `AuthoringAIService` (orchestrates AI providers) · `CurriculumPublishGuard` *(exists; implements Catalog `CoursePublishGuard`)*.

---

## Repository Interfaces

**Instructor ports:** `InstructorRepository`, `TeachingAssignmentRepository`, `InstructorReadRepository`; outbound `IdentityDirectory` (user), `CourseDirectory` (Catalog, course refs), `RevenueDirectory` (Commerce), `LearningStatsPort` (analytics by id).
**Authoring ports:** `CurriculumRepository`, `LessonRepository`, `AssessmentRepository`, `AuthoringReadRepository`; inbound `TeachingAuthority` (implemented by Instructor/Catalog: `canEdit(userId, courseId): bool`, `canReview/Approve(...)`); outbound `MediaAssetPort` (Media Platform), `CoursePublishGuard` implemented **for** Catalog, `AIProvider` (LLM adapters).

Neither context depends on the other's concrete classes — only these interfaces + events.

---

## API Ownership

- **Instructor API:** `/api/v1/teach/*` — profile, my-courses, assignments, schedule, revenue, reviews, team. (Backs the `/teach` web area from refactor 5E.)
- **Authoring API:** `/api/v1/authoring/*` (today's admin curriculum/lesson/section endpoints, opened to instructors via `TeachingAuthority`) — sections, lessons, versions, assessments, review/approve/publish, AI generation.
- **Assessment attempts** are **Learning's** API, not Authoring's.
- **OpenAPI:** `instructor.yaml` (new) + `authoring.yaml` (exists, extended for versions/assessments/AI). URLs preserved where they exist.

## Filament Ownership

- **Instructor admin resources:** `InstructorResource`, `TeachingAssignmentResource` (Administration oversight of teachers).
- **Authoring admin resources:** `SectionResource`, `LessonResource` (exist), plus `AssessmentResource`, `ReviewQueueResource`.
- Discovery via the **data map** in `AdminPanelProvider` (refactor 5E) — add `Contexts/Instructor/...` and `Contexts/Instructor/Authoring/...` map lines; **no branches**.
- Day-to-day instructor authoring is the **`/teach` web app**, not Filament.

## Search Strategy

- **Instructor search:** find instructors by name/expertise (public trainer directory is Catalog's read; Instructor owns the richer internal search); scoped by admin.
- **Authoring search:** find lessons/assessments within a course (in-editor search); admin content search; future full-text over lesson bodies fed by Authoring events.
- Public course content is **not** exposed by Authoring search (Marketing renders published content via Catalog read models).

## Cache Strategy

- Read models cached per aggregate: `authoring:curriculum:{courseId}`, `authoring:lesson:{id}`, `instructor:{id}:dashboard`; invalidated on the owning context's events.
- The **published curriculum version** projection (consumed by Learning's player) is cached and busted on `CurriculumPublished`/`LessonVersionPublished`.
- Editor/draft state is uncached (live) or per-session.

---

## Versioning Strategy

Extends and mirrors Catalog Phase 1 (a Catalog **Course Version** references an Authoring **Curriculum Version**).

- **Working Draft:** each Lesson has at most one editable working draft version; editing a published lesson **forks** a new draft (copy-on-write) — students keep studying the published version.
- **Published Version:** exactly one per lesson; the player/Learning targets it; immutable.
- **Rollback:** `RollbackLessonVersion(lessonId, toVersion)` re-promotes an archived version (pointer swap, no data migration); emits `LessonRolledBack`.
- **Parallel editing:** multiple instructors/assistants may hold drafts; concurrency handled by **optimistic version tokens** per lesson version.
- **Conflict detection:** a save carrying a stale base-version token is rejected with a merge prompt (`ConflictDetected`); Authoring never silently overwrites.
- **Review snapshot:** submitting for review freezes an **immutable review snapshot** so reviewers see exactly what was submitted; approval publishes that snapshot; further edits create a new draft.
- **Curriculum version:** publishing a curriculum bundles the set of published lesson versions into a `CurriculumVersion`, which is what a Catalog Course Version points at (Phase 1) — so Catalog rollback and Authoring rollback compose cleanly.

Storage: additive `lesson_versions`, `curriculum_versions` tables (references, not copies of media); the `lessons`/`sections` rows keep identity + pointers. **Zero schema change to existing tables** at introduction (backfill current content as v1 published).

## Publishing Strategy

- Publish is per-lesson-version and per-curriculum. `SchedulePublish(at)` enables timed release; a scheduler promotes at the target time.
- `PublishCurriculum` requires `ContentValidation` pass + all required lessons published; on success emits `CurriculumPublished` and answers Catalog's `CoursePublishGuard.canPublish = true`.
- Catalog owns the **course** publish decision; Authoring owns **content** publish and **readiness**.

## Review Strategy

- Roles: **author** (instructor/assistant) → **reviewer** (lead instructor / editor) → **approver** (admin, for governed orgs).
- Flow: `SubmitForReview` (freezes review snapshot) → reviewer `Review(approve|request-changes)` → on approve, `Approve` → publishable.
- Review queue is a read model per course; comments attach to the review snapshot (not the live draft).

## Approval Strategy

- Configurable per context/org capability: some orgs require an **approval gate** before publish (government/enterprise templates from Phase 2), others allow lead-instructor self-publish.
- Approval authority resolved via `TeachingAuthority.canApprove(userId, courseId)` + org capability (`CanUseAutomation`/governance policy).
- Approval emits `LessonApproved`/`CurriculumApproved`; publish is a separate, subsequent command (approve ≠ publish).

---

## AI Strategy

Authoring AI is a **human-in-the-loop suggestion pipeline** behind an `AIProvider` port (never a direct dependency on any LLM vendor); every AI output is a **suggestion pending human approval**, never auto-published.

| Capability | Input | Output (suggestion) | Approval |
|-----------|-------|--------------------|----------|
| Lesson generation | topic/outline | draft lesson body | author edits + accepts → new draft version |
| Quiz generation | lesson content | question set | author reviews correctness → `AddQuestion` |
| Assignment generation | learning objective | assignment spec + rubric | author edits → `CreateAssignment` |
| Translation | source version + locale | localized draft (LocalizedText) | reviewer approves per locale |
| Summarization | lesson body | summary/metadata | author accepts into metadata |
| Accessibility improvements | media + body | caption/transcript/alt-text suggestions | author accepts; a11y validation |
| Metadata generation | content | title/description/tags/SEO(hand-off to Catalog) | author accepts |
| AI suggestions | editor context | inline suggestions | accept/reject per suggestion |

Mechanics: `RequestAIGeneration(kind)` creates an `AIGenerationJob` (async, via outbox → `AIProvider`); result is an `AISuggestion` attached to a **draft** version; `AcceptAISuggestion` merges it into the working draft (creating an auditable trail: who generated, who accepted). **Guarantees:** provider-agnostic (swap models behind the port), idempotent by job id, failure-isolated (a provider outage never blocks manual authoring), and **no AI content reaches learners without an explicit human publish**.

---

## Dependency Rules

- **Instructor must NEVER own or import Lesson/Section/Assessment models.** **Authoring must never import Instructor models.**
- Interaction only via: (a) `TeachingAuthority` inbound port (Instructor/Catalog → Authoring authorization), (b) domain **events**, (c) read contracts (`CourseDirectory`, `IdentityDirectory`, `RevenueDirectory`, `LearningStatsPort`, `MediaAssetPort`, `AIProvider`).
- Authoring implements Catalog's `CoursePublishGuard` (Phase 1) — outbound to Catalog by contract only.
- Both depend on **Platform (Identity/Shared)** via interfaces; assessment **attempts** belong to Learning (Authoring exposes the *definition* contract only).
- **Forbidden:** Instructor↔Authoring model imports; events carrying Eloquent models; Authoring reading enrollment/attempts; Instructor reading content; either reaching S3/Mux/LLM directly (Media Platform / AIProvider ports only).
- Enforced by **Deptrac** once split.

---

## Event Flow (subscribers per event)

```
Instructor: InstructorAssigned ──┬─> Authoring: grant edit authority (via TeachingAuthority refresh)
                                 ├─> Catalog: reflect trainer on course (course_trainer) 
                                 ├─> Notifications: notify instructor [DTO]
                                 └─> Analytics: teaching-assignment metric [DTO]
Instructor: InstructorRemoved ───> Authoring (revoke authority), Catalog (trainer), Notifications

Authoring: LessonCreated/Updated ─> (internal projections); Analytics (content activity) [DTO]
Authoring: LessonSubmittedForReview ─> Notifications (reviewer), Instructor dashboard (review queue)
Authoring: LessonReviewed ───────> Notifications (author)
Authoring: LessonApproved ───────> (enables publish); Notifications
Authoring: LessonPublished / LessonVersionPublished ─┬─> Learning: refresh player/curriculum version
                                                     ├─> Catalog: contributes to publish-readiness
                                                     └─> Analytics/Notifications [DTO]
Authoring: QuizPublished / AssignmentPublished ──────> Learning: expose assessment definition for attempts
Authoring: CurriculumPublished ──────────────────────> Catalog: CoursePublishGuard.canPublish=true (course may publish)
Authoring: CourseReadyForPublish ────────────────────> Catalog: prompt/allow CoursePublish
Authoring: AIGenerationCompleted ────────────────────> Instructor (suggestion ready) [DTO]
Learning: CourseCompleted / StudentRated ────────────> Instructor: InstructorReviewReceived / rating update
Commerce: PayoutPosted ──────────────────────────────> Instructor: revenue read model
Media Platform: MediaAssetReady/Failed ──────────────> Authoring: mark lesson media usable/pending
```

Every subscriber consumes a **DTO**; no subscriber imports the emitter's models.

---

## Future Evolution

Additive-only, behind contracts/events (consistent with Phase 1/2 capability + template model):
- **Instructor Teams:** first-class `InstructorTeam` with roles; a lead delegates edit scope to assistants via `TeachingAssignment.scope`.
- **Assistant / Guest instructors:** `InstructorType` + scoped assignments (assistant = edit-limited; guest = deliver-only, no edit).
- **External / Partner instructors:** `InstructorType=external|partner` linked to a partner **Organization** (Phase 2); partner instructors bring their own content under co-brand.
- **Revenue sharing:** `InstructorRevenueShare` config → Commerce computes payouts; splits per course/edition (Catalog Editions, Phase 1); marketplace revenue-share for third-party creators.
- **Instructor Organizations:** an instructor may belong to an Organization (Phase 2) — assignments/revenue scoped to that tenant.
- **Instructor Marketplace:** instructors publish courses to a public marketplace (Catalog `PublisherId`, Phase 1); reputation from reviews; discovery via Catalog search.
- **Authoring scale:** assessment engine (question banks, adaptive quizzes), SCORM/xAPI, live collaborative editing (CRDT behind the version model), AI co-author expansion — all behind existing ports.

---

## Learning Object Model

Every learning object is modeled **independently** — an addressable, versionable unit with a single owning context — so objects can be composed, reused, and evolved without coupling. Objects reference each other **by id + version**, never by embedding another aggregate.

| Object | Owner | Lifecycle | Versioning | Dependencies | Reuse policy | Visibility | Inheritance | Assessment relation | Analytics relation | Searchable | Cache |
|--------|-------|-----------|-----------|--------------|--------------|-----------|-------------|--------------------|--------------------|-----------|-------|
| **Course** | Catalog | draft→published→archived (Phase 1) | Course Version | curriculum version, editions | referenced by editions | public/unlisted/private | edition inherits course | via curriculum | enrollment/completion metrics | yes (public read) | read-model |
| **Curriculum** | Authoring | draft→published (per course) | Curriculum Version | lessons, sections | one per course (not reused) | follows course | — | aggregates lesson assessments | structure metrics | admin/instructor | curriculum:{courseId} |
| **Section** | Authoring | with curriculum | with curriculum version | lessons | reusable as a template block | follows course | order inherited | groups lesson assessments | section progress | in-editor | with curriculum |
| **Lesson** | Authoring | Phase-3 content lifecycle | Lesson Version | media, transcripts, prereqs | reference/copy/fork (see Composition) | follows publish state | prereqs inherited | may host a quiz/assignment | lesson completion/time | in-editor | lesson:{id} |
| **Quiz** | Authoring (def) / Learning (attempts) | def: draft→published | Assessment Version | question bank/pool | reusable across lessons/courses | follows host | rules inheritable | is the assessment | attempt/score metrics | admin | assessment:{id} |
| **Question Bank** | Authoring | active/retired | Bank Version | questions | shared across quizzes/exams | private/org/shared | tags inherited | supplies pooled questions | item analysis | admin | bank:{id} |
| **Assignment** | Authoring (spec) / Learning (submissions) | def: draft→published | Assessment Version | rubric, resources | reusable | follows host | rubric inheritable | is the assessment | submission/grade metrics | admin | assessment:{id} |
| **Lab** (programming) | Authoring (spec) / Learning (runs) | def lifecycle | Assessment Version | runtime image ref (Media/infra) | reusable | follows host | — | graded attempt | pass rate, runtime | admin | assessment:{id} |
| **Exercise** | Authoring | with lesson | Lesson Version | lesson | reusable snippet | follows lesson | — | ungraded practice | practice metrics | in-editor | with lesson |
| **Project** | Authoring (spec) / Learning (submissions) | def lifecycle | Assessment Version | rubric, milestones | reusable | follows host | rubric inherit | graded, multi-stage | milestone metrics | admin | assessment:{id} |
| **Case Study** | Authoring | with lesson/assessment | version | media, questions | reusable | follows host | — | may be graded (essay) | engagement | in-editor | with host |
| **Survey** | Authoring (def) / Learning (responses) | def lifecycle | version | questions | reusable | follows host | — | ungraded, feedback | response analytics | admin | assessment:{id} |
| **Exam** | Authoring (def) / Learning (attempts) | def lifecycle | Assessment Version | question pools | reusable | restricted (timed) | rules inherit | high-stakes graded | pass/fail, integrity | admin | assessment:{id} |
| **Certification Requirement** | Certification | active/retired | requirement version | course/exam completion | referenced by cert templates | private | — | consumes exam/course result | cert issuance | admin | cert:{id} |
| **Learning Path Step** | Learning | active | path version | course/lesson/assessment refs | reusable across paths | org/public | path inherits step order | may gate on assessment | path progress | admin | path:{id} |
| **Prerequisite** | Authoring (content) / Learning (enforcement) | with lesson/course | with owner | lesson/course ref | rule, not content | follows host | inherited down curriculum | may gate assessment | unlock metrics | — | with host |
| **Competency** | Learning (framework) | active/retired | competency version | skills | shared taxonomy | org/public | rolls up from skills | mapped from assessments | mastery analytics | yes | competency:{id} |
| **Skill** | Learning (framework) | active/retired | version | — | shared taxonomy | org/public | maps to competency | tagged on assessments | skill mastery | yes | skill:{id} |
| **Learning Outcome** | Authoring (declared) / Learning (measured) | with course/lesson | with owner | skills/competencies | reusable statement | follows host | inherited to lessons | measured by assessment | attainment analytics | in-editor | with host |

**Rule:** ownership of a *definition* is always Authoring/Catalog/Certification; ownership of *learner interaction data* (attempts, submissions, responses, mastery) is always **Learning**. Competency/Skill/Path are Learning's framework (progression), consumed by Authoring for tagging.

---

## Content Composition

Content is composed by **reference, copy, or fork** — three explicit relationships with different sync semantics. A canonical object lives **once**; courses include it via one of these modes.

| Mode | Semantics | Ownership | Update propagation | Breaking change handling |
|------|-----------|-----------|--------------------|--------------------------|
| **Referenced (live)** | course points at canonical object **by id + pinned version** | canonical owner keeps control | referencing course pins a version; opting to "track latest" auto-updates on new **minor** versions only | a **major** version does NOT auto-propagate; referencing courses stay on the pinned version until an author re-pins (with re-review) |
| **Embedded** | referenced object rendered inline within a lesson | canonical owner | same as referenced | same |
| **Copied (snapshot)** | independent copy at copy time | copier owns the copy | **no** propagation (fully detached) | none (immutable divergence) |
| **Forked** | copy **with lineage** back to source | forker owns fork; lineage recorded | optional "pull upstream changes" (author-initiated merge) | conflicts surfaced via merge (see Collaboration) |
| **Bundle** | a named set of objects reused together (e.g., a module) | bundle owner | bundle version pins member versions | bundle re-versioned on member major change |

**Reusable objects:** shared lessons, reusable quizzes/assignments, reusable media (Media Platform asset ids), reusable resources, shared **question banks**, bundles. Cross-course reuse is always **by reference or fork**, never by hidden shared mutable state (this replaces the current `CrossCourseReferenceException` guard with an explicit, allowed reference model).

**Synchronization strategy:** every canonical object is **semver'd** (`major.minor.patch`): patch = fix (auto-propagates to references), minor = additive (auto-propagates to "track latest" references), major = breaking (never auto-propagates). References store `{objectId, versionConstraint}`.
**Update propagation:** a canonical publish emits `ContentVersionPublished(objectId, version, level)`; a projector fan-outs to references honoring their constraints; "track latest" references get a draft-refresh suggestion, not a silent live change to a published course.
**Breaking changes:** a major bump requires each referencing course's author to **re-pin + re-review + re-publish** (guarded by the review/approval workflow) — students on a published course never receive a breaking change silently. Copied content is immune (detached); forked content gets a merge prompt.

---

## Assessment Framework

**Split of ownership (invariant):** **Authoring owns the assessment *definition*** (questions, rules, rubric, pools); **Learning owns every *attempt*** (submission, grade, state, integrity). An assessment definition is versioned; a learner's attempt records the definition version it ran against (so re-versioning never corrupts historical grades).

| Assessment | Attempts | Passing rules | Randomization | Question pools | Rubrics | Time limits | Auto grading | Manual grading | Late submission | Retake policy | Version compat | Owner (def/attempt) |
|-----------|----------|---------------|---------------|----------------|---------|-------------|--------------|----------------|-----------------|---------------|----------------|---------------------|
| **Practice Quiz** | unlimited | none (formative) | optional shuffle | optional | — | none | yes | no | n/a | free | any published version | Authoring / Learning |
| **Graded Quiz** | configurable (n) | score ≥ threshold | shuffle + pool | yes | — | optional | yes | override allowed | policy (penalty/grace) | best/last/avg | attempt pins version | Authoring / Learning |
| **Assignment** | configurable | rubric ≥ threshold or manual | — | — | required | soft due | partial (checks) | required | penalty/grace/blocked | resubmit window | pins spec version | Authoring / Learning |
| **Programming Lab** | configurable | test suite pass % | seed variants | — | — | wall-clock | yes (test runner) | optional | policy | free/limited | pins lab version | Authoring / Learning |
| **Peer Review** | 1 submit + n reviews | reviews complete + score | reviewer assignment | — | required (peer rubric) | review window | aggregate | calibrated/override | policy | none | pins version | Authoring / Learning |
| **Essay** | configurable | rubric/manual | — | — | required | optional | AI-assist suggestion only | required | penalty | resubmit | pins version | Authoring / Learning |
| **Case Study** | configurable | rubric | scenario variants | — | required | optional | partial | required | policy | policy | pins version | Authoring / Learning |
| **Survey** | 1 | none | optional | — | — | none | n/a | n/a | n/a | none | any version | Authoring / Learning |
| **Exam** | limited (1–2) | score ≥ threshold | heavy (pool + shuffle) | yes (large pools) | — | strict | yes | override | blocked | strict lockout | attempt pins version | Authoring / Learning |
| **Certification Exam** | strict limit + cooldown | official cut score | proctoring + pool | yes | — | strict | yes | audited override | blocked | cooldown + fee | pinned, immutable record | Authoring (def) / Learning (attempt) / Certification (result→credential) |

**Interaction with Learning Context:** Authoring publishes `QuizPublished`/`AssignmentPublished`/`ExamPublished` → Learning exposes the **definition contract** (`AssessmentDefinition`) and hosts attempts; Learning emits `AttemptSubmitted`/`AttemptGraded`/`AssessmentPassed`; Certification consumes `AssessmentPassed` (for certification exams) to issue credentials. Manual grading happens in Learning's grading UI over the Authoring-defined rubric; auto-grading runs Learning-side against the Authoring definition. **Randomization/pools** are Authoring definitions; **the specific question set a learner saw** is recorded in Learning's attempt.

---

## Content Collaboration

Collaborative authoring over the **version model** (Phase 3): edits happen on working-draft versions; review freezes an immutable snapshot; publish promotes an approved snapshot. Roles are resolved via `TeachingAuthority` + Authoring permissions + org capability.

| Role | Permissions | Review scope | Approval authority | Conflict resolution | Locking | Concurrent editing | Merge | Commenting | Suggestion workflow |
|------|-------------|--------------|--------------------|--------------------|---------|--------------------|-------|-----------|--------------------|
| **Lead Author** | full edit + submit + (self-publish where allowed) | whole course | may approve co/assistant work (per policy) | decides on conflict | may lock a lesson | yes | resolves merges | resolve/close threads | accepts/rejects suggestions |
| **Co Author** | edit assigned lessons + submit | own lessons | none | optimistic token + prompt | section-scoped lock | yes | proposes merge | comment | creates suggestions |
| **Reviewer** | read draft + comment + request-changes/approve-content | assigned scope | content approve (not publish) | n/a (reads snapshot) | none | n/a | n/a | inline review comments | approves/rejects a snapshot |
| **Editor** | copy-edit (text/metadata) + suggest | text-level | none | suggestion-based | none | yes (suggest mode) | via suggestions | comment | suggestion mode (accepted by author) |
| **Approver** | approve for publish | whole submission | **publish approval gate** | n/a | none | n/a | n/a | approval note | approves the release |
| **Translator** | edit locale variants (LocalizedText) | per-locale | locale approve | per-locale token | locale-scoped lock | yes (per locale) | per-locale merge | locale comments | localizes suggestions |
| **Accessibility Reviewer** | annotate a11y + request-changes | a11y scope | a11y sign-off (quality gate) | n/a | none | n/a | n/a | a11y findings | flags a11y issues |
| **AI Reviewer** | run automated checks + post advisory | whole submission | none (advisory only) | n/a | none | n/a | n/a | automated comments | posts AI findings for human triage |

**Concurrency:** optimistic version tokens per lesson version; a stale save is rejected with a merge prompt (`ConflictDetected` from Phase 3). **Locking** is advisory + optional hard lock per lesson (lead author). **Merge:** suggestion-based (editors/AI/translators propose; author accepts) or explicit fork-merge for forked content. **Commenting:** threads bind to the immutable review snapshot, not the live draft. **Suggestion workflow:** all non-author changes (editor, translator, AI) enter as **suggestions** requiring author acceptance — nothing bypasses the author, and nothing bypasses the publish approval gate.

---

## Content Quality Framework

Quality is enforced by **gates** evaluated on a version before it can advance (submit/approve/publish). Each gate is **blocking** (must pass) or **warning** (advisory), with automatic and/or manual validation. Gates compose into `ContentValidation` (Phase 3); the publish command requires all **blocking** gates green.

| Gate | Owner | Blocking rules | Warnings | Automatic validation | Manual validation | Required approvals |
|------|-------|----------------|----------|----------------------|-------------------|--------------------|
| **Accessibility** | Authoring + Accessibility Reviewer | missing captions on video, no alt-text, contrast fail (for gov/edu templates) | minor a11y hints | WCAG automated checks on media/body | a11y reviewer sign-off (governed orgs) | a11y sign-off where org policy requires |
| **SEO** | Catalog (fields) / Marketing (render) | missing course-level title/description on publish | thin meta, missing OG image | metadata presence + length checks | editor review | none (warning-heavy) |
| **Readability** | Authoring | — (warning only) | grade-level too high, long paragraphs | readability scoring (AI-assist) | editor judgment | none |
| **Educational quality** | Authoring (rubric) | learning outcomes not declared for a graded course (governed) | weak objective-assessment alignment | outcome↔assessment coverage check | reviewer/instructional-designer | reviewer approval |
| **Technical validation** | Authoring | invalid curriculum (cycle/prereq), orphan lesson, cross-course reference not via allowed mode | deprecated block types | `CurriculumValidator` (exists) | — | none |
| **Media validation** | Media Platform | referenced asset not `Ready`, transcode failed, unsupported format | large file, low resolution | `MediaAssetReady`/`Failed` checks | — | none |
| **Broken links** | Authoring | external link 4xx/5xx on external_link lessons/resources | redirect chains | async link checker | — | none |
| **Localization completeness** | Translator | required locales missing for a multi-locale course (per policy) | partial translation | per-locale coverage check (LocalizedText) | translator sign-off | locale approval |
| **SCORM validation** | Authoring + Media Platform | invalid manifest, unsupported SCORM version, sandbox failure | large package | SCORM/xAPI validator | — | none |
| **AI safety review** | AI Reviewer (advisory) + human | AI-generated content not human-approved (hard block: no AI→learner without human publish) | potential bias/inaccuracy flags | AI safety/factuality scan on AI-sourced blocks | human triage of AI findings | human author acceptance (mandatory) |

**Mechanics:** gates run on `SubmitForReview` (report), on `Approve` (blocking gates must be green), and again pre-`Publish` (media/link freshness re-checked). A blocking failure produces a `ContentValidationFailed(gate, findings)` and prevents advancement; warnings are surfaced but non-blocking. **AI safety is a hard gate**: no AI-sourced content reaches learners without explicit human publish (reinforces Phase 3 AI Strategy). Gate configuration (which gates block vs warn) is driven by **org capability/template** (Phase 2) — e.g., government/enterprise templates make Accessibility + Localization blocking.

---

## Migration Strategy (no code in this phase)

Matches the backend chunking (Authoring already nests under `Contexts/Instructor/Authoring` in the dry-run 07A C8):
1. **Create the Instructor context** (`App\Contexts\Instructor`): introduce `InstructorProfile` (over Identity userId), `TeachingAssignment` (backfilled from Catalog `course_trainer`), and the `TeachingAuthority` port — additive tables, no change to existing ones.
2. **Relocate Authoring** to `App\Contexts\Instructor\Authoring` (its own gated chunk, same mechanism as 5E; add its Filament map line — no branches).
3. **Open Authoring writes to instructors:** Authoring policies consult `TeachingAuthority` instead of assuming admin — behavior-compatible for admins, newly-enabling for instructors.
4. **Introduce lesson/curriculum versioning** behind the existing endpoints (backfill current content as v1 published) — additive tables, no URL change.
5. **Introduce assessments** (Quiz/Assignment definitions in Authoring; attempts in Learning) — the missing capability from audit 06; additive.
6. **Formalize review/approval workflow** + AI suggestion pipeline behind `AIProvider` — additive.
7. **Publish contracts** (`TeachingAuthority`, `CourseDirectory`, `AssessmentDefinition`), wire seam events; **Deptrac** enforces Instructor⊥Authoring model isolation.

Each step: independently shippable; verified by `php artisan test` + `route:list` (URIs unchanged) + `/admin` resources visible; no schema change to existing tables, no URL change, no business-logic change.

---

## Acceptance Criteria

- **AC1 (two contexts):** `App\Contexts\Instructor` and `App\Contexts\Instructor\Authoring` exist; no Instructor file imports Lesson/Section/Assessment models and no Authoring file imports Instructor models (Deptrac green).
- **AC2 (ownership rule):** Instructor owns teachers/assignments/revenue/reviews; Authoring owns structure/lessons/versions/assessments — **Instructor owns zero lesson data**.
- **AC3 (authorization seam):** Authoring writes are gated by `TeachingAuthority` (an instructor can edit only assigned courses); admins retain full access.
- **AC4 (versioning):** each lesson has ≤1 published version and ≤1 working draft; editing a published lesson forks a draft; rollback and conflict detection work; students keep their published version.
- **AC5 (assessments):** quizzes/assignments are defined in Authoring and consumed by Learning for attempts; no attempt data lives in Authoring.
- **AC6 (publish readiness):** `CurriculumPublished` answers Catalog's `CoursePublishGuard`; Catalog (not Authoring) owns the course publish decision.
- **AC7 (media):** Authoring stores asset ids only; bytes/transcode/captions are Media Platform's; playback gated by Learning.
- **AC8 (AI human-in-loop):** no AI-generated content reaches learners without an explicit human publish; AI is behind a provider port; suggestions are auditable.
- **AC9 (review/approval):** submit→review→approve→publish is enforced where org capability requires an approval gate; review snapshots are immutable.
- **AC10 (events are DTOs):** all Instructor/Authoring events carry ids/primitives; Learning/Catalog/Analytics/Notifications consume without importing either context.
- **AC11 (Filament):** Instructor and Authoring resources are discovered via the data map (map lines added); no conditional discovery branches.
- **AC12 (no behavior/URL/DB change on migration):** `route:list` URIs identical, `php artisan test` green, `/admin` resources visible, existing schema untouched at every step.
- **AC13 (traceability):** every current Authoring artifact (Sections/Lessons/Media/Curriculum services/publish guard/Filament/events) maps to a target entity/command/event/read-model here; the new Instructor artifacts trace to the refactor-01 Instructor gap.

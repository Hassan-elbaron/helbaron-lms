# Learning Context — Dependency Audit (Cleanup Phase 1: Analysis Only)

> Chief Enterprise Architect. **Phase 1 is ANALYSIS ONLY** — no code, behavior, API, schema, port, or adapter was created or modified. This is the implementation guide for the first refactoring sprint. Every dependency, count, file, and line below comes from a direct `use`-import + usage scan of `apps/api/app/Contexts/Learning` on 2026-07-08, cross-referenced with `ARCHITECTURE_GAP_ANALYSIS.md`, `DEPENDENCY_CLEANUP_PLAN.md`, and `PROJECT_STATUS.md`. Claims requiring execution are marked **"Not verifiable from repository."**

---

## Executive Summary

The Learning context owns **execution of learning** (enrollment, progress, bookmarks/notes, continue-learning, media playback tokens) but does **not** own the content it executes against. It reaches directly into two other contexts' Eloquent models and one of their services, plus the Identity `User` model. A full scan finds **60 forbidden outbound import sites** (excluding the allowed `Shared` kernel):

| Group | Foreign types | Import sites |
|-------|---------------|:---:|
| **Curriculum** | `Authoring\Models\Lesson` (22), `Authoring\Models\Section` (6), `Authoring\Services\CurriculumTreeService` (1), `Catalog\Models\Course` (8), `Catalog\Enums\CourseStatus` (1) | **38** |
| **Media** | `Authoring\Models\LessonMedia` (5) | **5** |
| **Identity** | `Platform\Identity\Models\User` (16), `Platform\Identity\Enums\Role` (1) | **17** |
| Commerce / Analytics / Notifications / Certification | — none outbound — | **0** |
| **Total forbidden** | | **60** |

Learning has **zero outbound** dependencies on Commerce, Analytics, Notifications, or Certification. Those couplings are **inbound** (Commerce reads `Learning\Models\Enrollment`; Analytics + Certification subscribe to `Learning\Events\*`) and are therefore out of scope for *this* context's cleanup.

The critical mass is **Curriculum**: not just type-hints but **live Eloquent queries** against foreign tables (`Section::where('course_id', …)`, `Lesson::whereIn('section_id', …)`) inside four services and two controllers. These cannot be removed without a `CurriculumReadPort` and are **High Risk**. Media (5 sites) needs a `PlaybackPort`/`MediaPort`. Identity `User` (16 sites) is mechanical but needs an `IdentityContracts` user reference. A small set of dependencies (event DTOs carrying a `Lesson` only for a constructor type-hint; the dev-only `Role` enum) are **Quick Wins** removable with no port. **Recommended order: Quick Wins → Media (PlaybackPort) → Identity (IdentityContracts) → Curriculum (CurriculumReadPort) → Course/entitlement.**

---

## Current Learning Context

**Location:** `app/Contexts/Learning/` · Deptrac layer `Learning` (rule: may depend only on `[Shared, IdentityContracts]`).

### Responsibilities
Owns the **runtime of a learner against published content**: enrolling in a course, granting/revoking enrollment, recording per-lesson progress and completion, bookmarks and notes, "continue learning" / "my learning" projections, lesson access/prerequisite gating, and issuing signed media playback tokens. It does **not** own course/section/lesson definitions (Authoring/Catalog), media assets (Authoring), payment/entitlement (Commerce), users (Identity), certificates (Certification), or metrics (Analytics).

### Owned Models (5) — `Models/`
`Enrollment`, `LearningSession`, `LessonBookmark`, `LessonNote`, `LessonProgress`.

### Owned Services (4) — `Services/`
`ProgressService`, `LessonAccessService`, `LearningMediaService`, `ContinueLearningService`. Plus the media subsystem `Playback/` (`PlaybackTokenManager`, `Data/PlaybackToken`, 4 providers) and contract `Contracts/PlaybackTokenProvider`.

### Owned Actions (6) — `Actions/`
`Engagement/ToggleBookmarkAction`, `Engagement/UpsertLessonNoteAction`, `Enrollment/EnrollInCourseAction`, `Enrollment/GrantEnrollmentAction`, `Enrollment/UnenrollAction`, `Progress/RecordLessonProgressAction`.

### Owned Controllers (8) — `Http/Controllers/Api/V1/`
`BookmarkController`, `ContinueLearningController`, `EnrollmentController`, `LearnController`, `LessonPlayerController`, `LessonProgressController`, `MyLearningController`, `NoteController`. (Requests: `RecordProgressRequest`, `UpsertNoteRequest`.)

### Owned Events (4) — `Events/`
`CourseCompleted`, `LessonCompleted`, `LessonProgressRecorded`, `UserEnrolled`. (`CourseCompleted` is consumed by Certification + Analytics; `UserEnrolled` by Analytics — published language.)

### Owned Policies (1) — `Policies/`
`EnrollmentPolicy`.

### Owned Resources (6) — `Http/Resources/`
`ContinueLearningResource`, `LearnCourseResource`, `LearnLessonItemResource`, `LearnSectionResource`, `LearnerLessonResource`, `MyLearningItemResource`.

### Also owned
Enums (`EnrollmentSource`, `EnrollmentStatus`, `LearningPermission`, `LessonProgressStatus`), Exceptions (6), Listener `UpdateLearningSession`, `Providers/LearningServiceProvider`, `Filament/Resources/EnrollmentResource` (+2 Pages), Database (5 migrations, 3 factories, 1 seeder), `routes/learning.php`.

**Allowed dependencies (not forbidden):** the `Shared` kernel — `BaseAction`, `BaseService`, `BaseResource`, `BaseFormRequest`, `BasePolicy`, `BaseDomainException`, `BaseDomainServiceProvider`, `Support\ApiResponse`, `Support\CloudFrontUrlSigner`, `Support\Jwt`, `Traits\HasPublicId`. These conform to the Deptrac ruleset and require no change.

---

## Forbidden Dependencies

Every import outside Learning and outside `Shared`. `(dev)` = test/seed-only file. Line = the `use` statement line.

| # | File (`Contexts/Learning/…`) | Imported Class | Reason (why forbidden) | Current Usage | Risk | Priority |
|--:|------------------------------|----------------|------------------------|---------------|:----:|:--------:|
| 1 | `Services/ProgressService.php:5` | `Authoring\Models\Lesson` | Cross-context model | `Lesson::whereIn('section_id',…)->published()->pluck('id')`; type-hint `record(…, Lesson $lesson,…)` | High | P1 |
| 2 | `Services/ProgressService.php:6` | `Authoring\Models\Section` | Cross-context model | `Section::where('course_id',$courseId)->published()->pluck('id')`; `sectionPercentage(…, Section $section)` | High | P1 |
| 3 | `Services/LessonAccessService.php:5` | `Authoring\Models\Lesson` | Cross-context model | type-hints `courseIdForLesson/canAccess/assertAccess(Lesson $lesson)`; prerequisite checks | High | P1 |
| 4 | `Services/LessonAccessService.php:6` | `Authoring\Models\Section` | Cross-context model | `Section::whereKey($lesson->section_id)->value('course_id')` | High | P1 |
| 5 | `Services/LessonAccessService.php:7` | `Identity\Models\User` | Cross-context model | type-hints `activeEnrollment/canAccess/assertAccess(User $user)` | Medium | P2 |
| 6 | `Services/ContinueLearningService.php:5` | `Authoring\Models\Lesson` | Cross-context model | `Lesson::whereIn('section_id',…)`; `->first(fn(Lesson $lesson)…)` | High | P1 |
| 7 | `Services/ContinueLearningService.php:6` | `Authoring\Models\Section` | Cross-context model | `Section::where('course_id',$enrollment->course_id)` | High | P1 |
| 8 | `Services/ContinueLearningService.php:7` | `Identity\Models\User` | Cross-context model | `forUser(User $user)` | Medium | P2 |
| 9 | `Services/LearningMediaService.php:5` | `Authoring\Models\Lesson` | Cross-context model | `playbackFor(User $user, Lesson $lesson)`, `hasMedia(Lesson $lesson)` | High | P1 |
| 10 | `Services/LearningMediaService.php:6` | `Identity\Models\User` | Cross-context model | `playbackFor(User $user,…)` | Medium | P2 |
| 11 | `Http/Controllers/Api/V1/LearnController.php:5` | `Authoring\Services\CurriculumTreeService` | Cross-context **service** | injected `show(…, CurriculumTreeService $tree,…)`; builds curriculum tree | High | P1 |
| 12 | `Http/Controllers/Api/V1/LearnController.php:6` | `Catalog\Models\Course` | Cross-context model | `Course::where('public_id',$course)->first()`; `foreach($section->lessons …)` | High | P1 |
| 13 | `Http/Controllers/Api/V1/LessonPlayerController.php:5` | `Authoring\Models\Lesson` | Cross-context model | route-model-bound `show(…, Lesson $lesson,…)`; nav queries | High | P1 |
| 14 | `Http/Controllers/Api/V1/LessonPlayerController.php:6` | `Authoring\Models\Section` | Cross-context model | `Section::whereKey(...)->value('course_id')`; `Section::where('course_id',…)` | High | P1 |
| 15 | `Http/Controllers/Api/V1/LessonProgressController.php:5` | `Authoring\Models\Lesson` | Cross-context model | route-model binding + pass to `ProgressService` | High | P1 |
| 16 | `Http/Controllers/Api/V1/BookmarkController.php:5` | `Authoring\Models\Lesson` | Cross-context model | route-model binding → `ToggleBookmarkAction` | Medium | P1 |
| 17 | `Http/Controllers/Api/V1/NoteController.php:5` | `Authoring\Models\Lesson` | Cross-context model | route-model binding → `UpsertLessonNoteAction` | Medium | P1 |
| 18 | `Http/Controllers/Api/V1/EnrollmentController.php:5` | `Catalog\Models\Course` | Cross-context model | route-model binding → enroll/grant actions | High | P1 |
| 19 | `Actions/Progress/RecordLessonProgressAction.php:5` | `Authoring\Models\Lesson` | Cross-context model | `Lesson $lesson` param → `ProgressService::record` | High | P1 |
| 20 | `Actions/Progress/RecordLessonProgressAction.php:6` | `Identity\Models\User` | Cross-context model | `User $user` param | Medium | P2 |
| 21 | `Actions/Engagement/ToggleBookmarkAction.php:5` | `Authoring\Models\Lesson` | Cross-context model | `Lesson $lesson` param → `LessonBookmark` | Medium | P1 |
| 22 | `Actions/Engagement/ToggleBookmarkAction.php:6` | `Identity\Models\User` | Cross-context model | `User $user` param | Medium | P2 |
| 23 | `Actions/Engagement/UpsertLessonNoteAction.php:5` | `Authoring\Models\Lesson` | Cross-context model | `Lesson $lesson` param → `LessonNote` | Medium | P1 |
| 24 | `Actions/Engagement/UpsertLessonNoteAction.php:6` | `Identity\Models\User` | Cross-context model | `User $user` param | Medium | P2 |
| 25 | `Actions/Enrollment/EnrollInCourseAction.php:5` | `Catalog\Enums\CourseStatus` | Cross-context enum | enrollability guard (course status) | Medium | P1 |
| 26 | `Actions/Enrollment/EnrollInCourseAction.php:6` | `Catalog\Models\Course` | Cross-context model | `Course $course` param; status check | High | P1 |
| 27 | `Actions/Enrollment/EnrollInCourseAction.php:7` | `Identity\Models\User` | Cross-context model | `User $user` param | Medium | P2 |
| 28 | `Actions/Enrollment/GrantEnrollmentAction.php:5` | `Catalog\Models\Course` | Cross-context model | `Course $course` param | High | P1 |
| 29 | `Actions/Enrollment/GrantEnrollmentAction.php:6` | `Identity\Models\User` | Cross-context model | `User $user` param | Medium | P2 |
| 30 | `Models/Enrollment.php:5` | `Catalog\Models\Course` | Cross-context model | `belongsTo(Course::class)` relation | High | P3 |
| 31 | `Models/Enrollment.php:6` | `Identity\Models\User` | Cross-context model | `belongsTo(User::class)` relation | Medium | P3 |
| 32 | `Models/LearningSession.php:5` | `Authoring\Models\Lesson` | Cross-context model | relation / type-hint | High | P3 |
| 33 | `Models/LearningSession.php:6` | `Catalog\Models\Course` | Cross-context model | relation | High | P3 |
| 34 | `Models/LearningSession.php:7` | `Identity\Models\User` | Cross-context model | relation | Medium | P3 |
| 35 | `Models/LessonBookmark.php:5` | `Authoring\Models\Lesson` | Cross-context model | `belongsTo(Lesson)` relation | High | P3 |
| 36 | `Models/LessonBookmark.php:6` | `Identity\Models\User` | Cross-context model | `belongsTo(User)` relation | Medium | P3 |
| 37 | `Models/LessonNote.php:5` | `Authoring\Models\Lesson` | Cross-context model | `belongsTo(Lesson)` relation | High | P3 |
| 38 | `Models/LessonNote.php:6` | `Identity\Models\User` | Cross-context model | `belongsTo(User)` relation | Medium | P3 |
| 39 | `Models/LessonProgress.php:5` | `Authoring\Models\Lesson` | Cross-context model | `belongsTo(Lesson)` relation | High | P3 |
| 40 | `Policies/EnrollmentPolicy.php:5` | `Identity\Models\User` | Cross-context model | policy `User $user` args | Medium | P2 |
| 41 | `Events/LessonCompleted.php:5` | `Authoring\Models\Lesson` | Cross-context model | constructor type-hint only (payload) | Low | P0 |
| 42 | `Events/LessonProgressRecorded.php:5` | `Authoring\Models\Lesson` | Cross-context model | constructor type-hint only (payload) | Low | P0 |
| 43 | `Contracts/PlaybackTokenProvider.php:5` | `Authoring\Models\LessonMedia` | Cross-context model in a port signature | `issue(LessonMedia $media, int $ttl)` | High | P1 |
| 44 | `Playback/Providers/MuxPlaybackTokenProvider.php:5` | `Authoring\Models\LessonMedia` | Cross-context model | `issue(LessonMedia $media,…)` | Medium | P1 |
| 45 | `Playback/Providers/CloudFrontPlaybackTokenProvider.php:5` | `Authoring\Models\LessonMedia` | Cross-context model | `issue(LessonMedia $media,…)` | Medium | P1 |
| 46 | `Playback/Providers/S3PlaybackTokenProvider.php:5` | `Authoring\Models\LessonMedia` | Cross-context model | `issue(LessonMedia $media,…)` | Medium | P1 |
| 47 | `Playback/Providers/FakePlaybackTokenProvider.php:5` | `Authoring\Models\LessonMedia` | Cross-context model | `issue(LessonMedia $media,…)` | Low | P1 |
| 48 | `Http/Resources/ContinueLearningResource.php:5` | `Authoring\Models\Lesson` | Cross-context model | reads `$lesson` props for JSON | Medium | P2 |
| 49 | `Http/Resources/LearnLessonItemResource.php:5` | `Authoring\Models\Lesson` | Cross-context model | reads `$lesson` props for JSON | Medium | P2 |
| 50 | `Http/Resources/LearnSectionResource.php:5` | `Authoring\Models\Section` | Cross-context model | reads `$section` props for JSON | Medium | P2 |
| 51 | `Database/Factories/EnrollmentFactory.php:5` (dev) | `Catalog\Models\Course` | Cross-context model | factory `Course::factory()` | Low | P4 |
| 52 | `Database/Factories/EnrollmentFactory.php:6` (dev) | `Identity\Models\User` | Cross-context model | factory `User::factory()` | Low | P4 |
| 53 | `Database/Factories/LessonNoteFactory.php:5` (dev) | `Authoring\Models\Lesson` | Cross-context model | factory | Low | P4 |
| 54 | `Database/Factories/LessonNoteFactory.php:6` (dev) | `Identity\Models\User` | Cross-context model | factory | Low | P4 |
| 55 | `Database/Factories/LessonProgressFactory.php:5` (dev) | `Authoring\Models\Lesson` | Cross-context model | factory | Low | P4 |
| 56 | `Database/Seeders/LearningSeeder.php:5` (dev) | `Authoring\Models\Lesson` | Cross-context model | seed data | Low | P4 |
| 57 | `Database/Seeders/LearningSeeder.php:6` (dev) | `Authoring\Models\Section` | Cross-context model | seed data | Low | P4 |
| 58 | `Database/Seeders/LearningSeeder.php:7` (dev) | `Catalog\Models\Course` | Cross-context model | seed data | Low | P4 |
| 59 | `Database/Seeders/LearningSeeder.php:8` (dev) | `Identity\Enums\Role` | Cross-context enum | seed role assignment | Low | P0 |
| 60 | `Database/Seeders/LearningSeeder.php:9` (dev) | `Identity\Models\User` | Cross-context model | seed data | Low | P4 |

**Totals:** Lesson 22 · Section 6 · Course 8 · CourseStatus 1 · CurriculumTreeService 1 · LessonMedia 5 · User 16 · Role 1 = **60**. Production 51, dev-only 9.

---

## Dependency Groups

- **Curriculum (38 sites).** `Authoring\Models\Lesson` (22), `Authoring\Models\Section` (6), `Authoring\Services\CurriculumTreeService` (1), `Catalog\Models\Course` (8), `Catalog\Enums\CourseStatus` (1). The heart of the coupling — Learning reads curriculum structure and runs Eloquent queries against `lessons`/`sections`/`courses`. `CourseStatus` (enrollability) straddles Curriculum and entitlement.
- **Media (5 sites).** `Authoring\Models\LessonMedia` — the playback contract signature + 4 provider implementations + (indirectly) `LearningMediaService`. Media assets belong to a future Media Platform, not Authoring or Learning.
- **Commerce (0 outbound).** Learning imports **nothing** from Commerce. The seam is **inbound**: `Commerce` reads `Learning\Models\Enrollment` + `Learning\Enums\EnrollmentSource` (see `DEPENDENCY_CLEANUP_PLAN.md`). Cleaning that is Commerce's task (target Sprint 6 `EntitlementPort`), not Learning's. Learning's own entitlement touchpoint is `CourseStatus` (Curriculum group).
- **Identity (17 sites).** `Platform\Identity\Models\User` (16) across models, services, actions, policy, factories, seeder; `Platform\Identity\Enums\Role` (1, dev seeder). Mechanical but pervasive.
- **Analytics (0 outbound).** None. Inbound only — Analytics subscribes to `Learning\Events\{CourseCompleted,UserEnrolled}`.
- **Notifications (0 outbound).** None.
- **Certification (0 outbound).** None. Inbound only — Certification subscribes to `Learning\Events\CourseCompleted`.
- **Infrastructure (allowed, not forbidden).** `Shared` kernel: `ApiResponse`, `CloudFrontUrlSigner`, `Jwt`, `Base*` classes, `HasPublicId`. Conform to the Deptrac ruleset; **no action required**.

---

## Refactoring Candidates

Per-dependency target port + DTO (from `DEPENDENCY_CLEANUP_PLAN.md`). **No ports are created in Phase 1** — this is the target design only.

| Dependency (group) | Future Port | Expected DTO(s) | Owner Context | Migration Difficulty | Est. Files | Blocking Dependencies |
|--------------------|-------------|-----------------|---------------|:--------------------:|:----------:|-----------------------|
| `Lesson` (Curriculum, 22) | `CurriculumReadPort` | `LessonRef {id, sectionId, courseId, title, position, isPreview, isPublished, prerequisiteIds[]}` | Catalog/Authoring | **High** | ~18 prod | Port must exist + return parity data |
| `Section` (Curriculum, 6) | `CurriculumReadPort` | `SectionRef {id, courseId, title, position, publishedLessonIds[]}` | Catalog/Authoring | **High** | ~5 prod | same port |
| `CurriculumTreeService` (Curriculum, 1) | `CurriculumReadPort::tree(courseRef)` | `CurriculumTree {course, sections[], lessons[]}` | Authoring/Catalog | **High** | 1 (`LearnController`) | same port |
| `Course` (Curriculum, 8) | `CurriculumReadPort` (course ref) | `CourseRef {id, publicId, title, status}` | Catalog | **High** | ~6 prod | same port |
| `CourseStatus` (Curriculum/entitlement, 1) | `CurriculumReadPort` (`isEnrollable`) or `EntitlementPort` | boolean on `CourseRef` | Catalog / Commerce | Medium | 1 | port decision |
| `LessonMedia` (Media, 5) | `PlaybackPort` / `MediaPort` | `MediaAssetRef {id, provider, playbackId, policy}` | Media Platform | **Medium** | 6 (contract + 4 providers + `LearningMediaService`) | `PlaybackPort` + `MediaAssetRef` |
| `User` (Identity, 16) | `IdentityContracts` (`UserRef` / lookup) | `UserRef {id, publicId}` | Identity | Medium | ~13 prod | `IdentityContracts` user reference type |
| `Role` (Identity, 1, dev) | `IdentityContracts` enum re-export or fixture | — | Identity | Low | 1 (seeder) | none (dev) |

**Not verifiable from repository:** exact final file counts after refactor, and whether each port can return byte-identical data (parity) — confirm during implementation with an empty OpenAPI diff and green Learning tests.

---

## Quick Wins

Removable with minimal risk (no port required, no query rewrite):

1. **`Events/LessonCompleted.php` + `Events/LessonProgressRecorded.php`** (sites 41–42). `Lesson` is used only as a constructor type-hint in the event payload. Carry a scalar `lessonId` (int) / lightweight data instead of the model → makes the event a pure DTO (aligns with the A2-S05 "events are DTOs" rule). **No port, 2 files, Low risk.**
2. **`Database/Seeders/LearningSeeder.php:8` `Identity\Enums\Role`** (site 59). Dev-only; replace with a role string constant or an `IdentityContracts` re-export. **Trivial, dev-only.**
3. **`FakePlaybackTokenProvider`** (site 47) — the test-double provider; moves with the media contract but is the lowest-risk of the media set (no real infrastructure).

Everything else touches either an Eloquent query, a model relation, an injected service, or a response shape and is **not** a quick win.

---

## High Risk Refactoring

Migrate **only after the owning port exists** — these run foreign-table queries, define Eloquent relations, or inject a foreign service:

- **Services that query foreign tables** — `ProgressService`, `LessonAccessService`, `ContinueLearningService` (`Section::where(...)`, `Lesson::whereIn(...)`) and `LearningMediaService`. Require **`CurriculumReadPort`** (and `PlaybackPort` for media). *(sites 1–4, 6–7, 9)*
- **Controllers with route-model binding + queries** — `LearnController` (injects `CurriculumTreeService`, `Course::where`, `$section->lessons`), `LessonPlayerController` (nav queries), `LessonProgressController`, `EnrollmentController`. Require **`CurriculumReadPort`** + resolving route bindings by ref. *(sites 11–15, 18)*
- **Model relations** — `Enrollment`, `LearningSession`, `LessonBookmark`, `LessonNote`, `LessonProgress` (`belongsTo(Course/Lesson/User)`). Removing Eloquent relations affects eager-loading and every Resource that serializes them → **highest blast radius**; do **last**, after ports + read-model refs. *(sites 30–39)*
- **Media contract + providers** — `PlaybackTokenProvider` (port signature takes `LessonMedia`) + Mux/CloudFront/S3 providers. Require **`PlaybackPort`/`MediaPort`** with a `MediaAssetRef` DTO. *(sites 43–47)*
- **Course entitlement** — `EnrollInCourseAction` / `GrantEnrollmentAction` (course status guard). Require `CurriculumReadPort.isEnrollable` or `EntitlementPort`. *(sites 25–28)*

---

## Migration Order

Exact, file-by-file. **Phase 1 (this document) = analysis only; nothing below is executed yet.** Each step is expand-and-contract, parity-tested, one cluster per PR, Deptrac baseline shrinks, no API/DB change (per `DEPENDENCY_CLEANUP_PLAN.md` Safe Refactoring Rules).

**Step 0 — Quick Wins (no port):**
1. `Events/LessonCompleted.php` → payload `lessonId`.
2. `Events/LessonProgressRecorded.php` → payload `lessonId`.
3. `Database/Seeders/LearningSeeder.php` → drop `Identity\Enums\Role` import (role constant).

**Step 1 — Media (needs `PlaybackPort`/`MediaPort` + `MediaAssetRef`):**
4. `Contracts/PlaybackTokenProvider.php` (change signature to `MediaAssetRef`).
5. `Playback/Providers/FakePlaybackTokenProvider.php`
6. `Playback/Providers/MuxPlaybackTokenProvider.php`
7. `Playback/Providers/CloudFrontPlaybackTokenProvider.php`
8. `Playback/Providers/S3PlaybackTokenProvider.php`
9. `Services/LearningMediaService.php` (consume port; drop `Lesson` media reads).

**Step 2 — Identity (needs `IdentityContracts` `UserRef`):**
10. `Policies/EnrollmentPolicy.php`
11. `Actions/Progress/RecordLessonProgressAction.php`, `Actions/Engagement/ToggleBookmarkAction.php`, `Actions/Engagement/UpsertLessonNoteAction.php`, `Actions/Enrollment/EnrollInCourseAction.php`, `Actions/Enrollment/GrantEnrollmentAction.php`
12. `Services/LessonAccessService.php`, `Services/ContinueLearningService.php`, `Services/LearningMediaService.php`
13. Model `User` relations last (with Step 4).

**Step 3 — Curriculum (needs `CurriculumReadPort` + `CourseRef`/`SectionRef`/`LessonRef`):**
14. `Services/ProgressService.php` (replace `Section::`/`Lesson::` queries).
15. `Services/LessonAccessService.php` (course-for-lesson + prerequisites).
16. `Services/ContinueLearningService.php`.
17. `Http/Controllers/Api/V1/LearnController.php` (replace `CurriculumTreeService` + `Course::` with `CurriculumReadPort::tree`).
18. `Http/Controllers/Api/V1/LessonPlayerController.php` (nav via port).
19. `Http/Controllers/Api/V1/LessonProgressController.php`, `BookmarkController.php`, `NoteController.php`, `EnrollmentController.php` (route bindings by ref).
20. `Http/Resources/ContinueLearningResource.php`, `LearnLessonItemResource.php`, `LearnSectionResource.php` (serialize refs, not models).
21. `Actions/Enrollment/EnrollInCourseAction.php` + `GrantEnrollmentAction.php` (course status via port; drop `CourseStatus`).

**Step 4 — Model relations (highest blast radius, last):**
22. `Models/Enrollment.php`, `Models/LearningSession.php`, `Models/LessonBookmark.php`, `Models/LessonNote.php`, `Models/LessonProgress.php` (ID/ref-based; remove `belongsTo(Course/Lesson/User)` cross-context relations).

**Step 5 — Dev-only (after production is clean):**
23. `Database/Factories/EnrollmentFactory.php`, `LessonNoteFactory.php`, `LessonProgressFactory.php`, `Database/Seeders/LearningSeeder.php` (test fixtures / ref-based).

---

## Success Criteria

Measurable completion of the Learning cleanup:

1. **Deptrac:** the `Learning` layer resolves against `[Shared, IdentityContracts]` only; **zero `Learning` entries remain in `deptrac.baseline.yaml`**; a fresh `deptrac analyse` reports **0** Learning violations. *(Not verifiable from repository — requires running Deptrac.)*
2. **Import scan → 0:** `grep -rE 'use App\\Domains\\(Authoring|Catalog)\\' app/Contexts/Learning` and `grep -rE 'use App\\Platform\\Identity\\(Models|Enums)\\' app/Contexts/Learning` both return **0** (down from 43 + 17 = 60).
3. **No foreign queries:** zero `Section::`, `Lesson::`, `Course::` static/Eloquent calls inside `Contexts/Learning`; zero injected `CurriculumTreeService`; zero cross-context `belongsTo(Course|Lesson|User)`.
4. **PHPStan custom rules:** `NoCrossContextModelUsageRule` + `NoCrossContextEloquentAccessRule` report **0** findings for Learning (no new baseline entries). *(Not verifiable from repository.)*
5. **Behavior parity:** all existing Learning Pest tests pass unchanged; **OpenAPI diff for Learning endpoints is empty** (no API contract change); DB schema untouched. *(Not verifiable from repository — requires the test suite + spec diff.)*
6. **Ports return DTOs, not models:** Learning holds `CourseRef`/`SectionRef`/`LessonRef`/`MediaAssetRef`/`UserRef` — never a foreign Eloquent instance for business logic.
7. **Metric:** forbidden outbound import sites **60 → 0** (production 51 → 0, dev 9 → 0), tracked against the `DEPENDENCY_CLEANUP_PLAN.md` dashboard.

---

## Validation

- All 60 dependencies, their files, lines, and usage were obtained by direct scan of `apps/api/app/Contexts/Learning` (import lines + `grep` of `Lesson::`/`Section::`/`Course::`/`belongsTo`/`LessonMedia`/`CurriculumTreeService` usage) and reconciled with `ARCHITECTURE_GAP_ANALYSIS.md`, `DEPENDENCY_CLEANUP_PLAN.md`, and `PROJECT_STATUS.md`.
- Execution-dependent claims (Deptrac/PHPStan/test results, OpenAPI diffs, final file counts, port parity) are marked **"Not verifiable from repository."**
- **No code, behavior, API, database, port, or adapter was created or modified.** Only this file was created.

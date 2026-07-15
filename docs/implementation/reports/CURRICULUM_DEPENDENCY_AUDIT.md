# Curriculum Dependency Audit — Learning Context (Analysis Only)

> Chief Enterprise Architect. **ANALYSIS ONLY** — no code, port, adapter, API, or schema was created or modified. Scope: the **Curriculum** dependency group of the Learning context after the Media split-port refactor. Every dependency, count, field, and method below comes from a direct `use`-import + runtime-usage scan of `apps/api/app/Contexts/Learning` and the source models (`Authoring\Models\Lesson`/`Section`, `Authoring\Services\CurriculumTreeService`, `Catalog\Models\Course`, `Catalog\Enums\CourseStatus`), reconciled with `PROJECT_STATUS.md`, `ARCHITECTURE_GAP_ANALYSIS.md`, `DEPENDENCY_CLEANUP_PLAN.md`, `LEARNING_CONTEXT_DEPENDENCY_AUDIT.md`, and the two Media reports. Execution-dependent claims are marked **"Not verifiable from repository."**

---

## Executive Summary

Curriculum is the largest and deepest remaining coupling in Learning: **36 import sites** across services, controllers, actions, resources, models, and dev fixtures. Learning reaches directly into two contexts' Eloquent models — `Authoring\Models\Lesson` (20) and `Section` (6), `Catalog\Models\Course` (8), `Catalog\Enums\CourseStatus` (1) — plus one cross-context **service**, `Authoring\Services\CurriculumTreeService` (1). Unlike Media, the coupling is not just type-hints: it includes **live Eloquent queries** against foreign tables (`Section::where`, `Lesson::whereIn`), a **belongsToMany prerequisites** traversal, **route-model binding** of `Lesson`/`Course` in five controllers, and **cross-context `belongsTo` relations** on five Learning models (e.g. `Enrollment.course()`, `LessonProgress.lesson()`).

The good news from the evidence: Learning consumes a **small, stable slice** of each model — ids, `public_id`, `title`, `type`, `content`, `is_preview`, `position`, published-state, prerequisite ids, a `has_media` boolean, and course `slug`/`status`. That slice maps cleanly onto a `CurriculumReadPort` returning `CourseRef` / `SectionRef` / `LessonRef` / `CurriculumTree` DTOs.

The hard part is **structural**: route-model binding and Eloquent `belongsTo` relations are woven through the HTTP layer and models, and one resource traverses a relation (`$enrollment->course->public_id`). These are the highest-blast-radius items and must be migrated last, behind the port, with byte-parity tests. **There is no port-free quick win here** (contrast Media's event DTOs): even the smallest item — the `CourseStatus::Published` enrollability check — needs the port to supply an `isPublished` flag. **Recommendation: proceed, but as the biggest, highest-risk phase, split into category sub-PRs (Status → Resources → Services → Tree → route-binding Controllers → Actions → Model relations → dev), all behind one `CurriculumReadPort`, sequenced after Deptrac is installed and baselined.**

---

## Current Curriculum Coupling

| Target | Kind | Sites | Nature of use |
|--------|------|:-----:|---------------|
| `Authoring\Models\Lesson` | Eloquent model | 20 | route binding, `Lesson::where/whereIn` queries, `prerequisites()`, field reads, `belongsTo` relations, dev factories |
| `Authoring\Models\Section` | Eloquent model | 6 | `Section::where/whereKey` queries, `->lessons`, field reads, seeder |
| `Catalog\Models\Course` | Eloquent model | 8 | `Course::where('public_id')`, `$course->id/status/public_id/title/slug`, `belongsTo` relations |
| `Authoring\Services\CurriculumTreeService` | cross-context service | 1 | `forCourse($course, publishedOnly)` → eager-loaded section→lesson tree |
| `Catalog\Enums\CourseStatus` | enum | 1 | `$course->status !== CourseStatus::Published` enrollability guard |
| **Total** | | **36** | (production 32, dev-only 4) |

Learning forbidden outbound import sites overall: **52** (Curriculum 36 + Identity `User` 16). Clearing Curriculum takes Learning to **16** (Identity only).

---

## Dependency Inventory

`(dev)` = test/seed-only. Line-verified against the current tree.

| # | File (`Contexts/Learning/…`) | Imported Class | Why it exists | Runtime usage | Risk | Priority |
|--:|------------------------------|----------------|---------------|---------------|:----:|:--------:|
| 1 | `Services/ProgressService.php` | `Authoring\Models\Section` | completion math | `Section::where('course_id',…)->published()->pluck('id')`; `$section->id` | High | P1 |
| 2 | `Services/ProgressService.php` | `Authoring\Models\Lesson` | completion math | `Lesson::whereIn('section_id',…)->published()->pluck('id')`; `record(…, Lesson $lesson,…)` | High | P1 |
| 3 | `Services/LessonAccessService.php` | `Authoring\Models\Section` | course-of-lesson | `Section::whereKey($lesson->section_id)->value('course_id')` | High | P1 |
| 4 | `Services/LessonAccessService.php` | `Authoring\Models\Lesson` | access + prereqs | `$lesson->section_id`, `$lesson->is_preview`, `$lesson->prerequisites()->pluck('lessons.id')` | High | P1 |
| 5 | `Services/ContinueLearningService.php` | `Authoring\Models\Section` | next-lesson | `Section::where('course_id',…)->published()->pluck` | High | P1 |
| 6 | `Services/ContinueLearningService.php` | `Authoring\Models\Lesson` | next-lesson | `Lesson::whereIn('section_id',…)->published()`; `->first(fn(Lesson)…)` | High | P1 |
| 7 | `Services/LearningMediaService.php` | `Authoring\Models\Lesson` | media entry | type-hints `playbackFor(User,Lesson)`, `hasMedia(Lesson)`; uses `$lesson->id` | Med | P2 |
| 8 | `Http/Controllers/Api/V1/LearnController.php` | `Authoring\Services\CurriculumTreeService` | curriculum tree | injected; `$tree->forCourse($courseModel, publishedOnly:true)`; `foreach($section->lessons …)` | High | P1 |
| 9 | `Http/Controllers/Api/V1/LearnController.php` | `Catalog\Models\Course` | course lookup | `Course::where('public_id',$course)->first()`; `$courseModel->id` | High | P1 |
| 10 | `Http/Controllers/Api/V1/LessonPlayerController.php` | `Authoring\Models\Lesson` | player + nav | route-bound `Lesson $lesson`; `Lesson::whereIn(...)` nav; `$lesson->id` | High | P1 |
| 11 | `Http/Controllers/Api/V1/LessonPlayerController.php` | `Authoring\Models\Section` | nav | `Section::whereKey`, `Section::where('course_id',…)` | High | P1 |
| 12 | `Http/Controllers/Api/V1/LessonProgressController.php` | `Authoring\Models\Lesson` | record progress | route-bound `Lesson` → `ProgressService`/`RecordLessonProgressAction` | High | P1 |
| 13 | `Http/Controllers/Api/V1/BookmarkController.php` | `Authoring\Models\Lesson` | toggle bookmark | route-bound `Lesson` → `ToggleBookmarkAction` | Med | P1 |
| 14 | `Http/Controllers/Api/V1/NoteController.php` | `Authoring\Models\Lesson` | upsert note | route-bound `Lesson` → `UpsertLessonNoteAction` | Med | P1 |
| 15 | `Http/Controllers/Api/V1/EnrollmentController.php` | `Catalog\Models\Course` | enroll/grant | route-bound `Course` → enroll actions | High | P1 |
| 16 | `Actions/Enrollment/EnrollInCourseAction.php` | `Catalog\Enums\CourseStatus` | enrollability | `$course->status !== CourseStatus::Published` | Med | P1 |
| 17 | `Actions/Enrollment/EnrollInCourseAction.php` | `Catalog\Models\Course` | enrollability | `Course $course` param; passes to grant | High | P1 |
| 18 | `Actions/Enrollment/GrantEnrollmentAction.php` | `Catalog\Models\Course` | create enrollment | `$course->id` → `Enrollment` | High | P1 |
| 19 | `Actions/Progress/RecordLessonProgressAction.php` | `Authoring\Models\Lesson` | record progress | `Lesson $lesson` param; `$lesson->id` for events | High | P1 |
| 20 | `Actions/Engagement/ToggleBookmarkAction.php` | `Authoring\Models\Lesson` | bookmark | `$lesson->id` → `LessonBookmark` | Med | P1 |
| 21 | `Actions/Engagement/UpsertLessonNoteAction.php` | `Authoring\Models\Lesson` | note | `$lesson->id` → `LessonNote` | Med | P1 |
| 22 | `Http/Resources/LearnCourseResource.php`* | (`Course` via array) | course view | `$course->public_id/title/slug` (passed as model in array) | Med | P2 |
| 23 | `Http/Resources/LearnSectionResource.php` | `Authoring\Models\Section` | tree node | `$section->public_id/title/lessons` | Med | P2 |
| 24 | `Http/Resources/LearnLessonItemResource.php` | `Authoring\Models\Lesson` | tree leaf | `public_id/title/type->value/is_preview/id`; `relationLoaded('media') ? media!==null` | Med | P2 |
| 25 | `Http/Resources/ContinueLearningResource.php` | `Authoring\Models\Lesson` | next-lesson view | `$next->public_id/title/type->value`; also `$enrollment->course->public_id/title` | Med | P2 |
| 26 | `Models/Enrollment.php` | `Catalog\Models\Course` | ORM relation | `belongsTo(Course::class)` | High | P3 |
| 27 | `Models/LearningSession.php` | `Authoring\Models\Lesson` | ORM relation | `belongsTo(Lesson::class)` | High | P3 |
| 28 | `Models/LearningSession.php` | `Catalog\Models\Course` | ORM relation | `belongsTo(Course::class)` | High | P3 |
| 29 | `Models/LessonBookmark.php` | `Authoring\Models\Lesson` | ORM relation | `belongsTo(Lesson::class)` | High | P3 |
| 30 | `Models/LessonNote.php` | `Authoring\Models\Lesson` | ORM relation | `belongsTo(Lesson::class)` | High | P3 |
| 31 | `Models/LessonProgress.php` | `Authoring\Models\Lesson` | ORM relation | `belongsTo(Lesson::class)` | High | P3 |
| 32 | `Database/Factories/EnrollmentFactory.php` (dev) | `Catalog\Models\Course` | test data | `Course::factory()` | Low | P4 |
| 33 | `Database/Factories/LessonNoteFactory.php` (dev) | `Authoring\Models\Lesson` | test data | `Lesson::factory()` | Low | P4 |
| 34 | `Database/Factories/LessonProgressFactory.php` (dev) | `Authoring\Models\Lesson` | test data | `Lesson::factory()` | Low | P4 |
| 35 | `Database/Seeders/LearningSeeder.php` (dev) | `Authoring\Models\Lesson` + `Section` + `Catalog\Models\Course` | seed | seed enroll + progress | Low | P4 |

\* `LearnCourseResource` does not `use` a Curriculum class (it reads a `Course` passed inside an array, so it is not one of the 36 `use` sites), but it **reads Course fields at runtime** (`public_id/title/slug`) and is listed for completeness of the runtime picture. The 36 counted sites are the `use`-imports; rows 22 and 25's `$enrollment->course->…` traversal is enabled by the model relations in rows 26–31.

**Import-site totals:** Lesson 20 · Section 6 · Course 8 · CourseStatus 1 · CurriculumTreeService 1 = **36** (production 32, dev 4).

---

## Coupling Categories

- **Course (8 sites + 1 status).** `Catalog\Models\Course` in `EnrollInCourseAction`, `GrantEnrollmentAction`, `EnrollmentController`, `LearnController`, `Enrollment` (belongsTo), `LearningSession` (belongsTo), `EnrollmentFactory` (dev), `LearningSeeder` (dev). Reads: `id` (FK), `public_id`, `title`, `slug`, `status`. Lookup: `Course::where('public_id')`.
- **Section (6).** `Authoring\Models\Section` in `ProgressService`, `LessonAccessService`, `ContinueLearningService`, `LessonPlayerController`, `LearnSectionResource`, `LearningSeeder` (dev). Reads: `id`, `public_id`, `title`, `course_id`, `position`, `->lessons`, `published()` scope.
- **Lesson (20).** `Authoring\Models\Lesson` across 4 services, 4 controllers, 3 actions, 2 resources, 3 models, 3 dev files. Reads: `id`, `public_id`, `section_id`, `title`, `type`(enum→value), `content`, `is_preview`, `position`, `published()`, `prerequisites()`, `media` (has_media bool). Route-bound in 4 controllers; `belongsTo` in 3 models.
- **Curriculum Tree (1).** `Authoring\Services\CurriculumTreeService::forCourse($course, publishedOnly)` in `LearnController` — returns eager-loaded `Section`→`lessons`(→`media`) collection ordered by `position`.
- **Status (1).** `Catalog\Enums\CourseStatus::Published` in `EnrollInCourseAction` — enrollability guard.
- **Other (structural).** The five `belongsTo` relations (rows 26–31) are a distinct **relational** coupling class (not data reads): they enable eager-loading and resource traversal (`$enrollment->course->public_id`) — the highest-blast-radius category.

---

## Candidate Read Models

**Data Learning actually consumes (required):**

- **Course:** internal `id` (FK for enrollment/session/queries), `public_id`, `title`, `slug`, and an **enrollable/published** flag (from `status`). Lookup by `public_id`.
- **Section:** `id`, `public_id`, `title`, `course_id`, `position`, published flag.
- **Lesson:** `id`, `public_id`, `section_id`, derived `course_id`, `title`, `type` (string), `content` (array), `is_preview`, `position`, published flag, **prerequisite lesson ids** (from `prerequisites()`), **has_media** (boolean).
- **Curriculum tree:** ordered sections, each with ordered **published** lessons (for `/learn`).
- **Derived reads:** `courseIdForLesson`, `publishedLessonIds(course)` and `publishedLessonIds(section)` (progress %), ordered published lessons for a course (player nav + continue-learning).

**Incidental data (NOT required as coupling):**

- The full Eloquent models with all columns, `SoftDeletes`, raw `publish_state`, timestamps — Learning uses a subset.
- The **`media` relation itself** — `LearnLessonItemResource` only needs a `has_media` **boolean**; the actual asset already flows through the Media `MediaAssetPort`. The relation is incidental.
- `CurriculumTreeService` returning **models** with `lessons`+`media` eager-loaded — incidental; Learning needs a DTO tree, not Eloquent.
- Factory/seeder model construction — dev-only; replaceable with fixtures/ids.

---

## Future CurriculumReadPort

Learning-facing contract (in Shared), **design only — do not implement**. Methods reflect the exact reads above; kept minimal by embedding prerequisites/`course_id`/`has_media` in `LessonRef`.

```
interface CurriculumReadPort
{
    // Course lookups / entitlement (Status group)
    public function findCourseByPublicId(string $publicId): ?CourseRef;
    public function courseRef(int $courseId): ?CourseRef;
    public function isCourseEnrollable(int $courseId): bool;          // replaces CourseStatus::Published

    // Lesson / section resolution (route-binding + services)
    public function lessonRef(int $lessonId): ?LessonRef;             // incl. sectionId, courseId, prereqIds, hasMedia
    public function courseIdForLesson(int $lessonId): ?int;

    // Published-set queries (progress math)
    public function publishedLessonIdsForCourse(int $courseId): array;   // int[]
    public function publishedLessonIdsForSection(int $sectionId): array; // int[]
    public function orderedPublishedLessonRefs(int $courseId): array;    // LessonRef[] (player nav, continue-learning)

    // Full tree (LearnController)
    public function curriculumTree(int $courseId, bool $publishedOnly): CurriculumTree;
}
```

Method count is intentionally minimal (9); `courseIdForLesson` and `publishedLessonIds*` could later fold into `lessonRef`/`curriculumTree` if callers are consolidated.

**Ownership design note (surface, not decided here):** the port spans **Authoring** (sections/lessons) and **Catalog** (course). Implementing it wholly in Authoring would deepen the existing `Authoring→Catalog\Models\Course` coupling (already baselined, ×14). The cleaner realization composes a **Catalog-owned** course-ref provider (`findCourseByPublicId`/`courseRef`/`isCourseEnrollable`) with an **Authoring-owned** section/lesson/tree provider behind the single Shared `CurriculumReadPort`. Final split is an implementation decision for the build phase.

---

## Required DTOs

Immutable value objects (`final readonly`), fields only — **no implementation**:

- **CourseRef** — `id:int`, `publicId:string`, `title:string`, `slug:string`, `isPublished:bool`.
- **SectionRef** — `id:int`, `publicId:string`, `courseId:int`, `title:string`, `position:int`, `isPublished:bool`.
- **LessonRef** — `id:int`, `publicId:string`, `sectionId:int`, `courseId:int`, `title:string`, `type:string`, `content:?array`, `isPreview:bool`, `position:int`, `isPublished:bool`, `hasMedia:bool`, `prerequisiteLessonIds:int[]`.
- **CurriculumTree** — `course:CourseRef`, `sections:SectionNode[]`.
- **SectionNode** — `section:SectionRef`, `lessons:LessonRef[]`.

(No storage identifiers beyond ids; DTOs are server-side and drive the same JSON the resources emit today.)

---

## Refactoring Order

File-by-file, **lowest risk first**. All expand-and-contract, one category per PR, behind `CurriculumReadPort`; parity-tested; Deptrac baseline shrinks. Nothing executed in this phase.

1. **Introduce contracts + DTOs** (Shared): `CurriculumReadPort`, `CourseRef`, `SectionRef`, `LessonRef`, `CurriculumTree`, `SectionNode`. Adapter(s) as parity wrappers over current reads. *(No behavior.)*
2. **Status:** `Actions/Enrollment/EnrollInCourseAction.php` — `$course->status !== CourseStatus::Published` → `isCourseEnrollable(courseId)`. *(Smallest logic change.)*
3. **Resources (display-only):** `LearnLessonItemResource`, `LearnSectionResource`, `LearnCourseResource`, `ContinueLearningResource` — read `LessonRef`/`SectionRef`/`CourseRef` fields instead of models. *(Depends on upstream passing refs.)*
4. **Services (self-contained queries):** `ProgressService`, `LessonAccessService`, `ContinueLearningService` — replace `Section::`/`Lesson::` queries + `prerequisites()` with port methods. `LearningMediaService` type-hint `int $lessonId` instead of `Lesson`.
5. **Tree:** `LearnController` — replace `CurriculumTreeService` + `Course::where` with `curriculumTree()` + `findCourseByPublicId()`.
6. **Route-binding controllers:** `BookmarkController`, `NoteController`, `LessonProgressController`, `LessonPlayerController`, `EnrollmentController` — resolve `LessonRef`/`CourseRef` from the route param via the port instead of implicit model binding.
7. **Actions:** `ToggleBookmarkAction`, `UpsertLessonNoteAction`, `RecordLessonProgressAction`, `GrantEnrollmentAction`, `EnrollInCourseAction` — accept ids/refs.
8. **Model relations (highest blast radius, last):** `Enrollment`, `LearningSession`, `LessonBookmark`, `LessonNote`, `LessonProgress` — drop cross-context `belongsTo(Course|Lesson)`; keep FK columns; update any resource traversal (`$enrollment->course->…`) to use `CourseRef`.
9. **Dev fixtures:** `EnrollmentFactory`, `LessonNoteFactory`, `LessonProgressFactory`, `LearningSeeder` — use ids/fixtures.

---

## High Risk Areas

- **Route-model binding (rows 10, 12–15, 17–18).** Five controllers rely on Laravel implicit binding of `Lesson`/`Course` from the route; removing the model changes route resolution. Must resolve a ref via the port and preserve 404 behavior. Highest-touch HTTP change.
- **Cross-context `belongsTo` relations (rows 26–31) + relation traversal.** `ContinueLearningResource` reads `$enrollment->course->public_id/title`; removing `Enrollment.course()` breaks that traversal and eager-loading. Must replace with `CourseRef` lookups while keeping identical JSON. Deepest coupling; do last.
- **`CurriculumTreeService` → DTO tree (row 8).** `LearnController` iterates `$section->lessons` and computes `accessible_ids`/`completed_ids`; converting to `CurriculumTree` while preserving the exact flags and ordering is intricate.
- **`prerequisites()` self-relation (row 4).** `LessonAccessService` reads `belongsToMany` prerequisite ids; the port must expose `prerequisiteLessonIds` with identical semantics.
- **`content` payload fidelity.** `content` is a JSON/array cast surfaced verbatim in the player response; `LessonRef.content` must round-trip byte-identically.

---

## Quick Wins

**There is no port-free quick win in Curriculum** (unlike Media's event DTOs). Every removal needs `CurriculumReadPort` to exist first — even the `CourseStatus` check needs an `isPublished`/`isCourseEnrollable` flag from the port. Once the port exists, the **lowest-risk first steps** are:

1. **Status flag** — `EnrollInCourseAction` `CourseStatus::Published` → `isCourseEnrollable()` (single condition).
2. **Display-only resources** — `LearnLessonItemResource`, `LearnSectionResource`, `LearnCourseResource`, `ContinueLearningResource` read ref fields (no queries, no route binding).
3. **Dev fixtures** — factories/seeder (test-only, isolatable) — but defer to the end to avoid churn.

---

## Success Criteria

1. `grep -rE 'use App\\Domains\\Authoring\\(Models\\(Lesson|Section)|Services\\CurriculumTreeService)' app/Contexts/Learning` = **0**; `grep -rE 'use App\\Domains\\Catalog\\(Models\\Course|Enums\\CourseStatus)' app/Contexts/Learning` = **0** (down from 36).
2. **No foreign queries/relations in Learning:** zero `Section::`, `Lesson::`, `Course::`; zero cross-context `belongsTo(Course|Lesson)`; zero `$lesson->prerequisites()`.
3. Learning depends only on `Shared` (incl. `CurriculumReadPort` + DTOs) and `IdentityContracts`. Deptrac: 0 Curriculum violations for Learning; baseline shrinks by 36. *(Not verifiable from repository.)*
4. PHPStan `NoCrossContextModelUsageRule` / `NoCrossContextEloquentAccessRule` report **0** for Learning curriculum files. *(Not verifiable from repository.)*
5. **Behavior parity:** OpenAPI diff empty for `/learn`, `/lessons/{id}`, progress/bookmark/note, enroll, and continue-learning endpoints; all Learning Pest tests green; DB schema untouched. *(Not verifiable from repository.)*
6. **Metric:** Learning forbidden outbound import sites **52 → 16** (Curriculum cleared; Identity `User` 16 remains for the next phase).

---

## Final Recommendation

**Proceed — but treat Curriculum as the largest and highest-risk Learning phase and execute it as a sequence of small, category-scoped, parity-tested PRs behind one `CurriculumReadPort`, not a single change.** The consumed data slice is small and stable, so the `CourseRef`/`SectionRef`/`LessonRef`/`CurriculumTree` DTOs are well-defined; the risk is entirely structural — route-model binding and Eloquent `belongsTo` relations. Order the work Status → Resources → Services → Tree/LearnController → route-binding Controllers → Actions → **Model relations last** (highest blast radius, with byte-parity tests on relation-traversing resources). Realize the port as a Catalog-owned course provider composed with an Authoring-owned section/lesson/tree provider to avoid deepening `Authoring→Catalog`. Keep the port path flag-guarded for instant revert, and sequence after Deptrac is installed and the baseline seeded (fitness-readiness precondition). Clearing Curriculum removes 36 of the remaining 52 forbidden sites and leaves only the Identity (`User`) phase.

---

## Validation

- All 36 dependencies, their files, imported classes, and runtime usage were obtained by direct scan of `apps/api/app/Contexts/Learning` (imports + `$lesson->`/`$section->`/`$course->` field access, `Lesson::`/`Section::`/`Course::` queries, `belongsTo`, `prerequisites()`) and by reading the source models (`Lesson`, `Section`, `Course`), `CurriculumTreeService`, `CourseStatus`, and the affected services/controllers/resources/actions/models, reconciled with the referenced reports.
- Execution-dependent outcomes (Deptrac/PHPStan/test results, OpenAPI diffs, port parity) are marked **"Not verifiable from repository."**
- **No code, port, adapter, API, or schema was created or modified.** Only this file was created.

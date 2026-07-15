# Curriculum Refactor — Phase 4 Report (HTTP Layer)

> Chief Enterprise Architect. Executes Phase 4 of `CURRICULUM_FINAL_MIGRATION_PLAN.md`: migrate the HTTP layer — controllers stop route-model-binding and stop importing `Lesson`/`Section`/`Course`/`CurriculumTreeService`/`CourseStatus`, resolving ids/public-ids through `CurriculumReadPort` and calling only the Phase-3B id/ref service/action methods with DTO-fed resources. No database, API contract, or business behavior changed; the model-based service/action methods were kept. Runtime gates could not run here (no PHP/Composer) — marked **"Not verifiable from repository."**

---

## Executive Summary

The six coupled controllers were rewritten to be **model-free**: each receives the route `{lesson}`/`{course}` as a `string` public-id (route-model binding removed), resolves it via `CurriculumReadPort` (`findCourseByPublicId` / `findLessonByPublicId`), and calls the Phase-3B id/ref methods (`assertAccessByLessonId`, `canAccessByLessonId`, `hasMediaForLesson`/`playbackForLesson`, `executeById`, `curriculumTree`, `orderedPublishedLessonRefs`), feeding DTOs into the (already DTO-aware) resources. The two remaining Learning controllers (`ContinueLearningController`, `MyLearningController`) had no forbidden imports and were left unchanged. **Controller Curriculum imports: 8 → 0.** Learning Curriculum `use`-import sites overall: **31 → 22** (the rest are the retained service/action model type-hints, the model `belongsTo` relations, and dev fixtures — all for the Final Phase).

Two small **additive** supporting changes were required — both plan gaps discovered here, neither in Phase 4's frozen list (services/actions/resources/models/DB): (1) `findLessonByPublicId(string): ?LessonRef` on the port/adapter, because lessons are addressed by **public_id** (`HasPublicId::getRouteKeyName() === 'public_id'`) and the plan only defined a course-by-public-id resolver; (2) a `content` field on `LessonRef` (populated by the detail read methods), because the lesson-player resource renders `content` and `LessonRef` did not carry it.

---

## Controllers Updated

| Controller | Route | Change |
|------------|-------|--------|
| `LearnController` | `GET courses/{course}/learn` | `string $course` → `findCourseByPublicId` + `curriculumTree(publishedOnly:true)`; accessibility via `canAccessByLessonId`; `LearnCourseResource` fed the `CourseRef` + tree DTOs. Dropped `CurriculumTreeService`, `Course`. |
| `LessonPlayerController` | `GET lessons/{lesson}` | `string $lesson` → `findLessonByPublicId`; `assertAccessByLessonId`; `hasMediaForLesson`/`playbackForLesson`; nav via `orderedPublishedLessonRefs`; `LearnerLessonResource` fed the `LessonRef` + `content`. Dropped `Lesson`, `Section`; own-model reads (progress/bookmark/note) by id. |
| `LessonProgressController` | `POST lessons/{lesson}/progress` | `string $lesson` → `findLessonByPublicId` + `RecordLessonProgressAction::executeById`. Dropped `Lesson`. |
| `BookmarkController` | `POST lessons/{lesson}/bookmark` | `string $lesson` → `findLessonByPublicId` + `assertAccessByLessonId` + `ToggleBookmarkAction::executeById`. Dropped `Lesson`. |
| `NoteController` | `POST lessons/{lesson}/notes` | `string $lesson` → `findLessonByPublicId` + `assertAccessByLessonId` + `UpsertLessonNoteAction::executeById`. Dropped `Lesson`. |
| `EnrollmentController` | `POST courses/{course}/enroll` | `string $course` → `findCourseByPublicId` + `EnrollInCourseAction::executeById`. Dropped `Course`. |
| `ContinueLearningController` | `GET continue-learning` | **Unchanged** — no forbidden import (pass-through of `ContinueLearningService::forUser`). |
| `MyLearningController` | `GET my-learning` | **Unchanged** — no forbidden import (own `Enrollment` + `MyLearningItemResource`). |

Supporting (additive) files: `CurriculumReadPort` (+`findLessonByPublicId`), `CurriculumReadAdapter` (+`findLessonByPublicId`, `buildLessonRef` now populates `content` under its detail flag), `LessonRef` (+`content`, defaulted).

---

## Dependencies Removed

Controller Curriculum imports removed (8 → 0):
- `LearnController`: `Authoring\Services\CurriculumTreeService`, `Catalog\Models\Course`.
- `LessonPlayerController`: `Authoring\Models\Lesson`, `Authoring\Models\Section`.
- `LessonProgressController`, `BookmarkController`, `NoteController`: `Authoring\Models\Lesson`.
- `EnrollmentController`: `Catalog\Models\Course`.

Verified: `grep 'use App\Domains\(Authoring|Catalog)' app/Contexts/Learning/Http/Controllers` = **0**.

---

## Route Binding Changes

Implicit route-model binding for `{lesson}` (→ `Lesson`) and `{course}` (→ `Course`) is **removed** by changing the controller signatures from `Lesson $lesson` / `Course $course` to `string $lesson` / `string $course`. Laravel now passes the raw route segment (the public-id), which the controller resolves via `CurriculumReadPort`. The **route files were not modified** (binding is signature-driven; the `{lesson}`/`{course}` param names are unchanged). A missing lesson/course now yields a `NotFoundHttpException` (404), matching the binding's prior `ModelNotFoundException → 404` (the router converts both to the same 404).

---

## Remaining Coupling

Learning Curriculum `use`-import sites: **22** (from 31):
- **Actions (5):** retained model `execute(Lesson|Course)` type-hints (delegating to `executeById`).
- **Services (5):** retained model method type-hints (`Lesson`/`Section`) delegating to id methods.
- **Models (6):** the five `belongsTo(Course|Lesson)` relations.
- **Dev fixtures (6):** factories + seeder.

All are for the **Final Phase**: remove the now-unused model-based service/action methods, sever the `belongsTo` relations (keeping FK columns), migrate fixtures, retire the temporary Authoring→Catalog adapter (Catalog/Authoring split), and shrink the Deptrac baseline. The controllers themselves are now fully decoupled.

---

## Behavior Verification

Byte-identical by construction (each new controller reproduces the prior output):
- **LearnController:** `curriculumTree(publishedOnly:true)` reproduces `CurriculumTreeService::forCourse` ordering; accessibility uses `canAccessByLessonId` (≡ `canAccess` by Phase-3B delegation) over the same published lesson set; `LearnCourseResource` renders the same course/enrollment/sections JSON.
- **LessonPlayerController:** `findLessonByPublicId` yields a `LessonRef` whose fields equal the prior model reads (incl. `content`); `assertAccessByLessonId` throws the same 403s; playback identical; navigation `prev/next` public-ids identical (`orderedPublishedLessonRefs` reproduces the prior nav ordering); own-model progress/bookmark/note by id.
- **Progress/Bookmark/Note/Enrollment:** same access checks, same action effects/events, same response payloads.
- **404s:** preserved (missing lesson/course → 404).

**Runtime confirmation: Not verifiable from repository** (no PHP). The existing feature tests — `MediaSafetyTest` (hits `/api/v1/lessons/{public_id}`), `ProgressCompletionTest`, `PrerequisiteLockTest`, `/learn`, enroll, continue-learning, my-learning — exercise these exact endpoints and are the authoritative check.

---

## Compatibility

- **API:** unchanged — same routes, verbs, payloads, and (for present resources) byte-identical JSON; 404 status preserved.
- **Database:** untouched.
- **Business behavior:** identical (access decisions, progress, enrollment, events, navigation).
- **Services / Actions / Resources / Models / Routes:** not modified this phase (git shows no tracked modification); the retained model-based service/action methods remain for backward-compat.
- **DI:** controllers method-inject `CurriculumReadPort` (bound in `AuthoringServiceProvider`) alongside the existing services/actions; `{lesson}`/`{course}` bind by route-param name, the rest resolve from the container.

---

## Risk Assessment

- **Byte-identical parity (medium, unverified).** New controllers are faithful reconstructions; resources/services/actions are the already-tested Phase-3B code. Primary risk is a subtle payload difference (esp. the player `content` and the `/learn` tree). Mitigation: the existing feature tests — **must be run** (no PHP here).
- **404 body nuance (low).** The status is 404 as before; the body message may differ from the framework's default binding message (`LearnController` already used `NotFoundHttpException('Course not found.')`). Status contract preserved.
- **Perf on `/learn` (low, output-neutral).** The per-lesson `canAccessByLessonId` loop routes through `lessonRefById` (media + course + prerequisites); this is a modest increase to an already-N+1 loop (flagged in the audit) and can be optimized later with a batch accessibility pass. Output unchanged.
- **Additive support changes (low).** `findLessonByPublicId` and `LessonRef.content` are additive; `content` is populated only by the detail read methods (`findLessonByPublicId`/`lessonRefById`), null in tree/nav refs — no memory bloat in the tree.
- **Temporary `Authoring→Catalog` edge (unchanged).** Final-Phase split pending.

---

## Recommendation

**Merge after the gate suite is confirmed green on a PHP-capable environment.** Phase 4 completes the HTTP-layer decoupling: controllers are model-free, route-model binding is removed, and all data flows through `CurriculumReadPort` + id/ref methods + DTO-fed resources, with the model-based application methods retained for safety. Before merge, on PHP 8.3 (or `docker compose exec api …`): run `composer dump-autoload`, `vendor/bin/pint --test`, `vendor/bin/phpstan analyse`, `php artisan test` (the full Learning suite — especially `MediaSafetyTest` and the `/learn` flow), and — once installed — `vendor/bin/deptrac analyse`. Then proceed to the **Final Phase**: remove the now-dead model-based service/action methods, sever the five `belongsTo(Course|Lesson)` relations (retaining FK columns — no schema change), migrate the dev fixtures, split the temporary adapter into Catalog/Authoring providers, and shrink the Deptrac baseline to reach zero Curriculum coupling in Learning.

---

## Validation

Attempted, per request:

```
composer dump-autoload      -> Not verifiable from repository (php/composer not available in this environment)
vendor/bin/pint             -> Not verifiable from repository
vendor/bin/phpstan analyse  -> Not verifiable from repository
vendor/bin/deptrac analyse  -> Not verifiable from repository (deptrac not installed; baseline empty)
php artisan test            -> Not verifiable from repository
```

Static verification performed here (repository evidence): the six controllers import **none** of `Lesson`/`Section`/`Course`/`CurriculumTreeService`/`CourseStatus` (grep = 0); their route params are `string $lesson`/`string $course` (binding removed); they call only the id/ref port/service/action methods; git shows **no** modification to Services, Actions, Resources, Models, or route files; Learning Curriculum `use`-sites dropped 31 → 22 (controllers 8 → 0); `file(1)` reports all changed files as PHP text (no NUL). No database, API contract, or business behavior was changed; the model-based methods were kept; only the six controllers plus the three additive support files (`CurriculumReadPort`, `CurriculumReadAdapter`, `LessonRef`) were modified, and this report created.

Run the commands above (or `docker compose exec api …`) to obtain live pass/fail before the Final Phase.

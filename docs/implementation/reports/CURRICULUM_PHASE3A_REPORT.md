# Curriculum Refactor — Phase 3A Report (New id/ref APIs, Expand)

> Chief Enterprise Architect. Executes Phase 3A of `CURRICULUM_FINAL_MIGRATION_PLAN.md`: the **expand** half of expand-and-contract — introduce the id/ref read APIs and enrich the DTOs, **additively**. No controllers, route-model binding, services, actions, resources, routes, models, or database were modified; no existing model-based method was removed or changed; no runtime behavior changed. The new methods are **dormant** (no caller yet). Runtime gates could not run here (no PHP/Composer) — marked **"Not verifiable from repository."**

---

## Executive Summary

`CurriculumReadPort` gained the five id/ref read methods approved in the migration plan, and the DTOs gained the approved fields — all purely additive. The five new methods are implemented in the existing `CurriculumReadAdapter` using dedicated private builders (`buildCourseRef`, `buildLessonRef`), leaving the three existing model→DTO mappers (`courseRef`/`sectionRef`/`lessonRef(Model)`) **byte-for-byte untouched** so the current (model-passing) controllers, services, and resources keep working unchanged. The new DTO fields are defaulted, so every existing `new CourseRef(...)` / `new LessonRef(...)` call still compiles and still produces identical output for its current consumers (which read none of the new fields). Because nothing calls the new methods yet, there is **zero runtime-behavior change** — this phase only makes the ref-based API *available* for Phase 3B (services/actions/resources) and Phase 4 (controllers).

---

## Files Modified

Exactly four files (all within the already-uncommitted Curriculum feature; no other file touched):

1. `app/Platform/Shared/Curriculum/Data/CourseRef.php` — added one defaulted field.
2. `app/Platform/Shared/Curriculum/Data/LessonRef.php` — added four defaulted fields.
3. `app/Platform/Shared/Curriculum/Contracts/CurriculumReadPort.php` — added five method signatures.
4. `app/Domains/Authoring/Curriculum/CurriculumReadAdapter.php` — implemented the five methods + two private builders; existing mappers unchanged.

Confirmed **not** modified: any Service, Action, Controller, Resource, route file, or Model; and the adapter's existing `courseRef`/`sectionRef`/`lessonRef(Model)` methods.

---

## New Port Methods

Added to `CurriculumReadPort` (only the plan-approved set; no others):

| Method | Returns | Adapter implementation |
|--------|---------|------------------------|
| `findCourseByPublicId(string $publicId)` | `?CourseRef` | `Course::where('public_id')->first()` → `buildCourseRef` |
| `courseRefById(int $courseId)` | `?CourseRef` | `Course::find()` → `buildCourseRef` |
| `lessonRefById(int $lessonId)` | `?LessonRef` | `Lesson::with('media')->find()` → `buildLessonRef(courseId via courseIdForLesson, withPrerequisites: true)` |
| `curriculumTree(int $courseId, bool $publishedOnly)` | `array{course: ?CourseRef, sections: list<array{section: SectionRef, lessons: list<LessonRef>}>}` | replicates `CurriculumTreeService::forCourse` (sections `with(lessons.media)` ordered by position, `published()` when requested) → composed DTOs |
| `orderedPublishedLessonRefs(int $courseId)` | `list<LessonRef>` | published section ids ordered by position → published lessons ordered by `(section_id, position)` → `buildLessonRef` |

Design note: `curriculumTree` returns a **composed array of the existing DTOs** (`CourseRef`/`SectionRef`/`LessonRef`) rather than a new `CurriculumTree`/`SectionNode` class, because this task authorizes extending only `CourseRef` and `LessonRef` — no new DTO classes. The two private builders avoid touching the existing mappers; `buildLessonRef` fetches prerequisites only when `withPrerequisites` is true (so the tree/nav paths incur no per-lesson prerequisite query, i.e. no N+1).

---

## DTO Changes

Additive only; all new fields defaulted so existing construction is unaffected:

- **`CourseRef`** — added `public ?string $thumbnailPath = null`. Existing fields (`id`, `publicId`, `title`, `slug`) unchanged. Populated by the id-based methods (`buildCourseRef` reads `Course::$thumbnail_path`); the existing `courseRef(Model)` mapper leaves it `null` (its consumers don't read it).
- **`LessonRef`** — added `public int $sectionId = 0`, `public int $courseId = 0`, `public int $position = 0`, `public array $prerequisiteLessonIds = []` (`@param list<int>`). Existing fields (`id`, `publicId`, `title`, `type`, `isPreview`, `hasMedia`) unchanged. Populated by the id-based methods; the existing `lessonRef(Model)` mapper leaves them at defaults (it maps only render fields and must not add per-lesson queries in the tree loop).

No field was removed or reordered before an existing one; the only construction sites (three, all in the adapter) remain valid.

---

## Compatibility

- **APIs:** unchanged — no endpoint, payload, or route touched.
- **Database:** untouched.
- **Existing model-based methods:** retained and unchanged (`courseRef`/`sectionRef`/`lessonRef(Model)`, `isCourseEnrollable`, `publishedLessonIdsForCourse/Section`, `courseIdForLesson`).
- **Existing DTO consumers** (`LearnCourseResource`, `LearnSectionResource`, `LearnLessonItemResource`, `ContinueLearningResource`) read none of the new fields → identical output.
- **Services/actions/controllers/resources/routes/models:** not modified.
- **DI:** `CurriculumReadPort` binding unchanged (already bound to `CurriculumReadAdapter` in `AuthoringServiceProvider`).

---

## Behavior Verification

Zero runtime-behavior change, by construction:

- The five new methods have **no caller** in this phase (verified: only the interface + adapter reference them) → they cannot affect any request.
- New DTO fields are **defaulted** and read by **no** existing consumer → existing JSON is byte-identical.
- The three existing model→DTO mappers are **unchanged** → the current model-passing controllers/resources behave exactly as before.

**Runtime confirmation: Not verifiable from repository** (no PHP here). When Phase 3B/4 wire these methods, the existing feature tests (`ProgressCompletionTest`, `PrerequisiteLockTest`, `MediaSafetyTest`, `/learn`, continue-learning) plus new parity tests will validate them.

---

## Risk Assessment

- **Dormant-code risk (low).** The new methods are unused this phase; a defect in them cannot affect production until Phase 3B wires them, and will be caught by parity tests then.
- **DTO default consistency (low, documented).** DTOs built by the existing model mappers carry the new fields at defaults (`thumbnailPath=null`, `sectionId/courseId/position=0`, `prerequisiteLessonIds=[]`). This is safe because those mapper outputs are never read for the new fields; Phase 3B/4 will use the id-based methods (fully populated) for anything that needs them, and the Final Phase removes the model mappers. No consumer observes a default today.
- **Static typing (low).** `curriculumTree` returns a typed array shape; `buildLessonRef` maps `prerequisites()->pluck(...)` through `intval`. Minor PHPStan list-vs-array typing nuances are possible on the new (dormant) methods; worst case a baseline entry, not a runtime issue. **Not verifiable from repository.**
- **Temporary `Authoring→Catalog` edge (unchanged).** The adapter still references `Catalog\Models\Course`/`CourseStatus` (from Phase 1) — existing baselined debt, slated for the Catalog/Authoring split in the Final Phase.

---

## Next Step

**Phase 3B — Services + Actions + Resources migrate together** (per `CURRICULUM_FINAL_MIGRATION_PLAN.md`, New Phase 3): add id/ref-accepting entry points to `LessonAccessService`, `ProgressService`, `LearningMediaService`, and `ContinueLearningService` (resolving the Phase-2 deferrals via `lessonRefById`/`orderedPublishedLessonRefs`); add id-accepting `execute` to the four actions; migrate the resources to consume DTOs — all via expand-and-contract, still **without** touching controllers or route binding. Then Phase 4 (controllers + route binding), then the Final Phase (dead-code removal, model-relation severance, Deptrac cleanup).

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

Static verification performed here (repository evidence): the port declares the five new methods and the adapter implements them plus `buildCourseRef`/`buildLessonRef` (confirmed via authoritative file read); the three existing `*(Model)` mappers are present and unchanged; the only `new CourseRef(...)`/`new LessonRef(...)` sites are in the adapter and remain valid because the added fields are defaulted; no Service/Action/Controller/Resource/route/Model file was modified; `file(1)` reports all four changed files as PHP text (no NUL). No API, database, or behavior changed; only the four files were modified and this report created.

Run the commands above (or `docker compose exec api …`) to obtain live pass/fail before Phase 3B.

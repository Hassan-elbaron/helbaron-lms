# Curriculum Refactor — Phase 2 Report (Learning Services)

> Chief Enterprise Architect. Executes Phase 2 of the Curriculum cleanup per `CURRICULUM_DEPENDENCY_AUDIT.md`: removing direct `Section`/`Lesson`/`Course` **queries** from Learning services, replaced by `CurriculumReadPort`. Only audit-approved port methods were added. No controllers, route-model binding, actions, model relations, database, or APIs were modified. Runtime gates could not run here (no PHP/Composer) — marked **"Not verifiable from repository."**

---

## Executive Summary

`ProgressService` and `LessonAccessService` no longer run static `Section::`/`Lesson::` Eloquent queries; they delegate to three new, audit-approved `CurriculumReadPort` methods (`publishedLessonIdsForCourse`, `publishedLessonIdsForSection`, `courseIdForLesson`) implemented in the existing Authoring adapter. **Four direct foreign query-sites were removed** with byte-identical results (the adapter replicates the exact prior queries).

Two of the four in-scope services could not be de-queried within Phase-2 constraints and are documented as deferred:
- **ContinueLearningService** returns a `?Lesson` **model** consumed by the frozen Phase-1 `ContinueLearningResource` (which maps model→DTO). Removing its `Section`/`Lesson` queries would either break that frozen resource or add a compensating `Lesson` load — so it is deferred to the resource-migration phase. Left unchanged (byte-identical).
- **LearningMediaService** has **no** curriculum query (its media lookup already went through `MediaAssetPort` in the Media phase). Nothing to remove; left unchanged.

The `Lesson`/`Section` **type-hints** on the services' public methods remain, because their callers (controllers/actions) are frozen this phase; only the queries were removed. Learning Curriculum `use`-import sites: **32 → 31** (LessonAccessService dropped `use Section`). No new cross-context edge was introduced; the Shared Curriculum namespace still imports nothing outside Shared.

---

## Files Modified

- `app/Platform/Shared/Curriculum/Contracts/CurriculumReadPort.php` — added 3 methods: `publishedLessonIdsForCourse(int): array`, `publishedLessonIdsForSection(int): array`, `courseIdForLesson(int): ?int` (exactly the audit's approved signatures).
- `app/Domains/Authoring/Curriculum/CurriculumReadAdapter.php` — implemented the 3 methods by reading Authoring's own `Section`/`Lesson` models (intra-context; the exact prior queries).
- `app/Contexts/Learning/Services/ProgressService.php` — injected `CurriculumReadPort`; `publishedLessonIds()` and `sectionPercentage()` now call the port; 3 static queries removed. `use Lesson`/`use Section` kept (public-method param type-hints, frozen callers).
- `app/Contexts/Learning/Services/LessonAccessService.php` — injected `CurriculumReadPort`; `courseIdForLesson()` now calls the port; 1 static query removed; `use Section` removed; `use Lesson` kept; prerequisites relation query deferred (see below).

**Unchanged (documented):**
- `app/Contexts/Learning/Services/ContinueLearningService.php` — deferred (frozen resource requires a `Lesson` model).
- `app/Contexts/Learning/Services/LearningMediaService.php` — no curriculum query present; nothing to change.

No controller, route file, action, model, migration, or API was touched.

---

## Methods Replaced

| Service method | Before | After |
|----------------|--------|-------|
| `ProgressService::publishedLessonIds(int $courseId): Collection` | `Section::where('course_id',…)->published()->pluck('id')` then `Lesson::whereIn('section_id',…)->published()->pluck('id')` | `collect($this->curriculum->publishedLessonIdsForCourse($courseId))` |
| `ProgressService::sectionPercentage(Enrollment, Section $section): int` | `Lesson::where('section_id',$section->id)->published()->pluck('id')` | `collect($this->curriculum->publishedLessonIdsForSection($section->id))` |
| `LessonAccessService::courseIdForLesson(Lesson $lesson): int` | `(int) Section::whereKey($lesson->section_id)->value('course_id')` | `$this->curriculum->courseIdForLesson($lesson->id) ?? 0` |

New port methods (implemented in `CurriculumReadAdapter`, reading Authoring models):
- `publishedLessonIdsForCourse` → `Section::where('course_id')->published()->pluck('id')` + `Lesson::whereIn('section_id')->published()->pluck('id')->all()` (identical to the prior inline pair).
- `publishedLessonIdsForSection` → `Lesson::where('section_id')->published()->pluck('id')->all()`.
- `courseIdForLesson` → `Lesson::whereKey($id)->value('section_id')` then `Section::whereKey($sectionId)->value('course_id')`.

---

## Queries Removed

Four direct foreign Eloquent query-sites removed from Learning services:

1. `ProgressService` — `Section::where('course_id',…)->published()->pluck('id')` (publishedLessonIds).
2. `ProgressService` — `Lesson::whereIn('section_id',…)->published()->pluck('id')` (publishedLessonIds).
3. `ProgressService` — `Lesson::where('section_id',…)->published()->pluck('id')` (sectionPercentage).
4. `LessonAccessService` — `Section::whereKey(…)->value('course_id')` (courseIdForLesson).

Verified: `grep '(Section|Lesson)::' ProgressService LessonAccessService` = **0**.

**Not removed (deferred, documented):**
- `ContinueLearningService` — `Section::where(...)` + `Lesson::whereIn(...)->get()` (2 sites): produce the `?Lesson` model the frozen `ContinueLearningResource` consumes; removal needs that resource migrated to a `LessonRef` (out of Phase-2 scope).
- `LessonAccessService::prerequisitesMet` — `$lesson->prerequisites()->pluck('lessons.id')` (relation query on the passed model): the audit delivers prerequisites via `lessonRef(int)->prerequisiteLessonIds`, but the Phase-1 port already defines `lessonRef(Model)` (resource mapper) — the name is taken, and adding the int-based variant / a new method would either collide or exceed the "only audit-approved methods" instruction and would touch resources. Deferred to a later sub-phase.

---

## Remaining Curriculum Violations

Learning Curriculum `use`-import sites: **31** (was 32 after Phase 1; LessonAccessService `use Section` removed):

- `Authoring\Models\Lesson` — 18 (services/actions/controllers/models/dev; the service copies are frozen param type-hints).
- `Authoring\Models\Section` — 4 (ProgressService `sectionPercentage` param; LearningSeeder; LessonPlayerController; ContinueLearningService).
- `Authoring\Services\CurriculumTreeService` — 1 (`LearnController`).
- `Catalog\Models\Course` — 8.
- `Catalog\Enums\CourseStatus` — 0 (cleared Phase 1).

Plus, not counted in `use`-sites: ContinueLearningService's 2 static queries and LessonAccessService's `prerequisites()` relation query (both deferred). Later phases: ContinueLearningService (with resource migration), LessonAccessService prerequisites, then controllers/actions/model relations.

---

## Compatibility

- **API:** unchanged — no endpoint, payload, or status change.
- **Database:** untouched.
- **Behavior:** progress percentages, completion detection, and access decisions are computed from the same lesson-id sets and course resolution as before.
- **Constraints honored:** controllers, route-model binding, actions, model relations not modified. Service method **signatures** unchanged (so frozen callers keep working).
- **DI:** `ProgressService` and `LessonAccessService` now take `CurriculumReadPort` (bound in `AuthoringServiceProvider`); both are container-resolved everywhere (no `new` construction found), so injection resolves cleanly.

---

## Behavior Verification

Byte-identical reasoning (static):

- **publishedLessonIds / recomputeCoursePercentage:** the adapter runs the identical `Section→Lesson` published pluck; `collect(list<int>)` yields the same `Collection<int>` the prior `pluck('id')` produced; `percentage()` math (`isEmpty`, `whereIn`, `count`, `floor(...*100)`) is unchanged → identical percentage and completion transition.
- **sectionPercentage:** identical `Lesson::where('section_id')->published()->pluck('id')` set → identical percentage.
- **courseIdForLesson:** the adapter resolves lesson→section→course to the same `course_id`; `?? 0` reproduces the prior `(int) …->value('course_id')` null→0 behavior → identical enrollment lookup and access outcome.

**Runtime confirmation: Not verifiable from repository** (no PHP). The existing feature/unit tests exercise these exact paths and are the authoritative check: `ProgressCompletionTest`, `PrerequisiteLockTest`, `MediaSafetyTest`, and the `/learn` accessibility flow. Run them to confirm.

---

## Risk Assessment

- **Byte-parity (medium, unverified).** Logic is a faithful copy behind the port; risk is a subtle set/order difference. Mitigation: existing tests — must run.
- **`courseIdForLesson` query count (+1, output-identical).** The port re-derives `section_id` from the lesson id (2 keyed lookups) where the service previously used the loaded `$lesson->section_id` (1 lookup). In `LearnController`'s per-lesson `canAccess` loop this adds one keyed query per lesson — an incremental change to an already-N+1 loop (flagged in the audit), with identical output. To be optimized when the service signatures migrate to refs.
- **Deferrals (low, explicit).** ContinueLearningService and the `prerequisites()` query remain; both are blocked by frozen Phase-1 artifacts / the `lessonRef` naming and the "audit-approved-only" constraint. Clearly scoped for later phases.
- **Temporary `Authoring→Catalog` edge (low).** Unchanged from Phase 1; centralized in the adapter; slated for the audit's Catalog/Authoring split.
- **Toolchain (external).** Deptrac/Rector not installed; PHP not installed locally; ordering/analysis not enforced here.

---

## Recommendation

**Merge after the gate suite is confirmed green on a PHP-capable environment.** Phase 2 removes four direct `Section`/`Lesson` queries from `ProgressService` and `LessonAccessService` behind the audit-approved port methods, with no API/DB/behavior change and no new boundary-violation type. Before merge, on PHP 8.3 (or `docker compose exec api …`): run `composer dump-autoload`, `vendor/bin/pint --test`, `vendor/bin/phpstan analyse`, `php artisan test` (especially `ProgressCompletionTest`, `PrerequisiteLockTest`, `MediaSafetyTest`, and `/learn`), and — once installed — `vendor/bin/deptrac analyse`. Then proceed to the next sub-phases: ContinueLearningService + LessonAccessService prerequisites (with the corresponding resource migration), then controllers/route-binding, actions, and model relations last.

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

Static verification performed here (repository evidence): `ProgressService` and `LessonAccessService` contain **0** static `Section::`/`Lesson::` queries (4 removed); `CurriculumReadPort` and `CurriculumReadAdapter` contain the 3 audit-approved methods (confirmed via authoritative file read); both services inject `CurriculumReadPort` and are only container-resolved (no manual `new`); `LessonAccessService` dropped `use Section` (kept `use Lesson`); Learning Curriculum `use`-sites 32→31; `Shared\Curriculum` imports nothing outside Shared; `file(1)` reports all changed files as PHP text (no NUL). ContinueLearningService (2 queries) and LearningMediaService (0 queries) left unchanged as documented. No controller, route binding, action, model relation, database, or API was modified; only the listed files were changed and this report created.

Run the commands above (or `docker compose exec api …`) to obtain live pass/fail.

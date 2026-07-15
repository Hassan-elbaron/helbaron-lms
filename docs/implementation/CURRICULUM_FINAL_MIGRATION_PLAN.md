# Curriculum — Final Migration Plan (Revised After Phase 3 Block)

> Chief Enterprise Architect. **PLANNING ONLY** — no code, API, schema, or architecture changed. This supersedes the migration order in `CURRICULUM_DEPENDENCY_AUDIT.md` in light of `CURRICULUM_PHASE3_REPORT.md` (Phase 3 was NO-GO). Grounded in a fresh scan of `apps/api/app/Contexts/Learning` and the four prior Curriculum documents. Anything requiring execution is marked **"Not verifiable from repository."**

---

## Executive Summary

The original order (audit §"Refactoring Order": Status → Resources → Services → Controllers → Actions → Model relations) was **top-down**, and it deadlocked at Phase 3. The dependency chain in the running code flows **Controller → Service / Action / Resource → Model**: controllers *supply* `Lesson`/`Course` models to the layers beneath them. Two earlier phases, each correct under its own constraints, hardened that direction:

- **Phase 1** could not touch controllers, so resources were made to *map* a received model to a DTO (`courseRef(Model)`…), rather than *consume* a DTO. Resources therefore still require a model from the controller.
- **Phase 2** could not touch controllers/actions, so service signatures stayed **model-typed** (`assertAccess(User, Lesson)`…).

By the time Phase 3 tried to free the controllers of models, **every consumer beneath them still demanded a model**, and the only compliant data source (the audit-approved port) returns **DTOs/ids, never models**. A controller cannot stop supplying models while its callees still require them — so Phase 3 was impossible in isolation.

The fix is to reverse the direction: migrate **bottom-up** using **expand-and-contract**. Consumers (services, actions, resources) must gain **id/ref-accepting** entry points *before* the controllers stop supplying models; the controllers switch next; the model relations are severed **last**, once nothing traverses them. Each phase remains independently shippable because the old model-accepting paths are retained until their callers are gone.

---

## Remaining Couplings

Current Learning→Curriculum `use`-import sites: **31** (post-Phase-2), plus residual queries/relations. Grouped:

### Controllers (8 import sites)
- `LearnController` — `Authoring\Services\CurriculumTreeService`, `Catalog\Models\Course`; runtime: `Course::where('public_id')`, `foreach ($section->lessons)`, `LessonAccessService::canAccess(…, Lesson)` per lesson, builds `LearnCourseResource(['course'=>Course,'sections'=>Section[]])`.
- `LessonPlayerController` — `Authoring\Models\Lesson`, `Section`; runtime: route-bound `Lesson`, own nav queries `Section::whereKey`, `Section::where`, `Lesson::whereIn`, calls `assertAccess`/`playbackFor`/`hasMedia`, builds `LearnerLessonResource(['lesson'=>Lesson])`.
- `LessonProgressController` — `Lesson`; route-bound → `RecordLessonProgressAction::execute(…, Lesson,…)`.
- `BookmarkController` — `Lesson`; route-bound → `assertAccess` + `ToggleBookmarkAction::execute(…, Lesson)`.
- `NoteController` — `Lesson`; route-bound → `assertAccess` + `UpsertLessonNoteAction::execute(…, Lesson, string)`.
- `EnrollmentController` — `Catalog\Models\Course`; route-bound → `EnrollInCourseAction::execute(…, Course)`.
- (Not import-coupled but in the HTTP layer:) `ContinueLearningController` (builds `ContinueLearningResource` from `ContinueLearningService::forUser`), `MyLearningController` (builds `MyLearningItemResource`). No direct `use`, but they depend on the service/resource migrations below.

### Actions (5 import sites)
- `ToggleBookmarkAction::execute(User, Lesson)` — uses `$lesson->id` only.
- `UpsertLessonNoteAction::execute(User, Lesson, string)` — uses `$lesson->id` only.
- `RecordLessonProgressAction::execute(User, Lesson, …)` — passes `$lesson` to `LessonAccessService`+`ProgressService`, dispatches with `$lesson->id`.
- `EnrollInCourseAction::execute(User, Course)` — uses `$course->id` + `isCourseEnrollable` (already port-based).
- `GrantEnrollmentAction::execute(User, Course, …)` — uses `$course->id`.

### Services (6 import sites)
- `LessonAccessService` — `Lesson` (params `assertAccess`/`canAccess`/`courseIdForLesson`); residual **`$lesson->prerequisites()->pluck('lessons.id')`** relation query (Phase-2 deferral); `$lesson->is_preview` read.
- `ProgressService` — `Lesson` (`record` param), `Section` (`sectionPercentage` param). No static queries (Phase 2 removed them).
- `LearningMediaService` — `Lesson` (params `playbackFor`/`hasMedia`); no curriculum query (Media phase).
- `ContinueLearningService` — `Lesson`, `Section`; residual **`Section::where`+`Lesson::whereIn(...)->get()`** producing the `?Lesson` returned to `ContinueLearningResource` (Phase-2 deferral).

### Resources (0 import sites; runtime model consumption)
- `LearnCourseResource`, `LearnSectionResource`, `LearnLessonItemResource` — map received models to DTOs via `CurriculumReadPort::{courseRef,sectionRef,lessonRef}(Model)` (Phase 1).
- `LearnerLessonResource` — reads a `Lesson` model from its payload array dynamically (`$lesson->public_id/title/type/content/is_preview`); no import.
- `ContinueLearningResource` — reads `$enrollment->course` (relation) via `courseRef(Model)`, and receives a `?Lesson` next-lesson.
- `MyLearningItemResource` — reads `$this->resource->course->{public_id,title,slug,thumbnail_path}` via the Enrollment→Course relation; **needs `thumbnail_path`** (not yet on `CourseRef`).

### Relations (6 `belongsTo`, 6 model import sites)
- `Enrollment` → `belongsTo(Course)`.
- `LearningSession` → `belongsTo(Course)`, `belongsTo(Lesson, 'last_lesson_id')`.
- `LessonBookmark` → `belongsTo(Lesson)`.
- `LessonNote` → `belongsTo(Lesson)`.
- `LessonProgress` → `belongsTo(Lesson)`.

### Dev / fixtures (6 import sites, low priority)
- `EnrollmentFactory` (`Course`), `LessonNoteFactory` (`Lesson`), `LessonProgressFactory` (`Lesson`), `LearningSeeder` (`Lesson`+`Section`+`Course`, and `Course::query`/`Section::where`/`Lesson::whereIn`).

---

## Correct Dependency Order

The only valid order is **bottom-up with expand-and-contract**, so no phase breaks the running app:

1. **Data/contract layer first** — extend `CurriculumReadPort` (audit id-based reads) + enrich the DTOs. Pure additive; ships alone.
2. **Application + presentation layer** (New Phase 3) — give **services, actions, resources** id/ref-accepting APIs *alongside* the existing model APIs (expand). Controllers untouched, still call the old model APIs → app unchanged, ships green.
3. **HTTP layer** (New Phase 4) — controllers stop route-model binding, resolve refs/ids, call the new id/ref APIs, build resources from DTOs (contract at the HTTP edge). The application APIs already exist → ships green.
4. **Persistence + cleanup** (Final Phase) — remove now-dead model-accepting APIs and resource model-mapping, sever the `belongsTo` relations, migrate dev fixtures, shrink Deptrac. Nothing references the removed code → ships green.

Rule enforced throughout: **a layer may only drop its model dependency after every layer that calls it accepts ids/refs.** Data → Application → Presentation → HTTP → Persistence. Never HTTP-first.

---

## New Phase 3 — Application + Presentation to Refs (Services + Actions + Resources, together)

**Why together:** services produce data that resources render (`ContinueLearningService` → `ContinueLearningResource`; the tree → `LearnCourseResource`), and actions share the access/enrollability paths. Splitting them strands the runtime chain. Migrating them in one phase (expand only) keeps the model-passing controllers working.

**Port extension (audit-approved methods only; distinct names to avoid the Phase-1 `*(Model)` collision):**
- `findCourseByPublicId(string): ?CourseRef`
- `courseRefById(int): ?CourseRef`
- `lessonRefById(int): ?LessonRef`
- `curriculumTree(int $courseId, bool $publishedOnly): CurriculumTree`
- `orderedPublishedLessonRefs(int $courseId): list<LessonRef>`

**DTO enrichment (fields only):**
- `CourseRef` += `thumbnailPath` (for `MyLearningItemResource`).
- `LessonRef` += `sectionId`, `courseId`, `position`, `prerequisiteLessonIds: int[]` (for access, prerequisites, nav ordering).
- `CurriculumTree` / `SectionNode` (course + ordered sections + ordered lessons) — for the `/learn` tree.

**Services (add id/ref entry points; keep model methods delegating):**
- `LessonAccessService` — add `assertAccessByLessonId(User, int)`, `canAccessByLessonId(User, int)`; resolve prerequisites via `lessonRefById(...)->prerequisiteLessonIds` (removes the deferred `$lesson->prerequisites()` query). Keep `assertAccess(User, Lesson)` delegating to the id variant.
- `ProgressService` — add `recordByLessonId(Enrollment, int, LessonProgressStatus, ?int)`; keep `record(…, Lesson,…)` delegating. Change `sectionPercentage` to `int $sectionId` (no external caller — see Phase-2 report).
- `LearningMediaService` — add `playbackForLesson(User, int)`, `hasMediaForLesson(int)` (MediaAssetPort is already id-based); keep model methods delegating.
- `ContinueLearningService` — change `nextLesson` to return a `?LessonRef` via `orderedPublishedLessonRefs` (removes the deferred `Section::`/`Lesson::` queries), migrated in lockstep with `ContinueLearningResource`.

**Actions (accept ids; they only use `->id`):**
- `ToggleBookmarkAction`, `UpsertLessonNoteAction`, `RecordLessonProgressAction` → `execute(User, int $lessonId, …)`.
- `EnrollInCourseAction`, `GrantEnrollmentAction` → `execute(User, int $courseId, …)`.
- Expand-and-contract: keep model-typed overloads delegating to the id versions until Phase 4 flips the callers.

**Resources (consume DTOs/refs):**
- `LearnCourseResource`, `LearnSectionResource`, `LearnLessonItemResource`, `LearnerLessonResource` — accept `CourseRef`/`SectionRef`/`LessonRef`/`CurriculumTree`. During transition, accept **either** a model (map via the existing `*(Model)` port method) **or** a DTO, so model-passing controllers keep working until Phase 4.
- `ContinueLearningResource` — consume `LessonRef` (paired with `ContinueLearningService`); its builder `ContinueLearningController` is migrated here too.
- `MyLearningItemResource` — consume `CourseRef` (with `thumbnailPath`); its builder `MyLearningController` migrated here.

**Shippable because:** every change is additive/dual-mode; the six model-passing controllers still call the retained model APIs; behavior and JSON unchanged.

---

## New Phase 4 — Controllers, Route Binding, HTTP Layer

Now the application layer accepts ids/refs, so the HTTP layer can drop models.

- **Route-model binding removal:** change the six route definitions + controller signatures so `{lesson}`/`{course}` arrive as **route ids/public-ids** (strings), not bound models.
- **Controllers resolve refs via the port and call the new id/ref APIs:**
  - `LearnController` — `findCourseByPublicId($public)` → `courseRefById`/`curriculumTree(courseId, publishedOnly:true)`; compute accessibility via `canAccessByLessonId`; build `LearnCourseResource` from `CurriculumTree` DTOs. Drops `CurriculumTreeService` + `Course`.
  - `LessonPlayerController` — resolve `LessonRef` by route id; replace nav queries with `orderedPublishedLessonRefs(courseId)`; call `assertAccessByLessonId` + `playbackForLesson`/`hasMediaForLesson`; build `LearnerLessonResource` from the `LessonRef` + `PlaybackToken`. Drops `Lesson` + `Section`.
  - `LessonProgressController`, `BookmarkController`, `NoteController` — pass route lesson id to `assertAccessByLessonId` + the id-accepting actions. Drop `Lesson`.
  - `EnrollmentController` — pass route course id to `EnrollInCourseAction`. Drop `Course`.
  - `ContinueLearningController`, `MyLearningController` — already migrated in Phase 3 (their resources/services now consume refs).
- **Constraint achieved:** controllers import **none** of `Lesson`/`Section`/`Course`/`CurriculumTreeService`/`CourseStatus`.
- **Byte-identical JSON:** the DTOs carry exactly the fields the resources render; OpenAPI diff must be empty.

**Shippable because:** the id/ref APIs exist (Phase 3); flipping the controllers is a localized HTTP change; the model-accepting APIs still exist but are now unused.

---

## Final Phase — Model Relations, Cleanup, Dead Code, Deptrac

- **Contract (remove dead model APIs):** delete the retained model-accepting service methods (`assertAccess(Lesson)`, `record(Lesson)`, `playbackFor(Lesson)`, `sectionPercentage(Section)` model form), action model overloads, and the resources' model-mapping fallback. Nothing calls them after Phase 4.
- **Sever cross-context relations:** remove `belongsTo(Course|Lesson)` from `Enrollment`, `LearningSession`, `LessonBookmark`, `LessonNote`, `LessonProgress`. Keep the FK **columns** (`course_id`, `lesson_id`, `last_lesson_id`) — no schema change — and expose data via `CourseRef`/`LessonRef` where needed (e.g., `MyLearningItemResource`, `ContinueLearningResource` already use `courseRef`). This is the highest-blast-radius step; do it last with parity tests on every relation-traversing site.
- **Dev fixtures:** migrate `EnrollmentFactory`/`LessonNoteFactory`/`LessonProgressFactory`/`LearningSeeder` to resolve ids/refs (or test-only fixtures) — removes the last `use` sites.
- **Dead-code removal:** drop the Phase-1 `courseRef(Model)`/`sectionRef(Model)`/`lessonRef(Model)` mappers if fully superseded by the id-based reads; retire the temporary single `CurriculumReadAdapter` in favor of the audit's **Catalog course provider + Authoring section/lesson provider** split (removing the temporary `Authoring→Catalog` reference).
- **Deptrac cleanup:** regenerate the baseline; confirm the `Learning` layer has **zero** Curriculum entries and depends only on `[Shared, IdentityContracts]`; confirm no `Media/Catalog → Authoring` edge was introduced.

**Shippable because:** every removed symbol is unreferenced after Phase 4; relation severance is guarded by parity tests; schema untouched.

---

## Rollback Strategy

- **Per-phase feature flag.** Gate the id/ref code paths (services/actions/resources in Phase 3; controllers in Phase 4) behind a `learning.curriculum.use_refs` flag; a single flip reverts to the model paths, which remain present until the Final Phase.
- **Expand-and-contract = built-in rollback.** Because old model APIs coexist with new id/ref APIs through Phases 3–4, any regression is reverted by pointing callers back at the old method (or flipping the flag) with no data change.
- **Branch-per-phase + green gate.** Each phase is one branch, merged only when `pint`/`phpstan`/`deptrac`/`php artisan test` are green and the OpenAPI diff is empty. Revert = revert the merge; earlier phases are unaffected (additive).
- **Final Phase is the only irreversible-ish step** (deletions + relation severance); take it in small PRs (dead-APIs, then relations one model at a time, then fixtures, then Deptrac), each independently revertible, and only after Phase 4 has soaked in production.
- **DB safety:** no migration is part of this plan (FK columns are retained), so there is no data rollback to manage.

---

## Success Criteria

1. `grep -rE 'use App\\Domains\\(Authoring|Catalog)\\' app/Contexts/Learning` = **0** (from 31), including controllers, actions, services, resources, models, and dev fixtures.
2. No `Section::`/`Lesson::`/`Course::` static query, no `$lesson->prerequisites()`, and no cross-context `belongsTo(Course|Lesson)` anywhere in `Contexts/Learning`.
3. Controllers import none of `Lesson`/`Section`/`Course`/`CurriculumTreeService`/`CourseStatus`; route-model binding removed for `{lesson}`/`{course}`.
4. Deptrac: `Learning` depends only on `[Shared, IdentityContracts]`; Curriculum entries removed from the baseline; no new `Media/Catalog → Authoring` edge. *(Not verifiable from repository.)*
5. PHPStan `NoCrossContextModelUsageRule` + `NoCrossContextEloquentAccessRule` report **0** for Learning. *(Not verifiable from repository.)*
6. **Behavior parity:** empty OpenAPI diff for `/learn`, `/lessons/{id}`, progress/bookmark/note, enroll, continue-learning, my-learning; all Learning Pest tests green; DB schema untouched. *(Not verifiable from repository.)*
7. Metric: Learning forbidden outbound Curriculum sites **31 → 0**; overall Learning forbidden imports reach the Identity-only remainder (`Identity\Models\User`, next programme).

---

## Final Recommendation

**Adopt the bottom-up, expand-and-contract order and discard the top-down audit order for the remainder.** Concretely:

- **Approve New Phase 3** (Services + Actions + Resources migrate together, additive) with the listed port/DTO extensions — this also clears the two Phase-2 deferrals (`ContinueLearningService`, `LessonAccessService` prerequisites).
- **Then New Phase 4** (controllers + route binding), which is now unblocked because the application layer accepts ids/refs.
- **Then the Final Phase** (dead-code, relation severance, fixtures, Deptrac), in small independently-revertible PRs, model relations last.

Do **not** attempt controllers before Phase 3 lands (that is exactly what blocked the original Phase 3). Sequence the whole programme **after** Deptrac is installed and its baseline seeded (`ARCHITECTURE_FITNESS_READINESS.md`), keep every phase behind a flag, and gate each merge on a green suite + empty OpenAPI diff. This reaches zero Curriculum coupling in Learning with no API/DB/behavior change and a clean rollback at every step.

---

## Validation

- All couplings, files, fields, and residual queries/relations were derived from a direct scan of `apps/api/app/Contexts/Learning` (imports, `Section::`/`Lesson::`/`Course::` usage, `belongsTo`, `->prerequisites()`, resource field reads) reconciled with `CURRICULUM_DEPENDENCY_AUDIT.md`, `CURRICULUM_PHASE1_REPORT.md`, `CURRICULUM_PHASE2_REPORT.md`, and `CURRICULUM_PHASE3_REPORT.md`.
- New findings surfaced here: `MyLearningItemResource` reads `course->thumbnail_path` (⇒ `CourseRef.thumbnailPath`), and `ContinueLearningController`/`MyLearningController` are additional HTTP builders beyond the original six controllers.
- Items requiring execution (Deptrac/PHPStan/test/OpenAPI results) are marked **"Not verifiable from repository."**
- No code, API, database, or architecture was modified; only this document was created.

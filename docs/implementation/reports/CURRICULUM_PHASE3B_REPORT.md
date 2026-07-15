# Curriculum Refactor — Phase 3B Report (Application Layer, Expand-and-Contract)

> Chief Enterprise Architect. Executes Phase 3B of `CURRICULUM_FINAL_MIGRATION_PLAN.md`: give the **application layer** (services, actions, resources) id/ref-based entry points via expand-and-contract, so Phase 4 can free the controllers. No controllers, route-model binding, routes, database, or models were modified; no existing model-based public method was removed; existing APIs keep working. Runtime gates could not run here (no PHP/Composer) — marked **"Not verifiable from repository."**

---

## Executive Summary

The four services and five actions gained id/ref-based methods that are now the **canonical** implementations (using the Phase-3A `CurriculumReadPort` reads); their existing **model-based methods are retained and delegate** to them, so every current caller (the frozen controllers) keeps working with identical output. The six resources gained **dual input**: they render from a DTO when given one, and otherwise fall through to the **unchanged** model path — so the live JSON is byte-identical today, while the DTO paths stand ready (dormant) for Phase 4. This also cleared the two Phase-2 deferrals: `ContinueLearningService` no longer issues its own `Section::`/`Lesson::whereIn` queries (it uses `orderedPublishedLessonRefs`), and `LessonAccessService` resolves prerequisites through `lessonRefById` instead of `$lesson->prerequisites()`. The remaining model coupling is now confined to (a) type-hints on the retained model methods (bound by the still-frozen controllers) and (b) the model `belongsTo` relations — both slated for Phase 4 / the Final Phase.

---

## Files Modified

15 files, all in `app/Contexts/Learning`:

- **Services (4):** `LessonAccessService`, `ProgressService`, `LearningMediaService`, `ContinueLearningService`.
- **Actions (5):** `ToggleBookmarkAction`, `UpsertLessonNoteAction`, `RecordLessonProgressAction`, `EnrollInCourseAction`, `GrantEnrollmentAction`.
- **Resources (6):** `LearnCourseResource`, `LearnSectionResource`, `LearnLessonItemResource`, `LearnerLessonResource`, `ContinueLearningResource`, `MyLearningItemResource`.

Confirmed **not** modified (git shows no tracked modification): any Controller, route file, route-model binding, Model, or migration.

---

## New Service APIs

Each id/ref method is canonical; the retained model method delegates to it (unchanged signature).

| Service | New (canonical) method | Retained model method delegates |
|---------|------------------------|---------------------------------|
| `LessonAccessService` | `assertAccessByLessonId(User,int): Enrollment`, `canAccessByLessonId(User,int): bool`, `courseIdForLessonId(int): int` | `assertAccess(User,Lesson)`, `canAccess(User,Lesson)`, `courseIdForLesson(Lesson)` |
| `ProgressService` | `recordByLessonId(Enrollment,int,LessonProgressStatus,?int): array`, `sectionPercentageById(Enrollment,int): int` | `record(Enrollment,Lesson,…)`, `sectionPercentage(Enrollment,Section)` |
| `LearningMediaService` | `playbackForLesson(User,int): PlaybackToken`, `hasMediaForLesson(int): bool` | `playbackFor(User,Lesson)`, `hasMedia(Lesson)` |
| `ContinueLearningService` | `nextLessonRef(Enrollment): ?LessonRef` | `nextLesson(Enrollment): ?Lesson` (resolves ref, then `Lesson::find`) |

`LessonAccessService::assertAccessByLessonId` resolves `isPreview`, `courseId`, and `prerequisiteLessonIds` from `CurriculumReadPort::lessonRefById`; prerequisites are checked by a new private `prerequisitesMetByIds` (the old private `prerequisitesMet(…, Lesson)` was replaced — it was private, not a public API). `ContinueLearningService` now injects `CurriculumReadPort`, uses `orderedPublishedLessonRefs`, and dropped `use Section` (kept `use Lesson` for `nextLesson`'s return + `find`).

---

## New Action APIs

All five actions gained `executeById(...)` (canonical); the model `execute(...)` delegates by passing `$model->id`:

- `ToggleBookmarkAction::executeById(User,int): array{bookmarked:bool}`
- `UpsertLessonNoteAction::executeById(User,int,string): LessonNote`
- `RecordLessonProgressAction::executeById(User,int,LessonProgressStatus,?int): LessonProgress` (uses `assertAccessByLessonId` + `recordByLessonId`; dispatches events with `$lessonId`)
- `EnrollInCourseAction::executeById(User,int): Enrollment` (uses `isCourseEnrollable` + `grant->executeById`)
- `GrantEnrollmentAction::executeById(User,int,EnrollmentSource): Enrollment`

(PHP has no signature overloading, so "id-based `execute()` overload" is realized as a distinctly-named `executeById`; the model `execute` remains and delegates.)

---

## Resource Compatibility

Each resource accepts DTO input **in addition to** model input; the model path is byte-for-byte unchanged:

| Resource | DTO input accepted | Model path (unchanged) |
|----------|--------------------|------------------------|
| `LearnLessonItemResource` | `LessonRef` | Lesson model → `lessonRef(Model)` |
| `LearnSectionResource` | `['section'=>SectionRef,'lessons'=>LessonRef[]]` | Section model → `sectionRef(Model)` + `->lessons` |
| `LearnCourseResource` | `course=>CourseRef`, `sections=>` tree nodes | Course/Section models via port |
| `ContinueLearningResource` | `next_lesson=>?LessonRef` | `?Lesson` model → `lessonRef(Model)` |
| `LearnerLessonResource` | `lesson=>LessonRef` (+ `content` from payload) | Lesson model (reads `->content` etc.) |
| `MyLearningItemResource` | `additional['course_ref']=>CourseRef` | `whenLoaded('course')` relation |

All DTO branches are **dormant in Phase 3B** (the frozen controllers still pass models), so the live output is identical. Note: because `LessonRef` does not carry `content`, `LearnerLessonResource`'s DTO branch reads `content` from the payload — the Phase-4 `LessonPlayerController` must supply it.

---

## Behavior Verification

Byte-identical / behavior-identical by construction:

- **Services/actions:** model methods delegate to id/ref methods that apply the same logic on the same DB data → same access decisions, same progress math, same enrollment outcomes, same events. `ContinueLearningService::nextLesson` returns the same lesson (`orderedPublishedLessonRefs` reproduces the prior section/lesson ordering; first-uncompleted selection is identical; then `Lesson::find`).
- **Resources:** the model branch is the exact prior code; DTO branches are unused today.
- **Query-count deltas (output-neutral):** delegating through id/ref methods issues extra queries in some paths — `assertAccess` now goes via `lessonRefById` (loads media + resolves course + prerequisites), and `nextLesson` adds a single `Lesson::find`. Output is unchanged; the heavier `assertAccess` path in the per-lesson `canAccess` loop is transient and will be replaced in Phase 4 (controllers using `curriculumTree` + `canAccessByLessonId`).

**Runtime confirmation: Not verifiable from repository** (no PHP). The existing feature/unit tests exercise these paths (`ProgressCompletionTest`, `PrerequisiteLockTest`, `MediaSafetyTest`, `/learn`, continue-learning, enroll) and are the authoritative check.

---

## Remaining Coupling

- **Controllers (8 import sites):** unchanged — still route-model-bind and import `Lesson`/`Section`/`Course`/`CurriculumTreeService` (Phase 4).
- **Service/action model type-hints:** retained model methods still type `Lesson`/`Section`/`Course` params (bound by the frozen controllers). These `use` imports remain until Phase 4 switches controllers to the id/ref methods and the Final Phase removes the model methods.
- **`ContinueLearningService`:** dropped `use Section`; retains `use Lesson` (for `nextLesson`'s `?Lesson` return + `Lesson::find`) — removed when `nextLesson` is deleted (controllers use `nextLessonRef`).
- **Model relations:** the five `belongsTo(Course|Lesson)` relations are untouched (Final Phase).
- **`LearnerLessonResource` content gap:** `LessonRef` lacks `content`; the DTO path depends on the Phase-4 controller passing `content`.
- Overall Learning Curriculum `use`-import count is essentially unchanged this phase (expand adds ref methods but retains model type-hints); the reduction lands in Phase 4 (controllers) and the Final Phase (model methods + relations).

---

## Risk Assessment

- **Byte-identical parity (medium, unverified).** Model paths are unchanged or faithful delegations; DTO paths are dormant. Primary risk is a subtle delegation difference — mitigated by the existing tests, which **must be run** (no PHP here).
- **Query-count/perf deltas (low, output-neutral).** `assertAccess` via `lessonRefById` and `nextLesson`'s extra `find` add queries; the `canAccess` loop is heavier until Phase 4 redesigns it. No output change.
- **Dormant DTO paths (low).** Not exercised until Phase 4; a shape mismatch would surface then (with tests), not now. The `LearnerLessonResource` `content` requirement is documented.
- **DI (low).** `ContinueLearningService` now needs `CurriculumReadPort`; it is container-resolved (via `ContinueLearningController`) and the port is bound in `AuthoringServiceProvider`.
- **Temporary `Authoring→Catalog` edge (unchanged).** Still in the adapter (Phase 1); Final-Phase split pending.

---

## Recommendation

**Merge after the gate suite is confirmed green on a PHP-capable environment.** Phase 3B is a faithful expand: canonical id/ref APIs added, model APIs retained and delegating, resources dual-mode with byte-identical live output. Before merge, on PHP 8.3 (or `docker compose exec api …`): run `composer dump-autoload`, `vendor/bin/pint --test`, `vendor/bin/phpstan analyse`, `php artisan test` (especially the Learning suite), and — once installed — `vendor/bin/deptrac analyse`. Then proceed to **Phase 4** (controllers + route-model-binding removal): switch controllers to the id/ref service/action methods and DTO-fed resources (supplying `LearnerLessonResource`'s `content`), drop the model imports, and remove route-model binding — after which the Final Phase removes the now-dead model methods and severs the `belongsTo` relations.

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

Static verification performed here (repository evidence): each service retains its model methods and adds the id/ref methods; each action has `execute` + `executeById` (confirmed incl. `EnrollInCourseAction` via authoritative read); each resource has a DTO branch plus the unchanged model branch (confirmed incl. `MyLearningItemResource` via authoritative read); `ContinueLearningService` dropped `use Section`, kept `use Lesson`; git shows no controller/route/model modification; `file(1)` reports all 15 changed files as PHP text (no NUL). No controller, route binding, database, or model was modified; existing model-based methods were kept; only the 15 listed files were changed and this report created.

Run the commands above (or `docker compose exec api …`) to obtain live pass/fail before Phase 4.

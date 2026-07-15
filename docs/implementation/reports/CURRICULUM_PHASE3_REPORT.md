# Curriculum Refactor — Phase 3 Report (Controllers + Route-Model-Binding) — BLOCKED / NO-GO

> Chief Enterprise Architect. Phase 3 was to remove route-model binding from six Learning controllers and stop them importing `Lesson`/`Section`/`Course`/`CurriculumTreeService`/`CourseStatus`, resolving refs through `CurriculumReadPort` — **without touching services, actions, model relations, database, APIs, or behavior**, and **adding only audit-approved port methods**. After a full analysis of all six controllers and their downstream consumers, **this phase cannot be executed under the stated constraints**. **No code was changed.** This report documents the exact blocker and the required re-sequencing. Runtime gates were not run (no PHP/Composer) — marked **"Not verifiable from repository."**

---

## Executive Summary

Every one of the six controllers exists to hand a **`Lesson` or `Course` Eloquent model** to a downstream consumer:

- **Frozen services** — `LessonAccessService::assertAccess(User, Lesson)` / `canAccess(User, Lesson)`, `LearningMediaService::playbackFor(User, Lesson)` / `hasMedia(Lesson)`.
- **Frozen actions** — `ToggleBookmarkAction::execute(User, Lesson)`, `UpsertLessonNoteAction::execute(User, Lesson, string)`, `RecordLessonProgressAction::execute(User, Lesson, …)`, `EnrollInCourseAction::execute(User, Course)`.
- **Frozen Phase-1 resources** — `LearnCourseResource` (maps `Course`/`Section` models via `courseRef(Model)`/`sectionRef(Model)`), `LearnerLessonResource` (reads a `Lesson` model from its payload array).

To remove route-model binding, a controller must receive a route **id/public-id** and then obtain the object it passes downstream. But:

1. The downstream consumers are **frozen this phase** and their signatures **require the concrete `Lesson`/`Course` model** (verified below). They cannot accept an id or a `LessonRef`/`CourseRef`.
2. Every **audit-approved** `CurriculumReadPort` method returns a **DTO, id, or bool — never a model** (verified: no method returns `Model`/`Lesson`/`Section`/`Course`). Constraint 1 forbids adding any non-audit method, so I cannot add a model-returning resolver.
3. Constraint 4 forbids the controllers importing `Lesson`/`Section`/`Course`, so they cannot construct or type the model themselves.

There is therefore **no compliant way** for a controller to produce the model its frozen consumer demands. Any implementation would require one of: modifying services/actions (forbidden, constraints 5–6), modifying the frozen Phase-1 resources, adding a non-audit model-returning port method (forbidden, constraint 1), leaving the model imports in place (forbidden, constraint 4), or shipping a state where controllers pass DTOs to model-typed consumers (breaks the app → violates "do not modify business behavior"). Forcing any of these would break behavior or violate an explicit constraint, so the phase is **NO-GO** and no code was changed.

Root cause: the dependency chain is **controller → service/action/resource → model**. Phase 1 (by its own constraint) made resources *map* models to DTOs rather than consume DTOs, and Phase 2 (by its own constraint) kept service signatures model-typed. The chain must be inverted **bottom-up** — consumers accept ids/refs first — before the top (controllers) can drop models. Removing binding at the top while every consumer still demands a model is not possible in isolation.

---

## Controllers Updated

**None (0 of 6).** Each is blocked by a frozen, model-typed consumer:

| Controller | Passes a model to (frozen) | Model required | Blocked |
|------------|----------------------------|:---:|:---:|
| `BookmarkController` | `LessonAccessService::assertAccess(…, Lesson)` + `ToggleBookmarkAction::execute(…, Lesson)` | Lesson | ✅ |
| `NoteController` | `assertAccess(…, Lesson)` + `UpsertLessonNoteAction::execute(…, Lesson, string)` | Lesson | ✅ |
| `LessonProgressController` | `RecordLessonProgressAction::execute(…, Lesson, …)` | Lesson | ✅ |
| `EnrollmentController` | `EnrollInCourseAction::execute(…, Course)` | Course | ✅ |
| `LessonPlayerController` | `assertAccess(…, Lesson)`, `playbackFor(…, Lesson)`, `hasMedia(Lesson)`, `LearnerLessonResource(['lesson'=>Lesson])`; plus its own `Section::`/`Lesson::` nav queries | Lesson (+Section) | ✅ |
| `LearnController` | `canAccess(…, Lesson)` per lesson, `LearnCourseResource(['course'=>Course,'sections'=>Section[]])`; plus `Course::where('public_id')` + `CurriculumTreeService` | Course + Section + Lesson | ✅ |

(For `LearnController`/`LessonPlayerController`, the audit-approved `findCourseByPublicId`, `curriculumTree`, and `orderedPublishedLessonRefs` *could* replace the controllers' own `Course::where`/`CurriculumTreeService`/nav queries — but the controllers still must call `canAccess(Lesson)` and feed model-consuming resources, so they remain blocked overall and cannot become import-free.)

---

## Route Binding Changes

**None.** Route-model binding was not removed from any controller (doing so would leave the controller unable to supply the required model to its frozen consumers).

---

## Dependencies Removed

**None.** The controllers still import: `LearnController` → `CurriculumTreeService` + `Course`; `LessonPlayerController` → `Lesson` + `Section`; `LessonProgressController`, `BookmarkController`, `NoteController` → `Lesson`; `EnrollmentController` → `Course`. (8 controller import sites, unchanged.)

---

## Remaining Curriculum Violations

Unchanged from the end of Phase 2: **31** Learning Curriculum `use`-import sites (`Lesson` 18, `Section` 4, `Course` 8, `CurriculumTreeService` 1, `CourseStatus` 0), of which the six controllers account for 8 sites (Lesson 4, Section 1, Course 2, CurriculumTreeService 1). Plus the deferred `ContinueLearningService` queries and `LessonAccessService::prerequisites()` relation query from Phase 2. Nothing was removed in Phase 3.

---

## Behavior Verification

Not applicable — **no code changed**, so behavior is identical to the post-Phase-2 state. No `Not verifiable` items arise from this phase because there is nothing new to verify.

---

## Compatibility

Fully preserved — the repository is byte-for-byte unchanged by Phase 3. API, database, services, actions, model relations, and behavior are exactly as they were after Phase 2.

---

## Risk Assessment

The risk is entirely in the alternatives that were **rejected** to protect the codebase:

- **Adding a model-returning port method** (e.g. `lessonModel(id): Model`) — violates constraint 1 (audit-only methods) and launders the coupling: the controller would hold a `Lesson` (typed `Model`) with no `use` import, hiding — not removing — the dependency. Rejected.
- **Passing a `Model`/`mixed` to a `Lesson`-typed frozen method** — works at runtime but introduces PHPStan type violations and is fragile; and obtaining that model still needs a class reference or route binding. Rejected.
- **Keeping route-model binding but dropping the import** — impossible; the binding requires the `Lesson`/`Course` type-hint (hence the import), contradicting constraints 2 and 4. Rejected.
- **Shipping controllers that pass DTOs to model-typed consumers** — breaks every affected endpoint → violates "do not modify business behavior." Rejected.

Proceeding via any of these would be higher-risk than stopping. **Residual risk of the No-Go: none to the codebase; the only cost is that Curriculum controller cleanup is deferred until the sequencing is corrected.**

---

## Recommendation

**Re-sequence: controllers cannot be refactored before (or in isolation from) the consumers they feed.** The clean unblock is one of:

1. **(Recommended) Combine the HTTP + application layer into a single phase.** Migrate, together: the frozen **service access methods** (`LessonAccessService::assertAccess`/`canAccess`), the four **actions** (`ToggleBookmark`, `UpsertLessonNote`, `RecordLessonProgress`, `EnrollInCourse`), and the remaining **resources** (`LearnCourseResource`, `LearnerLessonResource`) to accept **ids / `LessonRef` / `CourseRef`** instead of models — then the controllers can drop route-model binding and imports in the same phase. This requires expanding the audit to add the ref/id-accepting signatures and (per the audit) `findCourseByPublicId`, `lessonRef(int)`, `curriculumTree`, `orderedPublishedLessonRefs`. Because `lessonRef(Model)` (Phase 1) already holds that name, the int-based lookup should be named distinctly (e.g. `lessonRefById(int): ?LessonRef`) or the Phase-1 mapper renamed as part of the same phase.
2. **(Alternative) Bottom-up in smaller steps, consumers first.** First migrate actions + service access methods + resources to ref/id signatures (their own phase, with the frozen callers being the controllers — which still pass models during that step via the existing binding). Then a final controllers phase removes binding + imports. This is more steps but each is individually shippable.

Either way, **Phase 3 as written (controllers only, everything else frozen, audit-only methods) is not achievable** and should be redefined. I recommend approving option 1 as "Phase 3 (revised): Learning HTTP + application layer to refs," explicitly permitting changes to `LessonAccessService` (access methods), the four actions, `LearnCourseResource`/`LearnerLessonResource`, and the six controllers together, plus the corresponding audit-approved port additions.

No work should proceed on the controllers until this decision is made.

---

## Validation

No commands were run and none are needed, because **no code was changed**:

```
composer dump-autoload      -> Not applicable (no changes); also Not verifiable from repository (no php/composer here)
vendor/bin/pint             -> Not applicable / Not verifiable from repository
vendor/bin/phpstan analyse  -> Not applicable / Not verifiable from repository
vendor/bin/deptrac analyse  -> Not applicable / Not verifiable from repository (deptrac not installed)
php artisan test            -> Not applicable / Not verifiable from repository
```

Static verification performed here (repository evidence) confirms the blocker: all six controllers still import `Lesson`/`Section`/`Course`/`CurriculumTreeService`; the frozen services (`assertAccess`/`canAccess`/`playbackFor`/`hasMedia`) and actions (`execute`) are typed on `Lesson`/`Course`; the frozen Phase-1 resource mappers are typed on `Model`; and **no** audit-approved `CurriculumReadPort` method returns a model. The repository is unchanged; only this report was created. Awaiting a re-sequencing decision before any controller work.

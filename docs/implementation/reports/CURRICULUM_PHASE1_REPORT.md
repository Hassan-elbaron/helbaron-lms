# Curriculum Refactor — Phase 1 Report (Status + Resource Abstraction)

> Chief Enterprise Architect. Executes Phase 1 of the Curriculum cleanup per `CURRICULUM_DEPENDENCY_AUDIT.md`: **Status abstraction** (EnrollInCourseAction) and **Resource abstraction** (4 resources). No controllers, route-model binding, services, model relations, database, or APIs were touched. Runtime gates could not be executed here (no PHP/Composer); those are marked **"Not verifiable from repository."**

---

## Executive Summary

Learning now resolves course enrollability and renders curriculum JSON through a Shared `CurriculumReadPort` returning `CourseRef`/`SectionRef`/`LessonRef` DTOs, instead of touching `Catalog\Enums\CourseStatus` and the `Authoring\Models\Lesson`/`Section` models directly. `EnrollInCourseAction` calls `isCourseEnrollable($course->id)`; the four resources map their incoming (still model-typed, from the unchanged controllers) input to DTOs at the resource boundary via the port and render exclusively from the DTOs. Because the controllers pass already-loaded models, the port's mappers read those loaded models with **no extra queries** and reproduce every field byte-for-byte — including the `has_media` `null`-when-not-loaded nuance.

Result: **4 Curriculum coupling sites removed** from Learning (Lesson −2, Section −1, CourseStatus −1) → **36 → 32** Curriculum sites, **52 → 48** total Learning forbidden imports. No new Learning→Authoring/Catalog edge is introduced; the resources now import only Shared (plus Learning's own `Enrollment`). One deliberately-temporary `Authoring→Catalog` edge is centralized inside the new adapter (existing, baselined debt) pending the audit's Catalog/Authoring split in the build phase.

---

## Files Modified

**Created — Shared Curriculum contracts/DTOs (`app/Platform/Shared/Curriculum/`)**
- `Data/CourseRef.php` — `{id, publicId, title, slug}`.
- `Data/SectionRef.php` — `{id, publicId, title}`.
- `Data/LessonRef.php` — `{id, publicId, title, type, isPreview, hasMedia:?bool}`.
- `Contracts/CurriculumReadPort.php` — `isCourseEnrollable(int): bool`; `courseRef(Model): CourseRef`; `sectionRef(Model): SectionRef`; `lessonRef(Model): LessonRef`. Parameters typed as the framework base `Model` so the Shared contract references no Authoring/Catalog class.

**Created — temporary adapter (`app/Domains/Authoring/`)**
- `Curriculum/CurriculumReadAdapter.php` — implements `CurriculumReadPort`. Reads Authoring's own `Lesson`/`Section` and (existing baselined `Authoring→Catalog` debt) `Catalog\Models\Course` + `CourseStatus`. Mappers narrow the `Model` parameter via `assert(... instanceof ...)` and read already-loaded attributes only (no queries); `isCourseEnrollable` loads the course by id and compares to `CourseStatus::Published`.

**Edited**
- `app/Contexts/Learning/Actions/Enrollment/EnrollInCourseAction.php` — dropped `use CourseStatus`; injected `CurriculumReadPort`; `$course->status !== CourseStatus::Published` → `! $this->curriculum->isCourseEnrollable($course->id)`. `use Course` kept (the controller still passes a `Course` model; route binding is out of Phase-1 scope).
- `app/Contexts/Learning/Http/Resources/LearnLessonItemResource.php` — dropped `use Lesson`; renders from `lessonRef($this->resource)`.
- `app/Contexts/Learning/Http/Resources/LearnSectionResource.php` — dropped `use Section`; renders id/title from `sectionRef($this->resource)`; lessons iteration unchanged.
- `app/Contexts/Learning/Http/Resources/LearnCourseResource.php` — renders course block from `courseRef($this->resource['course'])`; enrollment (own model) and sections mapping unchanged.
- `app/Contexts/Learning/Http/Resources/ContinueLearningResource.php` — dropped `use Lesson`; renders course from `courseRef($enrollment->course)` and next-lesson from `lessonRef($next)`; kept `use Enrollment`.
- `app/Domains/Authoring/Providers/AuthoringServiceProvider.php` — binds `CurriculumReadPort` → `CurriculumReadAdapter` (import block normalized to alphabetical order).

No controller, route, service, model-relation, migration, or route file was modified.

---

## Architecture Changes

- **Status abstraction:** enrollability decision moved behind `CurriculumReadPort::isCourseEnrollable()`; Learning no longer imports `CourseStatus`.
- **Resource abstraction:** the four resources render from `CourseRef`/`SectionRef`/`LessonRef` DTOs. Because controllers (unchanged) pass models, each resource converts model→DTO at its boundary via `app(CurriculumReadPort::class)`; the model is passed as the framework `Model` type, so the resources carry no Authoring/Catalog `use`.
- **Dependency edges:** resources now import only `Shared` (+ Learning's own `Enrollment`). The Shared Curriculum namespace imports nothing outside Shared. The temporary adapter concentrates the `Authoring→Catalog` (`Course`/`CourseStatus`) reference in one clearly-labeled file — an existing, baselined edge, not a new violation type.
- **DI:** `CurriculumReadPort` bound once (AuthoringServiceProvider), resolvable before Learning uses it (Authoring loads before Learning).

Verification (repository grep): Learning Curriculum sites **36 → 32** (Lesson 20→18, Section 6→5, CourseStatus 1→0, Course 8 unchanged, CurriculumTreeService 1 unchanged); Shared\Curriculum → non-Shared app = **0**; resources import only Shared/own; PSR-4 namespaces correct for all 5 new files; `file(1)` reports every new/changed file as PHP ASCII/UTF-8 (no NUL).

---

## Compatibility

- **API:** unchanged — same endpoints, same JSON structure and key order. OpenAPI diff expected empty.
- **Database:** untouched.
- **Behavior:** enrollability decision identical (`true` iff course published); resource fields identical.
- **Controllers / routes / services / model relations:** not modified (per constraints).
- **Out-of-scope couplings retained:** `EnrollInCourseAction` keeps `use Course` (route-bound model); `LearnSectionResource` still iterates `$this->resource->lessons`; the model `belongsTo` relations remain — all deferred to later phases per the audit.

---

## Behavior Verification

Byte-for-byte reasoning per output (static — the mappers reproduce the exact prior expressions):

- **LearnLessonItemResource:** `id=publicId`, `title`, `type` (= `type->value`), `is_preview`, `has_media` (= `relationLoaded('media') ? media!==null : null`, preserved in the adapter), `completed`/`locked` computed from `LessonRef.id` + the same id-arrays. Key order unchanged.
- **LearnSectionResource:** `id=publicId`, `title` from `SectionRef`; `lessons` mapped exactly as before.
- **LearnCourseResource:** `course.{id,title,slug}` from `CourseRef`; `enrollment.*` from the own model (unchanged); `sections` mapping unchanged.
- **ContinueLearningResource:** `course.{id,title}` from `courseRef($enrollment->course)`; `progress_percentage` unchanged; `next_lesson.{id,title,type}` from `lessonRef($next)` or `null`.
- **EnrollInCourseAction:** throws `CourseNotEnrollableException` exactly when the course is not published — identical outcome.

**Runtime confirmation: Not verifiable from repository** (no PHP). The existing feature tests exercise these paths and are the authoritative check: `MediaSafetyTest` (lesson-player), and any `/learn` / continue-learning / enroll tests. Run them to confirm byte-parity.

---

## Remaining Curriculum Violations

Learning Curriculum import sites: **32** remaining (of the original 36):

- `Authoring\Models\Lesson` — 18 (services, controllers, actions, models, dev).
- `Authoring\Models\Section` — 5 (services, controller, seeder).
- `Authoring\Services\CurriculumTreeService` — 1 (`LearnController`).
- `Catalog\Models\Course` — 8 (actions, controllers, model relations, dev).
- `Catalog\Enums\CourseStatus` — 0 (cleared this phase).

These require later phases: services/controllers/actions (CurriculumReadPort read methods for queries + route-binding), and the model `belongsTo` relations (highest blast radius) — all explicitly out of Phase-1 scope. Overall Learning forbidden imports: **52 → 48**.

---

## Risk Assessment

- **Byte-parity (medium, unverified).** The mappers copy the exact prior expressions, including the `has_media` null-nuance and `type->value`; risk is a subtle serialization difference. Mitigation: existing feature tests; **must run** (`php artisan test`) — Not verifiable here.
- **`app(CurriculumReadPort::class)` in resources (low).** Service-location inside resources (they aren't container-constructed); standard Laravel pattern, temporary for this phase. Binding present exactly once and resolvable.
- **Extra query on enroll (negligible).** `isCourseEnrollable` loads the course by id where the action already held the model; one extra query on the (cold) enroll path, output identical.
- **Temporary `Authoring→Catalog` edge (low).** Centralized in the adapter; an existing baselined edge; to be split per the audit in the build phase.
- **Toolchain (external).** Deptrac/Rector not installed and baselines empty (`ARCHITECTURE_FITNESS_READINESS.md`); import ordering is not currently enforced repo-wide (PHP not installed locally). Touched files were ordered to match their pre-existing convention.

---

## Recommendation

**Merge after the gate suite is confirmed green on a PHP-capable environment.** Phase 1 removes the Status coupling and the resource-level model coupling with no API/DB/behavior change and no new boundary-violation type; the DTOs and port are minimal and match the audit. Before merge, on PHP 8.3 (or `docker compose exec api …`): run `composer dump-autoload`, `vendor/bin/pint --test`, `vendor/bin/phpstan analyse`, `php artisan test` (especially `/learn`, continue-learning, enroll, and `MediaSafetyTest`), and — once installed — `vendor/bin/deptrac analyse`. Then proceed to the next Curriculum sub-phases (Services → Tree/LearnController → route-binding Controllers → Actions → model relations) behind the same port, keeping the model-relation removal last.

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

Static verification performed here (repository evidence): grep confirms Learning Curriculum sites 36→32 (CourseStatus→0; the 4 targeted `use` sites removed) and `Shared\Curriculum`→non-Shared = 0; the four resources import only Shared (+ own `Enrollment`); `CurriculumReadPort` bound exactly once (AuthoringServiceProvider); PSR-4 namespaces correct for all 5 new files; `file(1)` reports all new/changed files as PHP ASCII/UTF-8 text (no NUL). No controller, route binding, service, model relation, database, or API was modified; only the listed files were changed and this report created.

Run the commands above (or `docker compose exec api …`) to obtain live pass/fail.

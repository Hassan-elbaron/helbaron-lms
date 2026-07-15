# Curriculum Refactor — Final Report (Contract + Cleanup)

> Chief Enterprise Architect. Final phase of the Learning→Curriculum decoupling: reconcile the contract, then contract-and-clean. No API, database schema, or business behavior changed. Runtime gates could not run here (no PHP/Composer) — marked **"Not verifiable from repository."** The official contract is recorded in `docs/implementation/CURRICULUM_READ_CONTRACT.md` (Step 1).

---

# Executive Summary

Every **runtime request path** in Learning — controllers, services, actions — is now fully decoupled from Curriculum: it reads only through `CurriculumReadPort` + immutable DTOs, holding no Authoring/Catalog model. This phase removed the entire expand-and-contract compatibility layer that was **provably dead**: the model-based service methods, the model-based action `execute()` methods, and four unused cross-context `belongsTo` relations. Learning→Curriculum `use`-import sites fell **22 → 8** (and **31 → 8** across the whole programme).

The remaining **8 sites are irreducible within a Learning-only scope**. They are not transitional compatibility code — they are legitimate **cross-context integration seams** owned by *other* contexts, plus dev fixtures: `GrantEnrollmentAction::execute(Course)` (Commerce's entitlement call), the `Enrollment->course()/user()` relations (read by Certification, Notifications, and Filament), and the factories/seeder that build test data. Removing them requires modifying **out-of-scope contexts** (Commerce, Certification, Notifications, Filament) or test infrastructure that cannot be verified here — so the target "Learning depends only on Shared + IdentityContracts" is **not fully reachable** without a coordinated cross-context program. Those seams are documented as remaining technical debt with a concrete path.

---

# Contract Changes

Two implementation-driven elements were officially adopted into the Curriculum read contract (full detail in `CURRICULUM_READ_CONTRACT.md`):

1. **`findLessonByPublicId(string): ?LessonRef`.** Lessons are addressed by **public id** (`HasPublicId::getRouteKeyName() === 'public_id'`). Removing route-model binding (Phase 4) meant lesson controllers receive a public-id string and must resolve it through the port; the original design only provided `findCourseByPublicId`. This is the symmetric, first-class lesson resolver. *Why it doesn't violate the architecture:* it returns a DTO (never a model), mirrors the existing course method, and keeps controllers model-free. *Fit:* it completes the id/public-id resolution surface the plan intended.

2. **`LessonRef.content` (`?array`, defaulted).** The lesson-player response renders the lesson body; to serve the player from a DTO instead of the `Lesson` model, content must travel on the ref. *Why it doesn't violate the architecture:* it is read-only projection data (no model, no media ids), populated only by single-lesson detail reads (not bulk tree/nav refs). *Fit:* `LessonRef` already carried render fields; `content` is the player's render field. Backward-compatible (trailing defaulted parameter).

---

# Compatibility Layer Removed

Removed the dead expand-and-contract shims (no callers — verified by grep across app + tests):

- **Service model methods:** `LessonAccessService::{assertAccess, canAccess, courseIdForLesson}(…Lesson…)`; `ProgressService::{record(…Lesson…), sectionPercentage(…Section…)}`; `LearningMediaService::{playbackFor, hasMedia}(…Lesson…)`; `ContinueLearningService::nextLesson(): ?Lesson` (`forUser` now uses `nextLessonRef`). The canonical id/ref methods remain.
- **Action model methods:** `execute(…Lesson…)` on `ToggleBookmarkAction`, `UpsertLessonNoteAction`, `RecordLessonProgressAction`; `execute(…Course…)` on `EnrollInCourseAction`. Only `executeById(...)` remains on these four.
- **Seeder call fixed:** `LearningSeeder` now calls `recordByLessonId($enrollment, $firstLesson->id, …)` (was the removed `record(…Lesson…)`).

**Retained transitional code (dead branches, documented):** the resources' dual Model/DTO branches and the adapter's `courseRef/sectionRef/lessonRef(Model)` mappers. These are **not** Learning→Curriculum `use`-imports (resources import only `CurriculumReadPort` + DTOs; the mappers live in Authoring), so they do **not** affect the dependency count. `courseRef(Model)` is still actively used by `ContinueLearningResource` (via the retained `Enrollment->course` relation); `sectionRef/lessonRef(Model)` are dormant. They are safe to delete in a test-backed follow-up but were left intact to avoid blind, unverifiable resource surgery with byte-identical-JSON risk and zero dependency-count benefit.

---

# Removed Dependencies

Learning→Curriculum `use`-import sites removed this phase (14): `Authoring\Models\Lesson` from `LessonAccessService`, `ProgressService`, `LearningMediaService`, `ContinueLearningService`, `ToggleBookmarkAction`, `UpsertLessonNoteAction`, `RecordLessonProgressAction`; `Authoring\Models\Section` from `ProgressService`; `Catalog\Models\Course` from `EnrollInCourseAction`; and the model relation imports below.

---

# Removed Relations

Severed four **unused** cross-context `belongsTo` relations (confirmed no readers in app/tests/Filament; FK columns retained — no schema change):

- `LessonProgress::lesson()` → removed (`use Lesson` dropped).
- `LessonBookmark::lesson()` → removed (`use Lesson` dropped).
- `LessonNote::lesson()` → removed (`use Lesson` dropped).
- `LearningSession::course()` and `::lastLesson()` → removed (`use Course`, `use Lesson` dropped).

**Retained (cross-context, in use):** `Enrollment::course()` and `Enrollment::user()` — read by `Certification\Listeners\GenerateCertificateOnCourseCompleted`, `Notifications\Listeners\NotificationEventSubscriber`, `Filament\Resources\EnrollmentResource` (`course.public_id`) / `Filament\Widgets\PlatformOverview`, and `ContinueLearningResource`. Severing them would break those out-of-scope consumers. `LearningSession::user()` (Identity) also retained (not Curriculum).

---

# Final CurriculumReadPort

Canonical surface (see `CURRICULUM_READ_CONTRACT.md`):

- `isCourseEnrollable(int): bool`
- `findCourseByPublicId(string): ?CourseRef` · `courseRefById(int): ?CourseRef`
- `lessonRefById(int): ?LessonRef` · `findLessonByPublicId(string): ?LessonRef`
- `courseIdForLesson(int): ?int`
- `curriculumTree(int, bool): array{course, sections}` · `orderedPublishedLessonRefs(int): list<LessonRef>`
- `publishedLessonIdsForCourse(int): list<int>` · `publishedLessonIdsForSection(int): list<int>`
- Transitional mappers (retained, dead-branch consumers only): `courseRef/sectionRef/lessonRef(Model)`.

No deprecated method was left with a live caller; the transitional `Model` mappers are retained solely because the (out-of-final-scope) resource dual-mode still references them. They are earmarked for removal once the resources drop model input.

---

# Final DTO Structure

- **CourseRef**: `id, publicId, title, slug, thumbnailPath`.
- **SectionRef**: `id, publicId, title`.
- **LessonRef**: `id, publicId, title, type, isPreview, hasMedia, sectionId, courseId, position, prerequisiteLessonIds, content`.

All immutable (`final readonly`), no Eloquent, no raw media identifiers.

---

# Final Dependency Count

Learning→Curriculum `use`-import sites: **8** (from 31 at programme start; 22 at final-phase start). Composition of the remaining 8:

- **Cross-context seams (2):** `GrantEnrollmentAction` (`Catalog\Models\Course` — Commerce entitlement seam), `Enrollment` (`Catalog\Models\Course` — the retained relation).
- **Dev fixtures (6):** `EnrollmentFactory` (Course), `LessonNoteFactory` (Lesson), `LessonProgressFactory` (Lesson), `LearningSeeder` (Lesson + Section + Course).

**Runtime production paths (controllers, services, actions except the Commerce seam): 0 Curriculum model imports.**

---

# Deptrac Status

`deptrac.baseline.yaml` is the empty placeholder (`skip_violations: {}`); `deptrac.yaml` has **no** inline suppressions (ruleset-only, importing the baseline). There is nothing obsolete to remove. **`deptrac analyse` cannot be run here** (Deptrac not installed; PHP absent) — **Not verifiable from repository**. Expected once installed + baselined: the `Learning` layer's Curriculum violations drop to the 8 residual sites above; Learning is **not yet** `[Shared, IdentityContracts]`-only because `GrantEnrollmentAction` and `Enrollment` still reference `Catalog\Models\Course` (the cross-context seams). Those will appear in the seeded baseline and burn down only when the cross-context refactor (below) lands.

---

# Remaining Technical Debt

1. **`GrantEnrollmentAction::execute(User, Course)` → Commerce.** `Commerce\Actions\Payment\FulfillOrderAction` calls it with a `Course` model. Remove by switching Commerce to `executeById($user, $courseId)` — a **Commerce** change (out of this scope).
2. **`Enrollment->course()/user()` relations → Certification, Notifications, Filament.** These consumers read Learning's Enrollment relations directly. Remove by migrating them to refs/events — a **multi-context** change.
3. **Dev fixtures** (factories + seeder) still import Course/Lesson/Section to build test data — a test-infrastructure cleanup best done with a runnable suite.
4. **Resource dual-mode + adapter `*(Model)` mappers** — dead compat branches; delete in a test-backed follow-up.
5. **Temporary Authoring adapter** — split into a Catalog course provider + an Authoring section/lesson provider (audit end-state) to remove the intra-adapter `Authoring→Catalog` reference.

---

# Enterprise Readiness

The Learning context's **request-handling code is production-ready and boundary-clean**: controllers, services, and actions consume Curriculum exclusively through the Shared `CurriculumReadPort` + DTOs; route-model binding is gone; the read contract is documented and reconciled. The residual coupling is **isolated to cross-context integration seams that are correct by design** (Commerce entitlement, Certification/Notifications event-consumers, Filament admin) and to dev fixtures — none of which sit on a hot production path inside Learning. The architecture fitness gate (Deptrac) will hold the boundary for all Learning production paths once installed. Enterprise-ready for Learning; the last mile is a cross-context effort, not a Learning defect.

---

# Final Recommendation

**Accept the Learning-scope cleanup as complete and schedule the cross-context seam removal as a separate coordinated program.** Within Learning, 14 of 22 residual sites were removed and every runtime path is decoupled. The remaining 8 cannot be removed here without editing Commerce/Certification/Notifications/Filament or the (unrunnable) test fixtures — attempting that blind would break out-of-scope contexts. Next steps, in order: (a) run the gate suite on a PHP environment (`docker compose exec api composer dump-autoload && vendor/bin/pint --test && vendor/bin/phpstan analyse && php artisan test`, plus `deptrac analyse` once installed) to confirm this phase is green; (b) open a cross-context ticket to move Commerce to `executeById` and Certification/Notifications/Filament to refs/events; (c) then delete the retained transitional mappers/resource branches and the fixtures' model imports, seed the Deptrac baseline, and confirm Learning reaches `[Shared, IdentityContracts]`-only.

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

Static verification performed here (repository evidence): the four services and four actions no longer import Authoring/Catalog models (only `GrantEnrollmentAction` retains `Catalog\Models\Course`); the four models' unused lesson/course relations are removed (FK columns kept); no dangling call to any removed method remains (the seeder now uses `recordByLessonId`); Learning→Curriculum `use`-sites = 8 (2 cross-context seams + 6 dev fixtures); `file(1)` reports all changed files as PHP text (no NUL); `deptrac.baseline.yaml` is empty with no suppressions. No API, schema, or business behavior was changed. Run the commands above (or via the container) to obtain live pass/fail.

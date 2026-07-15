# Demo-Data Consistency Report — HElbaron LMS

**Date:** 2026-07-15
**Scope:** Fix the seeder inconsistency where courses were seeded **Published** without a publishable curriculum, add regression tests, and remove residual QA data.

## Root cause

`app/Domains/Catalog/Database/Seeders/CatalogSeeder.php` created its 12 "one published course per vertical" records with `status = CourseStatus::Published` **and attached only categories + trainers — no sections and no lessons**. The publish invariant (`App\Domains\Authoring\Services\CurriculumValidator::validateForPublish`) requires **≥1 section** and **≥1 published lesson** (and no cross-course prerequisites). So those courses were in a state the product's own publish action forbids (`CATALOG_COURSE_PUBLISH_BLOCKED — "The course has no sections."` / `"…no published lessons."`).

This is exactly what the Commerce/instructor QA surfaced (DATA-01): "Business AI for Decision Makers" and "Essential Business Skills" were Published with 0 sections, so once unpublished they could not be re-published through the UI. ("Project Management Foundations" happened to have curriculum from other seeding, which is why only some were affected.)

## Fix

`CatalogSeeder` now guarantees the invariant for every course it marks Published:

1. **`seedMinimalCurriculum(Course)`** — attaches one **Published** section ("Getting Started") with **two Published lessons** (a preview video "Welcome & Course Overview" + an article "Core Concepts"). Idempotent: it no-ops if the course already has sections.
2. **Draft fallback** — after seeding curriculum, `hasPublishableCurriculum(Course)` re-checks (≥1 section + ≥1 published lesson); if it is somehow not satisfied, the course is forced to **Draft** (`published_at = null`) rather than left as an un-publishable "Published" record. This encodes the rule: *no course is ever Published without a valid curriculum; otherwise it is Draft.*

Net effect: all 12 catalog courses are now legitimately Published (each with 1 section + 2 published lessons), and the invariant is impossible to violate from this seeder.

### Files changed
- `app/Domains/Catalog/Database/Seeders/CatalogSeeder.php` — added `App\Domains\Authoring` imports (Section, Lesson, LessonType, PublishState); added `seedMinimalCurriculum()` + `hasPublishableCurriculum()`; wired them into the course loop with the Draft fallback.
- `tests/Feature/Catalog/CatalogSeederPublishInvariantTest.php` — **new** regression tests (below).

## Regression tests (new)

`tests/Feature/Catalog/CatalogSeederPublishInvariantTest.php` (Pest + `RefreshDatabase`) proves:

1. **Published ⇒ publishable.** Every `Published` course after seeding has ≥1 section and ≥1 published lesson, **and** `CurriculumValidator::validateForPublish()` returns `[]` for it (the app's own guard accepts it).
2. **Content-less ⇒ Draft.** Any course lacking sections or a published lesson is `Draft`, never `Published`.
3. **Idempotent + deterministic.** Re-running the seeder does not change the course/section/lesson counts, and each seeded published course has exactly one section with exactly two lessons.

**Run on the host** (this QA environment has no PHP; the Docker `api` container does):
```
docker compose exec api php artisan test --filter=CatalogSeederPublishInvariant
```
The code and tests were statically verified against the models (`Course` casts `status` → `CourseStatus`; `Section` fillable = course_id/title/summary/position/publish_state; `Lesson` fillable includes type/content/publish_state/is_preview; `Lesson::published()` filters `publish_state = Published`), so the assertions match the real schema.

## Residual QA data removal

Three residual items from earlier QA live on the local demo DB and are all cleared by a reseed (which also applies the fix above, so the courses come back Published **with** curriculum):

- **"ZZ QA Temp Course delete me"** — temp draft course created during the Filament authoring audit.
- **"QA Test Announcement"** — test announcement on "Project Management Foundations".
- **"Business AI for Decision Makers"** — was left Draft after the instructor unpublish test; the reseed restores it to Published (now with a valid curriculum).

Run on the host:
```
docker compose exec api php artisan migrate:fresh --seed
```

After this, DATA-01 is resolved: no course is Published without a valid curriculum, and the residual QA records are gone.

# Database & Backend Review (Audit)

**Date:** 2026-07-16 · **Scope:** `apps/api` (Laravel 12, DDD). Evidence-based; no changes applied.

## Current state (mature, disciplined)
Clean hexagonal DDD: `Contexts/*`, `Domains/*`, `Platform/*`; thin controllers → Actions/Services; cross-context via Ports/Adapters + DTOs; **33 FormRequests, 22 Policies**. Indexing is generally excellent — `public_id` is `uuid()->unique()` via the `publicId()` macro; FKs auto-index via `constrained()`; good composites on `enrollments`, `courses`, `lesson_progress`, `orders`, `notifications`. Main list endpoints eager-load and paginate; notification delivery, OTP, and analytics exports are queued.

## Problems found (prioritized, evidence)
| # | Severity | Finding | Evidence |
|---|---|---|---|
| DB-1 | High | **Synchronous, unbounded notification fan-out** on course announcements — loads every enrolled student and loops synchronously (2 SELECTs + template render + txn per user). Request time scales linearly with enrollment; large courses can time out. | `Domains/Catalog/Http/Controllers/Api/V1/Instructor/AnnouncementController.php:50-65` → `Platform/Notifications/Actions/BulkNotificationAction.php:22-28` → `NotificationDispatcher.php:29-64` |
| DB-2 | Med | **N+1 speaker lookup in public Events listing** — `refsByIds` called per event row (each a `whereIn` query); ~12–24 extra queries per page | `Domains/Live/Http/Resources/EventListResource.php:48-59`, `Platform/Identity/Adapters/UserLookupAdapter.php:66`; correct batch pattern exists in `Instructor/StudentController.php:26-35` |
| DB-3 | Med | **Certificate PDF rendered synchronously in-request** (Browsershot/Chromium) on first download — blocks a PHP-FPM worker for the full render (idempotent/cached after) | `Domains/Certification/Http/Controllers/Api/V1/CertificateFileController.php:26` → `EnsureCertificatePdfAction.php:24`; `CertificateIssued` event already exists (`GenerateCertificateAction.php:62`) |
| DB-4 | Low | **Unbounded `->get()` list endpoints** (naturally per-user/per-course bounded, but uncapped) | `MyLearningController.php:16-20`, `MyCertificatesController.php`, `Instructor/AnnouncementController.php:22-26` |
| DB-5 | Low | **`course_trainer` pivot lacks a unique `(course_id,user_id)`** — allows duplicate trainer links | `Domains/Catalog/Database/Migrations/2025_01_02_000170_create_course_trainer_table.php` |
| DB-6 | Low | **`courses.published_at` (list ORDER BY key) not indexed** — sort not index-backed on large catalogs (`is_featured DESC, published_at DESC`) | `CourseSearchService.php:38-39` |

## Changes applied
None (audit). All queued in the backlog (implementation phase H).

## Remaining opportunities
DB-1: convert fan-out to a single queued job that chunks recipients + batch-loads locale/preferences. DB-2: batch speaker IDs in the controller (reuse the StudentController pattern). DB-3: pre-generate the PDF on `CertificateIssued` via a queued listener. DB-4: add `->paginate()` caps. DB-5/DB-6: additive migrations (unique index; composite index) — each measurable via `EXPLAIN` and query-count assertions.

# Demo Environment Guide

A deterministic, idempotent, production-safe demo dataset for the HElbaron LMS — populates every product area so the platform looks complete for investor/sales demos, client presentations, QA, screenshots, product videos, and usability testing.

Related: `docs/demo/DEMO_CONTENT_SOURCING_POLICY.md` (content licensing rules) · `config/demo.php` (configuration) · `database/seeders/DemoSeeder.php` · `app/Console/Commands/SeedDemo.php`.

## Scale profiles

Volume is config-driven via `DEMO_SCALE` (see `config/demo.php` → `profiles`):

- **`showcase`** (default) — modest, fast, CI/validation-safe. The counts in the table below.
- **`enterprise`** — a large, realistic dataset that makes every dashboard, report, chart, table, filter, and paginated list look like a platform that has operated for years: ~600 students, ~16 instructors, ~56 courses (14 blueprints × 4 cohort editions), ~350+ sections, ~700–1200 lessons, thousands of enrollments, tens of thousands of lesson-progress rows, ~900–1500 certificates (issued / revoked / reissued), ~1500 orders (paid / pending / refunded / cancelled, back-dated 12+ months for revenue trends), ~1000 invoices, 20–30 coupons + redemptions + ~100 refunds, 40 organizations with departments / teams / seat pools / members, 60 live sessions (upcoming / completed / cancelled) with registrations + waitlists + attendance + recordings, CRM (250 companies / 1000 contacts / 800 leads / 400 opportunities / 5000 activities / 4000 notes), 6000+ notifications, 25+ email templates, 365 days of daily analytics snapshots, and ~20000 audit records.

```bash
DEMO_SCALE=enterprise php artisan demo:seed
```

**Runtime:** the `enterprise` profile writes tens of thousands of rows and needs a raised PHP `memory_limit` and more than the CI time budget — run it on a **provisioning host**, not in CI. Keep `DEMO_SCALE=showcase` for tests/CI. The enterprise code path is validated end-to-end (at a representative cap) and is idempotent; the full-scale run's wall-clock/memory is environment-dependent.

## What the repository supports (and what it does not)

The demo is faithful to the real schema — it never fabricates tables or columns:

- **Quizzes** are represented as lessons of type `quiz_placeholder` (there is no separate Quiz model).
- **Assignments** and **course reviews/ratings** are **not supported** by the repository (no such models) and are therefore not seeded.
- **Analytics "events"** are stored as aggregate values in daily `MetricSnapshot` rows (there is no raw events table); cumulative totals read in the hundreds of thousands.
- **Refunds** are `PaymentTransaction` rows of type `refund` produced by the real `RefundOrderAction` (there is no separate Refund model).
- **Media assets** are `LessonMedia` metadata rows (Mux playback id + S3 key placeholders) attached to video lessons — no binary files are stored.

## What it seeds (showcase profile)

Running once at `showcase` produces (deterministic, `DEMO_SEED=20260711`):

| Area | Content |
|---|---|
| Identity | 6 instructors, 24 students (all `@demo.helbaron.local`, password `password`), profiles + roles |
| Catalog | 14 bilingual (EN + AR) published courses across Technology / AI / Marketing / Business verticals, with categories, tags, levels, language, trainers, SEO (Arabic copy in `seo.ar`) |
| Authoring | 28 sections, 84 published lessons (video + reading; video lessons carry an external embed reference only when `DEMO_EXTERNAL_MEDIA=true`, always with an original reading fallback) |
| Learning | 89 enrollments with varied progress, 216 lesson-progress records, bookmarks + notes; completed courses auto-issue certificates |
| Certification | 24 issued certificates (via the real `CourseCompleted` → issuance listener) |
| Commerce | 13 products + prices, 2 coupons, 36 orders (paid / pending / refunded mix), 36 order items, 31 invoices |
| Live | 1 live course, 2 scheduled sessions, 16 registrations |
| CRM | 1 organization, 5 members, 6 leads, 2 consulting requests (+ the seeded sales pipeline & stages) |
| Notifications | 161 in-app notifications from the seeded templates |
| Analytics | 180 daily metric snapshots (30 days × 6 metrics) so dashboards/charts render |
| Audit | audit-trail markers |

All content is **original / generated**; no copyrighted third-party content ships. External media is opt-in and placeholder-only until verified (see the sourcing policy).

## Demo accounts (password: `password`)

- **Super admin:** `admin@helbaron.local` (from the base `IdentitySeeder`; admin panel at `/admin`).
- **Instructors:** `yara.adel@demo.helbaron.local`, `omar.farouk@…`, `nour.hassan@…`, `laila.mansour@…`, `karim.saleh@…`, `huda.rashid@demo.helbaron.local`.
- **Students:** `student01@demo.helbaron.local` … `student24@demo.helbaron.local`.

## How to run

Demo mode is **off by default**. Enable it in the environment, then run the command:

```bash
# .env (never in production)
DEMO_MODE=true
DEMO_SEED=20260711          # deterministic; change for a different but reproducible dataset
DEMO_EXTERNAL_MEDIA=true    # false = video lessons use their reading fallback only
DEMO_RESET_ALLOWED=false    # set true only to permit a destructive reset

php artisan demo:seed
```

- **Idempotent:** safe to run repeatedly — every record is upserted on a stable key (`@demo.helbaron.local` email, `demo-` course/product slug, `DEMO…` coupon code, `user_id+course_id` enrollment, etc.). Re-running produces identical counts (verified).
- **Reset (destructive):** `php artisan demo:seed --reset` purges demo-marked records (FK-safe) and reseeds. Requires `DEMO_RESET_ALLOWED=true` **and** a non-production environment.
- **Not wired into `DatabaseSeeder`** — it only runs via this explicit command.

## Safety rails

- Refuses to run in the `production` environment, always (regardless of `DEMO_MODE`).
- Refuses unless `DEMO_MODE=true` (`config('demo.enabled')`).
- `--reset` additionally requires `DEMO_RESET_ALLOWED=true`.
- Demo records are unmistakably identifiable via the `@demo.helbaron.local` email domain and `demo-` / `DEMO` prefixes, so they never mingle with real data. No schema column was added for demo data.

## Content & licensing

External media (video embeds) is optional and, as shipped, **placeholder-only** — no third-party asset is asserted as licensed. To use real embeds, follow the verification and attribution steps in `docs/demo/DEMO_CONTENT_SOURCING_POLICY.md` and update the `media` manifest in `config/demo.php`, then re-run `demo:seed`.

## Validation status

Validated against PostgreSQL 16:
- **Showcase:** seeded twice with identical counts (idempotent), reset-and-reseed identical, production/disabled runs correctly refused.
- **Enterprise:** the full code path validated end-to-end at a representative cap (e.g. 200 students → 28 courses, 435 lessons, 1378 enrollments, 10,334 lesson-progress, 349 certificates, 500 orders, 35 refunds, 20 orgs / 213 members, 30 live sessions / 774 registrations / 379 attendances, CRM 120/400/300/150/1500/1200, 1239 notifications, 25 email templates, 2920 metric snapshots, 5001 audit rows) — completed and re-ran idempotently. The full-scale run is environment-dependent (raise PHP `memory_limit`, allow more wall-clock on a provisioning host).
- **Gates green:** Pint (all files), PHPStan 0 errors, Deptrac 0 violations, Pest 160/160 — the demo seeder is gated and not wired into `DatabaseSeeder`, so tests are unaffected.

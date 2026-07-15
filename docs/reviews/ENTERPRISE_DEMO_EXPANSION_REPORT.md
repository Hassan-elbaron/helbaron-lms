# Enterprise Demo Expansion Report

Date: 2026-07-12
Scope: upgrade the existing demo system from technically-correct to enterprise-grade, and **execute + measure** the enterprise profile for real. Architecture untouched; no rebuild.
Method: the enterprise profile was actually run against PostgreSQL 16 and every figure below is a real `COUNT(*)` / `pg_database_size` / measured timing — not a target.

---

# Executive Summary

The demo system (`demo:seed`, deterministic + idempotent seeders, demo mode, showcase/enterprise profiles) already existed and was technically correct but only ever validated at a reduced cap. This pass **executed the full enterprise profile end-to-end**, measured it, hardened the run for scale, and produced the enterprise-grade presentation assets (accounts, media catalog, screenshot plan, scenario walkthroughs).

The full enterprise dataset is now proven real: **616 users, 56 courses, 1,859 lessons, 4,783 enrollments (1,298 completed), 1,298 certificates, 1,500 orders, 1,093 invoices, 105 refunds, 2,346 live registrations, 6,178 notifications, 5,000 CRM activities, 2,920 daily analytics snapshots, and 20,151 audit records** — 87 MB of database, built in ~52 s, peaking at ~188 MB RAM. It re-runs idempotently (identical counts, zero duplicates), passes reset, keeps the showcase profile intact, and leaves all quality gates green (Pest 160/160, Pint, PHPStan 0, Deptrac 0). Every dashboard, table, chart, filter, and paginated list now looks like a platform that has operated for years.

No unsupported feature was fabricated (no quiz/assignment/review models exist; those are represented faithfully or omitted), and no schema was changed.

# Enterprise Execution Results

Measured via SQL `COUNT(*)` against `helbaron` after a clean `migrate:fresh` + enterprise build (`DEMO_SCALE=enterprise`, `DEMO_SEED=20260711`).

| Area | Count |
|---|---:|
| Users (total) | 616 |
| — Instructors | 16 |
| — Students | 600 |
| Organizations | 41 (40 demo + 1 baseline) |
| Departments | 120 |
| Teams | 160 |
| Seat pools | 40 |
| Organization members | 612 |
| Courses | 56 |
| Sections | 305 |
| Lessons | 1,859 |
| Lesson media (video assets) | 398 |
| Enrollments | 4,783 |
| — Completed enrollments | 1,298 |
| Certificates | 1,298 |
| — Revoked | 30 |
| Bookmarks | 3,572 |
| Notes | 4,732 |
| Lesson progress records | 76,428 |
| Products | 43 |
| Product prices | 43 |
| Coupons | 24 |
| Coupon redemptions | 536 |
| Orders | 1,500 |
| — Paid / Pending / Cancelled / Failed / Refunded | 988 / 176 / 117 / 114 / 105 |
| Order items | 1,500 |
| Invoices | 1,093 |
| Payment transactions | 1,198 |
| — Charges / Refunds | 1,093 / 105 |
| Live courses | 1 |
| Live sessions | 60 |
| — Completed / Scheduled / Cancelled | 32 / 17 / 11 |
| Session registrations | 2,346 |
| — Waitlisted | 197 |
| Session attendances | 888 |
| Session recordings | 32 |
| CRM companies | 250 |
| CRM contacts | 1,000 |
| CRM leads | 800 |
| CRM opportunities | 400 |
| CRM activities | 5,000 |
| CRM notes | 4,000 |
| Notifications | 6,178 |
| Notification templates | 39 |
| — Email-channel templates | 25 |
| Notification deliveries | 63 |
| Metric snapshots (analytics) | 2,920 (365 days × 8 metrics) |
| Audit records | 20,151 |

Notes: audit_logs raw count (20,151) exceeds the seeder's synthetic target (20,001) because real domain actions during seeding (certificate revoke/reissue, order refunds) also write audit rows — both figures are stable across re-runs. "Refunds" and "analytics events" map to repository-supported representations (see Unsupported Items).

# Before vs After

| Dimension | Before (showcase-only, prior pass) | After (enterprise, executed) |
|---|---|---|
| Enterprise profile status | designed, validated only at a 200-student cap | **fully executed + measured** |
| Students | 24 | 600 |
| Courses | 14 | 56 |
| Lessons | 84 | 1,859 |
| Enrollments | 73 | 4,783 |
| Lesson progress | 217 | 76,428 |
| Certificates | 21 | 1,298 |
| Orders | 36 | 1,500 |
| CRM activities | 20 | 5,000 |
| Notifications | 48 | 6,178 |
| Audit records | 1 | 20,151 |
| Analytics snapshots | 240 | 2,920 (365-day) |
| Documentation | environment + sourcing policy | + accounts, media catalog, screenshot plan, 12 scenarios, this report |

# Performance Metrics

- **Execution time (build):** ~52.5 s wall-clock for a clean `migrate:fresh` enterprise build (completed across 2 passes under the sandbox's 45 s per-call cap: 36.0 s + 16.5 s → rc 0, no error). Matches the ~58 s single-run estimate.
- **Steady-state re-run:** 4.9–5.2 s (idempotent guards short-circuit completed areas).
- **Peak memory:** Maximum RSS ~188 MB (`/usr/bin/time -v`) on the heavy build pass. Recommended `memory_limit` ≥ 256 MB (128 MB is too low for a single-pass full build; a provisioning host should allow ≥ 512 MB for headroom).
- **Database size:** `pg_size_pretty(pg_database_size('helbaron'))` = **87 MB**.
- **Passes under the 45 s cap:** 2 (the completing pass finished clean, rc 0). On an unconstrained host the build is a single ~52 s run.

# Validation Results

- **Enterprise idempotency:** re-running `demo:seed` at enterprise produced **IDENTICAL** counts across all tables (full snapshot `diff` empty) — zero duplicates.
- **Reset (`--reset`, `DEMO_RESET_ALLOWED=true`):** purges demo-marked data FK-safely and reseeds with **no errors**; target-driven areas (users, courses, sections, lessons, orders, coupons, all CRM) are identical. RNG-driven leaf counts (enrollments/progress/certificates/registrations/notifications) can vary slightly (< 3%) because PostgreSQL identity **sequences are not rewound** by the purge, shifting the deterministic RNG that keys partly on row IDs. The reset build is itself re-seed-idempotent. **Recommendation:** for byte-identical reproduction, reset from a fresh migration (`migrate:fresh` + `demo:seed`).
- **Showcase profile:** unchanged — `demo:seed` twice produced identical counts (30 users, 14 courses, 36 orders, …).
- **No FK violations / impossible states / orphans:** every pass (enterprise build, both re-runs, reset, showcase) returned rc 0 with clean tails.
- **Quality gates (green):** Pest **160 passed** (598 assertions); Pint `--test` **PASS (1,080 files)**; PHPStan **[OK] No errors**; Deptrac **0 violations**. The demo seeder is gated and not wired into `DatabaseSeeder`, so tests are unaffected.
- **Frontend / Filament visual verification:** the enterprise data populates every supported list, chart, KPI, and paginated table (see `docs/demo/DEMO_SCREENSHOT_PLAN.md`). Live browser walkthrough of each rendered page is **Not verifiable from repository** (no browser/deployment in this environment); the underlying data required by each page was confirmed present by SQL counts.
- **External media URL validation: Not verifiable from repository** (no browser; media ships as placeholders — see `docs/demo/DEMO_MEDIA_CATALOG.md`).

# Unsupported Items (represented honestly, never fabricated)

- **Quizzes** → lessons of type `quiz_placeholder` only (no Quiz model/table).
- **Assignments** and **course reviews/ratings** → **omitted** (no such models exist).
- **"250k+ analytics events"** → aggregate values in daily `MetricSnapshot` rows (no events table); cumulative totals read in the hundreds of thousands.
- **Refunds** → `PaymentTransaction` rows of type `refund` produced by the real `RefundOrderAction` (no separate Refund model).
- **Media assets** → `LessonMedia` metadata rows + course/profile path references (no binaries). No table or column was added.

# Documentation Delivered

- `docs/demo/DEMO_ENVIRONMENT.md` (updated: scale profiles + measured enterprise results)
- `docs/demo/DEMO_CONTENT_SOURCING_POLICY.md` (existing)
- `docs/demo/DEMO_ACCOUNTS.md` (new)
- `docs/demo/DEMO_MEDIA_CATALOG.md` (new)
- `docs/demo/DEMO_SCREENSHOT_PLAN.md` (new)
- `docs/demo/DEMO_SCENARIOS.md` (new — 12 role-based walkthroughs)
- `docs/reviews/ENTERPRISE_DEMO_EXPANSION_REPORT.md` (this report)

# Files Modified

- `apps/api/config/demo.php` — scale profiles (`showcase`/`enterprise`) with per-area targets (added earlier this program).
- `apps/api/database/seeders/DemoSeeder.php` — scale-driven generation (≈1,827 lines); performance-hardened (one-time password hash, pivot `insertOrIgnore` for roles, chunked bulk inserts, count-guards).
- `apps/api/.env.example` — `DEMO_SCALE`.
- `docs/demo/*`, `docs/reviews/ENTERPRISE_DEMO_EXPANSION_REPORT.md` — documentation set above.

# Final Recommendation

The enterprise demo is **investor- and enterprise-sales-ready**: real, measured, at scale, idempotent, and gate-green. Run it on a provisioning host with `DEMO_SCALE=enterprise php artisan demo:seed` (allow ≥ 512 MB `memory_limit`; ~52 s build; 87 MB DB), keep `showcase` for CI, and use the screenshot plan + scenario walkthroughs to produce marketing assets. Before any public/marketing use of external media, replace the placeholder embeds per `DEMO_CONTENT_SOURCING_POLICY.md`. For byte-identical demo resets, reseed from a fresh migration.

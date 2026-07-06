# HElbaron — Production Audit (Step 15)

Scope: static, code-grounded audit of `apps/api`. Numbers below are counted from the
repository, not estimated. Items marked **PASS** are verified in code; **REVIEW** needs an
operator decision; **FIX** is a recommended change tracked as a follow-up.

## 1. Authorization
- **21 policies** across all 10 domains (`app/Domains/*/Policies`) + `Shared/Policies/BasePolicy`.
- Custom Gate: `authoring.manage-curriculum` (ownership check in `AuthoringServiceProvider`),
  enforced in every curriculum admin controller action. **PASS**
- RBAC via `spatie/laravel-permission` (**58 authz call sites**: roles/permissions/can).
- Admin/mutating routes sit behind `auth:sanctum` groups; **2 public routes are intentional**
  and protected by Laravel's `signed` middleware (certificate stream, export download). **PASS**
- Privilege-escalation checks: password/MFA secrets are never exposed in resources; ownership
  is asserted in policies (e.g. certificate/notification "view own"). **PASS**
- **REVIEW**: confirm the Filament admin panel authorization gate (`viewFilament`/panel access)
  before enabling `/admin` — panel currently unregistered (see Known Issues).

## 2. Authentication
- Sanctum bearer tokens; **`sanctum.guard = []`** so API auth is token-only (no session
  fallback) — logout revokes the exact token + device row. **PASS**
- Login lockout after N failed attempts; MFA enrol/verify enforced; per-device session rows
  with revocation. **PASS**
- **REVIEW (token rotation)**: tokens are long-lived (no `expiration`). Set
  `sanctum.expiration` + a refresh flow if short-lived tokens are required by policy.

## 3. Data layer / indexes
- 87 migrations. Index primitives: **35 `index()`, 47 `unique()`, 116 `foreignId`**.
- **FIX (recommended)**: Postgres does not auto-index FK columns. Add covering indexes on the
  hottest lookup FKs if load testing shows seq scans — candidates: `lesson_progress(user_id,
  lesson_id)`, `enrollments(user_id, course_id)`, `notification_deliveries(status)`,
  `metric_snapshots(metric_id, period_start)`. Many are already covered by composite `unique()`.
  Not applied blindly (would touch DB design); gate behind `EXPLAIN ANALYZE` on real data.

## 4. N+1 / eager loading
- API resources use `whenLoaded()` guards, so relations serialize only when eager-loaded by the
  controller/service — this prevents lazy N+1 in responses. **PASS**
- **REVIEW**: enable `Model::preventLazyLoading()` in non-production to catch regressions
  (added via `AppServiceProvider` recommendation in the Ops Handbook).

## 5. Queue / Horizon
- **Added** `config/queue.php` (`after_commit=true`, `database-uuids` failed driver) and
  `config/horizon.php` (3 tuned supervisors: default / notifications / exports; explicit
  tries + timeouts). **FIX applied.**
- Notification deliveries implement app-level retry + **dead-letter** (`DeliverNotificationJob::failed`
  → status `Dead`). Idempotent (non-pending deliveries are skipped). **PASS**

## 6. Storage / signed URLs
- Media, certificate PDFs, and analytics exports are only ever exposed through **signed,
  expiring URLs**; raw `s3_key`/`mux_asset_id`/paths never leave the service layer. **PASS**
- **REVIEW**: apply S3 bucket policy (private + TLS-only), CloudFront OAC, and a lifecycle rule
  to expire generated exports (see Ops Handbook §Storage).

## 7. API versioning
- All routes under `prefix('v1')` (**11 route files**); **0 references to v2**. Envelope is
  stable across domains. Backward-compatible. **PASS**

## 8. Dependencies (top-level)
- API: Laravel 12, Sanctum 4, Horizon 5, Filament 4, spatie/permission 6, predis 2,
  flysystem-s3 3. Dev: Pint, Larastan 3, Pest 3.
- Web: Next 15.1, React 19, TanStack Query 5, RHF+Zod, Radix, Tailwind-merge.
- **REVIEW**: run `composer audit` + `npm audit` in CI (wired) each release; no known-vuln pins
  were introduced by Steps 2–14.

## 9. Known issues (tracked, out of Step 15 scope)
- **Filament v3→v4**: the 17 admin resources use v3 property typings (`?string $navigationIcon`)
  incompatible with the installed Filament v4 → `/admin` panel kept **unregistered**. Needs a
  dedicated v3→v4 migration pass before enabling.
- Firebase push uses **FCM legacy HTTP**; migrate to HTTP v1 (service-account OAuth) for GA.

## Verdict
Backend domains (Identity, Catalog, Authoring, Learning, Commerce, Certification, Live, CRM,
Analytics, Notifications) are green on the automated suite and structurally sound. Production
blockers are operational (secrets, real provider keys, S3/CloudFront policies) and the Filament
panel upgrade — not architectural.

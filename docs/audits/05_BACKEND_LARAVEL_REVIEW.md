# HElbaron LMS — Backend / Laravel Implementation Review (05)

**Repository:** local working copy (`apps/api`, Laravel 12 + Filament v4 + PostgreSQL + Redis/Horizon + S3/CloudFront + Mux).
**Scope:** Backend implementation quality, correctness, security, production readiness ONLY. No frontend/UI/UX/product review.
**Assumes:** Reviews 01–04 exist; not repeated.
**Method:** Code inspection + quantitative sweeps: 81 models, 41 controllers, 80 actions, 31 form requests, 36 API resources, migrations, policies, transactions, indexes, eager-loading, and direct reads of the checkout, webhook, coupon, and certificate flows.
**Benchmark bar:** production Laravel SaaS (Medusa/Cal.com-grade correctness & security expectations).

---

## Executive Summary

This is a **strong, production-leaning Laravel backend** with disciplined domain implementation. The evidence is consistently good where it matters most for a commerce+learning platform:

- **Mass-assignment is safe everywhere:** all **81 models declare `$fillable`** (0 use `$guarded`, 0 unguarded).
- **Transactional integrity is taken seriously:** **73 files use DB transactions**; the checkout wraps order/invoice/coupon/contract creation in one transaction and **locks the coupon row (`lockForUpdate`)** to serialize redemption.
- **Webhook handling is production-grade:** `ProcessWebhookAction` **dedups by unique `event_id` (`firstOrCreate`)**, guards on `processed_at`, verifies signature (`parseWebhook` throws on bad signature), locks the order row, and only advances a `Pending` order — a correct idempotent state machine.
- **Authorization coverage is comprehensive:** **20 policies** cover every user-facing aggregate (Course, Order, Certificate, Enrollment, Lead, Organization, Contract, LiveSession, Lesson, Section, Product, Report, Export, Dashboard, User, Device, Badge, Category, Notification, ConsultingRequest).
- **Certificate integrity is well-designed:** `number` unique, `verification_code(32)` unique, **`unique(user_id, course_id)`** prevents duplicate issuance, and verification checks `isValid()` + signature + exposes revocation.
- **Indexing is real:** 35 `->index()`, 67 `->unique()`, 22 composite unique, 33 composite index, 116 FK constraints (e.g., `courses` indexes `slug` unique + `(status,visibility)` + `is_featured`).
- **Thin controllers:** 80 Actions vs 41 controllers — business logic lives in Actions/Services, not controllers.
- **Tests exist:** 51 feature + 19 unit (Pest).

The real risks are a **small, specific set** — not systemic:

1. **External payment API call is inside the DB transaction.** In `CheckoutAction`, `gateway->charge()` (line 93) runs inside `transaction()` (line 48) while holding the coupon `lockForUpdate`. A slow/hanging gateway holds row locks and a DB connection for the duration of an external HTTP call — a classic lock-contention / connection-exhaustion risk under load.
2. **Organization/tenant scoping is manual.** Only **2 files** use global scopes while **8 CRM models carry `organization_id`**. Isolation depends on developers remembering to add `where('organization_id', …)` on every query — a latent **cross-org data-leakage** risk.
3. **Queued jobs declare no resilience config.** The jobs and 79 notifications set **no `$tries`, `$backoff`, `$timeout`, or `$maxExceptions`** — external-provider deliveries (mail/SMS/push) have no explicit retry/backoff policy.
4. **No model observers / limited audit trail at the model layer.** **0 observers**; audit is via events/`HasAudit` trait — verify it captures the sensitive mutations (role changes, refunds, grants).

None of these are architectural; they're targeted hardening items before production traffic.

---

## Overall Backend Score

**7.8 / 10** — "production-leaning; a few specific hardening items between here and go-live."

| Category | Score | Justification |
|----------|-------|---------------|
| Laravel structure | 9.0 | Consistent domain layout, thin controllers, providers ordered |
| Domain implementation | 8.5 | Actions/Services boundaries clear |
| Models | 8.5 | 100% `$fillable`, casts, SoftDeletes on 18 |
| Migrations | 8.5 | Indexes/unique/composite/FK all present |
| API resources | 8.0 | 36 resources, envelope, media-safe |
| Controllers | 8.5 | Thin; logic in actions |
| Actions/Services | 8.5 | Well-separated, transactional |
| Validation | 7.5 | 31 form requests; coverage gaps on some endpoints |
| Authorization | 8.0 | 20 policies; manual org scoping is the gap |
| Filament | 7.0 | Broad resources; some admin gaps (see 01) |
| DB performance | 7.5 | Good indexes; N+1 to verify; ext call in txn |
| Security | 7.0 | Mass-assign safe, webhook idempotent; org scoping + token posture |
| Payment/checkout | 8.0 | Idempotent, gated, coupon-locked; ext-call-in-txn |
| Certificate/learning integrity | 9.0 | Unique constraints, signature, revocation |
| Queues/jobs | 6.0 | No tries/backoff/timeout |
| Cache | 5.0 | Underused (1 read-cache usage; see 04) |
| Logging/observability | 8.0 | JSON logs + correlation IDs |
| Testing | 7.5 | 70 tests; security/edge coverage to deepen |

---

## Laravel Structure Review — 9.0

Domain-per-folder with identical taxonomy; `bootstrap/providers.php` orders providers deliberately; routes/config/OpenAPI per domain. No logic in controllers. **No action needed**; only document the Action-vs-Service rule.

## Domain Implementation Review — 8.5

80 Actions (one-use-case orchestration) + Services (reusable logic) + Events/Listeners for cross-domain side effects. Fulfillment gating (enrollment only after payment+contract) is correctly modeled. Minor: Actions/Services responsibility line is by convention, not enforced.

## Model Review — 8.5

| # | Sev | Finding | Evidence | Recommendation |
|---|-----|---------|----------|----------------|
| MOD-1 | — | Strength: all models `$fillable` | 81/81 | Keep |
| MOD-2 | Low | SoftDeletes on 18/81 — confirm all financially/legally significant records (orders, invoices, certificates, contracts) are non-destructive | grep | Ensure hard-delete is blocked on audit-critical tables |
| MOD-3 | Med | 0 observers — model-level lifecycle hooks absent | grep | Add observers (or confirm events cover) for audit-critical mutations (role change, refund, grant, revoke) |
| MOD-4 | Low | Verify casts on money/JSON/enum columns are complete | models | Ensure minor-unit ints, enum casts, and encrypted casts (MFA secrets) are set |

## Migration Review — 8.5

Indexes (35), unique (67), composite unique (22) & index (33), FK constraints (116), `publicId()`, `seoColumns()`, `softDeletes()`, timestamps. Strong.

| # | Sev | Finding | Recommendation |
|---|-----|---------|----------------|
| MIG-1 | Med | Postgres does **not** auto-index FK columns; confirm high-traffic FK lookup columns (e.g., `enrollments.user_id`, `order_items.order_id`, CRM `organization_id`) have explicit indexes | Add indexes on hot FK/filter columns not already covered |
| MIG-2 | Low | Confirm partial/unique indexes account for soft-deletes (unique on `slug` should be `WHERE deleted_at IS NULL` if reuse after delete is intended) | Add partial unique indexes where soft-delete reuse is expected |

## API Review — 8.0

Versioned `/api/v1`, standard envelope (`ApiResponse`), correlation IDs, 36 resources, media-safe learner resources. Gap: pagination/filter/sort not standardized as a shared contract (see 04/API-2); some write endpoints may lack rate limiting beyond auth routes — verify.

## Controller Review — 8.5

Thin controllers delegating to Actions. Verify each controller method has an authorization check (`authorize()`/policy) — with 41 controllers and 20 policies, confirm no controller mutates without a gate.

## Action and Service Review — 8.5

Transactions used pervasively (73). Checkout/webhook/coupon flows are correct. **Primary issue: external I/O inside a DB transaction (CHK-1 below).**

## Validation Review — 7.5

| # | Sev | Finding | Evidence | Recommendation |
|---|-----|---------|----------|----------------|
| VAL-1 | Med | 31 Form Requests for 41 controllers + 80 actions → some write paths may validate inline or thinly | counts | Ensure every state-changing endpoint has a FormRequest with explicit rules (types, ranges, enum `Rule::enum`, existence) |
| VAL-2 | Med | Verify money/amount fields validated as integer minor-units with min/max, and coupon codes/quantities bounded | flows | Add strict numeric + boundary validation on commerce inputs |
| VAL-3 | Low | Confirm file-upload requests validate mime/size/dimensions | media endpoints | Enforce mimes/max on all uploads |

## Authorization Review — 8.0

20 policies covering all aggregates — strong. **Gap: org scoping is manual (2 global scopes vs 8 org-scoped models)** → AUTHZ-1. Also coarse roles (from 01) limit policy expressiveness for org/support/finance scopes.

| # | Sev | Finding | Risk | Recommendation |
|---|-----|---------|------|----------------|
| AUTHZ-1 | High | Manual tenant scoping | Cross-org data leakage if a query forgets the `organization_id` filter | Add a global scope / trait (`BelongsToOrganization`) auto-applying the tenant constraint; add tests asserting cross-org access is denied |
| AUTHZ-2 | Med | Confirm instructor/course ownership enforced on authoring/live mutations | Instructor editing another's course | Policy checks tying mutations to `course.owner_id`/trainer assignment |
| AUTHZ-3 | Med | Confirm Filament resources enforce per-record policies (not just panel access) | Admin-tier data exposure | Ensure `canViewAny/canEdit` map to policies on every resource |

## Filament Review — 7.0

Broad resource coverage (24) with panel access gate + admin MFA. Gaps: some product areas lack resources (see 01: homepage/landing/SEO/brand). Verify each resource applies model policies and scopes lists (e.g., an org-admin Filament user shouldn't see all orgs). Brand-align later (non-functional).

## Database Performance Review — 7.5

| # | Sev | Finding | Evidence | Recommendation |
|---|-----|---------|----------|----------------|
| PERF-1 | Med | N+1 risk on list endpoints; eager loading present but partial (20 `with()` usages; 1 `withCount`) | sweep | Add `with()`/`withCount()` on all list resources; add `assertQueryCount` tests; consider `Model::preventLazyLoading()` in non-prod |
| PERF-2 | Med | External `charge()` inside DB transaction holds locks during network I/O | `CheckoutAction` L48/L93 | Move gateway call outside the transaction (see CHK-1) |
| PERF-3 | Low | No read caching for hot catalog/KPIs (1 usage) | from 04 | Add cache seam + event invalidation |

## Security Review — 7.0

| # | Sev | Finding | Risk | Recommendation |
|---|-----|---------|------|----------------|
| SEC-1 | High | Manual org scoping (AUTHZ-1) | Tenant data leakage | Global tenant scope + tests |
| SEC-2 | Med | Auth token in SPA `localStorage` (from 04) | XSS token theft | Sanctum stateful cookies |
| SEC-3 | Med | Confirm MFA secret & OTP hashes are encrypted/hashed at rest | Credential exposure | Encrypted casts / hashed OTP |
| SEC-4 | Med | Verify rate limiting on auth, checkout, coupon-apply, verification endpoints | Brute force / coupon abuse | Named RateLimiters on sensitive routes (some exist — extend to commerce) |
| SEC-5 | Low | Mass assignment safe (strength) | — | Keep |
| SEC-6 | Low | Ensure webhook endpoint is CSRF-exempt + signature-only auth (it verifies signature — confirm no session auth bypass) | Spoofed webhooks | Keep signature verification mandatory |

## Payment and Checkout Review — 8.0

**Strengths:** transactional order creation; coupon `lockForUpdate` prevents over-redemption; enrollment gated to post-payment+contract; idempotent webhook (dedup by `event_id`, order lock, `Pending`-only advance); money in integer minor units; invoice numbering service.

| # | Sev | Finding | Risk | Recommendation |
|---|-----|---------|------|----------------|
| CHK-1 | High | `gateway->charge()` executes inside the DB transaction | Lock held during external HTTP; connection exhaustion; partial-state on gateway timeout | Create order/invoice/coupon within txn (status `Pending`), **commit**, then call the gateway; record the transaction result separately; reconcile via webhook |
| CHK-2 | Med | Confirm coupon validity (expiry, usage caps, per-user limits, scope) is re-checked under the lock, not only at cart time | Coupon abuse via race/expired coupon | Re-validate coupon inside the locked section before incrementing `redeemed_count` |
| CHK-3 | Med | Refund path: verify enrollment revocation + idempotency (RevokeEnrollmentsOnRefund listener exists) | Double refund / access after refund | Ensure refund is idempotent and revokes grants atomically |
| CHK-4 | Low | Verify currency consistency between cart, order, coupon, gateway | Mismatched-currency charge | Assert single currency per order |

## Certificate and Learning Integrity Review — 9.0

**Strengths:** `certificates.number` unique, `verification_code(32)` unique, `unique(user_id, course_id)` (no duplicate certs), signature verification + revocation status in verification service, auto-generation on `CourseCompleted`.

| # | Sev | Finding | Recommendation |
|---|-----|---------|----------------|
| CERT-1 | Med | Confirm `CourseCompleted` cannot be replayed to mint duplicate/forged certificates (guard on completion state + the unique constraint) | Idempotent issuance keyed on enrollment completion |
| CERT-2 | Low | Ensure verification endpoint is public but rate-limited and leaks no PII beyond necessary | Rate-limit + minimal payload |
| LRN-1 | Med | Verify lesson media/playback tokens require active enrollment + are short-lived/signed (Mux/CloudFront signers exist) | Enforce enrollment check before issuing playback token |

## Queue and Job Review — 6.0

| # | Sev | Finding | Evidence | Recommendation |
|---|-----|---------|----------|----------------|
| JOB-1 | Med | Jobs/notifications declare no `$tries`/`$backoff`/`$timeout`/`$maxExceptions` | grep = none | Set explicit retry policy + exponential backoff on `DeliverNotificationJob`, `ProcessExportJob`, and notification sends |
| JOB-2 | Med | Verify `failed()` handlers + `failed_jobs` monitoring + Horizon alerting | — | Implement `failed()` + dead-letter handling (NotificationDeadLettered event exists — wire it) |
| JOB-3 | Low | Ensure jobs are idempotent (safe re-run) and use unique/`ShouldBeUnique` where needed (e.g., export, delivery) | — | Add `ShouldBeUnique` to non-idempotent jobs |

## Cache Review — 5.0

Only 1 read-cache usage across domains (from 04). Redis is wired for cache/queue/session but not leveraged for hot reads. → Add a cache seam with tag invalidation on domain events (catalog/course/KPIs).

## Logging and Observability Review — 8.0

JSON logging channel + `CorrelationProcessor` + `AssignCorrelationId` middleware; health/readiness endpoints. Gap: confirm an **audit trail** captures sensitive mutations (role/permission changes, refunds, enrollment grants/revokes, certificate revoke) with actor + before/after — `HasAudit` trait exists; verify coverage (ties to MOD-3).

## Testing Review — 7.5

51 feature + 19 unit (Pest), factories + Fake providers for gateways/playback/PDF/channels. Good breadth. Deepen:

| # | Sev | Gap | Recommendation |
|---|-----|-----|----------------|
| TST-1 | Med | Authorization/negative tests (cross-org access denied, non-owner instructor edit denied, student accessing others' certs) | Add policy/tenant isolation tests |
| TST-2 | Med | Concurrency/idempotency tests (double webhook, coupon race, double refund) | Add replay + race tests |
| TST-3 | Low | Query-count / N+1 assertions on list endpoints | Add `assertDatabaseQueryCount` |
| TST-4 | Low | Validation boundary tests (amount limits, invalid enums, oversized uploads) | Add edge-case tests |

---

## Production Readiness

**Verdict: near-ready; ship after the 4 High items.** Blocking-for-production: CHK-1 (ext call in txn), AUTHZ-1/SEC-1 (tenant scoping), JOB-1/JOB-2 (queue resilience for money/notification side effects), and TST-1/TST-2 (isolation + idempotency tests to lock the above). Everything else is Medium/Low hardening that can follow in fast-follow releases.

---

## High Priority Fixes (ordered)

- **P0-1 (CHK-1/PERF-2):** Move the payment gateway call outside the DB transaction.
- **P0-2 (AUTHZ-1/SEC-1):** Enforce tenant/org scoping via a global scope + tests.
- **P0-3 (JOB-1/JOB-2):** Add retry/backoff/timeout + `failed()` + dead-letter wiring to jobs and notification delivery.
- **P1-1 (CHK-2/CHK-3):** Re-validate coupon under lock; make refund revocation idempotent.
- **P1-2 (PERF-1/TST-3):** Eager-load all list endpoints + query-count tests; enable `preventLazyLoading` in non-prod.
- **P1-3 (SEC-4):** Rate-limit checkout, coupon-apply, verification, and auth endpoints.
- **P2-1 (MOD-3/logging):** Audit trail/observers for role/refund/grant/revoke mutations.
- **P2-2 (VAL-1/VAL-2):** Close FormRequest coverage + commerce boundary validation.

---

## AI Implementation Prompts

**AIP-1 — Gateway call outside transaction (CHK-1)**
> Refactor `app/Domains/Commerce/Actions/Checkout/CheckoutAction.php` so the DB transaction only creates the Order/OrderItems/Invoice/Contract and increments/records the coupon (with `lockForUpdate`), leaving the order in `Pending`. **Commit the transaction**, then call `$this->gateway->charge(...)` outside it, and persist the resulting `PaymentTransaction` in a separate short write. If the charge call throws, leave the order `Pending` for webhook/retry reconciliation rather than rolling back committed order data. Keep enrollment gating unchanged. Add a test proving no DB lock is held during the (faked) gateway call.

**AIP-2 — Tenant/org global scope (AUTHZ-1/SEC-1)**
> Create `app/Domains/Crm/Concerns/BelongsToOrganization.php` (a trait applying a global scope constraining queries to the current actor's `organization_id`) and apply it to the 8 CRM models carrying `organization_id`. Provide an explicit `withoutOrganizationScope()` escape hatch for admin/system contexts. Add feature tests asserting a user from org A receives 404/403 for org B records across list and detail endpoints.

**AIP-3 — Queue resilience (JOB-1/JOB-2)**
> On `app/Domains/Notifications/Jobs/DeliverNotificationJob.php`, `app/Domains/Analytics/Jobs/ProcessExportJob.php`, and queued notifications, set `public int $tries`, `public int $timeout`, and `public function backoff(): array` (exponential, e.g. [10,30,60]); add `$maxExceptions`; implement `failed(\Throwable $e)` that logs with correlation id and dispatches the existing `NotificationDeadLettered` event where applicable. Make delivery idempotent (guard on delivery status) and add `ShouldBeUnique` to the export job.

**AIP-4 — Coupon re-validation under lock + idempotent refund (CHK-2/CHK-3)**
> In `CheckoutAction`, after acquiring the coupon `lockForUpdate`, re-validate expiry, global usage cap, per-user limit, and product scope before incrementing `redeemed_count`; throw the appropriate domain exception on failure. In `RefundOrderAction`/`RevokeEnrollmentsOnRefund`, ensure the refund is idempotent (guard on `OrderStatus::Refunded`) and revokes course grants atomically inside one transaction.

**AIP-5 — Eager loading + N+1 guard (PERF-1/TST-3)**
> Audit all list controllers/resources; add `with()`/`withCount()` for every relationship rendered in list/detail resources (courses, enrollments, orders, leads, sessions). Enable `Model::preventLazyLoading(! app()->isProduction())` in `AppServiceProvider`. Add `assertDatabaseQueryCount` (or Pest query-count) assertions to the heaviest list endpoints.

**AIP-6 — Rate limiting sensitive endpoints (SEC-4)**
> Define named RateLimiters (in the existing rate-limiter registration) for `checkout`, `coupon-apply`, `certificate-verify`, and reinforce auth limiters; apply `throttle:<name>` middleware to those routes. Add tests asserting the limiter triggers.

**AIP-7 — Audit trail for sensitive mutations (MOD-3)**
> Ensure the `HasAudit` trait (or dedicated observers) records actor + before/after for: role/permission changes, order refunds, enrollment grant/revoke, and certificate revoke/reissue. Add a query to confirm each produces an audit record; add tests.

---

## Acceptance Criteria

- AC1 (CHK-1): No external gateway/network call occurs inside a DB transaction; a test asserts the order is committed before charge and locks aren't held during the call.
- AC2 (AUTHZ-1/SEC-1): Cross-organization access is denied by default via a global scope; tenant-isolation tests pass for list and detail across all org-scoped models.
- AC3 (JOB-1/JOB-2): Every queued job/notification defines tries, timeout, backoff, and a `failed()` handler; dead-lettering is wired; delivery is idempotent.
- AC4 (CHK-2/CHK-3): Coupon validity is re-checked under lock; refunds are idempotent and atomically revoke access; tests cover double-webhook, coupon race, double-refund.
- AC5 (PERF-1): List endpoints have no N+1 (query-count tests); `preventLazyLoading` active in non-prod.
- AC6 (SEC-4): Checkout, coupon-apply, verification, and auth endpoints are rate-limited (tested).
- AC7 (MOD-3): Sensitive mutations produce audit records with actor + before/after.
- AC8 (VAL-1/VAL-2): Every state-changing endpoint has a FormRequest; commerce inputs enforce integer minor-units + boundaries.
- AC9 (CERT-1/LRN-1): Certificate issuance is idempotent; playback tokens require active enrollment and are short-lived/signed.
- AC10 (traceability): Every issue (MOD/MIG/VAL/AUTHZ/SEC/CHK/CERT/JOB/PERF/TST IDs) maps to a fix and a criterion.

---

### Appendix — Evidence index
- Models: 81 total, 81 `$fillable`, 0 `$guarded`, 0 unguarded, SoftDeletes 18.
- Transactions: 73 files. Indexes: `->index(` 35, `->unique(` 67, composite unique 22, composite index 33, FK 116.
- Policies: 20 (all aggregates). Form Requests 31, Controllers 41, Actions 80, API Resources 36, Observers 0, Notifications 79.
- Checkout: `Commerce/Actions/Checkout/CheckoutAction.php` (txn L48, coupon `lockForUpdate` L51, gateway `charge` L93).
- Webhook: `Commerce/Actions/Payment/ProcessWebhookAction.php` (dedup `firstOrCreate(event_id)`, `processed_at` guard, order `lockForUpdate`, `Pending`-only advance).
- Certificates: `certificates` migration (`number` unique, `verification_code(32)` unique, `unique(user_id,course_id)`); `CertificateVerificationService` (isValid + signature + revoked_at).
- Org scoping: 2 global-scope files vs 8 CRM models with `organization_id`.
- Tests: 51 feature + 19 unit (Pest); Fake gateway/playback/PDF/channel providers.

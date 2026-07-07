# Sprint 1 · Story A2-S03 — Tenant Model Adoption & Leakage Protection — Report

> EXECUTION MODE. Story A2-S03 only. Implements ADR-07 (adoption). **No database migration, no API change, no UI change.** This is the first behavioral story: the 8 genuinely tenant-owned models now isolate by tenant; platform admins bypass (so the admin panel is unaffected). Compliant with `101_EXECUTION_RULES.md` §3-§7, §15, §16.

## Summary

Classified every Eloquent model, then adopted `BelongsToTenant` on the **8 models that truly belong to a tenant** (all carry `organization_id`). Added **platform-admin bypass** to the resolver so `super_admin`/`admin` operate across all tenants (no Filament/admin regression, satisfying the "Admin bypass" requirement). Confirmed there are **no current-tenant manual filters to remove**. Delivered a **cross-tenant leakage suite** (8 scenarios) and **composite-index recommendations** (no migrations).

## Model classification table

Legend: **Global** = platform content shared by all tenants · **Tenant Scoped** = owned by a specific organization · **System** = platform/infra/config · **Identity** = identity kernel · **Learner/User** = per-user data (indirectly tenant-related, no org column). "Adopted" = `BelongsToTenant` applied now.

| Context | Models | Classification | Adopted? |
|---------|--------|----------------|:--------:|
| **Identity** | User, UserDevice, UserOtp, UserProfile | Identity (never tenant-scoped — scoping identity would break auth/admin) | No |
| **Catalog** | Course, Category, CourseLanguage, CourseLevel, CourseTag | Global (platform course catalog) | No (forbidden by req 2) |
| **Authoring** | Lesson, LessonMedia, Section | Global (course content) | No (forbidden by req 2) |
| **Live** | LiveCourse, LiveSession, SessionSeries, SessionRecording, SessionRegistration, SessionAttendance, SessionJoinToken, SessionReminder | Global (course-attached live content) | No |
| **Certification** | Badge, CertificateTemplate, CertificateSetting | Global/System (platform templates + settings) | No |
| | Certificate, BadgeAward | Learner/User (per-user credentials) | No (no org column) |
| **Learning** | Enrollment, LearningSession, LessonProgress, LessonBookmark, LessonNote | Learner/User (per-user; indirectly tenant via user) | No (no org column) |
| **Commerce** | Product, ProductPrice, Coupon, CouponRedemption, ContractTemplate, PaymentWebhookEvent | Global/System | No |
| | Cart, CartItem, Order, OrderItem, OrderCourseGrant, Invoice, PaymentTransaction, Contract, ContractAcceptance | Learner/User + System (order/user owned) | No (no org column) |
| **Analytics** | MetricDefinition, DashboardDefinition, DashboardWidget, ReportDefinition, ScheduledReport, MetricSnapshot | System (platform analytics config) | No |
| | ReportRun, ExportJob | Learner/User (per-user runs/exports) | No (no org column) |
| **Notifications** | NotificationTemplate, AutomationRule, AutomationAction, ScheduledAutomation | System (platform config) | No |
| | Notification, NotificationDelivery, NotificationPreference, UserNotificationSetting | Learner/User (per-user) | No (no org column) |
| **CRM — tenant root** | Organization | System / Tenant-root (IS the tenant; not scoped by itself) | No |
| **CRM — tenant-owned** | **Company, OrganizationMember, Department, Team, SeatPool, ConsultingRequest, ConsultingProject, BillingProfile** | **Tenant Scoped (org-owned, has `organization_id`)** | **YES (8)** |
| **CRM — indirect** | Lead, Opportunity, Contact, Pipeline, Stage, ConsultingSession, CrmActivity, CrmNote, CrmTag, CrmTask, SeatAssignment | Tenant Scoped (indirect — belong via parent; **no `organization_id` column**) | No (would need a migration — deferred) |

## Models modified

**Adopted `BelongsToTenant` (8):** `Company`, `OrganizationMember`, `Department`, `Team`, `SeatPool`, `ConsultingRequest`, `ConsultingProject`, `BillingProfile` — each: added the import (before `Traits\HasPublicId` to keep Pint order) + `use BelongsToTenant;`. No other change to these models. Deptrac-clean (CRM -> Shared is allowed).

**Resolver:** `RequestTenantResolver` — added platform-admin bypass (`super_admin`/`admin` via `hasAnyRole`, guarded by `method_exists`, so it stays decoupled from the Identity model). Returns null (no tenant) for admins.

## Manual filters removed

**None removed — and that is correct.** A full scan found **no current-tenant manual filtering** in the codebase (the app never implemented tenant isolation manually; the scope now provides it). The single `OrganizationMember::where('organization_id', $organization->id)` in `InviteMemberAction` is **retained**: it filters by a *specific* org (the invite target), not the current tenant, so it is an intentional targeted query, not a duplicated current-tenant check. With the scope active and the current tenant equal to that org, it composes correctly; for admins (bypassed) it remains the sole, correct filter. Requirement 3 is satisfied: nothing to replace, no duplicated ownership checks remain.

## Leakage tests

`tests/Feature/Tenancy/CrossTenantLeakageTest.php` — 8 scenarios on a tenant-scoped throwaway model (`TenantLeakModel`, identical trait/scope to the 8 adopted models) + a public model:

1. **Tenant A cannot READ Tenant B** — `find(bId)` / filtered `first()` return null.
2. **Tenant A cannot UPDATE Tenant B** — a scoped mass `update()` leaves B's row unchanged.
3. **Tenant A cannot DELETE Tenant B** — a scoped `delete()` leaves B's row present.
4. **Tenant A cannot LIST Tenant B** — `count()`/`pluck()` return only A's rows.
5. **Admin/explicit bypass sees all** — `runWithoutTenancy()` returns all 3 rows.
6. **Maintenance bypass** — with maintenance mode active, the scope no-ops (all rows).
7. **Queue bypass** — the `WithoutTenancy` job middleware runs `$next` across all tenants.
8. **Public resources remain public** — a model without the trait is unaffected by the tenant context.

(This complements `TenantScopeTest` from A2-S02: filter/assign/ownership/withoutGlobalScope/forTenant.)

## Index recommendations (no migrations created)

Every tenant-scoped query now prefixes `WHERE organization_id = ?`. To keep those queries index-served at scale, add **leading-`organization_id` composite indexes** in a future migration (Sprint 1 close-out or A2-S04):

| Table | Recommended composite index | Rationale |
|-------|-----------------------------|-----------|
| `crm_companies` | `(organization_id, id)`, `(organization_id, name)` | list + name lookups within a tenant |
| `organization_members` | `(organization_id, user_id)`, `(organization_id, status)` | membership lookup + active-member lists |
| `crm_departments` | `(organization_id, id)` | tenant-scoped listing |
| `crm_teams` | `(organization_id, id)` | tenant-scoped listing |
| `seat_pools` | `(organization_id, id)` | tenant-scoped listing |
| `consulting_requests` | `(organization_id, status)` | tenant + status filters |
| `consulting_projects` | `(organization_id, status)` | tenant + status filters |
| `billing_profiles` | `(organization_id)` | typically 1:1 per org |

General rule: any existing single-column index frequently used with the tenant filter should become `(organization_id, <that column>)`. **No migration is created in this story** (per requirement 5) — these are recommendations for a later, reviewed migration.

## Validation output

Environment note: no PHP/Composer here, so Deptrac/PHPStan/Pint/Pest were not executed; changes were validated statically.

```
8 CRM models        : each has the trait import (Pint-ordered) + `use BelongsToTenant;`; Deptrac CRM->Shared allowed
RequestTenantResolver: admin bypass via method_exists(hasAnyRole) + PLATFORM_ADMIN_ROLES; NO App\Platform\Identity import
adoption scope       : exactly 8 production models use the trait (grep) — no global/content/identity model adopted
leakage test         : declare(strict_types=1); balanced; 8 `it()` scenarios (read/update/delete/list/admin/maintenance/queue/public)
manual filters       : 0 current-tenant filters removed (none existed); InviteMemberAction targeted query retained by design
```
(Note: the shell mount again served truncated copies of the small CRM model files during verification — brace counts looked off; the authoritative file state via the editor is correct, with import+use present on all 8. Documented file-tool/shell divergence.)

Runtime validation — **run on your machine (from `apps/api`)**:
```bash
composer dump-autoload
vendor/bin/pint --test
vendor/bin/phpstan analyse --memory-limit=1G          # expect: no new errors
vendor/bin/deptrac analyse --no-progress               # expect: no new violations (CRM->Shared allowed)
php artisan test                                        # runs the leakage + scope suites + existing suite
```
Expected: leakage + scope suites green; existing suite green — **investigate any CRM test that assumed cross-org visibility** (that would be a previously-latent leak now correctly blocked, or a test that must authenticate as an admin / same org).

## Backward compatibility analysis

- **Platform admins (Filament + admin API):** `super_admin`/`admin` resolve to **no tenant** -> the scope never filters for them -> the admin panel and admin APIs see all orgs exactly as before. **No regression.**
- **Unauthenticated / public / console / queue:** no tenant resolved -> no filtering. **No regression.**
- **Org-scoped users (non-admin) hitting CRM for the 8 models:** now correctly limited to their own org. This is the *intended* isolation; if any prior behavior showed them other orgs' records, that was a leak now closed (a security fix, not a regression).
- **Writes/seeders:** seeders run in console (no tenant) -> no auto-assign, records keep their explicit `organization_id`. Auto-assign only stamps when a tenant is resolved and the field is null (correct owner for authenticated org users).
- **`ConsultingRequest` nullable org:** when created by an authenticated org user with a null org, it is stamped with their org (sensible); by an admin (bypassed) or publicly (no tenant) it stays null. No schema change.
- **Reversibility:** remove the `use BelongsToTenant;` line (and its import) from any model to revert that model instantly; remove the resolver admin-bypass block to revert admin behavior.

## Known limitations

1. **Leakage suite is mechanism-level.** It runs on throwaway models that use the *identical* trait/scope the 8 production models now inherit, avoiding heavy CRM/Organization FK fixtures. Real-model integration leakage tests (create org A/B + records, authenticate per tenant) are recommended in A2-S04 once tenant provisioning + factories are wired.
2. **Indirect CRM models not isolated.** Lead/Opportunity/Contact/Pipeline/Stage/ConsultingSession/CRM activities/notes/tasks/SeatAssignment have no `organization_id` column, so they are not isolated yet. Isolating them needs a migration (add `organization_id` or scope-via-parent) — deferred (no migrations this story).
3. **Admin bypass adds a role check per resolution.** `hasAnyRole` triggers a roles lookup once per request (cached in `TenantContext`); negligible now, cache/eager-load if it ever shows on a hot path.
4. **No PHP execution here.** Static validation only; the full `php artisan test` run on your machine is the real gate for "no regression" — any failing CRM test indicates a previously-latent cross-org assumption to fix.
5. **Index recommendations only.** Composite indexes are documented, not created (per requirement 5).

## Next Story dependencies

- **A2-S04 (Tenant provisioning):** provision/suspend tenants + usage limits; add the recommended composite indexes in a reviewed migration; add real-model integration leakage tests using provisioned tenants.
- **Indirect CRM isolation** (Lead/Opportunity/etc.) becomes a follow-up once a column/parent-scoping migration is scheduled.
- Administration (Sprint 3) uses `runWithoutTenancy` for legitimate cross-tenant operations; the leakage suite is the permanent regression gate for all future adoption.

---

## STOP

Story A2-S03 is implemented (classification + 8-model adoption + admin bypass + leakage suite + index recommendations + report). **No other Sprint 1 story was started; no migration, API, or UI changed.** Awaiting approval before Story A2-S04.

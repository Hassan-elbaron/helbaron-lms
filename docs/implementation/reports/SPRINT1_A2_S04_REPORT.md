# Sprint 1 · Story A2-S04 — Tenant Provisioning Foundation — Report

> EXECUTION MODE. Story A2-S04 only (+ the two requested pre-implementation refactors). **No billing, no UI, no migrations, no provisioning workflows.** Foundation abstractions only. Implements ADR-07/ADR-05 (foundation); refines ADR-20. Compliant with `101_EXECUTION_RULES.md` §3-§7, §15, §16.

## Summary

Two refactors decoupled tenancy from role knowledge and from a hardcoded column, then the tenant **lifecycle foundation** (status machine + value objects + ports) was introduced. Everything is additive and backward-compatible: no persistence, no workflow, no schema.

## Architecture changes

**Refactor 1 — bypass without role knowledge in Shared.** Introduced a `TenancyBypassPolicy` **port** in Shared with a `NullTenancyBypassPolicy` default (never bypass). The role-based decision moved to Identity's `RoleBasedTenancyBypassPolicy` (Identity owns RBAC). `TenantScope` and `BelongsToTenant` now consult the port; `RequestTenantResolver` no longer knows any roles. Behavior is equivalent to A2-S03 (platform admins still bypass) but the coupling is gone — Shared depends only on the port (honors ADR-20).

**Refactor 2 — column resolution without a hardcoded default.** Introduced `TenantMetadata` (Shared) driven by `config/tenancy.php`: a default column, a supported-columns list (`organization_id`, `tenant_id`, `workspace_id`, `company_id`, `school_id`), and per-model overrides. `BelongsToTenant::getTenantColumn()` delegates to `TenantMetadata` (or a model's own `$tenantColumn`); the hardcoded `'organization_id'` is gone from the trait and the scope. A new tenant column is enabled via config/override — **no change to `BelongsToTenant`**.

**Lifecycle foundation.** A `TenantStatus` state machine + immutable value objects (`Tenant`, `TenantSettings`, `TenantLimits`, `TenantUsage`, `TenantBranding`, `TenantDomains`) + three ports (`TenantRepository`, `TenantProvisioner`, `TenantUsageTracker`) — all in `App\Platform\Shared\Tenancy\Lifecycle`. Administration implements the ports later.

## Files created

| File | Purpose |
|------|---------|
| `Shared/Tenancy/TenancyBypassPolicy.php` | Port: should the current actor bypass tenancy? |
| `Shared/Tenancy/NullTenancyBypassPolicy.php` | Default: never bypass. |
| `Identity/Tenancy/RoleBasedTenancyBypassPolicy.php` | Identity impl: `super_admin`/`admin` bypass. |
| `Shared/Tenancy/TenantMetadata.php` | Config-driven tenant-column resolution (no hardcoded default). |
| `config/tenancy.php` | Default/ supported columns, per-model overrides, default limits. |
| `Shared/Tenancy/Lifecycle/TenantStatus.php` | Lifecycle enum + transition rules. |
| `Shared/Tenancy/Lifecycle/TenantSettings.php` | Immutable settings bag. |
| `Shared/Tenancy/Lifecycle/TenantLimits.php` | Immutable limits (null = unlimited). |
| `Shared/Tenancy/Lifecycle/TenantUsage.php` | Immutable usage snapshot + `within(limits)`. |
| `Shared/Tenancy/Lifecycle/TenantBranding.php` | White-label attributes. |
| `Shared/Tenancy/Lifecycle/TenantDomains.php` | Primary + alias hosts. |
| `Shared/Tenancy/Lifecycle/Tenant.php` | Immutable tenant descriptor tying it together. |
| `Shared/Tenancy/Lifecycle/TenantRepository.php` | Port: persistence. |
| `Shared/Tenancy/Lifecycle/TenantProvisioner.php` | Port: provision/activate/suspend/archive/restore. |
| `Shared/Tenancy/Lifecycle/TenantUsageTracker.php` | Port: usage tracking. |
| `tests/Unit/Tenancy/TenantLifecycleTest.php` | Unit tests (state machine, metadata, limits/usage, descriptor). |
| `docs/implementation/reports/SPRINT1_A2_S04_REPORT.md` | This report. |

## Files modified

| File | Change |
|------|--------|
| `Shared/Tenancy/RequestTenantResolver.php` | Removed role logic (org-only resolution). |
| `Shared/Tenancy/TenantScope.php` | Consults `TenancyBypassPolicy`; column via `TenantMetadata` (no hardcoded default). |
| `Shared/Tenancy/Concerns/BelongsToTenant.php` | `getTenantColumn()` via `TenantMetadata`; `assignTenantOnCreate()` honors the bypass policy. |
| `Shared/Providers/SharedServiceProvider.php` | Merge `config/tenancy.php`; bind `TenantMetadata` + `NullTenancyBypassPolicy` default. |
| `Identity/Providers/IdentityServiceProvider.php` | Bind `RoleBasedTenancyBypassPolicy` (overrides the Shared default). |
| `apps/api/deptrac.yaml` | Add `app/Platform/Identity/Tenancy/.*` to the Identity layer so the new policy is boundary-checked. |

No business logic, no migration, no API, no UI touched.

## Tenant lifecycle

`TenantStatus` (`provisioning`, `active`, `suspended`, `archived`) with an enforced transition graph:
- `Provisioning -> Active | Archived`
- `Active <-> Suspended`
- `Active | Suspended -> Archived`
- `Archived -> Active` (restore)

`Tenant` is an immutable descriptor: `withStatus()` returns a new instance (validated against `canTransitionTo()` by the future provisioner). `isActive()` reflects operational state. No persistence — that is the `TenantRepository`/`TenantProvisioner` implementers' job (Administration, Sprint 3).

## Provisioning design

`TenantProvisioner` is a **port** defining the lifecycle operations (`provision/activate/suspend/archive/restore`) — the **contract**, not the workflow. Deliberately unimplemented in this story (requirement: no provisioning workflows). Administration will implement it later, orchestrating persistence + resource setup + events, respecting `TenantStatus::canTransitionTo()`. `TenantRepository` is the persistence port (`findById`, `findByDomain`, `save`) used for tenant lookup (incl. host-based via `TenantDomains`).

## Usage tracking

`TenantUsage` models a current-counts snapshot and can answer `within(TenantLimits)`. `TenantUsageTracker` is the port for live tracking (`usageFor`, `increment`, `decrement`) — implemented later (Administration/Analytics). `TenantLimits` models per-metric caps (null = unlimited) with `exceeds()`. **Enforcement (blocking on breach) is a later story**; this only models limits + usage.

## Future billing integration

Billing is explicitly **out of scope** (requirement). The foundation is billing-ready without coupling to it: `TenantLimits`/`TenantUsage` express plan entitlements and consumption that a future Commerce/billing integration can drive (e.g., a plan sets limits; usage feeds metered billing) — via events/ports, with no billing code in the tenancy foundation. `config/tenancy.php` carries illustrative `default_limits` only.

## Validation output

Environment note: no PHP/Composer here; validated statically.

```
15 new tenancy files : declare(strict_types=1); braces balanced; 0 cross-context imports
Shared/Tenancy/*     : imports NO context (Deptrac: Shared depends on nothing) -> clean
Identity policy      : imports only the Shared port (Identity->Shared allowed); deptrac.yaml Identity layer now covers Identity/Tenancy
RequestTenantResolver: 0 role references remain in Shared (role knowledge moved to Identity)
config/tenancy.php   : default_column + columns + overrides + default_limits present
unit test            : 5 `it()` cases (state machine, metadata column, no-bypass default, limits/usage, immutable descriptor)
```
Runtime validation — **run on your machine (from `apps/api`)**:
```bash
composer dump-autoload
vendor/bin/pint --test
vendor/bin/phpstan analyse --memory-limit=1G          # expect: no new errors
vendor/bin/deptrac analyse --no-progress               # expect: no new violations
php artisan test                                        # tenancy suites + existing suite green
```
Expected: green; the A2-S02/S03 tenancy suites still pass (behavior preserved); the new `TenantLifecycleTest` passes.

## Backward compatibility

- **Admin bypass preserved.** Equivalent to A2-S03: platform admins bypass via the Identity policy (the scope now checks the policy instead of the resolver returning null). No Filament/admin regression.
- **Default column unchanged.** `TenantMetadata` default is `organization_id` (config default), so the 8 adopted models behave identically.
- **Provider order.** Shared binds the Null default first; Identity binds the role-based policy (loads after Shared) → the concrete policy wins.
- **No persistence/schema/API/UI change.** The lifecycle classes are pure value objects + interfaces; nothing runs until implemented.
- **Reversible.** Remove the Identity binding to fall back to no-bypass; revert config/metadata to restore the prior hardcoded behavior.

## Known limitations

1. **Ports unimplemented.** `TenantRepository`/`TenantProvisioner`/`TenantUsageTracker` have no concrete implementation and no persistence — by design (foundation). Administration (Sprint 3) implements them.
2. **Limits not enforced.** `TenantLimits`/`TenantUsage` model caps/consumption but nothing blocks on breach yet.
3. **No tenant table.** A `Tenant` is a value object; there is no `tenants` migration (forbidden this story). Persistence + the composite indexes recommended in A2-S03 land with Administration/provisioning.
4. **Role-check cost.** The Identity bypass policy calls `hasAnyRole` per request (cached in context); negligible, optimizable later.
5. **No PHP execution here.** Static validation only; confirm with the commands above.

## Next Story dependencies

- **A2-S05 / Administration (Sprint 3):** implement `TenantRepository`/`TenantProvisioner`/`TenantUsageTracker` against a `tenants` table (migration), wire provisioning workflows + events, and enforce limits. Host-based tenant resolution uses `TenantDomains` + `TenantRepository::findByDomain`.
- **Billing (later):** a plan drives `TenantLimits`; usage feeds metered billing — via ports/events, keeping billing out of the tenancy foundation.
- **Capabilities (Sprint 3, ADR-06):** `TenantSettings`/capability grants layer on this foundation.

---

## STOP

Story A2-S04 is implemented (bypass-port + column-metadata refactors + tenant lifecycle foundation + tests + report). **No billing, UI, migration, or provisioning workflow was implemented; no other Sprint 1 story was started.** Awaiting approval before A2-S05.

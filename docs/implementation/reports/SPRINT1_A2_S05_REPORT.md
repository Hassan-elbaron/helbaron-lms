# Sprint 1 · Story A2-S05 — Tenant Events & Platform Contracts — Report

> EXECUTION MODE. Story A2-S05 only. Completes the multi-tenancy foundation with immutable event DTOs + platform contracts + Integration-Platform contract stubs. **No messaging implemented, no business logic, no API/DB/UI change.** Implements ADR-03/ADR-16 (contracts), ADR-05/ADR-07 (tenancy). Compliant with `101_EXECUTION_RULES.md` §3-§7, §15, §16.

## Summary

Introduced 11 immutable **tenant event DTOs** (no Eloquent, no framework coupling), 9 new **platform contracts** (ports) for tenancy read/lifecycle/publishing, and 4 **Integration-Platform contract stubs** (EventBus, Outbox, WebhookPublisher, MessageBroker — contracts only). Wired `CurrentTenantProvider` to the existing `TenantContext`. All additive; nothing is implemented beyond `CurrentTenantProvider`.

## Events introduced

All implement the `TenantEvent` marker (`tenantId(): TenantId`, `occurredAt(): DateTimeImmutable`), are `final` with `readonly` promoted properties, and import only `Shared\Tenancy` + PHP-core `DateTimeImmutable` — **no Eloquent, no Illuminate, no infrastructure**.

| Event | Payload |
|-------|---------|
| `TenantProvisioned` | tenantId, metadata[] |
| `TenantActivated` | tenantId |
| `TenantSuspended` | tenantId, ?reason |
| `TenantArchived` | tenantId |
| `TenantRestored` | tenantId |
| `TenantLimitsChanged` | tenantId, TenantLimits |
| `TenantBrandingChanged` | tenantId, TenantBranding |
| `TenantDomainAdded` | tenantId, domain |
| `TenantDomainRemoved` | tenantId, domain |
| `TenantSettingsChanged` | tenantId, TenantSettings |
| `TenantDeleted` | tenantId (**future reserved** — hard delete not yet in the lifecycle) |

Location: `App\Platform\Shared\Tenancy\Events`.

## Contracts introduced

**Platform tenancy contracts** (`App\Platform\Shared\Tenancy\Contracts`):

| Port | Purpose |
|------|---------|
| `CurrentTenantProvider` | active tenant (`currentTenant`, `hasTenant`) — **implemented now** by `TenantContext`, bound in Shared |
| `TenantLookup` | read a `Tenant` by id / host |
| `TenantLifecycleManager` | event-emitting mutation surface: `transition`, `changeLimits/Branding/Settings`, `addDomain/removeDomain` |
| `TenantUsageProvider` | usage for a tenant |
| `TenantLimitProvider` | limits for a tenant |
| `TenantSettingsProvider` | settings for a tenant |
| `TenantBrandingProvider` | branding for a tenant |
| `TenantDomainProvider` | domains + host→tenant resolution |
| `TenantEventPublisher` | publish `TenantEvent` DTOs |

(`TenantProvisioner` — the 10th named contract — already exists from A2-S04 in `…\Tenancy\Lifecycle`; unchanged.)

**Integration-Platform contracts** (`App\Platform\Integration\Contracts`) — **contracts only, no messaging**:

| Port | Purpose |
|------|---------|
| `EventBus` | in-process dispatch of event DTOs |
| `Outbox` | transactional outbox (store / pending / markPublished) |
| `WebhookPublisher` | outbound webhook delivery |
| `MessageBroker` | **future reserved** external broker |

## Files created

11 events + `TenantEvent` marker (`Shared/Tenancy/Events/`) · 9 contracts (`Shared/Tenancy/Contracts/`) · 4 integration contracts (`Integration/Contracts/`) · `tests/Unit/Tenancy/TenantEventsTest.php` · this report. (26 new files.)

## Files modified

| File | Change |
|------|--------|
| `Shared/Tenancy/TenantContext.php` | `implements CurrentTenantProvider` + `currentTenant()`/`hasTenant()` (delegating to `id()`/`has()`). |
| `Shared/Providers/SharedServiceProvider.php` | Bind `CurrentTenantProvider` -> `TenantContext`. |

No business logic, migration, API, or UI touched.

## Dependency validation

```
Events (12)          : import only App\Platform\Shared\Tenancy + DateTimeImmutable (PHP core) -> 0 framework/Eloquent imports
Contracts (9)        : interfaces; import only Shared\Tenancy types -> intra-Shared
Integration (4)      : interfaces; import nothing (object/array/string) -> depend on nothing
No Event/Contract    : imports a Domain/Context (grep clean)
TenantContext        : implements CurrentTenantProvider; bound in Shared
```

## Architecture validation (requirement 4)

| Rule | Result |
|------|--------|
| **Shared depends on nothing** | ✅ events/contracts reference only Shared + PHP core. |
| **Administration depends on Shared only** | ✅ Administration is not yet built; its Deptrac rule is `[Shared, IdentityContracts]`. All tenancy contracts/events live in **Shared**, so Administration will depend on Shared (not the reverse). |
| **Identity depends on Shared only** | ✅ the Identity bypass policy (A2-S04) imports only the Shared port; Deptrac rule `[Shared, IdentityContracts]`. |
| **No Context depends directly on Administration** | ✅ contracts are in Shared; contexts depend on Shared. Deptrac forbids any Domain/Context → Administration. |
| **No Event references Eloquent** | ✅ verified (import-line scan: zero Eloquent/Illuminate). |

(Deptrac coverage: `Integration/Contracts` is covered by the broad `app/Platform/Integration/.*` collector; `Shared/Tenancy/{Events,Contracts}` by the broad Shared collector — no `deptrac.yaml` change needed.)

## Backward compatibility

Fully preserved: everything is additive. The only wiring is `CurrentTenantProvider -> TenantContext` (a new binding + two delegating methods on `TenantContext`); no existing behavior changes. All other contracts are interfaces with **no implementation** (Administration/Integration implement them later). No schema, API, or UI change.

## Validation output

Environment note: no PHP/Composer here; validated statically (see Dependency validation).
```
26 new files : declare(strict_types=1); balanced; events are final + readonly DTOs
tests        : TenantEventsTest (event DTO construction/immutability; CurrentTenantProvider binding) + existing TenantLifecycleTest
```
Runtime validation — **run on your machine (from `apps/api`)**:
```bash
composer dump-autoload
vendor/bin/pint --test
vendor/bin/phpstan analyse --memory-limit=1G
vendor/bin/deptrac analyse --no-progress
php artisan test
```
Expected: green; the new event/contract tests pass; the A2-S02/S03/S04 tenancy suites still pass.

## Known limitations

1. **Contracts unimplemented (except `CurrentTenantProvider`).** `TenantLookup`, `TenantLifecycleManager`, the read providers, `TenantEventPublisher`, and all Integration ports are interfaces only — implemented later by Administration/Integration.
2. **No messaging / no outbox implementation** — by requirement. `Outbox`/`EventBus`/`WebhookPublisher`/`MessageBroker` are contracts to prepare the Integration Platform.
3. **`TenantDeleted`/`MessageBroker` are future-reserved** — declared for forward compatibility; not wired into any flow.
4. **No PHP execution here** — confirm with the commands above.

## Sprint 1 readiness for closure

Epic **A2 — Multi-Tenancy & Isolation** is functionally complete across its stories:

| Story | Delivered |
|-------|-----------|
| A2-S01 | Tenant-resolution abstraction (foundation). |
| A2-S02 | Enforcement machinery (global scope, trait, bypass) — no models adopted. |
| A2-S03 | Adoption on the 8 org-owned models + admin bypass + cross-tenant leakage suite + index recommendations. |
| A2-S04 | Bypass-port + column-metadata refactors + tenant lifecycle foundation (status, VOs, ports). |
| A2-S05 | Tenant events (DTOs) + platform contracts + Integration contract stubs. |

**Ready to close Sprint 1 once, on your machine:** (a) the full gate suite is green — `pint`, `phpstan`, `deptrac`, `php artisan test` (incl. the tenancy leakage/lifecycle/events suites); (b) the Deptrac + PHPStan baselines are regenerated/committed (from Sprint 0) if not already; (c) `composer dump-autoload`. **Deferred to later sprints (not blockers):** the composite-index migration (recommended in A2-S03), and all contract implementations + provisioning workflows (Administration, Sprint 3). No API/DB/UI changed across Sprint 1; every step was additive and reversible.

---

## STOP

Story A2-S05 is implemented (tenant event DTOs + platform contracts + Integration contract stubs + `CurrentTenantProvider` wiring + tests + report). **Sprint 2 was not started; no messaging, business logic, API, DB, or UI was changed.** Awaiting approval.

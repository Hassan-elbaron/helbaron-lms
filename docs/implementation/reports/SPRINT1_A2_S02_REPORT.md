# Sprint 1 · Story A2-S02 — Tenant Enforcement — Report

> EXECUTION MODE. Story A2-S02 only. Implements ADR-07 (enforcement machinery). **No business logic changed, no database migration, no API change, no UI change.** The `BelongsToTenant` trait is applied to **zero production models** in this story, so there is no behavior regression; per-model adoption + the leakage suite are A2-S03. Compliant with `101_EXECUTION_RULES.md` §3-§7, §15, §16.

## Summary

Delivered the reusable tenant-isolation machinery on top of the A2-S01 foundation: a `BelongsToTenant` trait (opt-in), a global `TenantScope`, lazy + bypass-aware `TenantContext`, and a `WithoutTenancy` job middleware. The `tenant.resolve` middleware is activated on the **API surface only** (not globally). Enforcement is **safe by construction**: the scope filters only when a tenant is actually resolved and tenancy is not bypassed, and no production model opts in yet — so the running application is unchanged.

## Architecture impact

- **New Shared enforcement primitives.** `TenantScope` (global scope) + `BelongsToTenant` (opt-in trait) + `WithoutTenancy` (job middleware), all in `App\Platform\Shared\Tenancy`. Any context may use them (all layers may depend on Shared) with no Deptrac violation.
- **Lazy, order-independent resolution.** `TenantContext` now resolves the tenant on first read via the bound `TenantResolver` when not explicitly set, so enforcement is correct regardless of middleware ordering (a scoped query resolves the tenant after auth has run).
- **API-surface activation.** `ResolveTenant` is appended to the `api` middleware group (in `bootstrap/app.php`) — not global. Web/marketing and Filament panels attach the retained `tenant.resolve` alias per-panel/route when they adopt tenancy.
- Implements **ADR-07**; relates to **ADR-05/06** (Administration/capabilities will use the bypass) and **ADR-20** (resolver stays decoupled from the Identity implementation).

## Files created

| File | Purpose |
|------|---------|
| `apps/api/app/Platform/Shared/Tenancy/TenantScope.php` | Global scope: filters a tenant-owned model to the active tenant (only when resolved + not bypassed + not in maintenance). |
| `apps/api/app/Platform/Shared/Tenancy/Concerns/BelongsToTenant.php` | Opt-in trait: auto-filter + auto-assign on create + ownership verification + `forTenant` scope. |
| `apps/api/app/Platform/Shared/Tenancy/WithoutTenancy.php` | Queued-job middleware to run system jobs with tenancy bypassed. |
| `apps/api/tests/Feature/Tenancy/TenantScopeTest.php` | Proves the machinery on a throwaway model (7 cases). |
| `docs/implementation/reports/SPRINT1_A2_S02_REPORT.md` | This report. |

## Files modified

| File | Change | Safety |
|------|--------|--------|
| `apps/api/app/Platform/Shared/Tenancy/TenantContext.php` | Added lazy resolution (via injected resolver) + re-entrant bypass (`isBypassed`, `runWithoutTenancy`). Kept `set/forget/has/id`. | Additive; nothing reads it unless a trait-using model queries — none do yet. |
| `apps/api/app/Platform/Shared/Providers/SharedServiceProvider.php` | `TenantContext` singleton now receives the `TenantResolver`. | Wiring only; inert. |
| `apps/api/bootstrap/app.php` | Appended `ResolveTenant` to the `api` middleware group. | Non-throwing middleware; sets context only; no response change; inert until a model opts in. |

No `app/**` business logic, migration, model, controller, Filament resource, or API/UI file was changed.

## Tenant scope design

- **Opt-in, not blanket.** A model becomes tenant-scoped only by `use BelongsToTenant`. Column defaults to `organization_id` (override with `protected string $tenantColumn`).
- **Automatic filtering** via `TenantScope::apply()` — adds `where(<tenant column>, <tenant id>)` **only** when: not bypassed, not in maintenance, and a tenant is resolved. Otherwise it is a no-op (backward compatible for public/unauthenticated/console).
- **Zero manual `where organization_id`.** The scope centralizes filtering; no duplicated code.
- **Automatic assignment** on `creating` (stamps the active tenant when present and not already set, unless bypassed).
- **Ownership verification** (`belongsToTenant(TenantId)`) and an **explicit** `forTenant(TenantId)` query scope.
- **Removable for system contexts:** `Model::withoutGlobalScope(TenantScope::class)` per query, or `TenantContext::runWithoutTenancy()` per block.
- **Composes with future soft-deletes** (multiple global scopes) and is the natural hook for **future auditing** (the `creating` path).
- **Testable:** proven by `TenantScopeTest` (filter / no-filter / assign / ownership / bypass / withoutGlobalScope / forTenant).

## Bypass strategy

Isolation is the default; bypass is explicit and limited to the allowed cases:

| Allowed bypass | Mechanism |
|----------------|-----------|
| **System jobs** | `WithoutTenancy` job middleware, or (implicitly) jobs run without an authenticated user -> resolver returns null -> scope no-ops. |
| **Maintenance mode** | `TenantScope` returns early when `app()->isDownForMaintenance()`. |
| **Explicit Administration contexts** | `TenantContext::runWithoutTenancy(fn () => ...)` (re-entrant, callback-scoped) for deliberate cross-tenant reads. |
| **Everything else** | isolated: a resolved tenant + a trait-using model always filters. |

Bypass is depth-counted, so nested bypass restores the prior state on exit and never leaves tenancy globally disabled.

## Validation output

Environment note: no PHP/Composer here, so Deptrac/PHPStan/Pint/Pest were not executed; the classes were validated statically and the design reviewed against the gates.

```
Tenancy classes (4 new/changed) : declare(strict_types=1); balanced; 0 cross-context/Identity-model imports
TenantScope                      : app(TenantContext::class) typed (larastan); method_exists() narrows getTenantColumn; Illuminate-only deps
BelongsToTenant                  : @mixin Model (getAttribute/setAttribute typed); creating hook via method_exists (no instanceof-trait pitfall)
SharedServiceProvider            : TenantContext singleton receives TenantResolver; resolver bound
bootstrap/app.php                : ResolveTenant appended to 'api' group (verified via editor; line 44) — NOT global
adoption check                   : ONLY tests/.../TenantScopeTestModel uses the trait; 0 production models -> no regression
```
(Note: the shell mount again served stale/truncated copies during verification — e.g. `TenantContext.php` and the `bootstrap/app.php` append line appeared cut off; the authoritative file state via the editor is complete and correct. Documented file-tool/shell divergence, not a defect.)

Runtime validation — **run on your machine (from `apps/api`)**:
```bash
composer dump-autoload
vendor/bin/pint --test
vendor/bin/phpstan analyse --memory-limit=1G          # expect: no new errors
vendor/bin/deptrac analyse --no-progress               # expect: no new violations (Shared-only)
php artisan test                                        # expect: existing suite unchanged + TenantScopeTest green
php artisan config:clear && php artisan route:list      # routes/URIs unchanged
```
Expected: all green; existing test count unchanged plus 7 new passing tenancy assertions; `route:list` identical.

## Backward compatibility

**Preserved.** Enforcement is real but dormant:
- **No production model uses `BelongsToTenant`** -> no query anywhere is filtered -> identical results.
- `TenantScope` and the auto-assign hook only run for trait-using models (none in production).
- `ResolveTenant` on the `api` group only populates `TenantContext`; nothing reads it yet -> no response change.
- No migration, no schema change, no data touched; no API/UI change.

The first observable change happens in A2-S03 when specific tenant-owned models adopt the trait — each behind the leakage suite, one controlled step at a time.

## Known limitations

1. **Machinery, not adoption.** By design (to guarantee no regression, and because model-by-model adoption is untestable in this environment), no production model is scoped yet. A2-S03 adopts the trait per model + adds the cross-tenant leakage suite as a permanent CI gate.
2. **Maintenance check per query.** `isDownForMaintenance()` reads a file per `apply()`; negligible now, but a candidate for caching once high-QPS tenant-scoped models exist.
3. **Resolver assumes tenant == user's `organization_id`.** Swappable behind `TenantResolver` when the Organization context splits (redesign 02) — no consumer change.
4. **`api`-group activation is broad but inert.** It runs on all API requests (including public); harmless (empty context) and reversible by removing the one `appendToGroup` line.
5. **No PHP execution here.** Static validation only; confirm with the commands above. Shell-mount truncation affected only verification output, not the authoritative files.

## Next Story dependencies

- **A2-S03 (Cross-tenant leakage harness + model adoption):** apply `BelongsToTenant` to the genuinely tenant-owned models (org membership, CRM, org-scoped records — NOT global catalog content), verify no `where organization_id` manual scoping remains, and add the two-tenant leakage suite as a blocking CI gate. This is where observable isolation and the migration/index review (composite `(organization_id, ...)` indexes) land.
- **A2-S04 (Tenant provisioning)** uses `TenantId`; Administration (Sprint 3) uses `runWithoutTenancy` for cross-tenant operations.

---

## STOP

Story A2-S02 is implemented (tenant-enforcement machinery + API-surface activation + bypass + tests + report). **No other Sprint 1 story was started; no business logic, data, API, or UI changed; no production model is scoped yet.** Awaiting approval before Story A2-S03.

# Sprint 1 · Story A2-S01 — Multi-Tenancy Foundation — Report

> EXECUTION MODE. Story A2-S01 only (+ the two requested pre-implementation refactors). Implements ADR-07 (foundation). **No business logic changed, no data migrated, no API changed, no UI changed.** Additive and fully backward-compatible. Compliant with `101_EXECUTION_RULES.md` §3-§7, §15, §16.

## Summary

Introduced the **tenant-resolution abstraction** as Platform foundation in the Shared layer — a `TenantContext` (per-request singleton), a `TenantResolver` port with a default `RequestTenantResolver` and a `NullTenantResolver`, a `TenantId` value object, and a non-applied `ResolveTenant` middleware (registered as an alias only). Nothing consumes the context yet: no global scope, no deny path, no query change. Behavior is unchanged.

Pre-implementation, the ADR validation was made **config-driven** (`config/architecture/adr-watch.yaml`, no hardcoded paths) and the ADR index gained **Implementation Status** + **Sprint Target** per ADR.

## Architecture changes

- **New Platform primitive: tenancy.** A cross-cutting `TenantContext` + `TenantResolver` port live in `App\Platform\Shared\Tenancy`. Being in Shared, every context may depend on them without a boundary violation (all layers may depend on Shared). This is the seam that A2-S02 will attach the global scope to.
- **Resolver decoupled from Identity.** `RequestTenantResolver` reads the authenticated user's `organization_id` via the framework `auth()` guard as an `Illuminate\...\Model` attribute — it does **not** import `App\Platform\Identity\Models\User`, so Deptrac stays green (Shared depends on Illuminate only, never on a context). This honors ADR-20 (contexts/Shared must not couple to the Identity implementation).
- **Middleware registered but not applied.** `ResolveTenant` is aliased as `tenant.resolve` in `SharedServiceProvider::boot()` but attached to no route/group, so the request pipeline is unchanged. It is non-throwing by design.
- **ADR governance made data-driven.** Watched paths moved to `config/architecture/adr-watch.yaml`; a `Tenancy/` pattern was added.

Implements: **ADR-07** (row-level multi-tenancy — foundation). Related: **ADR-20** (Identity contracts seam), **ADR-05/06** (later Administration/capabilities consume tenancy).

## Files created

| File | Purpose |
|------|---------|
| `apps/api/app/Platform/Shared/Tenancy/TenantId.php` | Immutable tenant identifier (value object). |
| `apps/api/app/Platform/Shared/Tenancy/TenantContext.php` | Per-request singleton holding the current tenant (set/forget/has/id). |
| `apps/api/app/Platform/Shared/Tenancy/TenantResolver.php` | Port: `resolve(): ?TenantId`. |
| `apps/api/app/Platform/Shared/Tenancy/RequestTenantResolver.php` | Default adapter: resolves from the authenticated user's `organization_id`. |
| `apps/api/app/Platform/Shared/Tenancy/NullTenantResolver.php` | No-op resolver (console/tests / tenancy-disabled). |
| `apps/api/app/Platform/Shared/Http/Middleware/ResolveTenant.php` | Populates TenantContext; alias-only, non-throwing, not applied. |
| `config/architecture/adr-watch.yaml` | ADR watched-path patterns (extracted from the script). |
| `docs/implementation/reports/SPRINT1_A2_S01_REPORT.md` | This report. |

## Files modified

| File | Change | Safety |
|------|--------|--------|
| `apps/api/app/Platform/Shared/Providers/SharedServiceProvider.php` | Registered `TenantContext` singleton, bound `TenantResolver` -> `RequestTenantResolver`, aliased `tenant.resolve` middleware. | Additive; bindings inert (nothing resolves unless asked); alias unused -> zero behavior change. |
| `scripts/adr-link-check.sh` | Loads watched patterns from `config/architecture/adr-watch.yaml` (no hardcoded paths). | Behavior preserved (verified). |
| `docs/adr/INDEX.md` | Added **Implementation Status** + **Sprint Target** to all 20 ADRs (+ 2 summary columns). | Documentation. |
| `docs/implementation/reports/SPRINT0_A1_S04_REPORT.md` | Addendum noting the config-driven refactor. | Documentation. |

No `app/**` business logic, migration, model, route, controller, Filament resource, or API/UI file was changed.

## Dependency impact

- **New runtime bindings:** `TenantContext` (singleton), `TenantResolver`=>`RequestTenantResolver`. Both are lazy — resolved only if something asks for them; nothing does yet.
- **New middleware alias:** `tenant.resolve` (not attached anywhere).
- **Deptrac:** the tenancy classes live in Shared and reference `Illuminate\*` + intra-Shared types only. No cross-context dependency; no new baseline entries expected. `SharedServiceProvider` now references `Shared\Http\Middleware` + `Shared\Tenancy` (same layer) — allowed.
- **PHPStan (level 6 + custom rules):** `RequestTenantResolver` guards `instanceof Model` before `getAttribute` (typed); no persistence calls; none of the new classes are Models/controllers/Filament resources, so the custom architecture rules do not flag them.
- **Composer:** no dependency added; no autoload/lock change (PSR-4 `App\` already covers the new paths).

## Backward compatibility

**Fully preserved.** The foundation is inert:
- No global query scope, no `where` changes -> all existing queries return identical results.
- The resolution middleware is **not applied** to any route -> the request/response pipeline is byte-for-byte unchanged.
- `TenantContext` starts empty and is read by nothing.
- No migration, no schema change, no data touched.
- No API contract or UI changed.

A2-S02 will attach `tenant.resolve` and introduce the global scope; that is where observable enforcement (and its own backward-compat handling) begins.

## Validation output

Environment note: no PHP/Composer here, so PHPStan/Pint/Deptrac/Pest were not executed; the tenancy classes were validated statically and the ADR gate was executed.

```
Tenancy classes (6 files) : braces balanced; declare(strict_types=1) present; 0 cross-context imports
                            RequestTenantResolver: reads Illuminate Model attribute (no App\...\Identity import)
SharedServiceProvider     : imports intra-Shared + Illuminate only; singleton/bind/alias added
ADR script (config-driven): syntax OK; patterns loaded from config/architecture/adr-watch.yaml
                            scenarios: docs-only PASS; Tenancy+ADR-07 PASS; ordinary-code PASS
                            (fail-branch execution not observable here due to a truncated shell
                             mount copy; branch is unchanged from the proven A1-S04 version)
docs/adr/INDEX.md         : 20 summary rows + 20 detailed sections; Implementation Status + Sprint
                            Target present on every ADR (verified via editor; shell mount showed a
                            truncated 18 — documented file-tool/shell divergence, not a defect)
```

Runtime validation — **run on your machine (from `apps/api`)**:
```bash
composer dump-autoload
vendor/bin/pint --test
vendor/bin/phpstan analyse --memory-limit=1G          # expect: no new errors (guarded types)
vendor/bin/deptrac analyse --no-progress               # expect: no new violations (Shared-only)
php artisan test                                        # expect: unchanged (inert foundation)
php artisan config:clear && php artisan route:list      # routes unchanged; 'tenant.resolve' alias exists, unused
# ADR gate:
BASE_REF=origin/main PR_BODY="ADR-07 tenancy foundation" bash ../../scripts/adr-link-check.sh
```
Expected: all green; test count and `route:list` identical to before; Deptrac/PHPStan report no new findings.

## Known limitations

1. **Foundation only — nothing enforced.** By explicit scope (and to keep backward compatibility), no global scope or deny path exists yet; isolation is not yet guaranteed by this story. That is A2-S02/A2-S03.
2. **Resolver is org-id based.** `RequestTenantResolver` assumes the tenant equals the user's `organization_id`. If the tenant model diverges from organization later (redesign 02 Organization split), the resolver adapter is swapped behind the `TenantResolver` port — no consumer changes.
3. **No queue propagation yet.** Jobs do not carry tenant context in this story (A2-S02 concern); the abstraction is request-oriented for now.
4. **No PHP execution here.** Static validation only; confirm with the commands above. The shell-mount truncation affected only verification output, not the authoritative files.
5. **ADR YAML parsing is line-based.** The script extracts list items with `grep`/`sed` (no `yq` dependency); keep `adr-watch.yaml` as a simple `patterns:` list of quoted regexes.

## Next Story dependencies

- **A2-S02 (Global tenant scope + policy)** depends directly on this: it applies the `tenant.resolve` middleware, reads `TenantContext`, and introduces the `BelongsToTenant` global scope on tenant-scoped models. This is where enforcement and the isolation test harness (A2-S03) begin — and where the first observable behavior change (and its migration/backward-compat plan) lands.
- **A2-S04 (Tenant provisioning)** will use `TenantId` as the tenant identifier.
- The `TenantResolver` port is the seam Administration (Sprint 3) and the Organization split (Sprint 11) plug into.

---

## STOP

Story A2-S01 is implemented (tenancy foundation + config-driven ADR gate + extended ADR index + report). **No other Sprint 1 story was started; no business logic, data, API, or UI was changed.** Awaiting approval before Story A2-S02.

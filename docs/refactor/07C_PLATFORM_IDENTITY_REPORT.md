# STEP 5C - Chunk C2: `App\Domains\Identity` -> `App\Platform\Identity` (report)

**Scope:** ONLY Identity -> Platform/Identity. No other domain touched. No schema, no behavior, no URL change.
**Runner:** `scripts/refactor-c2-platform-identity.ps1` (host move + long-path-safe blanket rewrite + Filament discovery patch + docker verify).
**Precondition:** run ONLY after C1 (`App\Shared`) is confirmed green (`php artisan test`).

## Execution note
My executor can't run `composer`/`php artisan`; the artisan/test outputs are produced by the script on your machine (§ pending). Run:
```powershell
cd "D:\Claude_Files\Projects\LMS\CoreLMS Implementation\corelms"
powershell -ExecutionPolicy Bypass -File scripts/refactor-c2-platform-identity.ps1
```

## Files moved
- `apps/api/app/Domains/Identity/` -> `apps/api/app/Platform/Identity/` (entire tree: Actions, Database{Migrations,Factories,Seeders}, Enums, Events, Exceptions, Filament/Resources(UserResource), Http{Controllers,Middleware(EnforceAdminMfa),Requests,Resources}, Listeners, Models{User,UserDevice,UserOtp,UserProfile}, Notifications, Policies{User,Device}, Providers(IdentityServiceProvider), Services{Device,Mfa,Otp}, routes{auth,devices,profile}).
- Migration files move with the domain; **table names unchanged -> zero schema impact**.

## Namespaces changed
- `namespace App\Domains\Identity\...;` -> `namespace App\Platform\Identity\...;` in every moved file (e.g. `App\Platform\Identity\Models\User`, `...\Services\MfaService`, `...\Http\Middleware\EnforceAdminMfa`, `...\Providers\IdentityServiceProvider`).
- No `composer.json` change (PSR-4 `App\ => app/` maps `App\Platform\Identity\*` -> `app/Platform/Identity/*`).

## Middleware updated
- `EnforceAdminMfa` FQCN `App\Domains\Identity\Http\Middleware\EnforceAdminMfa` -> `App\Platform\Identity\Http\Middleware\EnforceAdminMfa`. Updated in: `AdminPanelProvider::authMiddleware([...])` import, and any alias in `bootstrap/app.php` (blanket rewrite covers both).

## Guards updated
- `config/auth.php` provider model reference `App\Domains\Identity\Models\User` -> `App\Platform\Identity\Models\User` (via blanket rewrite). Guard `web`/`sanctum` names unchanged.

## Providers updated
- `bootstrap/providers.php`: `App\Domains\Identity\Providers\IdentityServiceProvider` -> `App\Platform\Identity\Providers\IdentityServiceProvider`.

## Policy updates
- `UserPolicy`, `DevicePolicy` move with Identity; their registration in `IdentityServiceProvider` (`Model::class => Policy::class`) updates automatically since both FQCNs are rewritten.

## Auth updates
- `config/auth.php` User model (above). Sanctum/session config keys unchanged; only the User class path changed.

## Sanctum updates
- No `App\Domains\Identity` class is referenced in `config/sanctum.php` (stateful domains are env). `HasApiTokens` on the moved `User` model is unaffected. No change beyond the model path.

## Filament updates (the one non-blanket fix)
- `AdminPanelProvider` discovery loop builds paths from strings: `app_path("Domains/{$domain}/...")` + `"App\\Domains\\{$domain}\\..."`. A blanket rewrite does NOT touch this template, so after the move it would look in the now-empty `app/Domains/Identity/Filament/Resources` and **drop UserResource**.
- Fix (script step C2.3): inject an explicit branch into the loop -
  ```php
  if ($domain === 'Identity') {
      $panel->discoverResources(
          in: app_path('Platform/Identity/Filament/Resources'),
          for: 'App\Platform\Identity\Filament\Resources',
      );
      continue;
  }
  ```
  `navigationGroups(['Identity', ...])` (display labels) is left untouched.

## Imports updated
- Every `use App\Domains\Identity\...;` / FQCN across `apps/api/**/*.php` (heavy consumers: Learning/Commerce/etc. reference `App\Domains\Identity\Models\User` in type hints, factories, tests, seeders; `DatabaseSeeder` calls `IdentitySeeder`/`RolePermissionSeeder`). All rewritten. Two literal passes handle `App\\Domains\\Identity` (config strings) and `App\Domains\Identity` (code). Long paths handled via `\\?\`.

## Artisan outputs  PENDING (paste from the run)
```
composer dump-autoload      -> (paste)
php artisan optimize:clear   -> (paste)
php artisan config:clear     -> (paste)
php artisan route:list (tail)-> (paste - identical URIs to before)
```

## Tests  PENDING (paste from the run)
```
php artisan test             -> (paste - same pass count as C1 baseline)
```

## Remaining references
- Script asserts **zero** remaining `App\Domains\Identity` (both slash forms). Expected: none. Paste the "OK: no remaining..." line (or any SKIP/remaining entries).

## Risks
| # | Risk | Status |
|---|------|--------|
| R1 | Filament UserResource lost (string-built discovery) | Covered - explicit Platform branch injected (C2.3). If script prints "anchor not found", patch `AdminPanelProvider` manually. |
| R2 | `config/auth.php` User model missed | Covered - blanket rewrite (single + double slash) |
| R3 | `EnforceAdminMfa` middleware alias / import stale | Covered - blanket rewrite |
| R4 | Long-path read failure (deep Identity Filament pages) | Covered - `\\?\` prefix + skip-and-report |
| R5 | Native docker stderr aborts verify | Covered - `ErrorActionPreference=Continue` before docker |

## Acceptance criteria
| Criterion | Status |
|-----------|--------|
| authentication / login works | verify (API auth + `/admin` login) |
| admin login works | verify (`/admin`) |
| Sanctum works | verify (token endpoints) |
| middleware resolves | verify (no boot error; `EnforceAdminMfa` loads) |
| `php artisan test` passes | PENDING (expected: same as baseline) |
| `route:list` passes (unchanged URIs) | PENDING |
| no `App\Domains\Identity` references remain | asserted by script (paste to confirm) |

## STOP
C2 only. **Do not proceed to C3 (Notifications).** After you paste `route:list` + `test` + the "no remaining" line, I finalize this report to PASS and wait for your approval before C3.

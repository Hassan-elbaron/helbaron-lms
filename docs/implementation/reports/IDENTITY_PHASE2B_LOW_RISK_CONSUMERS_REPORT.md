# Identity Cleanup — Phase 2B: Low-Risk Consumers (Report)

> Chief Enterprise Architect. Phase 2B migrates only the audit's low-risk Identity consumers onto `IdentityContracts`. **No policy, authorization logic, model relation, or high-risk `User` ownership path was touched. No API or database schema changed.** Runtime gates could not run here (no PHP/Composer) — marked **"Not verifiable from repository."**

---

## Executive Summary

Three low-risk consumer groups were migrated off the concrete Identity surface: the eight cross-context seeders that imported the `Role` enum now use literal role slugs; `TrainerController`/`TrainerResource` read through `UserLookupPort::instructors()` and render a `UserRef` instead of the `User` model; and CRM's `InviteMemberAction` resolves a user id via `UserLookupPort::idByEmail()` instead of `User::where('email')`. The fourth item — `PlatformOverview`'s user count — is **blocked**: `UserLookupPort` has no count method and the instructions forbid inventing one, so the widget is left unchanged and the missing contract is documented. All migrations are behavior- and JSON-preserving; the only Identity dependency they carried has been removed (or, for the blocked item, retained and flagged).

---

## Files Modified

Eleven files, all consumers (no Identity-owned file changed this phase):

| # | File | Change |
|---|------|--------|
| 1 | `app/Domains/Authoring/Database/Seeders/AuthoringSeeder.php` | Removed `Role` import; `[Role::Admin, Role::Instructor]` → `['admin', 'instructor']`. |
| 2 | `app/Domains/Certification/Database/Seeders/CertificationSeeder.php` | Removed `Role` import; `Role::Admin->value` → `'admin'`. |
| 3 | `app/Platform/Notifications/Database/Seeders/NotificationsSeeder.php` | Same (`'admin'`). |
| 4 | `app/Domains/Live/Database/Seeders/LiveSeeder.php` | Same (`'admin'`). |
| 5 | `app/Domains/Crm/Database/Seeders/CrmSeeder.php` | Same (`'admin'`). |
| 6 | `app/Contexts/Analytics/Database/Seeders/AnalyticsSeeder.php` | Same (`'admin'`). |
| 7 | `app/Contexts/Commerce/Database/Seeders/CommerceSeeder.php` | Same (`'admin'`). |
| 8 | `app/Domains/Catalog/Database/Seeders/CatalogSeeder.php` | Removed `Role` import; `Role::Instructor->value` → `'instructor'`. (`User` import retained — see Remaining Coupling.) |
| 9 | `app/Domains/Catalog/Http/Controllers/Api/V1/TrainerController.php` | `User::query()...` → `UserLookupPort::instructors()` (method-injected); removed `User` import. |
| 10 | `app/Domains/Catalog/Http/Resources/TrainerResource.php` | Now consumes `UserRef` (fields `publicId`/`name`/`headline`/`avatarPath`); removed `User` import. |
| 11 | `app/Domains/Crm/Actions/Organization/InviteMemberAction.php` | Constructor-injects `UserLookupPort`; `User::where('email')->first()` → `idByEmail()`; removed `User` import. |

**Not modified (blocked):** `app/Filament/Widgets/PlatformOverview.php` — see Blocked Items.

---

## Dependencies Removed

Cross-context Identity `use`-import sites eliminated this phase: **11**.

- `App\Platform\Identity\Enums\Role` — removed from **8** seeders (Authoring, Certification, Notifications, Live, Crm, Analytics, Commerce, Catalog). Role values are now the literal slugs the seeder already produced (`'admin'`, `'instructor'`), identical to `Role::Admin->value` / `Role::Instructor->value`.
- `App\Platform\Identity\Models\User` — removed from **3** consumers: `TrainerController`, `TrainerResource`, `InviteMemberAction`. Each now depends only on `IdentityContracts` (`UserLookupPort` / `UserRef`).

Identity-internal seeders (`RolePermissionSeeder`, `IdentitySeeder`) were intentionally **left untouched**: they live inside the Identity context (intra-layer, not a cross-context violation) and `RolePermissionSeeder` is the canonical owner of the role definitions.

---

## Behavior Compatibility

- **Seeders:** slug strings are byte-identical to the enum values (`Role::Admin->value === 'admin'`, `Role::Instructor->value === 'instructor'`), verified against `Role.php`. Assigned roles, permission grants, and idempotency are unchanged.
- **TrainerController:** `UserLookupPort::instructors()` reproduces the controller's original query verbatim (built in Phase 2A) — `where('is_active', true)` + `whereHas('roles', name = 'instructor')` + `with('profile')` + `orderBy('name')` — so the same users, in the same order, are returned.
- **InviteMemberAction:** `idByEmail()` runs the same `where('email', …)` lookup and returns the same nullable id (now typed `?int`); `user_id` is set identically (null when no account matches). Transaction boundary, idempotency check, and `MemberInvited` dispatch are unchanged.
- *(Runtime confirmation — Not verifiable from repository; see Validation.)*

---

## JSON Compatibility

`TrainerResource` output is unchanged — same keys, same values, same order:

| Key | Before (`User`) | After (`UserRef`) | Same value? |
|-----|-----------------|-------------------|-------------|
| `id` | `$user->public_id` | `$ref->publicId` | Yes (both the UUIDv7 public id) |
| `name` | `$user->name` | `$ref->name` | Yes |
| `headline` | `$user->profile?->bio` | `$ref->headline` (mapped from `profile.bio`) | Yes |
| `avatar_path` | `$user->profile?->avatar_path` | `$ref->avatarPath` (mapped from `profile.avatar_path`) | Yes |

No key added or removed; the `GET /api/v1/trainers` response schema is identical. `InviteMemberAction` returns the same `OrganizationMember`, so its endpoint JSON is unchanged.

---

## Remaining Identity Coupling

- **`CatalogSeeder` still imports `App\Platform\Identity\Models\User`** — it *provisions* trainer accounts (`User::firstOrCreate(...)`, `assignRole`, `profile()->firstOrCreate`). That is a **write**, which `UserLookupPort` (read-only) cannot cover. Removing it needs a user-provisioning contract (e.g. a `UserProvisioningPort` / a seeding-time factory), out of this low-risk read-only scope. Flagged for a later phase.
- **`PlatformOverview` still imports `User`** (blocked — see below).
- Everything else in scope is fully decoupled.

The broader high-risk coupling (18 policies + gates, ~21 `belongsTo(User)` ownership relations, `RegisterUserAction`'s default-role assignment, `UserRegistered` event consumers, `EnforceAdminMfa`, factories/tests) is untouched by design and remains for later phases.

---

## Blocked Items

**`PlatformOverview` user count — BLOCKED (missing contract).** Line 31 uses `User::query()->count()`. `UserLookupPort` exposes `refById`, `refByPublicId`, `idByEmail`, `instructors()` — **no count method**. Per instruction ("if the method does not exist, stop and report the missing contract; do NOT invent new port methods"), the widget was left unchanged and still imports `User`.

**Required contract to unblock (documented, not implemented):** add a counting method to `UserLookupPort`, e.g.

```php
/** Total number of user accounts (respects soft-deletes like the current count). */
public function totalCount(): int;
```

Backed by a one-line adapter (`User::query()->count()`). Once added and bound, `PlatformOverview` becomes `$users->totalCount()` and drops its `User` import. This is the only new port method the phase surfaced as required; adding it is deferred to an approved contract-change phase.

---

## Risk Assessment

- **Overall: low.** Seeder changes are literal-for-enum substitutions (dev/seed-time only, not a runtime request path). The two runtime consumers (`TrainerController`, `InviteMemberAction`) delegate to a Phase-2A adapter that reproduces their original queries; JSON and behavior are preserved.
- **Watch items:** (a) `TrainerResource` now receives a `UserRef`, not a `User` — confirmed field-compatible, but verify the trainers endpoint response in tests; (b) `InviteMemberAction` is now constructor-injected — confirm the container resolves it (it extends `BaseAction`, which has no constructor, so injection is clean); (c) a stray import-formatting artifact (two `use` on one line) was introduced during import removal and then corrected in all six affected seeders — re-confirm with Pint.
- **Environmental risk:** no PHP/Composer here; nothing machine-verified. Mitigated by authoritative file reads + `file(1)`; confirm on a PHP environment before the next phase.

---

## Next Step

Await authorization for the next phase. Two natural follow-ons: (a) an **approved contract-change** adding `UserLookupPort::totalCount()` to unblock `PlatformOverview` (and a `UserProvisioningPort` to decouple `CatalogSeeder`); then (b) the medium-risk **actions/services** migration (`CurrentUserPort`/`UserRef` into actions), keeping the high-risk **policies (`Actor`)** and **ownership relations** for last. Do not begin until instructed. Run the toolchain (below) on a PHP-capable environment to confirm Phase 2B is green first.

---

## Validation

Run if available (attempted here):

```
composer dump-autoload      -> Not verifiable from repository (php/composer not available)
vendor/bin/pint             -> Not verifiable from repository
vendor/bin/phpstan analyse  -> Not verifiable from repository
vendor/bin/deptrac analyse  -> Not verifiable from repository
php artisan test            -> Not verifiable from repository
```

Static verification performed here (repository evidence, via authoritative file reads):

- **Role enum removed** from all 8 seeders; slug literals (`'admin'` / `'instructor'`) confirmed in place; the only remaining `Role::` token is `SpatieRole::findByName(...)` (the Spatie model, not the Identity enum — this caused a false "STILL HAS Role" in a substring grep, disproven by direct read).
- **`User` import removed** from `TrainerController`, `TrainerResource`, `InviteMemberAction`; each now imports `UserLookupPort` / `Contracts\Data\UserRef`. `InviteMemberAction` body uses `$this->users->idByEmail(...)` → `user_id`.
- **`PlatformOverview` unchanged** — still `User::query()->count()` with the `User` import (blocked, as reported).
- **Integrity:** all modified files report as *"PHP script … text"* via `file(1)` (no NUL / no `data`). The transient one-line-`use` join from import removal was corrected in all six affected seeders (verified by read).
- **Scope:** no policy, gate, authorization logic, model relation, migration, route, or API file was modified; no Identity-owned file changed this phase.

Run the commands above on a PHP-capable environment to obtain live pass/fail before the next phase.

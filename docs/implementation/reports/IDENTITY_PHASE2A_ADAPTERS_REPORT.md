# Identity Cleanup — Phase 2A: Adapters & User→Actor (Report)

> Chief Enterprise Architect. Phase 2A implements the Identity-side adapters for the four Phase-1 ports, makes `User implement Actor`, and binds the ports in `IdentityServiceProvider`. **No consumer was migrated. No policy, controller, service/action outside Identity, model outside Identity, API, or database schema was modified.** Runtime gates could not run here (no PHP/Composer) — marked **"Not verifiable from repository."**

---

## Executive Summary

The `IdentityContracts` ports now have concrete implementations, all confined to the Identity context — the only layer permitted to touch the `User` model, Spatie roles/permissions, and the auth guard. `User` now declares `implements Actor`, gaining exactly two additive methods (`actorId()`, `toUserRef()`); its `hasRole()` requirement is already satisfied by the existing Spatie `HasRoles` trait, and its `Authenticatable`/`Authorizable` requirements by the framework base class. The four adapters are registered in `IdentityServiceProvider::register()` so that any later phase can type-hint a port and receive the working implementation. This phase is behavior-preserving: the adapters are new and nothing consumes them yet; the `User` change is purely additive; no runtime path was altered.

---

## Files Modified

Modified (2, both inside Identity):

| File | Change |
|------|--------|
| `app/Platform/Identity/Models/User.php` | Added `implements Actor` (alongside `FilamentUser, HasName`); imported `Actor` + `UserRef`; added `actorId(): int` and `toUserRef(): UserRef`. Nothing else changed. |
| `app/Platform/Identity/Providers/IdentityServiceProvider.php` | Imported the 4 ports + 4 adapters; added 4 `bind()` calls in `register()`. |

Created (4, all inside Identity):

| File |
|------|
| `app/Platform/Identity/Adapters/CurrentUserAdapter.php` |
| `app/Platform/Identity/Adapters/UserLookupAdapter.php` |
| `app/Platform/Identity/Adapters/UserPermissionAdapter.php` |
| `app/Platform/Identity/Adapters/UserRoleAdapter.php` |

No file outside `app/Platform/Identity/` was created or modified by this phase.

---

## Adapters Created

All `final`, all in `App\Platform\Identity\Adapters`, each implementing one port:

- **`CurrentUserAdapter implements CurrentUserPort`** — `currentUserId()` → `Auth::id()` (cast int); `currentUserRef()` → `Auth::user()` mapped via `toUserRef()` when it is a `User`; `isAuthenticated()` → `Auth::check()`. No authentication performed.
- **`UserLookupAdapter implements UserLookupPort`** — `refById`/`refByPublicId` eager-load `profile` then `toUserRef()`; `idByEmail` → `where('email')->value('id')` (cast int|null); `instructors()` **mirrors the existing `TrainerController` query exactly** (`is_active = true` + `whereHas('roles', name = 'instructor')` + `with('profile')` + `orderBy('name')`), mapped to `list<UserRef>`.
- **`UserPermissionAdapter implements UserPermissionPort`** — `can()` delegates to the user's Gate ability (`$user->can($permission)`), the same mechanism used today; `canAny()` short-circuits over the list. Missing user ⇒ `false`.
- **`UserRoleAdapter implements UserRolePort`** — `hasRole()`/`hasAnyRole()` delegate to Spatie (`$user->hasRole/hasAnyRole`); `rolesFor()` → `getRoleNames()` as `list<string>`. Missing user ⇒ `false`/`[]`.

---

## User Actor Implementation

`class User extends Authenticatable implements Actor, FilamentUser, HasName`. The `Actor` contract requires four members; three are pre-satisfied, so only two methods were added:

- **`actorId(): int`** → `(int) $this->getKey()` — the stable internal id (mirrors `$user->id`).
- **`toUserRef(): UserRef`** → `new UserRef(id, publicId, name, avatarPath: profile?->avatar_path, headline: profile?->bio)` — reads only public display fields; never the `$hidden` secrets or account/PII internals.
- **`hasRole($roles): bool`** — already provided by the Spatie `HasRoles` trait (compatible signature); not redeclared.
- **`Authenticatable` + `Authorizable`** — already provided by `Illuminate\Foundation\Auth\User` (the base class) and its `Authorizable` trait; nothing added.

No existing property, cast, relation, or method was changed.

---

## Bindings Added

In `IdentityServiceProvider::register()`, four singleton-less `bind()` calls (each present exactly once; verified no duplicates anywhere in the app):

```php
$this->app->bind(CurrentUserPort::class, CurrentUserAdapter::class);
$this->app->bind(UserLookupPort::class, UserLookupAdapter::class);
$this->app->bind(UserPermissionPort::class, UserPermissionAdapter::class);
$this->app->bind(UserRolePort::class, UserRoleAdapter::class);
```

Identity is the **only** layer that binds these ports (it alone may reference the `User` model). Consumers will depend on the interfaces, never the adapters. No `UserOrganizationPort` binding (still deferred).

---

## Behavior Compatibility

- **Additive only.** The adapters are new code with **no callers** — autoloading and registering them changes no existing runtime path. The `User` change adds two methods + one interface; every existing method/property/cast is untouched, so all current behavior (auth, Filament, RBAC, serialization) is unchanged.
- **`instructors()` parity.** The adapter reproduces the `TrainerController` query verbatim (same filters, eager-load, ordering) so that when the controller migrates in a later phase the result set is identical.
- **Decision parity.** Permission checks delegate to the same `$user->can()` Gate path and role checks to the same Spatie methods used today — the ports are facades, not a reimplementation.
- **Serialization safety unchanged.** `toUserRef()` reads only whitelisted public fields; the model's `$hidden` set is untouched.
- *(Runtime confirmation — syntax/lint/analyse/tests — Not verifiable from repository; see Validation.)*

---

## Security Constraints

- **No secret leaves Identity.** `UserRef` (and thus `toUserRef()`) carries only `id`, `publicId`, `name`, `avatarPath`, `headline`. The `$hidden` secrets (`password`, `remember_token`, `two_factor_secret`, `two_factor_recovery_codes`) and account/PII internals (`email`, `phone`, `is_active`, verification/lock/MFA columns, `locale`) are never projected.
- **No new authentication/authorization logic.** Adapters *delegate* to the existing guard/Gate/Spatie engine; they define no rules, grants, or credentials, and perform no login/token/MFA work.
- **Ownership containment.** Only Identity references the `User` model and binds the ports; the dependency direction (`IdentityContracts` → Shared only; everyone → `IdentityContracts`) is preserved.

---

## Deferred Work

- **`UserOrganizationPort`** — not created (org-membership ownership between Identity and CRM still unresolved).
- **Consumer migration** — no controller, resource, policy, service, action, seeder, listener, or middleware was changed. The 94 cross-context Identity import sites are untouched this phase by design.
- **`refByUsername`** — remains excluded (no `username` column).
- **Policy `Actor` type-swap**, quick wins (`Role` slugs, events), display/lookup, and relations — all deferred to their respective later phases.

---

## Risk Assessment

- **This phase: low risk.** Two additive edits inside Identity + four unconsumed adapters + four container bindings. Fully revertible. Worst case (an imperfect adapter signature) is a compile/analyse error caught before any consumer depends on it.
- **Watch items:** (a) `Actor::hasRole` vs Spatie trait compatibility — must be confirmed green by PHPStan once a PHP environment is available (static reasoning says compatible: trait's untyped first param widens the interface's `string|array`, matching `: bool`); (b) the container now resolves four new interfaces — confirm no accidental early resolution during boot (none introduced; binds are lazy).
- **Environmental risk:** no PHP/Composer here, so nothing is machine-verified. Mitigated by matching established conventions and static checks below; must be confirmed before Phase 2B.

---

## Next Step

Await authorization for the next phase. Recommended sequencing per the audit (bottom-up): **Phase 2B — Quick Wins** (seeder `Role` enum → role slugs; `UserRegistered` event-consumer decision; `PlatformOverview` count / `EnforceAdminMfa` wiring) — the lowest-risk consumer migrations that need no new contracts. Do not begin until instructed. Before proceeding, run the toolchain (below) on a PHP-capable environment to confirm Phase 2A is green.

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

Static verification performed here (repository evidence):

- **Integrity:** all four adapters + the modified `User.php` report as *"PHP script … text"* via `file(1)` (no NUL / no `data`).
- **Actor wiring:** `User` line 26 = `class User extends Authenticatable implements Actor, FilamentUser, HasName`; `actorId()` and `toUserRef()` present; `hasRole` not redeclared (inherited from Spatie).
- **Bindings:** all four `bind(...Port::class, ...Adapter::class)` present exactly once in `IdentityServiceProvider::register()` (confirmed by direct file read); no duplicate binding of any port anywhere in `app/`.
- **Scope:** the only files changed/added by this phase live under `app/Platform/Identity/` (`Models/User.php`, `Providers/IdentityServiceProvider.php`, `Adapters/*`). Other entries in the working tree (`app/Contexts/Learning/Playback/*` deletions/moves, Media/Curriculum, prior reports) are **pre-existing uncommitted work from earlier phases**, not touched here.
- **No consumer migrated:** no policy/controller/service/action/model outside Identity, no API, no migration was modified.

Run the commands above on a PHP-capable environment to obtain live pass/fail before the next phase.

# Identity Cleanup — Phase 1: Contracts Created (Report)

> Chief Enterprise Architect. Phase 1 of the Identity decoupling programme: create the `IdentityContracts` layer only — interfaces + one DTO. **No consumers, policies, controllers, services, actions, non-Identity models, APIs, or database schema were modified. No adapters were implemented and nothing was bound in any service provider. The User model was not changed.** Runtime gates could not run here (no PHP/Composer) — marked **"Not verifiable from repository."**

---

## Executive Summary

The `IdentityContracts` layer — previously an empty `.gitkeep` directory — now contains the full contract surface designed in `IDENTITY_CONTRACTS_SPECIFICATION.md`: one immutable DTO (`UserRef`), an `Actor` interface for Gate-compatible policy type-hints, and four ports (`CurrentUserPort`, `UserLookupPort`, `UserPermissionPort`, `UserRolePort`). These are pure type declarations — no behavior, no Eloquent, no authentication or authorization logic. Nothing consumes them yet; that is deliberate. This phase is additive and inert: it introduces the vocabulary every later phase will migrate onto, while leaving all runtime behavior, wiring, and the `User` model exactly as they were. Two designed contracts were intentionally **not** created — `UserOrganizationPort` (ownership unresolved between Identity and CRM) and any `refByUsername` method (no `username` column exists).

---

## Files Created

Six files, all under `app/Platform/Identity/Contracts/` (the Deptrac `IdentityContracts` layer, collector `app/Platform/Identity/Contracts/.*`):

| File | Kind |
|------|------|
| `app/Platform/Identity/Contracts/Data/UserRef.php` | `final readonly class` (DTO) |
| `app/Platform/Identity/Contracts/Actor.php` | `interface` |
| `app/Platform/Identity/Contracts/CurrentUserPort.php` | `interface` |
| `app/Platform/Identity/Contracts/UserLookupPort.php` | `interface` |
| `app/Platform/Identity/Contracts/UserPermissionPort.php` | `interface` |
| `app/Platform/Identity/Contracts/UserRolePort.php` | `interface` |

The pre-existing `Contracts/.gitkeep` was left in place. No other file in the repository was created or modified by this phase.

---

## Contracts Created

- **`UserRef`** (DTO) — the only user shape allowed to cross a context boundary.
- **`Actor`** (interface) — `extends Illuminate\Contracts\Auth\Authenticatable, Illuminate\Contracts\Auth\Access\Authorizable`; adds `actorId(): int`, `hasRole($roles): bool`, `toUserRef(): UserRef`. Because it extends the two framework auth contracts that Laravel's Gate already injects, a policy typed `Actor $user` receives the same principal and `$user->can(...)` still works — this is what will let policies drop the concrete `App\Platform\Identity\Models\User` type-hint in a later phase. The `hasRole($roles)` signature is Spatie-compatible (untyped/`mixed` first param on the trait widens the interface's `string|array`), so the `User` model will satisfy `Actor` with no new authorization code.
- **`CurrentUserPort`** — `currentUserId(): ?int`, `currentUserRef(): ?UserRef`, `isAuthenticated(): bool`. Reads the already-authenticated principal; performs no authentication.
- **`UserLookupPort`** — `refById(int): ?UserRef`, `refByPublicId(string): ?UserRef`, `idByEmail(string): ?int`, `instructors(): list<UserRef>`. Resolve/list users as refs/scalars only.
- **`UserPermissionPort`** — `can(int $userId, string): bool`, `canAny(int $userId, array): bool`. Permission decisions for non-policy code.
- **`UserRolePort`** — `hasRole(int $userId, string): bool`, `hasAnyRole(int $userId, array): bool`, `rolesFor(int $userId): list<string>`. Role decisions / slug enumeration.

All ports declare **decisions and queries, never rules or logic**; all return `UserRef`/scalars/arrays and never an Eloquent model.

---

## DTO Structure

`UserRef` (`final readonly`, promoted constructor):

| Field | Type | Purpose |
|-------|------|---------|
| `id` | `int` | Internal join key — ownership FKs (`user_id`/`owner_id`/`requested_by`) and owner checks. |
| `publicId` | `string` | External identity (UUIDv7 `public_id`) — the only id exposed in APIs/URLs. |
| `name` | `string` | Display name (also backs Filament `HasName`). |
| `avatarPath` | `?string` | Public avatar path (`profile.avatar_path`); null when absent. Defaulted `null`. |
| `headline` | `?string` | Public profile summary (`profile.bio`, rendered as "headline"); nullable. Defaulted `null`. |

**Excluded by design** (documented in the class): secrets `password`, `remember_token`, `two_factor_secret`, `two_factor_recovery_codes`; PII/account internals `email`, `phone`, `is_active`, `email_verified_at`, `phone_verified_at`, `locked_until`, `failed_login_count`, `mfa_enabled`, `two_factor_confirmed_at`, `locale`. Authorization is exposed as a decision on a port, never as data on the ref.

---

## Deferred Contracts

Designed in the specification but intentionally **not** created in this phase:

- **`UserOrganizationPort`** — deferred (see below).
- **`UserLookupPort::refByUsername` / any username lookup** — omitted (see below).
- **Adapters** for all ports, **`User implements Actor`**, and all **service-provider bindings** — deferred to Phase 2 (implementation), by instruction. Consumer migration (quick wins, display/lookup, actions/services, policies, relations) follows in later phases per the audit's bottom-up order.

---

## Why username was excluded

The current database schema has **no `username` column**. The `users` migration defines `name`, `email` (unique), `phone` (nullable unique), and `public_id` — the credential/identity keys are **email** (login) and **public_id** (external addressing). A `refByUsername` method would therefore be unsatisfiable by any adapter today and would advertise a lookup the system cannot perform. It is omitted until (and unless) a username field is introduced; email (`idByEmail`) and public id (`refByPublicId`) cover every audited lookup need.

---

## Why UserOrganizationPort was deferred

Organization membership is **not owned by Identity**. In the current codebase membership lives in **CRM** (`OrganizationMember`, consumed by `InviteMemberAction`), not in the Identity context. Defining a `UserOrganizationPort` now would force a premature decision about who owns and implements user↔organization queries — Identity, CRM, or a future dedicated Organization context. Committing an interface before that ownership is resolved risks placing it in the wrong layer and churning the contract later. It is deferred until the Identity-vs-CRM/Organization boundary is decided; none of this phase's consumers need it.

---

## Architecture Compatibility

- **Layer placement is correct.** All six files sit in `app/Platform/Identity/Contracts/`, which Deptrac collects as the `IdentityContracts` layer. Per `deptrac.yaml`, `IdentityContracts` may depend on **`Shared`** only, and **every** platform/context layer may depend on `IdentityContracts` — so these contracts are consumable everywhere without a new rule.
- **No forbidden dependencies introduced.** `Actor` references only framework contracts (`Illuminate\Contracts\Auth\*`), which are not a Deptrac layer; the ports and DTO reference only `UserRef` (same layer) and PHP primitives. Nothing here imports `Identity\Models\*` or any context.
- **Gate compatibility preserved by construction.** `Actor` extends exactly the two contracts the Gate injects, so the eventual policy type-swap is behavior-neutral.
- **Additive and inert.** Because nothing is bound or consumed, autoloading these files changes no runtime path. *(Static reasoning; runtime confirmation — see Validation — Not verifiable from repository.)*

---

## Migration Readiness

The contract vocabulary now exists for the bottom-up programme:

1. **Phase 1 (this phase): contracts + DTO + `Actor`.** ✅ Done.
2. **Phase 2: adapters + `User implements Actor` + bindings** in `IdentityServiceProvider` (`UserOrganizationPort` still deferred).
3. **Phase 3: quick wins** — seeder `Role` enum → role slugs; `UserRegistered` event decision; middleware/count wiring.
4. **Phase 4: display + lookup** — `TrainerController`/`TrainerResource` → `UserLookupPort`; CRM invite → `idByEmail`.
5. **Phase 5: actions/services** — expand-and-contract to `int $userId`/`UserRef` (+ `CurrentUserPort`).
6. **Phase 6: policies/gates** → type-hint `Actor`; non-policy checks → permission/role ports.
7. **Phase 7: ownership relations last**; keep FK columns (no schema change).

---

## Risk Assessment

- **This phase: negligible risk.** Six additive type declarations, zero consumers, zero bindings, no model/schema/API change; fully revertible by deleting the files. Worst case if a signature is imperfect is a later-phase compile error caught by PHPStan before anything ships.
- **Watch items for later phases (not incurred now):** (a) the `Actor::hasRole` signature must stay compatible with Spatie's trait when `User implements Actor` lands — validate with PHPStan/policy tests in Phase 2; (b) the policy type-swap (Phase 6) is the highest-blast-radius step and must be one reviewed change with green policy tests; (c) cross-context `$enrollment->user` readers (Certification, Notifications) mirror the Curriculum seams and need coordinated handling at the relations step.
- **Environmental risk:** no PHP/Composer here means Phase 1 has not been machine-verified (syntax/lint/analyse/tests). Mitigated by matching the established DTO/port conventions and by static checks below; must be confirmed on a PHP-capable environment before Phase 2.

---

## Next Step

Await explicit authorization for **Identity Cleanup Phase 2 — Adapters & Wiring**: implement the four ports as Identity adapters, add `UserRef` mapping, declare `User implements Actor` (additively), and bind the ports in `IdentityServiceProvider` — with `UserOrganizationPort` still deferred. Do not begin until instructed. Before Phase 2, run the toolchain (below) on a PHP environment to confirm Phase 1 is green.

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

- **Files present & well-formed:** all six exist under `app/Platform/Identity/Contracts/`; `file(1)` reports each as *"PHP script … UTF-8 text"* (no NUL bytes / no `data`).
- **Interfaces vs class:** the `Contracts` tree contains exactly **one** `class` — `final readonly class UserRef` — and **five** `interface` declarations; no concrete adapter class exists.
- **No out-of-scope edits:** `git status` shows the six new Contracts files as the only additions from this phase; `git diff --name-only` confirms **no** change to `app/Platform/Identity/Models/User.php`, `IdentityServiceProvider`, or `bootstrap/providers.php`. (Other untracked/deleted paths in the working tree — Media, Curriculum, prior reports — are pre-existing from earlier phases, not this one.)
- **Dependency direction:** `Actor` imports only `Illuminate\Contracts\Auth\{Authenticatable, Access\Authorizable}` + `UserRef`; the ports import only `UserRef`; `UserRef` imports nothing. No `Identity\Models\*` or context import in the layer.

No consumers, policies, controllers, services, actions, non-Identity models, APIs, database schema, service-provider bindings, adapters, or the `User` model were modified. Only the six contract files were created. Run the commands above on a PHP-capable environment to obtain live pass/fail before Phase 2.

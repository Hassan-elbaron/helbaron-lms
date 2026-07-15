# Identity Contracts — Specification (Design Only)

> Chief Enterprise Architect. Design of the Identity Contracts layer that lets every context read/authorize users without importing `App\Platform\Identity\Models\User`. **DESIGN ONLY — no code, contract, adapter, API, or schema created or modified.** Grounded in `IDENTITY_DEPENDENCY_AUDIT.md`, the `User`/`UserProfile` models and migrations, and the Deptrac ruleset. Anything not confirmable from the repository is marked **"Not verifiable from repository."**

---

# Executive Summary

The audit found **94 cross-context imports of Identity** (82 `User`, 8 `Role`, 2 `UserRegistered`, 1 middleware, 1 seeder), dominated by **authorization** (`can`/`hasRole` inside 18 policies + 2 gates) and **ownership** (`$user->id` + ~21 `belongsTo(User)`). Identity is a **kernel**: the Deptrac ruleset already permits every context to depend on `IdentityContracts` (`app/Platform/Identity/Contracts`, which today contains only `.gitkeep`). This document specifies the full contract surface to place there so contexts consume **`IdentityContracts` only** and never `Identity\Models\User`.

The design is small because the consumed surface is small (confirmed by the audit): a read-only `UserRef` (`id`, `publicId`, `name`, `avatarPath`, `headline`), five ports (`CurrentUserPort`, `UserLookupPort`, `UserPermissionPort`, `UserRolePort`, `UserOrganizationPort`), and an `Actor` interface the `User` model implements so Laravel-Gate policies can type an interface instead of the concrete model. No authentication or authorization *logic* lives in the contracts — only shapes and query/decision signatures; Identity keeps the implementations. This is the design artifact for the Identity cleanup programme; implementation follows in later phases.

---

# Design Principles

- **Identity as Kernel.** Identity is a foundational platform capability, like Shared. Its *contracts* (`App\Platform\Identity\Contracts`) are the `IdentityContracts` Deptrac layer, which every context may depend on. The acceptable end state is "every context depends on `IdentityContracts`," **not** "no Identity dependency" — a context needing the current user or a permission check is legitimate; needing the `User` *Eloquent model* is not.
- **Contracts location (not Shared).** All ports, `UserRef`, and `Actor` live in `App\Platform\Identity\Contracts` (the `IdentityContracts` layer). They must **not** live in `Shared` — Shared is lower in the ruleset and may not reference Identity concepts. `IdentityContracts` may depend on `Shared` + framework only.
- **Read-only DTOs.** `UserRef` is `final readonly`, immutable, constructed once by Identity, never mutated by consumers. It carries data, not behavior, and never an Eloquent model.
- **No ORM leakage.** No contract returns, accepts, or exposes `Illuminate\Database\Eloquent\Model`, `User`, relations, query builders, or collections of models. Ports return `UserRef`/scalars/arrays of `UserRef`.
- **No authentication logic.** Contracts never log in, issue tokens, verify passwords/OTP/MFA, or touch sessions. `CurrentUserPort` *reads* the already-authenticated principal (resolved by the framework); it does not authenticate.
- **No authorization logic.** Contracts declare *decisions* (`can`, `hasRole`), not *rules*. The rule engine (Spatie permissions, Gate, policies) stays inside Identity/the framework; the ports are thin decision facades so business code asks "may this user id do X?" without importing the model.

---

# UserRef

Immutable projection of a user for cross-context display and ownership. `final readonly class UserRef` in `App\Platform\Identity\Contracts` (or `…\Contracts\Data`).

| Field | Type | Why it exists (evidence) |
|-------|------|--------------------------|
| `id` | `int` | Ownership FKs and owner checks — `$user->id` (×38) and `belongsTo(User)`/`user_id`/`owner_id`/`requested_by` across ~21 models. The internal join key. |
| `publicId` | `string` | External identity — `TrainerResource` renders `public_id`; UUIDv7 external id (never expose sequential `id` in APIs). |
| `name` | `string` | Display — `TrainerResource.name`, `HasName`/`getFilamentName`. |
| `avatarPath` | `?string` | Public display — `TrainerResource.avatar_path` from `profile.avatar_path` (nullable; user may have no profile/avatar). |
| `headline` | `?string` | Public display — `TrainerResource.headline` maps `profile.bio` (nullable). Named `headline` (its rendered role) rather than `bio` to keep the ref presentation-oriented. |

**Excluded fields and why (forbidden / not needed cross-context):**
- `password`, `remember_token`, `two_factor_secret`, `two_factor_recovery_codes` — the model's `$hidden` set; secrets that must never leave Identity.
- `email`, `phone` — PII; not needed *outbound* (email is a lookup *input* to `UserLookupPort`, not a ref field; no consumer renders it cross-context).
- `is_active`, `email_verified_at`, `phone_verified_at`, `locked_until`, `failed_login_count`, `mfa_enabled`, `two_factor_confirmed_at`, `locale` — authentication/account-state internals; used only inside Identity (e.g. the instructor query filters `is_active` server-side).
- Roles/permissions — not fields on the ref; exposed via `UserPermissionPort`/`UserRolePort` as decisions, not data.

Rationale: `UserRef` is the *identity + public display* slice only. Anything an authorization or account concern needs is a **decision** (port), not ref data — this keeps PII/secrets structurally out of reach of every consumer.

---

# CurrentUserPort

Reads the already-authenticated principal for business code (replaces `request()->user()->id` / injected `User $user` for "who am I acting as"). `interface CurrentUserPort` in `App\Platform\Identity\Contracts`.

| Method | Purpose |
|--------|---------|
| `currentUserId(): ?int` | The authenticated user's internal id, or null if unauthenticated (guest). Replaces `request()->user()->id` in actions/services. |
| `currentUserRef(): ?UserRef` | The authenticated user as a `UserRef`, or null. For display/ownership without the model. |
| `isAuthenticated(): bool` | Convenience guard. |

No mutation, no login. Implemented in Identity over the framework auth guard.

---

# UserLookupPort

Resolve users by identifier or list them, returning `UserRef`(s) — never models. `interface UserLookupPort` in `App\Platform\Identity\Contracts`.

| Method | Lookup by | Purpose / evidence |
|--------|-----------|--------------------|
| `refById(int $id): ?UserRef` | Id | Resolve an owner for display (e.g. record `owner_id` → name/avatar). |
| `refByPublicId(string $publicId): ?UserRef` | PublicId | Resolve from an external/API identifier. |
| `idByEmail(string $email): ?int` | Email | CRM `InviteMemberAction` (`User::where('email')->first()?->id`). Returns the id only (email is a credential-adjacent input; no ref needed). |
| `refByUsername(string $username): ?UserRef` | Username | **Designed for forward-compatibility only.** **Not verifiable from repository — there is no `username` column** (the natural credential is `email`; `public_id` is the external key). Until a username field exists this method is unsatisfiable; recommend deferring it or treating `email`/`public_id` as the identifier. Documented here because the task requested it. |
| `instructors(): list<UserRef>` | Instructor listing | `TrainerController` (`User::query()->where('is_active')->whereHas('roles','instructor')->with('profile')->orderBy('name')`). The `is_active`/role filtering stays inside Identity; consumers get `UserRef`s with `name`/`avatarPath`/`headline`. |
| `refsForOrganization(int $organizationId): list<UserRef>` | Organization membership | Members of an organization (overlaps `UserOrganizationPort`; see below — kept there as the authoritative membership port, exposed here only if a plain member list is needed). |

All list methods return `list<UserRef>`; pagination/filtering parameters may be added during implementation (kept minimal here). The `instructors()` filter set (`is_active`, role `instructor`) is an Identity implementation detail, not part of the contract.

---

# UserPermissionPort

Permission *decisions* by user id (facade over Spatie/Gate; **no rule logic in the contract**). `interface UserPermissionPort` in `App\Platform\Identity\Contracts`.

| Method | Purpose |
|--------|---------|
| `can(int $userId, string $permission): bool` | Does this user hold the permission? Replaces `$user->can('…')` (×26) in business code that only needs a boolean. |
| `canAny(int $userId, array $permissions): bool` | Any-of check (convenience for multi-permission gates). |

Policies themselves keep using Gate (via `Actor`, below); this port is for **non-policy** business code that needs a permission decision without the model. Design only — no implementation.

---

# UserRolePort

Role *decisions* by user id. `interface UserRolePort` in `App\Platform\Identity\Contracts`.

| Method | Purpose |
|--------|---------|
| `hasRole(int $userId, string $role): bool` | Single-role check — replaces `$user->hasRole('super_admin')` (×18) where only a boolean is needed. |
| `hasAnyRole(int $userId, array $roles): bool` | Any-of (e.g. `['super_admin','admin']`, as in `canAccessPanel`). |
| `rolesFor(int $userId): list<string>` | Role slugs, for consumers that must enumerate (e.g. building an instructor filter or display). |

Role slugs are strings (`super_admin`, `admin`, `instructor`, `student`) — the same values seeders use; this port also underpins retiring the `Role` enum imports.

---

# UserOrganizationPort

Organization-membership queries (CRM/organization scoping). `interface UserOrganizationPort` in `App\Platform\Identity\Contracts`.

> Evidence note: organization membership currently lives in **CRM** (`OrganizationMember`, `InviteMemberAction`), not Identity. This port is designed as the *seam* for user↔organization questions; whether Identity or CRM implements it is an implementation decision (likely CRM implements it, exposed through the `IdentityContracts` interface, OR it lives in a future Organization context). Marked accordingly.

| Method | Purpose |
|--------|---------|
| `organizationIdsFor(int $userId): list<int>` | Organizations a user belongs to (scoping queries). |
| `isMember(int $userId, int $organizationId): bool` | Membership check for authorization/scoping. |
| `roleInOrganization(int $userId, int $organizationId): ?string` | The user's role within an org (`OrganizationMember.role`), or null if not a member. |

**Not verifiable from repository:** the exact membership semantics beyond `OrganizationMember` (organization_id, user_id, role, status); confirm during implementation.

---

# Actor Interface

The bridge for **authorization/policies**, which are Laravel-Gate-coupled. `interface Actor` in `App\Platform\Identity\Contracts`.

**Problem it solves:** Laravel's Gate injects the authenticated user (an `Illuminate\Contracts\Auth\Authenticatable & …\Access\Authorizable`) into policy methods. Today policies type-hint the concrete `App\Platform\Identity\Models\User` (18 files), coupling every context to the model.

**Design (Gate-compatible):**

```
interface Actor extends
    \Illuminate\Contracts\Auth\Authenticatable,
    \Illuminate\Contracts\Auth\Access\Authorizable
{
    public function actorId(): int;                     // stable internal id (== getKey())
    public function hasRole(string|array $role): bool;  // Spatie-compatible role check
    public function toUserRef(): UserRef;               // convenience projection
}
```

- `extends Authenticatable, Authorizable` — so a policy typed `Actor $user` still receives the Gate-injected principal and `$user->can('…')` (from `Authorizable`) works unchanged.
- `actorId(): int` — replaces `$user->id` in ownership checks (`$model->user_id === $user->actorId()`).
- `hasRole(...)` — declares the Spatie method policies use (`$user->hasRole('super_admin')`), so policies need not import the model or the Spatie trait.
- `toUserRef()` — optional projection for policies that also render.

**How it replaces concrete `User` type-hints:** the `User` model **implements `Actor`** (it already extends `Authenticatable`, is `Authorizable` via the framework, and has Spatie `hasRole`; `actorId()`/`toUserRef()` are the only additions). Policies then change `function view(User $user, …)` → `function view(Actor $user, …)` and `use App\Platform\Identity\Models\User` → `use App\Platform\Identity\Contracts\Actor`. Because the Gate-injected `User` **is an `Actor`**, authorization behavior is unchanged. The `before(mixed $user, …)` guards that do `$user instanceof User` become `$user instanceof Actor`. This removes the concrete-model import from all 18 policies + the 2 gates with zero behavior change. **Not verifiable from repository** until run (PHPStan + policy tests confirm).

---

# DTO Mapping Rules

`User` → `UserRef` mapping (performed only inside Identity, e.g. a `UserRefMapper` used by the adapters):

- `id` ← `(int) $user->id`
- `publicId` ← `(string) $user->public_id`
- `name` ← `(string) $user->name`
- `avatarPath` ← `$user->profile?->avatar_path` (nullable; no profile ⇒ null)
- `headline` ← `$user->profile?->bio` (nullable)

Rules:
1. **One-way, read-only.** Map model → `UserRef`; never the reverse, never write back through a ref.
2. **Whitelist only.** Only the five fields above. Never map `$hidden` (password/tokens/2FA) or account/PII internals (email, phone, is_active, verification/lock/MFA columns, locale).
3. **No lazy model leakage.** The mapper reads `profile` (eager-load in the adapter query to avoid N+1); the resulting `UserRef` holds scalars only — the `Profile`/`User` models never escape Identity.
4. **Null-safe.** Missing profile ⇒ `avatarPath`/`headline` null; missing user ⇒ the port returns `null` (not an empty ref).
5. **Stable.** `UserRef` is immutable; equality is by `id`.

---

# Dependency Rules

- `App\Platform\Identity\Contracts\*` (ports, `UserRef`, `Actor`) = the **`IdentityContracts`** Deptrac layer. **Every** domain/context/platform layer may depend on it (already allowed: each layer's ruleset is `[Shared, IdentityContracts]`).
- `App\Platform\Identity\Models\User` and all Identity implementation = the **`Identity`** layer. **No** context may depend on it (Deptrac forbids `→ Identity`); only Identity itself and the composition root.
- `IdentityContracts` may depend on **`Shared`** + framework only; it must **not** depend on `Identity` (implementation) or any domain context.
- `Shared` must **not** depend on `IdentityContracts` or `Identity` (Shared is the lowest kernel) — hence the contracts live under Identity, not Shared.
- Adapters implementing the ports live in **`Identity`** (they read `User`) and are bound in `IdentityServiceProvider`; `UserOrganizationPort`'s implementation may live in CRM/Organization (it owns membership), still exposed via the `IdentityContracts` interface.

---

# Migration Strategy (execution order — bottom-up)

1. **Contracts + DTO + `Actor`** in `Identity\Contracts` (this spec). No behavior.
2. **Adapters** in Identity implementing the five ports + `UserRefMapper`; `User implements Actor` (additive); bind in `IdentityServiceProvider`.
3. **Quick wins:** seeders' `Role` enum → role-slug strings; `UserRegistered` per the event-DTO decision; `PlatformOverview` count + `EnforceAdminMfa` wiring behind contracts/aliases.
4. **Display + lookup:** `TrainerController`/`TrainerResource` → `UserLookupPort::instructors()` + `UserRef`; CRM `InviteMemberAction` → `UserLookupPort::idByEmail()`.
5. **Actions/Services:** expand-and-contract to accept `int $userId`/`UserRef` (+ `CurrentUserPort`); keep `User` overloads until callers move.
6. **Policies/gates:** type-hint `Actor` (one coordinated step; Gate still injects `User` which is an `Actor`); non-policy permission checks → `UserPermissionPort`/`UserRolePort`.
7. **Ownership relations LAST:** replace cross-context `belongsTo(User)` reads with `UserRef` via `UserLookupPort` where display is needed; keep FK columns (no schema change); coordinate cross-context `$…->user` readers (Certification, Notifications).
8. **Cleanup:** remove transitional overloads; migrate factories/fixtures; seed/shrink the Deptrac baseline.

---

# Compatibility Strategy (expand-and-contract)

- **Expand:** introduce all contracts + adapters and make `User implements Actor` **additively** — nothing changes for existing callers; the model keeps working.
- **Dual signatures:** services/actions gain `…ByUserId(int)` / `UserRef` methods alongside the existing `User`-typed ones (which delegate), exactly as the Curriculum programme did. Ship at each step; the model path stays until its callers migrate.
- **Policies flip in place:** because `User implements Actor`, changing a policy's type-hint from `User` to `Actor` is behavior-neutral (the same object is injected) — no dual method needed, but do it as one reviewed step with policy tests.
- **Relations keep FK columns:** severing `belongsTo(User)` removes only the relation method + import; `user_id`/`owner_id` columns stay — **no schema change**.
- **Reversible per phase:** each phase is independently shippable and revertible; the concrete-model paths remain until the final contract step removes them. Guard risky steps behind a flag.

---

# Success Criteria

1. `grep -rE 'use App\\Platform\\Identity\\(Models\\User|Enums\\Role)' app --exclude-dir Identity` → **0** (from 90 today).
2. No cross-context `belongsTo(User)`, `User::query/where/factory`, `$user->can/hasRole`, or `$user->id` on the concrete model; ownership via FK columns + `UserRef`; decisions via ports.
3. Policies type `Actor` (not `Identity\Models\User`); Gate/authorization behavior unchanged. *(Not verifiable from repository.)*
4. Every context depends only on `Shared` + `IdentityContracts` (Deptrac clean; no `→ Identity`). *(Not verifiable from repository.)*
5. Forbidden fields (`password`, `remember_token`, `two_factor_secret`, `two_factor_recovery_codes`, plus PII/account internals) provably unreachable through any contract/DTO.
6. **Behavior parity:** empty OpenAPI diff on affected endpoints; all tests green; DB schema untouched. *(Not verifiable from repository.)*
7. Metric: Identity cross-context import sites **94 → 0** (contexts consume `IdentityContracts` only).

---

# Final Recommendation

**Adopt this contract surface and implement it bottom-up, contracts-first, as the next Identity phase — but keep it minimal and resist scope creep.** The five ports + `UserRef` + `Actor` cover 100% of the audited cross-context usage (id/publicId/name/profile display, `can`/`hasRole` decisions, email/instructor/org lookup, current-user) with no authentication or authorization logic leaking out of Identity. The two design risks to manage: (1) the **`Actor` interface** must precisely match what Gate injects (extend `Authenticatable`+`Authorizable`) so policy flips are behavior-neutral — validate with policy tests; (2) `refByUsername` has **no backing column** and should be dropped or deferred until a username field exists. Sequence exactly as the migration order (quick wins → display/lookup → actions/services → policies → relations last), confirm each phase on a PHP-capable environment with an empty OpenAPI diff, and treat cross-context `$…->user` relation readers (Certification, Notifications) as coordinated work — the same lesson the Curriculum programme proved. Implementation is a separate, approved phase; this document is design only.

---

## Validation

- Field/method/column names, `$hidden`, profile fields, traits, and interfaces are from `User.php`, `UserProfile.php`, and the `users`/`user_profiles` migrations; usage counts from `IDENTITY_DEPENDENCY_AUDIT.md`. `App\Platform\Identity\Contracts` exists today as an empty (`.gitkeep`) directory — the intended home for these contracts.
- **Not verifiable from repository:** absence of a `username` column (confirmed absent in migrations, so `refByUsername` is unsatisfiable today); exact `UserOrganizationPort` semantics; and all runtime outcomes (Gate behavior, PHPStan, tests, OpenAPI diff) — these require implementation + a PHP environment.
- **No code, contract, adapter, API, or schema was created or modified.** Only this document was created.

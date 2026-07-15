# Identity Cleanup — Phase 3A: Policies & Gates → Actor (Report)

> Chief Enterprise Architect. Phase 3A migrates every authorization Policy and Gate **outside Identity** from the concrete `App\Platform\Identity\Models\User` to the `App\Platform\Identity\Contracts\Actor` interface. **No model relation, factory, seeder, Filament resource/widget, API, or database schema was touched. No authorization decision, permission name, or role name changed.** Runtime gates could not run here (no PHP/Composer) — marked **"Not verifiable from repository."**

---

# Executive Summary

The 18 non-Identity policies and the single non-Identity Gate (`authoring.manage-curriculum`) now type-hint `Actor` instead of the `User` Eloquent model. Every existing check is preserved exactly: the `super_admin` `before()` bypass, all `can('…')` permission checks, all `hasRole('super_admin')` checks, and all ownership comparisons — the latter switched from `$user->id` to the Actor-contract accessor `$user->actorId()`, which returns the identical value. Because the `User` model **implements `Actor`** (Phase 2A) and `Actor` extends the two framework contracts Laravel's Gate injects (`Authenticatable` + `Authorizable`), the Gate keeps injecting the concrete `User` wherever an `Actor` is now required — so authorization behavior is unchanged. Result: **zero** non-Identity policy/gate imports `Identity\Models\User` (down from 19).

---

# Files Modified

19 files: 18 policies + 1 service provider (Gate definition).

**Policies (18):**

| Context | Policies |
|---------|----------|
| Learning | `EnrollmentPolicy` |
| Commerce | `ProductPolicy`, `ContractPolicy`, `OrderPolicy` |
| Analytics | `ReportDefinitionPolicy`, `ExportJobPolicy`, `DashboardDefinitionPolicy` |
| Notifications | `NotificationPolicy` |
| Live | `LiveSessionPolicy` |
| CRM | `LeadPolicy`, `OrganizationPolicy`, `ConsultingRequestPolicy` |
| Certification | `BadgePolicy`, `CertificatePolicy` |
| Catalog | `CategoryPolicy`, `CoursePolicy` |
| Authoring | `LessonPolicy`, `SectionPolicy` |

**Gate (1):** `app/Domains/Authoring/Providers/AuthoringServiceProvider.php` — the `authoring.manage-curriculum` Gate closure.

**Not modified (out of scope):** `Platform/Identity/Policies/UserPolicy.php` and `DevicePolicy.php` (Identity's *own* policies — `UserPolicy` legitimately keeps `User $target` for the concrete user it manages).

---

# Policies Migrated

Each policy received the identical, behavior-preserving transformation:

1. Import: `use App\Platform\Identity\Models\User;` → `use App\Platform\Identity\Contracts\Actor;`
2. `before(mixed $user, …)` body: `$user instanceof User` → `$user instanceof Actor` (the `mixed` parameter type is unchanged; the `super_admin` bypass is preserved).
3. Ability method params: `view/create/update/delete/refund/accept/manage/viewAny(User $user, …)` → `(Actor $user, …)`.
4. Ownership checks: `$user->id` → `$user->actorId()` (8 policies: Enrollment, Order, Contract, ReportDefinition, ExportJob, Notification, ConsultingRequest, Certificate). `actorId()` returns `(int) getKey()` — the same value as `$user->id` — so `$model->user_id === $user->actorId()` is identical to the original.
5. `$user->can('…')` and `$user->hasRole('super_admin')` left byte-for-byte unchanged (both are members of `Actor` via `Authorizable` / the declared `hasRole`).

No permission string (`commerce.orders.view`, `catalog.courses.manage`, `crm.leads.manage`, `certification.certificates.manage`, `authoring.curriculum.manage`, `analytics.view`, `live.sessions.manage`, etc.) and no role name (`super_admin`) was altered.

---

# Gates Migrated

`AuthoringServiceProvider::bootDomain()` defines one Gate outside Identity:

```php
Gate::define('authoring.manage-curriculum', function (Actor $user, Course $course): bool {
    return $user->hasRole('super_admin') || $user->can('authoring.curriculum.manage');
});
```

Only the closure's `User` type-hint → `Actor` and the file's import changed; the ability name, the `hasRole`/`can` decision, and the `Course` argument are untouched. (No `Gate::define`/`Gate::before` exists in any other non-Identity provider — verified.)

---

# Concrete Dependencies Removed

- **`App\Platform\Identity\Models\User` import removed from all 18 non-Identity policies + the Authoring Gate provider.** Non-Identity policy/gate coupling to the `User` model: **19 → 0** (confirmed by grep — the only residual `Identity\Models\User` in `**/Policies/**` is in Identity's own `UserPolicy`/`DevicePolicy`, which are out of scope).
- No new import beyond `Identity\Contracts\Actor` was added; the `IdentityContracts` layer is the allowed dependency for every context.

---

# Authorization Compatibility

- **Decisions unchanged.** `before()` still returns `true` for `super_admin` and `null` otherwise; every ability still returns the same boolean from the same `can()`/`hasRole()`/ownership expression.
- **Ownership parity.** `$user->actorId()` ≡ `$user->id` for any authenticated `User` (both are the bigint primary key as `int`); the strict `===` comparison against the model FK column is identical.
- **Null-safety preserved.** `before(mixed $user)` guards with `$user instanceof Actor` before calling `hasRole`/`actorId`, so an unauthenticated (null) principal is handled exactly as before; ability methods are only invoked by the Gate for a non-null authenticated user.
- *(Runtime confirmation — policy tests, OpenAPI/HTTP behavior — Not verifiable from repository; see Validation.)*

---

# Actor Compatibility

- **User still satisfies Actor:** `class User extends Authenticatable implements Actor, FilamentUser, HasName` (unchanged since Phase 2A); `actorId(): int` present; `hasRole` provided by Spatie; `can()` via the framework `Authorizable`.
- **Gate can inject User where Actor is required:** `Actor extends Illuminate\Contracts\Auth\Authenticatable, Illuminate\Contracts\Auth\Access\Authorizable` — precisely the shape Laravel's Gate resolves and injects. The Gate passes the authenticated `User` (which *is an* `Actor`) into every migrated policy method and the migrated Gate closure with no adapter or wrapper. This is the exact mechanism the Phase-1 specification designed for.
- **PHPStan alignment:** ability bodies use only members declared on `Actor` (`actorId()`, `hasRole()`, `can()`), so the type-hint swap is static-analysis-clean.

---

# Remaining Identity Coupling

- **Identity's own policies** (`UserPolicy`, `DevicePolicy`) still reference `User`/`UserDevice` — correct and out of scope; `UserPolicy` needs the concrete `User $target`.
- **`belongsTo(User)` ownership relations** on the domain models remain (untouched, per instruction) — the policies read the FK columns (`user_id`, `owner_id`, `requested_by`) directly, not the relations.
- **Identity's own Actions/Services** still use `User` (Login/Register/MFA/OTP/Device/Profile) — out of scope.
- **Filament** resources/widgets, factories, and seeders were not touched (some still reference `User` — deferred to their own phases).

---

# Blocked Items

**None.** All 18 non-Identity policies and the one non-Identity Gate were migratable with a pure type/accessor swap. No policy needed a `User`-only capability that `Actor` lacks (the only model-specific accessor used, `$user->id`, has the `Actor::actorId()` equivalent).

---

# Risk Assessment

- **Low, but this is the highest-blast-radius Identity phase so far** because it touches authorization. Mitigations: the change is mechanical and behavior-preserving; the `User` model already implements `Actor`; the Gate injection contract is unchanged; ownership uses the value-identical `actorId()`.
- **Watch items for the PHP environment:** (a) run the policy/feature test suite — it exercises owner-vs-other and super_admin paths (e.g., `OwnerEndpointsTest`, certificate/enrollment/order policies); (b) PHPStan to confirm no `Actor`-property access slipped through; (c) `pint --test` may want import-ordering normalization (the swap kept the import in place; some files' import order predates strict ordering).
- **Environmental risk:** no PHP/Composer here — nothing machine-verified. Mitigated by an exhaustive grep sweep (zero `Identity\Models\User`/`User $user`/`instanceof User`/`$user->id` in the 18 policies + gate) and `file(1)` integrity (all 19 clean PHP text) + confirmation that `User implements Actor`.

---

# Next Step

Await authorization for the next phase. Remaining Identity decoupling: the **`belongsTo(User)` ownership relations** (coordinated, since Certification/Notifications read `$enrollment->user`/`$certificate->user` cross-context), Identity-internal items (`RegisterUserAction` default-role slug, `UserRegistered` event consumers), and the remaining `User` references in Filament/factories/seeders. Do not begin until instructed. Run the toolchain (below) on a PHP-capable environment to confirm Phase 3A is green first.

---

# Validation

Run if available (attempted here):

```
composer dump-autoload                 -> Not verifiable from repository (php/composer not available)
vendor/bin/pint --test                 -> Not verifiable from repository
vendor/bin/phpstan analyse --no-progress -> Not verifiable from repository
vendor/bin/deptrac analyse --no-progress -> Not verifiable from repository
php artisan test                       -> Not verifiable from repository
```

Static verification performed here (repository evidence):

- **Migration complete:** grep for `Identity\Models\User` / `User $user` / `instanceof User` / `$user->id` across `app/**/Policies/**` returns **only** Identity's own `UserPolicy`/`DevicePolicy` (out of scope). All 18 non-Identity policies import `Identity\Contracts\Actor` (count = 18) and use `Actor $user`.
- **Gate migrated:** `AuthoringServiceProvider` imports `Contracts\Actor` and its Gate closure is `function (Actor $user, Course $course)`.
- **Actor satisfied by User:** `User` line 26 = `... implements Actor, FilamentUser, HasName`; `actorId(): int` present.
- **Integrity:** all 19 modified files report as clean PHP text via `file(1)` (no NUL / no `data`).
- **Scope:** no model relation, factory, seeder, Filament resource/widget, migration, route, or API modified; no permission or role name changed; no authorization decision altered.

Run the commands above on a PHP-capable environment to obtain live pass/fail (especially the policy/feature tests) before the next phase.

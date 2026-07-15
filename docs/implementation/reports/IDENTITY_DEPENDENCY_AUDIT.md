# Identity Dependency Audit (Analysis Only)

> Chief Enterprise Architect. Opening analysis for the Identity Context Cleanup. **No code, contract, adapter, API, schema, or behavior changed.** Every figure is from a direct `use`-import + usage scan of `apps/api/app` on 2026-07-09, reconciled with `PROJECT_STATUS.md`, `ARCHITECTURE_GAP_ANALYSIS.md`, `DEPENDENCY_CLEANUP_PLAN.md`, and the Curriculum programme. Execution-dependent claims are marked **"Not verifiable from repository."**

---

# Executive Summary

`App\Platform\Identity\Models\User` is the **single largest cross-context coupling in the backend**: **82 import sites** across every domain and platform context, plus `Identity\Enums\Role` (8, all seeders), `Identity\Events\UserRegistered` (2 listeners), `Identity\Http\Middleware\EnforceAdminMfa` (1, admin panel), and `Identity\Database\Seeders\RolePermissionSeeder` (1). Total: **94 import sites in 92 non-Identity files** (this is ADR-20 in the gap analysis).

Usage is dominated by two concerns: **authorization** (`$user->can(...)` ×26 and `$user->hasRole(...)` ×18, almost all inside the 18 policy classes and a couple of gates) and **ownership** (`$user->id` ×38 and 18 `belongsTo(User)` relations that record who owns a row). The rest is thin: user **display** (`TrainerResource`: name/public_id/profile), **lookup** (by email, and "instructor" listing), **events** (`UserRegistered`), a **middleware** wiring, and **dev fixtures** (factories/seeders).

Two facts shape the whole programme. First, **Identity is a kernel** — the target architecture (ADR-02/20, Deptrac ruleset) lets every context depend on `IdentityContracts`, so the goal is for contexts to consume `Identity\Contracts\*` (ports + a `UserRef` DTO), never `Identity\Models\User`. Second, **authorization is framework-coupled**: Laravel's Gate injects the concrete `User` (an `Authenticatable & Authorizable`) into policy methods, and role/permission checks come from Spatie's `HasRoles` on that model. Decoupling policies is therefore the hardest step and must be contracts-first; the ownership `belongsTo(User)` relations are the highest blast radius (as the Curriculum relations were). Everything else (events, seeders, factories, the trainer display) is low risk. This will be a multi-phase, bottom-up programme — larger than Curriculum (94 vs 36).

---

# Current Identity Coupling

| Imported symbol | Sites | Nature |
|-----------------|:-----:|--------|
| `Identity\Models\User` | 82 | policy type-hints + `hasRole`/`can`/`id`, `belongsTo(User)`, lookups, display, actions, factories |
| `Identity\Enums\Role` | 8 | seeders only (`Role::X->value` for `assignRole`) |
| `Identity\Events\UserRegistered` | 2 | Analytics + Notifications event subscribers (published language) |
| `Identity\Http\Middleware\EnforceAdminMfa` | 1 | `AdminPanelProvider` (composition root) |
| `Identity\Database\Seeders\RolePermissionSeeder` | 1 | `CatalogSeeder` (dev) |
| **Total** | **94** | across 92 files, every context |

`User` internals confirmed: extends `Illuminate\Foundation\Auth\User` (Authenticatable), implements `FilamentUser`, `HasName`; uses `HasPublicId` + Spatie `HasRoles`; `$hidden = [password, remember_token, two_factor_secret, two_factor_recovery_codes]` (the forbidden cross-context surface); has a `profile()` HasOne.

---

# Dependency Inventory

Grouped by owning context (all `Identity\Models\User` unless noted). "Runtime usage" from the scan.

### Analytics (7)
| File | Imported | Runtime usage | Why | Risk | Priority |
|------|----------|---------------|-----|:----:|:--------:|
| `Models/ExportJob.php` | User | `belongsTo(User)` | owner FK | High | P3 |
| `Models/ReportDefinition.php` | User | `belongsTo(User,'owner_id')` | owner FK | High | P3 |
| `Policies/ExportJobPolicy.php` | User | `User $user`, `can`/`id` | authorization | High | P4 |
| `Policies/ReportDefinitionPolicy.php` | User | authorization | authorization | High | P4 |
| `Policies/DashboardDefinitionPolicy.php` | User | authorization | authorization | High | P4 |
| `Actions/CreateExportJobAction.php` | User | `User $user` → `owner_id` | ownership | Med | P2 |
| `Listeners/MetricEventSubscriber.php` | `Events\UserRegistered` | subscribes | metrics on signup | Low | P1 |
| `Database/Seeders/AnalyticsSeeder.php` | `Enums\Role` | `Role::x->value` | seed roles | Low | P1 |

### Commerce (11)
`Models/{Cart,Contract,Order}.php` (`belongsTo(User)` — High/P3); `Policies/{Contract,Order,Product}Policy.php` (authorization — High/P4); `Services/{CartService,ContractService}.php` (`User $user` params — Med/P2); `Actions/Cart/{AddToCart,ApplyCoupon,ClearCart,RemoveFromCart}Action.php` + `Actions/Checkout/CheckoutAction.php` (`User $user` → cart/order owner — Med/P2); `Database/Factories/OrderFactory.php` (`User::factory()` — Low/P4 dev); `Database/Seeders/CommerceSeeder.php` (`Enums\Role` — Low/P1 dev).

### Learning (12)
`Models/{Enrollment,LearningSession,LessonBookmark,LessonNote}.php` (`belongsTo(User)` — High/P3); `Policies/EnrollmentPolicy.php` (`before` `instanceof User`+`hasRole`, `view` `id` — High/P4); `Services/{ContinueLearning,LearningMedia,LessonAccess}Service.php` (`User $user` params — Med/P2); `Actions/Engagement/{ToggleBookmark,UpsertLessonNote}Action.php`, `Actions/Enrollment/{EnrollInCourse,GrantEnrollment}Action.php`, `Actions/Progress/RecordLessonProgressAction.php` (`User $user` params — Med/P2); `Database/Factories/{Enrollment,LessonNote}Factory.php` + `Database/Seeders/LearningSeeder.php` (`User::factory`/`firstOrCreate` — Low/P4 dev). (`GrantEnrollmentAction` is also the Commerce entitlement seam.)

### Catalog (7)
`Models/Course.php` (User — instructor relation, High/P3); `Http/Controllers/Api/V1/TrainerController.php` (`User::query()->whereHas('roles','instructor')->with('profile')` — Med/P2); `Http/Resources/TrainerResource.php` (reads `public_id,name,profile?->bio,profile?->avatar_path` — Med/P2); `Policies/{Category,Course}Policy.php` (authorization — High/P4); `Database/Seeders/CatalogSeeder.php` (`Role`, `User::firstOrCreate`, `RolePermissionSeeder` — Low/P1 dev).

### Certification (6)
`Models/{BadgeAward,Certificate}.php` (`belongsTo(User)` — High/P3); `Actions/{AwardBadge,GenerateCertificate}Action.php` (`User $user` — Med/P2; called by the CourseCompleted listener with `$enrollment->user`); `Policies/{Badge,Certificate}Policy.php` (authorization — High/P4); `Database/{Factories/CertificateFactory,Seeders/CertificationSeeder}.php` (`User::factory`/`Role` — Low/P4/P1 dev).

### CRM (7)
`Models/{ConsultingRequest,Lead,OrganizationMember}.php` (`belongsTo(User)`; Lead `owner_id`, ConsultingRequest `requested_by` — High/P3); `Actions/Organization/InviteMemberAction.php` (`User::where('email')->first()` → `user_id` — Med/P2, an org-membership lookup); `Policies/{ConsultingRequest,Lead,Organization}Policy.php` (authorization — High/P4); `Database/Seeders/CrmSeeder.php` (`Role` — Low/P1 dev).

### Live (9)
`Models/{LiveSession,SessionAttendance,SessionRegistration}.php` (`belongsTo(User)` — High/P3); `Services/{AttendanceValidation,JoinToken}Service.php` (`User $user` — Med/P2); `Actions/Registration/{Cancel,Join,RecordAttendance,RegisterForSession}...Action.php` (`User $user` — Med/P2); `Policies/LiveSessionPolicy.php` (authorization — High/P4); `Database/Seeders/LiveSeeder.php` (`Role` — Low/P1 dev).

### Notifications (11)
`Models/{Notification,NotificationPreference,UserNotificationSetting}.php` (`belongsTo(User)` — High/P3); `Services/{Digest,NotificationDispatcher,Preference,WorkflowEngine}Service.php` (`User $user` — Med/P2); `Actions/{BulkNotification,SendNotification,UpdatePreferences}Action.php` (`User $user` — Med/P2); `Policies/NotificationPolicy.php` (authorization — High/P4); `Listeners/NotificationEventSubscriber.php` (`Events\UserRegistered`, `$event->enrollment->user` — Low/P1); `Database/{Factories/NotificationFactory,Seeders/NotificationsSeeder}.php` (`User::factory`/`Role` — Low dev).

### Authoring (4)
`Policies/{Lesson,Section}Policy.php` (authorization — High/P4); `Providers/AuthoringServiceProvider.php` (`Gate::define('authoring.manage-curriculum', fn(User $user, Course $course) => $user->hasRole|can)` — High/P4); `Database/Seeders/AuthoringSeeder.php` (`Role` — Low/P1 dev).

### Platform / App (3)
`Filament/Widgets/PlatformOverview.php` (`User::query()->count()` — Low/P2, admin metric); `Providers/AdminPanelProvider.php` (`EnforceAdminMfa` middleware — Low/P2, composition root); (Notifications listener counted above).

---

# Coupling Categories

- **Authentication:** current-user resolution (`request->user()`), and `EnforceAdminMfa` (AdminPanelProvider). The authenticated user is Laravel-framework supplied; contexts read it but don't authenticate.
- **Authorization (cross-cutting, ~44 calls):** `$user->can(...)` ×26, `$user->hasRole(...)` ×18 — almost entirely inside policies + two gates (`AuthoringServiceProvider`). The core coupling.
- **Policies (18):** Analytics 3, Commerce 3, Learning 1, Authoring 2, Catalog 2, Certification 2, CRM 3, Live 1, Notifications 1. Each type-hints `User $user` (Gate-injected) and calls `hasRole`/`can`/`id`.
- **Actions (~20):** Learning 5, Commerce 5, Live 4, Notifications 3, Certification 2, CRM 1, Analytics 1 — all take `User $user` and use `->id` for ownership.
- **Models (belongsTo User, ~21 files):** ownership relations across Analytics, Commerce, Learning, Certification, CRM, Live, Notifications, Catalog(Course instructor).
- **Resources (1):** `TrainerResource` (public trainer display).
- **Controllers (1):** `TrainerController` (instructor listing).
- **Events (1 published):** `Identity\Events\UserRegistered`.
- **Listeners (2):** Analytics `MetricEventSubscriber`, Notifications `NotificationEventSubscriber` (subscribe to `UserRegistered`).
- **Jobs:** none import User directly (**Not verifiable** beyond the scan; Analytics uses an `ExportJob` model + action, not a User-coupled job).
- **Filament (2):** `PlatformOverview` widget (`User::query()->count()`), plus the `EnforceAdminMfa` wiring in `AdminPanelProvider`.
- **Factories (5):** Order, Certificate, Enrollment, LessonNote, Notification (`User::factory()`).
- **Seeders (9):** all use `Role` and/or `User` (Analytics, Commerce, Authoring, Catalog, Certification, CRM, Live, Notifications, Learning); Catalog also calls `RolePermissionSeeder`.
- **Tests:** the scan covered `app/` only. Tests use `User::factory()` pervasively (outside the 94 app-count). **Exact test coupling: Not verifiable from this app-scoped scan.**

---

# Required Identity Data

What other contexts actually consume from `User`:

**Required data (must be provided via contracts):**
- `id` (int) — ownership FKs (`belongsTo(User)`, `user_id`/`owner_id`/`requested_by`) and owner comparison in policies (`$model->user_id === $user->id`).
- `public_id` (string) — external identity (TrainerResource, refs).
- `name` (string) — display (TrainerResource, HasName).
- `profile.bio`, `profile.avatar_path` — public trainer display.
- `email`, `is_active` — lookup (CRM invite by email) and instructor listing/filtering.
- **authorization**: `can(permission)` and `hasRole(role)` — for policies + gates; plus role-set membership for the "instructor" query.

**Incidental data (used, but not essential coupling):**
- The full `User` Eloquent model (contexts use a slice).
- `assignRole(...)` — seeders only (dev).
- `User::factory()` — factories/seeders (dev/test).

**Forbidden access (must NEVER cross a context boundary):**
- The `$hidden` set: `password`, `remember_token`, `two_factor_secret`, `two_factor_recovery_codes`.
- All auth internals: sessions, OTP, email-verification tokens, MFA state.
- Any **write** to `User` (contexts must not mutate identity).

---

# Future Identity Contracts (design only — do NOT implement)

Home: `App\Platform\Identity\Contracts\*` (the `IdentityContracts` Deptrac layer) + a Shared/IdentityContracts `UserRef` DTO.

- **UserRef** (DTO, `final readonly`): `id:int`, `publicId:string`, `name:string`, `avatarPath:?string`. Read-only projection; never carries `$hidden` fields.
- **CurrentUserPort**: `currentUserId(): ?int`, `currentUserRef(): ?UserRef` — replaces `request()->user()->id` in business code.
- **UserLookupPort**: `refById(int): ?UserRef`, `refByPublicId(string): ?UserRef`, `idByEmail(string): ?int` (CRM invite), `instructors(): list<UserRef>` (trainer listing).
- **UserPermissionPort**: `can(int $userId, string $permission): bool`.
- **UserRolePort**: `hasRole(int $userId, string $role): bool`, `rolesFor(int $userId): list<string>`.
- **UserOrganizationPort**: `organizationIdsFor(int $userId): list<int>`, `isMember(int $userId, int $organizationId): bool` — for CRM/organization scoping.

**Policy-specific note:** Laravel's Gate injects the concrete authenticatable into policy methods, so policies cannot simply take a `UserRef`. The realistic design is an `IdentityContracts` **interface** (e.g. `Actor` extending Illuminate's `Authorizable`) that the `User` model implements and that policies type-hint — removing the concrete `Identity\Models\User` import while remaining Gate-compatible. `UserPermissionPort`/`UserRolePort` back the checks. This is the crux of the Authorization/Policies category and is designed here only.

---

# Refactoring Candidates

| Dependency group | Future contract | Expected DTO | Owner | Difficulty | Est. files | Blocking deps |
|------------------|-----------------|--------------|-------|:----------:|:----------:|---------------|
| `belongsTo(User)` ownership relations | `UserLookupPort` (+ FK columns retained) | `UserRef` | Identity | **High** | ~21 | ports + rework of any relation traversal |
| Policies (`User $user`, `can`/`hasRole`/`id`) | `Actor` interface + `UserPermissionPort`/`UserRolePort` | `UserRef`/interface | Identity | **High** | 18 | interface on User; Gate compatibility |
| Gates (`AuthoringServiceProvider`) | `UserPermissionPort`/`UserRolePort` | — | Identity | High | 1 | ports |
| Actions/Services (`User $user` → `->id`) | `CurrentUserPort` / pass `int $userId` | `UserRef` | Identity | Med | ~25 | ports; expand-and-contract |
| `TrainerController`/`TrainerResource` | `UserLookupPort::instructors()` | `UserRef` (+avatar/bio) | Identity | Med | 2 | port; DTO must carry profile bits |
| CRM `InviteMemberAction` (`User::where('email')`) | `UserLookupPort::idByEmail()` | — | Identity | Med | 1 | port |
| `Filament\PlatformOverview` (`User::query()->count()`) | `UserLookupPort` (count) or accept (admin) | — | Identity | Low | 1 | port |
| `UserRegistered` subscriptions | published event DTO (Shared) or Deptrac `Events` allowance | event DTO | Identity/Shared | Med | 2 | event-DTO policy decision |
| `Role` enum in seeders | role-slug strings or `IdentityContracts` re-export | — | Identity | Low | 8 | none (dev) |
| Factories (`User::factory()`) | test fixtures / `UserLookupPort` in tests | — | Identity | Low | 5 | test infra |
| `EnforceAdminMfa` (AdminPanelProvider) | accept (composition root) or `IdentityContracts` middleware alias | — | App/Identity | Low | 1 | none |

**Not verifiable from repository:** final file counts and whether every port can return byte-identical data (confirm with tests + OpenAPI diff during implementation).

---

# Quick Wins

Removable with minimal risk (small, isolated, mostly dev):
1. **`Role` enum in the 8 seeders** → replace `Role::X->value` with the role-slug string (exactly the fix applied to `LearningSeeder` in Curriculum Phase 2). Zero-port, dev-only.
2. **`UserRegistered` listeners (2)** → decide the published-event policy (Shared event-DTO package, or an explicit Deptrac allowance for the `Events` seam), then repoint — same decision pending for the Curriculum events.
3. **`EnforceAdminMfa` (1)** and **`RolePermissionSeeder` (1)** — composition-root / dev-seeder wiring; lowest-value, can be aliased behind `IdentityContracts` or accepted.
4. **`PlatformOverview` count (1)** — a single `User::query()->count()`; trivially replaceable by a `UserLookupPort` count once the port exists.

(No fully port-free quick win exists for the authorization/ownership bulk — those need contracts first.)

---

# High Risk Refactoring (do NOT touch until contracts exist)

- **Policies (18) + gates** — Gate injects the concrete `User`; requires the `Actor` interface + permission/role ports before the `Identity\Models\User` import can be dropped. Touching these prematurely breaks authorization app-wide.
- **`belongsTo(User)` ownership relations (~21 models)** — highest blast radius; widely traversed (incl. cross-context: Certification reads `$enrollment->user`, Notifications reads `$event->enrollment->user`). Must follow the ports and be done last, FK columns retained, with parity tests.
- **Actions/services carrying `User $user`** — pervasive; migrate via expand-and-contract only after `CurrentUserPort`/`UserRef` exist.
- **Cross-context `User` reads via relations** (e.g. `$enrollment->user` in Certification/Notifications) — mirror the Curriculum finding: these are consumed by *other* contexts, so severing needs coordination.

---

# Migration Order (bottom-up)

1. **Contracts + DTO first:** define `UserRef`, `CurrentUserPort`, `UserLookupPort`, `UserPermissionPort`, `UserRolePort`, `UserOrganizationPort`, and the `Actor` interface under `Identity\Contracts` (`IdentityContracts` layer). No behavior.
2. **Identity adapters:** implement the ports in Identity over the `User` model + Spatie; make `User implements Actor`. Bind in `IdentityServiceProvider`.
3. **Quick wins:** seeders' `Role` → slugs; `UserRegistered` per the event-DTO decision; `PlatformOverview`/`EnforceAdminMfa` wiring.
4. **Display + lookup:** `TrainerController`/`TrainerResource` → `UserLookupPort::instructors()` + `UserRef`; CRM `InviteMemberAction` → `UserLookupPort::idByEmail()`.
5. **Actions/Services:** expand-and-contract to accept `int $userId`/`UserRef` (+ `CurrentUserPort`), retaining `User` overloads until callers migrate.
6. **Policies/gates:** switch type-hints to the `Actor` interface; route checks through `UserPermissionPort`/`UserRolePort`. (Authorization migrated as one coordinated step.)
7. **Model relations LAST:** replace `belongsTo(User)` reads with `UserRef` via `UserLookupPort` where display data is needed; keep FK columns (no schema change); coordinate the cross-context `$…->user` readers (Certification/Notifications).
8. **Cleanup:** remove transitional overloads; migrate factories/fixtures; regenerate the Deptrac baseline.

---

# Success Criteria

1. `grep -rE 'use App\\Platform\\Identity\\(Models\\User|Enums\\Role)' app --exclude-dir Identity` → **0** (from 90 today: 82 User + 8 Role).
2. No cross-context `belongsTo(User)`, `User::query/where/factory`, `$user->can/hasRole`, or `$user->id` on a concrete `Identity\Models\User` outside Identity; ownership via FK columns + `UserRef`/ports.
3. Every context depends only on `Shared` + `IdentityContracts` (Deptrac clean; `Identity\Models\User` no longer referenced cross-context). *(Not verifiable from repository.)*
4. Policies type an `IdentityContracts` interface, not `Identity\Models\User`; PHPStan `NoCrossContextModelUsageRule` clean for Identity. *(Not verifiable from repository.)*
5. Forbidden fields (`password`, `remember_token`, `two_factor_secret`, `two_factor_recovery_codes`) provably never reachable via any contract/DTO.
6. **Behavior parity:** empty OpenAPI diff on all affected endpoints; all tests green; DB schema untouched. *(Not verifiable from repository.)*
7. Metric: Identity cross-context import sites **94 → 0** (contexts consume `IdentityContracts` only).

---

# Final Recommendation

**Proceed, but scope this as the largest and longest programme so far — multi-phase, bottom-up, contracts-first — and do not start on policies or ownership relations until the contracts + adapters exist.** The consumed surface is genuinely small (`id`, `public_id`, `name`, `profile` bits, `can`/`hasRole`, email/instructor lookup), so `UserRef` + the five ports + an `Actor` interface cover it cleanly. Sequence: contracts/adapters → quick wins (seeder `Role` slugs, events, wiring) → display/lookup → actions/services (expand-and-contract) → authorization/policies (one coordinated step behind the `Actor` interface) → ownership relations last (FK columns retained). Recognize up front — as the Curriculum programme showed — that **cross-context `User` reads via relations (Certification, Notifications) require coordinated multi-context work**, and Identity being a *kernel* means the acceptable end state is "every context depends on `IdentityContracts`," not "no Identity dependency at all." Confirm each phase with the gate suite (run on a PHP-capable environment) and an empty OpenAPI diff.

---

## Validation

- All 94 sites, their files, imported symbols, and runtime usage come from a direct scan of `apps/api/app` (imports; `$user->` attribute/method usage; `belongsTo(User)`; `User::` static calls; policy bodies; `User` model traits/`$hidden`), reconciled with the referenced reports.
- Test-suite coupling and any runtime results (Deptrac/PHPStan/tests/OpenAPI) are marked **"Not verifiable from repository."**
- **No code, contract, adapter, API, schema, or architecture was modified.** Only this file was created.

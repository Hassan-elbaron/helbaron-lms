# Identity Cleanup — Phase 3B: Relations & Fixtures (Report)

> Chief Enterprise Architect. Phase 3B removes the remaining ownership/display relations to the concrete `App\Platform\Identity\Models\User` outside Identity, using expand-and-contract. **No database schema changed; no foreign-key column removed; no API or business behavior changed; no Identity authentication internals touched.** Runtime gates could not run here (no PHP/Composer) — marked **"Not verifiable from repository."**

---

# Executive Summary

The three production consumers that still traversed a cross-context `User` relation — the Notifications event subscriber, the Certification auto-generate listener, and the Certificate PDF render service — were migrated to read the stored foreign-key id (`user_id`, `requested_by`, `order_id`→`user_id`, etc.) or, for display, the `UserLookupPort`. With no runtime caller left, the **19 callerless `belongsTo(User)` ownership relations** across Learning, Commerce, Analytics, Certification, CRM, Live, and Notifications were removed along with their now-unused `use User` (and `use BelongsTo`) imports. The foreign-key columns were kept intact. Result: **zero `belongsTo(User)` relations remain outside Identity**, and non-Identity production application code importing `User` drops to just the two `trainers()` **belongsToMany** relations (Catalog `Course`, Live `LiveSession`) plus the `PlatformOverview` count widget — all retained as documented technical debt with a concrete plan, because they are actively used for pivot-sync writes / display and cannot be removed safely without a running test suite.

---

# Initial Remaining Dependency Inventory

Every non-Identity import of `Identity\Models\User` at phase start, classified:

- **Active ownership `belongsTo(User)` relations (19, target for removal):** Learning `Enrollment.user`, `LearningSession.user`, `LessonNote.user`, `LessonBookmark.user`; Commerce `Order.user`, `Cart.user`, `Contract.user`; Analytics `ExportJob.user`, `ReportDefinition.owner`; Certification `Certificate.user`, `BadgeAward.user`; CRM `ConsultingRequest.requester`, `Lead.owner`, `OrganizationMember.user`; Live `SessionRegistration.user`, `SessionAttendance.user`; Notifications `Notification.user`, `NotificationPreference.user`, `UserNotificationSetting.user`.
- **Active display / assignment `belongsToMany(User)` relations (2):** Catalog `Course.trainers`, Live `LiveSession.trainers` (pivot `course_trainer` / `session_trainers`).
- **Cross-context consumers (production, 3):** `Notifications\NotificationEventSubscriber` (reads `$event->{enrollment,order,certificate}->user`, `$registration->user`, `$event->request->requester`); `Certification\GenerateCertificateOnCourseCompleted` (`$enrollment->user`); `Certification\CertificateRenderService` (`$certificate->user->name` for the PDF holder name).
- **Filament usage (1):** `Filament\Widgets\PlatformOverview` (`User::query()->count()` — no relation).
- **Factories (5):** `EnrollmentFactory`, `LessonNoteFactory`, `OrderFactory`, `NotificationFactory`, `CertificateFactory`.
- **Seeders (2):** `LearningSeeder`, `CatalogSeeder`.
- **Test infrastructure (~30 files under `tests/`).**
- **Composition root:** `config/auth.php` (auth guard/provider), `deptrac.yaml` (the ruleset), `Shared\Tenancy\RequestTenantResolver` (docblock mention only — reads `auth()->user()` as a framework `Model`, no `use` import).
- **Published Identity event DTOs / Identity-owned code (~40 files under `app/Platform/Identity/**`).**

---

# Production Consumers Migrated

1. **`NotificationEventSubscriber`** — all cross-context relation reads replaced with FK columns: `$event->enrollment->user->id` → `$event->enrollment->user_id` (enrollment_confirmed, course_completed); `$event->order->user->id` → `$event->order->user_id`; `$event->certificate->user->id` → `$event->certificate->user_id`; `$registration->user->id` → `$registration->user_id` (and the `->with('user')` eager-load dropped); `$event->request->requester->id` → `$event->request->requested_by`. The `super_admin`-agnostic null guards became FK guards (`… !== null`). `onUserRegistered` still reads `$event->user->{id,name}` — the **published Identity event DTO** (retained exception).
2. **`GenerateCertificateOnCourseCompleted`** — `loadMissing(['user','course'])` → `loadMissing(['course'])`; guard `$enrollment->user !== null` → `$enrollment->user_id !== null`; call `executeByUserId($enrollment->user_id, …)`.
3. **`CertificateRenderService`** — injected `UserLookupPort`; `loadMissing(['user','course','template'])` → `loadMissing(['course','template'])`; `{{ holder_name }}` now `(string) $this->users->refById($certificate->user_id)?->name` (identical rendered name via the read port + `UserRef`).

All three are behavior-preserving: the same user is notified / certified / named; only the source of the id/name changed from an Eloquent relation to the FK column or `UserLookupPort`.

---

# Relations Removed

All **19** callerless `belongsTo(User)` relations were removed (method + `use User`; and `use BelongsTo` where it became unused because `user`/`owner` was the model's only `belongsTo`):

- **Drop `BelongsTo` import too (user/owner was the sole belongsTo) — 9:** `LearningSession`, `LessonNote`, `LessonBookmark`, `Order`, `ExportJob`, `ReportDefinition`, `Notification`, `NotificationPreference`, `UserNotificationSetting`.
- **Keep `BelongsTo` import (other belongsTo relations remain) — 10:** `Enrollment` (course), `Cart` (coupon), `Contract` (order/template), `Certificate` (course/template), `BadgeAward` (badge), `ConsultingRequest` (organization), `Lead` (pipeline/stage), `OrganizationMember` (organization), `SessionRegistration` (session), `SessionAttendance` (session).

Every removal was preceded by proof of zero callers in **both** `app/` and `tests/` (no `->user`/`->owner`/`->requester` attribute read, no `with('user')`/`load('user')`, no Filament relation, after the consumer migration above). **Foreign-key columns (`user_id`, `owner_id`, `requested_by`) are all retained** — no migration, no schema change.

---

# Filament Migration

Filament required **no relation migration**: a sweep of `app/Filament/**` found **no** traversal of any `user`/`owner`/`trainers` relation (no `->relationship('user')`, no `user.name` column). The only Filament `User` reference is `PlatformOverview::getStats()`'s `User::query()->count()`, which is a scalar count, not a relation, and remains **blocked** pending a `UserLookupPort::totalCount(): int` contract (documented since Phase 2B). No business logic was added to any Filament resource/widget. Identity's own `Filament\Resources\UserResource` is out of scope (Identity-owned).

---

# Factories and Seeders

Untouched — per the phase rule ("migrate only after production consumers are clean; they may continue to create Identity users through Identity-owned test infrastructure"). `EnrollmentFactory`, `LessonNoteFactory`, `OrderFactory`, `NotificationFactory`, `CertificateFactory` still call `User::factory()`, and `LearningSeeder`/`CatalogSeeder` still provision users via `User::firstOrCreate`/`User::factory` — this is legitimate test/seed infrastructure, not production application code, and creating an Identity user is intrinsically an Identity-model operation. Removing the relations did not break them (they set FK columns via `user_id`, not the removed relations). Production architecture was not weakened to simplify them.

---

# Tests Updated

None updated this phase. A sweep of `tests/` found **no** reader of any removed relation (`->user`, `->owner`, `->requester`, `with('user')`, etc.) — the tests use FK columns and factories, both untouched. The Phase-2D test migrations (GrantEnrollment/GenerateCertificate call sites) already stand. No test references a removed relation, so none required changes.

---

# Dependencies Removed

- **19 `belongsTo(User)` relations + 19 `use App\Platform\Identity\Models\User;` imports** removed from non-Identity models.
- **9 now-unused `use Illuminate\Database\Eloquent\Relations\BelongsTo;`** imports removed.
- **3 cross-context relation-read sites** eliminated from production consumers (subscriber, listener, render service).
- No temporary compatibility code remained to remove (Phase 2D/3A already contracted the actions/services/policies).

---

# Retained Exceptions

Documented, with repository evidence proving legitimacy:

1. **`Course.trainers()` / `LiveSession.trainers()` (belongsToMany User).** Actively used for **pivot-sync writes** (`CreateCourseAction`, `UpdateCourseAction`, `ScheduleSessionAction`, `CatalogSeeder`: `->trainers()->sync($ids)`) and **display** (`CourseResource`/`LiveSessionResource` render trainer `public_id`+`name` via `whenLoaded('trainers')`; `CourseSearchService`/`UpdateCourseAction` eager-load `trainers`). Removing them would break pivot writes and require reconstructing the trainer display via `UserLookupPort` — a coordinated refactor that cannot be verified without a running suite and risks changing the trainers JSON. Retained as technical debt.
2. **`PlatformOverview` (`User::query()->count()`).** Needs a `UserLookupPort::totalCount(): int` contract addition (blocked since Phase 2B; contract-change phase). Retained.
3. **Published Identity event DTO seam** — `NotificationEventSubscriber::onUserRegistered` reads `$event->user` off `Identity\Events\UserRegistered` (a published Identity event carrying the User). Legitimate producer→consumer event seam.
4. **`RequestTenantResolver`** — composition-root middleware wiring; reads `auth()->user()` as a framework `Model` and has **no** `use User` import (the match is a docblock note). Already decoupled.
5. **Factories/seeders/tests** — Identity-owned test/seed infrastructure that creates users; not production application code.
6. **All `app/Platform/Identity/**`** — Identity-owned code (models, adapters, contracts, services, policies, actions, events, HTTP, Filament UserResource) and **`config/auth.php`** (auth provider) and **`deptrac.yaml`** (the ruleset itself).

---

# Final Identity Dependency Count

- **`belongsTo(User)` relations outside Identity: 0** (was 19).
- **Non-Identity production application code importing `User`: 3** — `Course` + `LiveSession` (`trainers()` belongsToMany) + `PlatformOverview` (count). All retained/documented.
- **Cross-context relation reads in production: 0** (was 3+ across subscriber/listener/render service).
- **Test/seed infrastructure importing `User` (allowed): 7** (5 factories + 2 seeders).
- **Identity-owned + composition-root + event-DTO (retained): unchanged.**

Programme trajectory: the audit's 94 non-Identity `User` import sites are now reduced to the 3 documented production seams (2 trainer belongsToMany + 1 count widget) plus allowed test/seed infrastructure.

---

# Behavior Compatibility

- **Notifications:** the same user id is dispatched to for welcome/enrollment/completion/order/certificate/session/consulting; reading `user_id`/`requested_by` instead of `->user->id` yields the identical id for any row with referential integrity.
- **Certification:** the certificate is generated for the same `user_id`; the rendered PDF `holder_name` is the same name (now via `UserLookupPort::refById(...)->name`, which maps `User.name`).
- **Relation removal is inert:** a `belongsTo` method Eloquent never auto-invokes; with zero callers, deletion cannot change any runtime path, response, or query.
- *(Runtime confirmation — Not verifiable from repository; see Validation.)*

---

# Database Compatibility

**No schema change and no foreign-key column removed.** Every `user_id` / `owner_id` / `requested_by` column remains on its table; only the Eloquent relation *methods* (in-code convenience accessors) were deleted. No migration was created or altered. Pivot tables (`course_trainer`, `session_trainers`) are untouched.

---

# Deptrac Impact

`deptrac analyse` cannot run here (Deptrac not installed; no PHP) — **Not verifiable from repository.** Expected once run: the Learning, Commerce, Analytics, Certification, CRM (except via trainers—CRM has none), Notifications, and Live layers lose their `→ Identity` (concrete model) violations from the removed relations; the residual `→ Identity` edges reduce to Catalog `Course` and Live `LiveSession` (the `trainers()` belongsToMany) — which will remain in the seeded baseline until the trainer-display/assignment refactor lands. No new violation is introduced (only `IdentityContracts` is referenced by the migrated render service).

---

# Remaining Technical Debt

1. **Trainer belongsToMany (`Course.trainers`, `LiveSession.trainers`).** Replace pivot-sync with an id-based assignment seam and the resource display with `UserLookupPort::refsByIds(...)` (a new bulk contract method) so the pivot can target a plain `user_id` without a `User` relation. Requires a running suite to preserve the trainers JSON.
2. **`UserLookupPort::totalCount(): int`** (+ a `refsByIds(array): list<UserRef>` for #1) — the two contract additions this programme has surfaced; an approved contract-change phase unblocks `PlatformOverview` and #1.
3. **CRM timeline `Activity->user`** — the CRM `TimelineService` eager-loads `activities()->with('user')` for the actor display; the `Activity` model's user relation was not part of this phase's 19 (it is not among the non-Identity models that `use` the concrete `User`), and should be confirmed/decoupled in a CRM-scoped follow-up.
4. **Factories/seeders/tests** — could later route user creation through an Identity-owned test factory helper if the team wants zero `User` references even in infrastructure; not required by the architecture rule.

---

# Risk Assessment

- **Low for the removals** (deleting callerless relation methods cannot alter behavior; FK columns intact). **Moderate for the 3 consumer migrations** — behavior-preserving FK/port swaps, but they sit on notification/certificate hot paths, so the feature tests (`AutoGenerationTest`, `EventDeliveryTest`, `PublicVerificationTest`) must be run to confirm.
- **One nuance:** the null guards changed from "relation object present" to "FK column present"; for any row with referential integrity these are equivalent, differing only for an orphaned FK (soft-deleted user) — an edge case not exercised by the flows.
- **Environmental risk:** no PHP/Composer here — nothing machine-verified. Mitigated by exhaustive grep (0 `belongsTo(User)` outside Identity; only `Course`/`LiveSession` `trainers` + `PlatformOverview` retain `User`; 0 removed-relation readers in app/ or tests/) and `file(1)` integrity (all edited files clean PHP text).

---

# Final Recommendation

**Accept Phase 3B as the near-complete decoupling of non-Identity code from the concrete `User` model, and schedule one small contract-change phase to finish it.** All 19 ownership relations and all 3 production cross-context relation reads are gone with zero schema or API change; the only production `User` references left are the two `trainers()` belongsToMany (pivot writes + display) and the count widget, all documented with a concrete plan. Next, in priority order: (a) run the toolchain (below) on a PHP-capable environment — especially the Certification/Notifications/Live feature tests — to confirm this phase is green; (b) add `UserLookupPort::{totalCount, refsByIds}` in an approved contract phase; (c) migrate `Course`/`LiveSession` trainer assignment+display onto that port and drop the last two `belongsToMany(User)` relations; (d) confirm the CRM `Activity->user` timeline seam. Do not remove the trainer relations before their test-backed refactor.

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

- **Zero `belongsTo(User::class)` outside Identity** (grep across `app/**` excluding `Platform/Identity`). The only `User` relations remaining are the two `belongsToMany(User::class)` trainer relations (`Course`, `LiveSession`).
- **Non-Identity production app code importing `User` = `Course`, `LiveSession` (+ `PlatformOverview` count).** All 19 model relation imports + 9 `BelongsTo` imports removed.
- **`RequestTenantResolver`** has no `use User` import (docblock mention only) — confirmed.
- **No removed-relation reader remains** in `app/` or `tests/` (swept `->user`/`->owner`/`->requester`/`with('user')`/`load('user')`).
- **Integrity:** the 3 consumers + edited models report as clean PHP text via `file(1)` (no NUL / no `data`).
- **Scope:** no migration/schema changed; no FK column removed; no route/API/resource output changed; no Identity authentication internal touched.

Run the commands above on a PHP-capable environment to obtain live pass/fail before the contract-change phase.

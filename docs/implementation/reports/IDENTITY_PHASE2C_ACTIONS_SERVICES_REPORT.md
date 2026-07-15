# Identity Cleanup — Phase 2C: Actions & Services (Expand) Report

> Chief Enterprise Architect. Phase 2C applies **expand-and-contract (expand half only)** to every Action and Service *outside* Identity that imports `App\Platform\Identity\Models\User`. Each gains an id-based method; the existing `User`-based method is **retained** and now **delegates** to it. **No policy, controller, model relation, Filament, seeder, factory, test, API, or database schema was touched. No authorization behavior changed. No `User`-based compatibility method was removed.** Runtime gates could not run here (no PHP/Composer) — marked **"Not verifiable from repository."**

---

## Executive Summary

31 Actions/Services across Learning, Commerce, Live, Notifications, Certification, and Analytics now expose an id-based (`int $userId`) API alongside their original `User`-typed method. Every audited usage of the `User` model in these files was `$user->id` (a foreign-key/scoping value) — none needed user *data* or *display* — so the id-based methods take a plain `int $userId` and the `User`-typed methods become one-line shims that call them with `$user->id`. This is behavior-identical: the delegating method and its id-based target run the same query/write with the same value. Because the `User`-typed shims are retained (as instructed), the `Identity\Models\User` **import stays in every file this phase** — this is the *expand* step; the imports and shims are removed later in the *contract* step, once callers (controllers, listeners) pass ids directly. `UserRef`/`CurrentUserPort`/`UserLookupPort` were available but not needed here: these methods only require the id, and no new contract or user-data lookup was introduced.

---

## Files Modified

31 files (all Actions/Services outside Identity). Each: **+1 id-based method, `User` method retained + delegating.**

**Learning (8):** `Services/LessonAccessService` (3 methods), `Services/ContinueLearningService`, `Services/LearningMediaService`, `Actions/Progress/RecordLessonProgressAction`, `Actions/Engagement/UpsertLessonNoteAction`, `Actions/Engagement/ToggleBookmarkAction`, `Actions/Enrollment/GrantEnrollmentAction`, `Actions/Enrollment/EnrollInCourseAction`.

**Commerce (7):** `Services/CartService`, `Services/ContractService`, `Actions/Checkout/CheckoutAction`, `Actions/Cart/AddToCartAction`, `Actions/Cart/RemoveFromCartAction`, `Actions/Cart/ClearCartAction`, `Actions/Cart/ApplyCouponAction`.

**Live (6):** `Services/JoinTokenService`, `Services/AttendanceValidationService`, `Actions/Registration/RegisterForSessionAction`, `Actions/Registration/RecordAttendanceAction`, `Actions/Registration/JoinSessionAction`, `Actions/Registration/CancelRegistrationAction`.

**Notifications (7):** `Services/NotificationDispatcher`, `Services/PreferenceService` (2 methods), `Services/DigestService`, `Services/WorkflowEngine`, `Actions/SendNotificationAction`, `Actions/UpdatePreferencesAction`, `Actions/BulkNotificationAction`.

**Certification (2):** `Actions/GenerateCertificateAction`, `Actions/AwardBadgeAction`.

**Analytics (1):** `Actions/CreateExportJobAction`.

---

## New id/ref APIs

33 id-based methods added (existing `User` methods retained and delegating):

- **Learning:** `LessonAccessService::{activeEnrollmentByUserId, canAccessByUserId, assertAccessByUserId}`; `ContinueLearningService::forUserId`; `LearningMediaService::playbackForLessonByUserId`; `RecordLessonProgressAction::executeByUserId`; `UpsertLessonNoteAction::executeByUserId`; `ToggleBookmarkAction::executeByUserId`; `GrantEnrollmentAction::executeByUserId`; `EnrollInCourseAction::executeByUserId`.
- **Commerce:** `CartService::currentByUserId`; `ContractService::createForOrderByUserId`; `CheckoutAction::executeByUserId`; `AddToCartAction::executeByUserId`; `RemoveFromCartAction::executeByUserId`; `ClearCartAction::executeByUserId`; `ApplyCouponAction::executeByUserId`.
- **Live:** `JoinTokenService::issueByUserId`; `AttendanceValidationService::assertCanAttendByUserId`; `RegisterForSessionAction::executeByUserId`; `RecordAttendanceAction::executeByUserId`; `JoinSessionAction::executeByUserId`; `CancelRegistrationAction::executeByUserId`.
- **Notifications:** `NotificationDispatcher::dispatchToUserId` (+ private `localeForUserId`); `PreferenceService::{isEnabledForUserId, enabledChannelsForUserId}`; `DigestService::pendingForUserId`; `WorkflowEngine::handleEventForUserId`; `SendNotificationAction::executeForUserId`; `UpdatePreferencesAction::executeForUserId`; `BulkNotificationAction::executeForUserIds`.
- **Certification/Analytics:** `GenerateCertificateAction::executeByUserId`; `AwardBadgeAction::executeByUserId`; `CreateExportJobAction::executeByUserId`.

All id-based methods reference the user only as `int $userId`; they carry **zero** dependency on the `User` model. Intra-context chains were rewired to the id-based path (e.g. `LearningMediaService::playbackForLessonByUserId` → `LessonAccessService::assertAccessByUserId`; `CheckoutAction::executeByUserId` → `CartService::currentByUserId` + `ContractService::createForOrderByUserId`; `WorkflowEngine::handleEventForUserId` → `NotificationDispatcher::dispatchToUserId`).

---

## Compatibility Layer

Each original method is kept verbatim in signature and simply delegates:

```php
public function execute(User $user, …): …        // retained (unchanged signature)
{
    return $this->executeByUserId($user->id, …);  // delegates to the id-based method
}
```

No caller changes are required — controllers, listeners, and other actions still call the `User`-typed methods, which now route through the id-based implementation. `GrantEnrollmentAction` keeps its full chain: `execute(User, Course)` → `executeById(User, int)` → `executeByUserId(int, int)`; `BulkNotificationAction::execute(Collection<User>)` delegates via `$users->pluck('id')->all()` to `executeForUserIds(array)`.

---

## Dependencies Removed

**None this phase — by design.** This is the *expand* half of expand-and-contract: the `User`-typed compatibility methods are retained (per the task), so each file still imports `App\Platform\Identity\Models\User` for those signatures. The **coupling has been made removable**, not removed: every runtime code path now has an id-based entry point with no `User` dependency, so the *contract* step (a later phase) can migrate callers to the id-based methods and then delete the `User` shims + imports. Net Identity-import delta for these 31 files this phase: **0** (intended).

---

## Remaining Identity Coupling

- **`User` import + `User`-typed shim in all 31 files** — retained compatibility surface; removed in the contract phase after callers pass ids.
- **`BulkNotificationAction`** keeps a `@param Collection<int, User>` docblock on the retained `execute()` (the id-based `executeForUserIds(array)` is `User`-free).
- **Non-Identity model params untouched:** `GrantEnrollmentAction`/`GenerateCertificateAction` still accept a `Catalog\Models\Course` (a *Catalog* coupling, out of this Identity-scoped phase); `Commerce` cart actions still accept `Product`; Live actions accept `LiveSession`. These are not Identity dependencies.
- **Out of scope (untouched):** all Identity-owned Actions/Services (`OtpService`, `MfaService`, `DeviceService`, `LoginAction`, `RegisterUserAction`, etc.), all policies, controllers, models, factories, seeders, tests.

---

## Blocked Items

**None.** All 31 in-scope Actions/Services were expanded. No method required user *data* (name/email/etc.) that would have needed `UserRef`/`UserLookupPort`, and none required the current principal that would have needed `CurrentUserPort` — every one needed only the id, satisfied by `int $userId`. No missing contract surfaced. (For reference, the previously reported `UserLookupPort::totalCount()` gap for `PlatformOverview` is a widget, not an Action/Service, and is out of this phase's scope.)

---

## Risk Assessment

- **Overall: low–moderate.** Each change is a mechanical `$user->id` → `int $userId` extraction with a behavior-identical delegating shim; the id-based method runs the same query/write. No authorization or transaction boundary changed.
- **One delegation bug found and fixed during verification:** in `NotificationDispatcher::dispatchToUserId`, the `dedup_key` expression still referenced `$user->id` after the closure was rebound to capture `$userId` — it would have been an undefined variable. Corrected to `$userId` and re-verified. This is exactly the class of error the verification sweep targets.
- **Watch items:** confirm PHPStan is clean (the id-based closures capture `$userId`, not `$user`); confirm no test asserts against the *absence* of the new methods. `BulkNotificationAction`'s `pluck('id')` assumes each item exposes `id` (true for `User`).
- **Environmental risk:** no PHP/Composer here — nothing machine-verified. Mitigated by an exhaustive `$user`-reference sweep (every remaining `$user` in the 31 files is either a retained `User $user` signature or a `$this->…($user->id, …)` delegation line) and `file(1)` integrity (all 31 clean PHP text).

---

## Next Step

Await authorization for the **contract** step (a later phase): migrate the callers of these methods — HTTP controllers (via `CurrentUserPort`/route ids) and domain listeners — to the id-based APIs, then remove the `User`-typed shims and the `Identity\Models\User` imports from these 31 files, dropping the Identity coupling to zero for Actions/Services. Policies and ownership relations remain last. Do not begin until instructed. Run the toolchain (below) on a PHP-capable environment to confirm Phase 2C is green first.

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

- **33 id-based methods** added across the 31 files (confirmed by enumeration).
- **Delegation is total:** every remaining `$user` reference in the 31 in-scope files is either a retained `User $user` parameter or a `$this->…ByUserId($user->id, …)` / `…ForUserId($user->id, …)` delegation call — no id-based method body references `$user` (verified by a full `$user`-token sweep). The single exception found (`NotificationDispatcher` dedup key) was fixed.
- **Integrity:** all 31 modified files report as PHP/text via `file(1)` (no NUL / no `data`).
- **Scope:** no policy, controller, model relation, Filament, seeder, factory, test, migration, route, or API file modified; no Identity-owned Action/Service touched; no `User`-based method removed; no authorization behavior changed.

Run the commands above on a PHP-capable environment to obtain live pass/fail before the contract phase.

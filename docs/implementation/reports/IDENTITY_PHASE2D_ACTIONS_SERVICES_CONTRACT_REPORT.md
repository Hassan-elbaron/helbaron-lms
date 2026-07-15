# Identity Cleanup — Phase 2D: Actions & Services (Contract) Report

> Chief Enterprise Architect. Phase 2D is the **contract** half of expand-and-contract for Actions/Services: migrate every caller of the Phase-2C `User`-typed compatibility shims to the id-based methods, then **remove** the shims and the now-unused `Identity\Models\User` imports. **No policy, model relation, or Filament resource was touched. No API or database schema changed.** Seeders/factories/tests were touched only where they called a removed method (as permitted). Runtime gates could not run here (no PHP/Composer) — marked **"Not verifiable from repository."**

---

## Executive Summary

The 31 non-Identity Actions/Services that still imported `App\Platform\Identity\Models\User` (via the retained 2C shims) are now **fully decoupled**: every caller was migrated to the id-based method, and the `User`-typed shim + `use …\Models\User` import were deleted from each file. Callers pass the id at the edge where a user still exists — `$request->user()->id` in controllers, `$order->user_id` / `$event->…->user->id` in the fulfillment action and notification listener, `$user->id` in the seeder/tests. Result: **zero** non-Identity Action/Service imports `Identity\Models\User` (down from 31). This completes the Actions/Services slice of the Identity kernel decoupling; the remaining Identity coupling is in policies, ownership relations, and Identity's own code, all deferred by design.

---

## Files Modified

**Callers migrated (18):**

| Area | Files |
|------|-------|
| Learning controllers (7) | `NoteController`, `BookmarkController`, `LessonPlayerController`, `LearnController`, `ContinueLearningController`, `LessonProgressController`, `EnrollmentController` |
| Commerce controllers (2) | `CartController`, `CheckoutController` |
| Live / Notifications / Analytics controllers (3) | `SessionParticipationController`, `PreferenceController`, `ExportController` |
| Listeners (2) | `Notifications\NotificationEventSubscriber` (7 dispatch calls), `Certification\GenerateCertificateOnCourseCompleted` |
| Cross-context action (1) | `Commerce\Actions\Payment\FulfillOrderAction` |
| Seeder (1) | `Learning\Database\Seeders\LearningSeeder` |
| Tests (5 files) | `Learning\{EngagementTest, MediaSafetyTest, PrerequisiteLockTest, ProgressCompletionTest}`, `Certification\{RevokeReissueTest, PublicVerificationTest, OwnerEndpointsTest}` |

**Shims + imports removed (31 Actions/Services):** the 8 Learning, 7 Commerce, 6 Live, 7 Notifications, 2 Certification, 1 Analytics files from Phase 2C. `GrantEnrollmentAction` also dropped its now-orphaned `Catalog\Models\Course` import; `BulkNotificationAction` dropped its orphaned `Illuminate\Support\Collection` import.

---

## Callers Migrated

Representative transformations (all behavior-preserving):

- **Controllers:** `$access->assertAccessByLessonId($request->user(), $ref->id)` → `assertAccessByUserId($request->user()->id, $ref->id)`; `$carts->current($request->user())` → `currentByUserId($request->user()->id)`; `$action->execute($session, $request->user())` → `executeByUserId($session, $request->user()->id)`; `$action->execute($request->user(), …)` → `executeByUserId/executeForUserId($request->user()->id, …)`.
- **`NotificationEventSubscriber`:** all 7 `dispatch($event->…->user, …)` → `dispatchToUserId($event->…->user->id, …)` (the relation guards and the `welcome` payload's `$event->user->name` are unchanged).
- **`GenerateCertificateOnCourseCompleted`:** `execute($enrollment->user, $enrollment->course, …)` → `executeByUserId($enrollment->user->id, $enrollment->course, …)`.
- **`FulfillOrderAction`:** `grant->execute($order->user, $course, …)` → `grant->executeByUserId($order->user_id, $course->id, …)` (uses the FK column; no user-relation load).
- **Seeder/tests:** `GrantEnrollmentAction::execute($user, $course, …)` → `executeByUserId($user->id, $course->id, …)`; `GenerateCertificateAction::execute($user, $course)` → `executeByUserId($user->id, $course)`.

The seeder and test files retain their own `User` imports — they still create users with `User::factory()`/`User::firstOrCreate` for setup and authenticate with `Sanctum::actingAs($user)`. Only the removed-method calls were changed.

---

## Compatibility Methods Removed

All Phase-2C `User`-typed shims deleted (id-based method retained as the sole public API):

- **Had callers (migrated first, then removed):** `LessonAccessService::{assertAccessByLessonId, canAccessByLessonId}`, `ContinueLearningService::forUser`, `LearningMediaService::playbackForLesson`, `RecordLessonProgressAction::executeById`, `UpsertLessonNoteAction::executeById`, `ToggleBookmarkAction::executeById`, `GrantEnrollmentAction::{execute, executeById}`, `EnrollInCourseAction::executeById`, `CartService::current`, `CheckoutAction::execute`, `AddToCartAction::execute`, `ApplyCouponAction::execute`, `ClearCartAction::execute`, `RegisterForSessionAction::execute`, `RecordAttendanceAction::execute`, `JoinSessionAction::execute`, `CancelRegistrationAction::execute`, `NotificationDispatcher::dispatch`, `UpdatePreferencesAction::execute`, `GenerateCertificateAction::execute`, `CreateExportJobAction::execute`.
- **Had no callers (removed directly):** `LessonAccessService::activeEnrollment`, `ContractService::createForOrder`, `RemoveFromCartAction::execute`, `JoinTokenService::issue`, `AttendanceValidationService::assertCanAttend`, `PreferenceService::{isEnabled, enabledChannels}`, `DigestService::pendingFor`, `WorkflowEngine::handleEvent`, `SendNotificationAction::execute`, `BulkNotificationAction::execute`, `AwardBadgeAction::execute`. (Verified zero references across `app/`, `tests/`, `database/` before removal.)

---

## Dependencies Removed

- **`App\Platform\Identity\Models\User` import removed from all 31 Actions/Services** — cross-context Identity-model coupling in the Actions/Services layer goes **31 → 0**.
- **Orphaned imports also removed:** `Catalog\Models\Course` from `GrantEnrollmentAction`; `Illuminate\Support\Collection` from `BulkNotificationAction` (both became unused once the `User`-typed method left).
- The id-based methods reference the user only as `int $userId`; none of the 31 files has any `User` type-hint, import, or `$user->` access remaining (verified).

---

## Remaining Identity Coupling

- **Identity's own Actions/Services** (`OtpService`, `MfaService`, `DeviceService`, `LoginAction`, `RegisterUserAction`, `Profile/UpdateProfileAction`, MFA/Device/Auth actions) still use `User` — correct and out of scope ("outside Identity").
- **Policies** (18 + gates) still type `Identity\Models\User` — deferred (needs the `Actor` swap).
- **`belongsTo(User)` ownership relations** are untouched (per instruction) — including the ones read cross-context (`$order->user`, `$event->enrollment->user`, `$event->certificate->user`, `$event->request->requester`) whose FK/`->id` this phase read at the caller edge.
- **CRM `InviteMemberAction`** already uses `UserLookupPort` (Phase 2B); **`CatalogSeeder`/`PlatformOverview`** retain `User` for provisioning/counting (documented in Phase 2B, out of scope here).

---

## Blocked Items

**None.** All in-scope callers were migratable and all 31 shims removed. No caller lived in a forbidden area (policies/Filament) — the only callers found in policy/Filament-adjacent code were Identity's own controllers calling Identity's own (out-of-scope) actions, which were left alone.

---

## Risk Assessment

- **Moderate, well-contained.** Each caller change is a literal `→ …ByUserId(id, …)` swap that runs the identical query/write; each removal deletes a shim with no remaining reference (verified). No authorization, transaction boundary, API shape, or schema changed.
- **`FulfillOrderAction`** now reads `$order->user_id` instead of lazy-loading `$order->user` — one fewer query, same `user_id` written (behavior-equivalent for the grant, which only used the id).
- **Verification caught and fixed 5 cosmetic defects:** duplicated PHPDoc blocks left over when a shim was removed from just above its id-based twin (`JoinSessionAction`, `RecordAttendanceAction`, `NotificationDispatcher`, `SendNotificationAction`, `UpdatePreferencesAction`). All corrected; a final multiline sweep shows **zero** remaining duplicate docblocks. No logic defect was found.
- **Environmental risk:** no PHP/Composer here — nothing machine-verified. Mitigated by an exhaustive ripgrep sweep (zero `Identity\Models\User` / `User $user` / `$user->` in the 31 files; zero stale callers of removed methods) + authoritative `Read` confirmation of the files bash misreported as "binary/data" (the recurring stale-mount artifact). **Must** run PHPStan + tests on a PHP environment before relying on this.

---

## Next Step

Await authorization for the next phase. With Actions/Services fully decoupled, the remaining Identity work is: the **policy `Actor` swap** (18 policies + 2 gates — the highest-blast-radius step), the **ownership `belongsTo(User)` relations** (coordinated, since some are read cross-context), and Identity-internal items (`RegisterUserAction` default-role slug, `UserRegistered` event consumers, factories). Do not begin until instructed. Run the toolchain (below) on a PHP-capable environment to confirm Phase 2D is green first.

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

- **Decoupling complete:** `grep` for `Identity\Models\User` across `app/**/{Actions,Services}/**` returns **only** `app/Platform/Identity/…` files (Identity's own, out of scope) and CRM `InviteMemberAction` uses the contract, not the model — **zero** of the 31 target files import `User`. No `User $user` param or `$user->` access remains in them.
- **No stale callers:** no remaining call to any removed shim (`execute(User…)`, `dispatch(User…)`, `current(User)`, `assertAccessByLessonId`, `forUser`, `playbackForLesson`, `createForOrder`, `handleEvent`, `pendingFor`, `isEnabled`, `enabledChannels`, `issue`(JoinToken), `assertCanAttend`, `activeEnrollment`) in `app/`, `tests/`, or `database/`.
- **Docblock hygiene:** final multiline sweep for adjacent duplicate `/** … */` blocks in the 31 files returns **no files** (the 5 transient duplicates from shim removal were fixed).
- **Integrity:** files bash flagged as "binary/data" were confirmed clean, valid PHP via the authoritative `Read` tool (stale-mount artifact, not corruption).
- **Scope:** no policy, model relation, Filament resource, migration, route, or API modified; seeders/tests changed only where they called a removed method.

Run the commands above on a PHP-capable environment to obtain live pass/fail before the next phase.

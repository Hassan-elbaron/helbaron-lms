# Identity Context Cleanup — Phase 3C: Final Production Report

_CoreLMS · Laravel 12 modular monolith · July 2026_

## Executive Summary

Phase 3C removes the last remaining production dependencies on the concrete
`App\Platform\Identity\Models\User` model outside the Identity context. Three surfaces held the final
references: the admin `PlatformOverview` widget's user count, `Course::trainers()`, and
`LiveSession::trainers()` — the two surviving `belongsToMany(User)` relations retained as documented
debt in Phase 3B.

The `UserLookupPort` contract was extended with exactly two methods — `totalCount(): int` and
`refsByIds(array $userIds): array<int, UserRef>` — implemented once in `UserLookupAdapter`. The widget
now reads its count through the port. Both trainer relations were replaced with a context-local pivot
read model (`CourseTrainer`, `SessionTrainer`) exposed through a `trainerLinks` HasMany relation plus a
`syncTrainers()` writer; trainer display resolves stored user ids through `UserLookupPort::refsByIds()`.
The `course_trainer` and `session_trainers` pivot tables are untouched, and the pivot write semantics
(flat, idempotent id sync) are preserved.

After this phase, no application code outside `App\Platform\Identity` imports the concrete `User`
model, calls `User::query()`, or declares a `belongsTo(User)` / `belongsToMany(User)` relation, other
than the documented test-infrastructure and published-event-DTO exceptions. The database schema, REST
API payloads, and business behavior are unchanged.

## Contract Changes

`app/Platform/Identity/Contracts/UserLookupPort.php` gained two methods and nothing else:

```php
/** Total number of user accounts (reproduces User::query()->count()). */
public function totalCount(): int;

/**
 * @param  array<int, int>  $userIds
 * @return array<int, UserRef>
 */
public function refsByIds(array $userIds): array;
```

`refsByIds` is contractually specified to key results by user id, preserve input ordering, silently
skip ids with no matching user, and never expose forbidden Identity fields. No other method was added;
the four pre-existing methods (`refById`, `refByPublicId`, `idByEmail`, `instructors`) are unchanged.

## PlatformOverview Migration

`app/Filament/Widgets/PlatformOverview.php`:

- Removed `use App\Platform\Identity\Models\User;`, added `use App\Platform\Identity\Contracts\UserLookupPort;`.
- `Stat::make('Users', (string) User::query()->count())` → `Stat::make('Users', (string) app(UserLookupPort::class)->totalCount())`.

`totalCount()` reproduces `User::query()->count()` exactly, so the rendered value, label, description,
icon, and color are identical. No business logic was added to the widget; it remains a read-only
aggregate. The other seven stats (Courses, Orders, Revenue, Enrollments, Live sessions, CRM leads,
Notifications) are untouched.

## Course Trainers Migration

The `belongsToMany(User, 'course_trainer')` relation was removed from `Course` and replaced with:

- **`app/Domains/Catalog/Models/CourseTrainer.php`** (new) — a read model over the `course_trainer`
  pivot (`$table = 'course_trainer'`, `$timestamps = false`, `$incrementing = false`,
  `$primaryKey = 'user_id'`). It carries `user_id`/`position` and has **no** relation to the Identity
  `User` model.
- **`Course::trainerLinks(): HasMany`** — `hasMany(CourseTrainer::class, 'course_id')`, preserving
  eager-loading and `whenLoaded` conditional inclusion (no natural ordering was added, matching the
  prior relation's default order).
- **`Course::syncTrainers(array $userIds): void`** — an idempotent, flat sync of the pivot via the
  query builder that reproduces the previous `trainers()->sync($ids)` behavior (dedupe, detach removed
  ids, insert new ids; no position/timestamp columns are written, exactly as before).

Consumers migrated:

| File | Before | After |
|------|--------|-------|
| `CourseResource.php` | `TrainerResource::collection($this->whenLoaded('trainers'))` | `whenLoaded('trainerLinks', …refsByIds(trainerLinks->pluck('user_id'))…)` → `TrainerResource::collection` |
| `CourseSearchService.php` | `->with([…, 'trainers'])` | `->with([…, 'trainerLinks'])` |
| `CreateCourseAction.php` | `$course->trainers()->sync($ids)` | `$course->syncTrainers($ids)` |
| `UpdateCourseAction.php` | `sync` + `fresh([…, 'trainers'])` | `syncTrainers` + `fresh([…, 'trainerLinks'])` |
| `CatalogSeeder.php` | `$course->trainers()->sync([…])` | `$course->syncTrainers([…])` |

Trainer JSON is preserved: `TrainerResource` already consumes `UserRef` (from Phase 2B), and
`refsByIds` preserves input (pivot) order, so `trainers[]` renders the same `id` (public id), `name`,
`headline`, and `avatar_path` fields in the same order. The `course_trainer` table was not modified or
removed. `Course::trainers()` was removed only after all five runtime callers were migrated.

## LiveSession Trainers Migration

Identical pattern applied to `LiveSession`:

- **`app/Domains/Live/Models/SessionTrainer.php`** (new) — read model over `session_trainers`
  (`user_id`/`role`/`position`), no `User` relation.
- **`LiveSession::trainerLinks(): HasMany`** — `hasMany(SessionTrainer::class, 'session_id')`.
- **`LiveSession::syncTrainers(array $userIds): void`** — flat pivot sync reproducing the previous
  `trainers()->sync($ids)` behavior on `session_trainers`.

Consumers migrated:

| File | Before | After |
|------|--------|-------|
| `LiveSessionResource.php` | `whenLoaded('trainers', … ['id'=>public_id,'name'=>name])` | `whenLoaded('trainerLinks', …refsByIds… ['id'=>publicId,'name'=>name])` |
| `ScheduleSessionAction.php` | `$session->trainers()->sync($ids)` | `$session->syncTrainers($ids)` |
| `LiveSessionController.php` | `->load(['trainers', 'recordings'])` | `->load(['trainerLinks', 'recordings'])` |
| `LiveSessionAdminController.php` | `->load('trainers')` | `->load('trainerLinks')` |

The resource JSON shape (`{ id: public_id, name }`, in pivot order) is preserved. The `session_trainers`
table was not modified or removed. `LiveSession::trainers()` was removed only after all four runtime
callers were migrated. Scheduling/update sync behavior is unchanged (`ScheduleSessionAction` still
syncs only when `trainer_ids` is present; `RescheduleSessionAction` does not touch trainers).

## Relations Removed

| Relation | Model | Replacement |
|----------|-------|-------------|
| `trainers()` — `belongsToMany(User, 'course_trainer')` | `App\Domains\Catalog\Models\Course` | `trainerLinks()` (HasMany → `CourseTrainer`) + `syncTrainers()` |
| `trainers()` — `belongsToMany(User, 'session_trainers')` | `App\Domains\Live\Models\LiveSession` | `trainerLinks()` (HasMany → `SessionTrainer`) + `syncTrainers()` |

Both `use App\Platform\Identity\Models\User;` imports were removed from the two models. The now-unused
`use …\Relations\BelongsToMany;` import was removed from `LiveSession` (retained in `Course`, which
still uses it for `categories()` and `tags()`).

## Published Event Exception

`App\Platform\Identity\Events\UserRegistered` continues to carry the concrete `User` model as its
payload and was **not** modified, per instruction. This is the single documented cross-context seam: it
is an Identity-owned published event DTO, consumed by subscribers via the model instance. It remains an
explicit, accepted exception and is not counted as an external dependency.

## Final Production Dependency Count

Repository sweep across `apps/api/app` (excluding Identity-owned code, tests, factories, seeders, and
the published-event DTO seam):

| Pattern | Non-Identity production hits |
|---------|------------------------------|
| `use App\Platform\Identity\Models\User;` | **0** |
| `User::query()` | **0** |
| `belongsTo(User…)` | **0** |
| `belongsToMany(User…)` | **0** |

All matches for these patterns now resolve to one of: code inside `App\Platform\Identity`
(adapters, policies, services, actions, events, models, Filament `UserResource`); test infrastructure
(factories: `NotificationFactory`, `EnrollmentFactory`, `LessonNoteFactory`, `OrderFactory`,
`CertificateFactory`; seeders: `CatalogSeeder`, `LearningSeeder`); the `UserRegistered` published-event
seam; or documentation comments (`RequestTenantResolver` docblock, and the `syncTrainers` docblocks on
`Course`/`LiveSession` that name the removed relation). No non-Identity production application class
depends on the concrete `User` model.

## API Compatibility

Unchanged. `CourseResource.trainers[]` still emits `{ id, name, headline, avatar_path }`;
`LiveSessionResource.trainers[]` still emits `{ id, name }`; both preserve pivot ordering and remain
conditionally included only when the caller eager-loads the (now-renamed) link relation, exactly as the
prior `whenLoaded('trainers')` behaved. The admin dashboard `Users` stat renders the identical value.
No routes, request shapes, or response envelopes were altered.

## Database Compatibility

Unchanged. No migration was added or modified. The `course_trainer` and `session_trainers` pivot tables
retain their columns and data. `CourseTrainer`/`SessionTrainer` are read/write models mapped onto the
existing tables; `syncTrainers()` writes the same `course_id`/`user_id` (resp. `session_id`/`user_id`)
rows the prior `belongsToMany` sync produced, with no schema assumptions beyond the existing columns.

## Deptrac Impact

Net reduction in cross-context coupling. `Course` (Catalog) and `LiveSession` (Live) no longer depend
on `App\Platform\Identity\Models` — two fewer edges into the Identity models layer. `PlatformOverview`
(Filament) now depends on `IdentityContracts` (allowed by every layer) instead of `Identity\Models`.
`CourseResource`/`LiveSessionResource` depend on `IdentityContracts` (`UserLookupPort`, `UserRef`),
which is permitted. The two new pivot models depend only on `Illuminate\Database\Eloquent\Model`,
staying within their own contexts. No new violations are introduced.

## Remaining Test Infrastructure Debt

Factories and seeders still construct users through Identity test infrastructure (`User::factory()`,
direct `User` imports in `CatalogSeeder`, `LearningSeeder`, and the five listed factories). This is
permitted test/seed scaffolding, not production application code, and is intentionally out of scope:
these files create fixtures rather than encoding runtime domain behavior. No production class was
weakened to simplify tests. Converting fixtures to an Identity-owned test factory port remains optional
future cleanup and is not required for the production boundary to be complete.

## Risk Assessment

- **Pivot write parity (medium→low).** `syncTrainers()` reimplements `belongsToMany::sync()` for the
  flat-id case actually used (no pivot payload). It dedupes, detaches removed ids, and inserts new ids
  without touching `position`/`role` or timestamps — matching the prior behavior, which also never set
  those columns on sync. Risk is confined to the pivot rows and is covered by existing
  create/update/schedule tests once a runtime is available.
- **Trainer display ordering (low).** The prior `belongsToMany` had no explicit order; `trainerLinks`
  likewise applies none, so both rely on the same database row order. `refsByIds` preserves that order.
- **Keyless pivot hydration (low).** `CourseTrainer`/`SessionTrainer` set `primaryKey = 'user_id'`,
  `incrementing = false`; they are used for read + explicit row insert/delete, never for id-based
  model updates.
- **N+1 (none introduced).** Display still eager-loads via `whenLoaded('trainerLinks')`; `refsByIds`
  batches the user resolution in a single `whereIn` query with `profile` eager-loaded.

## Final Recommendation

Phase 3C is complete and safe to merge. The concrete `User` model is now fully encapsulated inside the
Identity context for all production code, with only the sanctioned test-infrastructure and published-
event exceptions remaining. The Identity Context Cleanup program's production objective is met. The
optional test-fixture port is the only follow-up, and it does not block release.

## Validation

The execution environment has no PHP/Composer runtime, so the following could not be run and their
results are **Not verifiable from repository**:

- `composer dump-autoload` — Not verifiable from repository.
- `vendor/bin/pint --test` — Not verifiable from repository.
- `vendor/bin/phpstan analyse --no-progress` — Not verifiable from repository.
- `vendor/bin/deptrac analyse --no-progress` — Not verifiable from repository.
- `php artisan test` — Not verifiable from repository.

Static verification performed from the repository:

- Full-tree grep confirms **0** non-Identity production hits for `Identity\Models\User` imports,
  `User::query()`, `belongsTo(User)`, and `belongsToMany(User)` (excluding Identity-owned code, tests,
  factories, seeders, and the `UserRegistered` seam).
- Grep confirms no remaining runtime caller of `Course::trainers()` / `LiveSession::trainers()`; the
  only `trainers` references left are the resource output keys (`whenLoaded('trainerLinks')`), the
  unrelated `/trainers` catalog route, and docblocks.
- All 16 touched files (14 modified, 2 new) were edited via exact-match replacements.

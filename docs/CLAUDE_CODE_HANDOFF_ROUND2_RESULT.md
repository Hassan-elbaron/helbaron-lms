# HElbaron LMS — Round 2 Result (Phase A completion)

**Branch:** `pr-1`
**Scope executed:** everything in `CLAUDE_CODE_HANDOFF_ROUND2.md` §6, in order. Phase B not started.
**Date:** 2026-08-30.

Companion to `CLAUDE_CODE_HANDOFF_RESULT.md` (Round 1).

---

## 1. Verification — all gates green

| Gate | Result |
|---|---|
| `php artisan test` (serial, chunked) | **2097 passed, 0 failed** |
| `vendor/bin/pint --test` | **passes — 0 files** (was 1315) |
| `vendor/bin/deptrac analyse` | **0 violations, exit 0** (was 5) |
| `vendor/bin/phpstan analyse` | **290 errors** (was 318). **Exactly 0 introduced — measured, see §2** |
| `npm run lint` | clean |
| `npm run typecheck` | clean |
| `npm run test` | **161 files, 856 tests, all passed** |
| `npm run build` | **succeeds** — compiled in 39s, 86 static pages |

Backend per-chunk: Unit **289**; Admin→Branding **450**; Catalog→Entitlements **582**;
Features→I18n **103**; Identity **141**; Integration(s) **46**; Learning **76**; Live **23**;
Marketing→Pages **161**; Payments→Reviews **75**; Search→Subscriptions **75**; Tax→Timezone **76**.

**All nine pre-existing failures from Round 1 are fixed** (§4 below). The suite has never been green
on this branch before; it is now.

### PHPStan: exactly one error introduced, and it was fixed

I diffed the full JSON report against the Round 1 snapshot rather than eyeballing it. One error
appeared that had not existed before — `SeatProvisioningAdapter.php`, where I had inserted a new
method *between* an existing docblock and the function it documented, so `@return list<int>` landed
on a method returning `?int`. Fixed; that file is now at 0.

The remaining 290 are all pre-existing. 28 were cleared as a side effect of A0.3.

---

## 2. Where I disagreed with the review

Two Round 2 items turned out to rest on premises that do not hold. Both are reported rather than
"fixed", because implementing them would have added code the product does not need.

### A9 — the missing index already exists. No migration was added.

The review states `product_courses` has only `primary(['product_id','course_id'])` and no standalone
`course_id` index. It has one: **`database/migrations/2026_07_21_000050_add_performance_indexes.php`
lines 54–58** creates `product_courses_course_id_index`, with a comment giving the same reasoning the
review gives ("course_id … is the TRAILING column of the primary key and so cannot be used as a
leading key").

I wrote the migration before discovering this, and it failed on a fresh migrate with
`Duplicate table: relation "product_courses_course_id_index" already exists`. I then verified two
things directly: that `foreignId()->constrained()` creates only the primary-key index on PostgreSQL
(so the review's underlying concern is sound in general), and that a `migrate:fresh` **without** my
migration still ends with the index present. My migration is deleted. **No action needed.**

My Round 1 grep missed it because I searched `app/` only; this migration lives in
`database/migrations/`. That is my error, not the review's.

### A11.5 — there is no `PublishState::Archived` to test

The review lists "Archived block (`PublishState::Archived`); only Draft is tested" as a coverage gap.
`PublishState` has exactly two cases, `Draft` and `Published`. Draft is therefore the only
not-published state a block can be in, and it was already covered. I left a comment in
`CourseReadinessTest` recording this so the gap is not re-raised; inventing the enum case to satisfy
the item would have added a state the product does not have.

---

## 3. §1 — defects in the pre-existing white-label work

### A0.1 — the `env()` empty-string bug, and something worse underneath it

Your addition was correct and I confirmed it: `.env.example` shipped `SEED_ADMIN_PASSWORD=`, so
`env('SEED_ADMIN_PASSWORD', self::DEV_PASSWORD)` returned `''` and the development admin was created
with `Hash::make('')`. Nobody could log into a fresh local install, silently.

**But the audit turned up a bigger one.** `scripts/deploy.sh:40` runs `php artisan config:cache` on
every production deploy, and Laravel's `LoadEnvironmentVariables` bootstrapper **returns early when
the config is cached** — `.env` is never read. So `env()` called from anywhere outside a config file
returns `null` in production, and **every `BRAND_*` key an operator set was silently ignored on every
deployed instance.** The whole white-label mechanism was inert in the only environment that matters.

I proved it end to end: with `BRAND_NAME_EN="Acme Academy"` set, config cached, and `.env` removed
(production's exact shape), `config('branding.name.en')` returns `Acme Academy` while
`env('BRAND_NAME_EN')` returns `NULL`.

**Fixed by:**

| File | Change |
|---|---|
| `app/Platform/Shared/Support/Env.php` *(new)* | `Env::string()` / `orFallback()` — defaults fire for absent **and** present-but-blank keys. `false` and `0` are NOT treated as blank, so `LEARNING_PLAYBACK_ALLOW_FAKE=false` keeps meaning false. |
| `config/branding.php` *(new)* | Resolves every `BRAND_*` key. Being a config file, it is evaluated while the cache is built, so the values survive `config:cache`. This is also exactly what larastan's `noEnvCallsOutsideOfConfig` rule was flagging. |
| `BrandSetting.php` | Reads `config('branding.*')` instead of calling `env()` at runtime. |
| `IdentitySeeder.php` | `Env::string` for `SEED_ADMIN_*`. The production guard was already correct (it uses `blank()`). |
| `config/certification.php`, `config/identity.php` | `Env::string` for the issuer chains. |
| `.env.example`, `.env.production.example` | Optional keys **commented out** rather than shipped empty, with a comment explaining why they must not be "tidied" back in. |

`BRAND_NAME_EN="${APP_NAME}"` interpolation was checked as instructed: **it works** (phpdotenv 5.6.4),
verified by test.

**Tests:** `EnvFallbackTest` (6) — including an assertion that plain `env('KEY', 'fallback')` returns
`''` for the bug's input, so the fix is provably load-bearing. `BrandDefaultsFromEnvTest` (9) —
re-evaluates `config/branding.php` against a controlled environment, because the framework evaluates
a config file once per process and a `putenv()`-then-assert test would pass no matter what the file
did. `SeededAdminCredentialsTest` (5) — including that the seeded admin can actually log in.

`BrandingTest` / `PerOrgBrandingTest` were rewritten to assert **behaviour** (stored values override
defaults; defaults are never empty) instead of the vendor literals `'HElbaron'` / `'إلبارون'`. Note
one of those assertions was passing only because this machine's `APP_NAME` happened to match — it
would have failed on any real customer instance.

### A0.2 — Deptrac
Applied decision (e): `CreateAdminUser.php` moved to `app/Platform/Identity/Console/Commands/`, and
`app/Platform/Identity/Console/.*` added to the `Identity` layer collectors. Registered explicitly in
`IdentityServiceProvider` (Identity has no auto-discovered console namespace). **`deptrac analyse`
now exits 0 with 0 violations**, and both `identity:create-admin` and `identity:openapi-export` still
resolve. No baseline entry.

### A0.3 — the 25 PHPStan errors
Two were genuine type errors and are fixed (`BrandSetting` redundant nullsafe, `CreateAdminUser` dead
`??`). Three are correct as written and now carry documented `ignoreErrors` entries in the same style
the file already uses for `ReportCache` and `ValidateEnvironment` — these are justified exemptions,
not baseline entries:

- `Env.php` / `IdentitySeeder.php` `noEnvCallsOutsideOfConfig` — reading the **raw** environment is
  the point in both cases.
- `IdentitySeeder.php` `nullsafe.neverNull` — **this one would have been a real regression.**
  Illuminate's `Seeder` declares `protected $command;` with no type and no default, so it is
  uninitialised whenever a seeder runs programmatically (every test, `$this->seed()`). Laravel's own
  Seeder guards it with `isset()`. Removing the `?->` to satisfy the analyser would have introduced a
  null dereference. Flagged per your instruction rather than forced.

### A0.4 — line endings
`core.autocrlf` was set to **`true` at the system level** (Git for Windows default), overriding
`.gitattributes`. Set to false locally and converted the working tree — diff-neutral for PHP, since
the repo already stores LF. **Pint went from 1315 files to 0.**

One correction to my own work: my first conversion pass used "add count == delete count" to identify
EOL-only diffs, which also caught four files with genuine one-line changes and reverted them — one of
mine and three of the branch's. I caught it, restored mine from a backup, and reconstructed the other
three from §3.3's description (`NotificationTemplateFactory` `{{ brand }}`; the `no-reply@helbaron.test`
fallback removed from `config/services.php` and `MailgunMailProvider`). Those three were uncommitted
work with no other copy, so **please spot-check them** — they are small and their intent is documented,
but they are reconstructions rather than the originals.

**~37 `apps/web` files are stored in Git with CRLF** (they predate `.gitattributes`). Converting those
is a real diff, so I left them alone; they want a dedicated `git add --renormalize` commit separate
from feature work. Recorded in `docs/ops/LOCAL_TEST_ENVIRONMENT.md`.

---

## 4. §2 — the four blockers

### A4 — entitlement no longer re-sourced as a lifetime free grant
Your provenance correction is reflected in the code comments. The fix:

- New `EntitlementPort::courseEntitlement()` returning `CourseEntitlement{kind, expiresAt}` (a Shared
  DTO + `EntitlementKind` enum) instead of a boolean.
- `EntitlementService` resolves the strongest-first: purchase (perpetual) → subscription (period end)
  → seat (latest of pool-subscription period end / company access window).
- `EnrollInCourseAction` grants with that source and window. New `EnrollmentSource::Subscription`
  (the `source` column is `string(16)` with no constraint, so no migration was needed).
- **`OrganizationSubscriptionService::unassignEmployee()` now revokes the enrollments the seat
  produced.** It previously only released the seat, so access survived to the end of the billing
  period. This needed one small addition — `SeatProvisioningPort::userIdForMember()` — because
  members cross that boundary as scalar ids. `revokeCompanySeat()` filters on `source = company_seat`,
  which is precisely why recording the seat correctly is what makes revocation reach it at all.

**Tests (`EntitledEnrollmentSourceTest`, 9):** subscriber gets `source=subscription` + non-null
`expires_at`; lapsed subscription removes access; seated employee gets `company_seat` + expiry;
lapsed org subscription removes access; **seat release removes access immediately**; a learner's own
purchase survives seat release; purchase stays perpetual; re-enrolling leaves an existing purchase row
untouched; an unentitled user is still refused with no row written.

### A5 — `Course.is_free` (the option I chose, and you approved)
- Reversible migration adding `courses.is_free` (default **false**, fail-closed), backfilling `true`
  wherever no `product_courses` row grants the course — behaviour unchanged at the moment it runs.
  Rollback verified: applied, rolled back (column gone), re-applied.
- Resolution rule, in one place: **free = declared free AND not sold by an active product.** Draft and
  archived products stop mattering entirely, which is the one-way door closed.
- Both your additions are done:
  - **Readiness warning** `course.not_acquirable` (Warning, never Blocker) when a published course is
    neither free nor actively sold, naming both remedies.
  - **Filament `is_free` toggle** with helper text stating the interaction explicitly: an active
    product always wins and the toggle has no effect while one sells the course.

The A1 draft/archived tests were rewritten rather than deleted: the revenue protection they existed
for is preserved, expressed as "a course nobody declared free" (which is what a paid course actually
looks like) instead of "a course with any product row".

### A6 — entities and Unicode whitespace, in the order we agreed
`strip_tags` → `html_entity_decode` → strip Unicode blanks. Verified matrix:

| Input | Verdict |
|---|---|
| `<p>&nbsp;</p>`, literal NBSP, `&zwnj;`, `&#8203;`, `&emsp;`, `&nbsp;&nbsp;&nbsp;`, BOM | **empty** |
| `<pre>&lt;div class="x"&gt;&lt;/div&gt;</pre>` (code sample) | **substance** |
| `&lt;img&gt;` | **substance** — visible characters; embed regex runs on the raw string |
| `&amp;` | **substance** — deliberate, and documented as such |
| `<oembed>`, `<svg>`, `<canvas>`, `<picture>`, `<track>` | **substance** (added) |

`<oembed>` was a false negative that **blocked** publishing CKEditor 5 media — the exact failure class
A3 exists to prevent.

### A7 — the poison course
Catches `Throwable` per course (ERROR for unexpected, WARNING kept for readiness), continues the loop,
returns a non-zero exit code only when something unexpected failed, and uses `chunkById`.
**Tests (5 new):** a course whose `CoursePublished` listener throws no longer stops the course behind
it; the failure is logged at error with the course id; a readiness block alone still exits 0.

---

## 5. §2 (cont.) and §3 — A8, A10, A11

**A8 — unified.** New `Commerce\Support\SoldCourseIds` is the single query for "which of these courses
does a product grant", asking the pivot table directly with **no model scopes**. Both call sites use
it. The guard's `whereHas('courses', …)` previously applied `Course`'s `SoftDeletes` **and**
`SharedOrOwnedTenantScope`, so its answer changed with the request's resolved tenant while the
catalogue's did not — and where they disagreed the guard failed **open**.

**A10 — done.** `OtpService::send()` retires prior unconsumed codes for the channel/destination.
Tests prove the *old* code stops working (the previous tests only proved the new one works), that
exactly one code is live after several resends, and that the retired rows are **not deleted** — the
hourly budget counts issued codes, so deleting them would hand out a free rate-limit reset.

**A11.1 / A11.2 — the two inert tests, rewritten.** I confirmed both claims first: `['media_id' => 0]`,
`['url' => '  ']`, `['src' => null]` and `<imgur></imgur>` all return false under the old string-only
recursion. Each now pairs the positive and negative in one assertion, so the test dies if the feature
is removed rather than only if a boundary is tweaked.

**A11.3 — done.** The scheduled-publish "no warning" test now also asserts the course actually
published (it would have stayed green if the command did nothing), and the draft-block readiness test
asserts *which* blocker fired.

**A11.4 — done.** Every rejection case in `FreeEnrollmentApiTest` now also asserts
`assertDatabaseMissing('enrollments', …)`, so a path that creates the row and then returns 402 fails.

**A11.5 — done**, and three of the listed shapes were real false negatives that would have blocked a
legitimate lesson. Fixed as well as tested:
- `['media' => ['id' => 7]]` — a bare `id` now counts **inside a media container** only (a serialised
  record's own top-level `id` still does not).
- `['attachment_ids' => [3,4]]`, `['media_id' => [12,13]]` — reference **lists** now count.
- `color`, `size`, `icon`, `placeholder`, `dir`, `label` added to the presentation denylist.
- `public_id` **removed** from the reference allowlist, per your over-broad note — any API-shaped
  payload echoes it.
- `safeRedirect()` now has a test (15 cases: `//evil`, `https://evil`, `/\evil`, `/javascript:`,
  `data:`, tab/newline obfuscation, and a valid `/foo/bar:baz`). It had none.
- Lesson-media branch, `content_i18n` branch, anonymous caller, and double-enrolment idempotency all
  covered.

---

## 6. §4 — all nine pre-existing failures fixed

| Failure | Fix |
|---|---|
| 3 × branding (`BrandingTest`, `PerOrgBrandingTest`) | Rewritten to assert behaviour, not vendor literals (part of A0.1). |
| 2 × `ProductionConfigValidatorTest` | `safeProductionConfig()` now sets a real `learning.playback.provider`. The validator rule was correct; the helper had never been taught about it. |
| 4 × publish (`PublishValidationTest`, `CourseLifecycleTest`, `CourseLifecycleSchedulingTest`, `InstructorPortalTest`) | `LessonFactory::published()` now includes content. A published lesson with none is invalid by construction once `lesson.empty_content` is a blocker — the same reasoning the factory already documents for quiz lessons. Tests that want a published-but-empty lesson pass `'content' => null` explicitly and still work. |

---

## 7. §5 — smaller items

- **`isEmailVerificationRequired` moved out of the one component.** Handling now lives in the shared
  `MutationCache` in `query-client.ts`, so every authenticated surface routes to `/verify-email`
  instead of showing a generic toast. New `recoveryPathFor()` centralises the destination.
- **Resend cooldown** now branches on `isRateLimited()` (429 / `AUTH_OTP_RATE_LIMITED`). A dropped
  connection no longer locks the button for 60s. Covered by two tests, one of each kind.
- **Related-course cards** now receive purchase summaries, resolved in one batched call. Previously
  every cross-sell card rendered the fail-closed "Not available yet", including buyable courses.
- **`errors.ts` `HTTP_FORBIDDEN`** — confirmed dead (the client falls back to `"HTTP_ERROR"`).
  Pre-existing; left alone and reported, as you specified.
- **`ResendEmailOtpTest` burst count** annotated so the limit is not silently hard-coded.
- **`RequireVerifiedEmail`** now carries a comment recording that `$request->is()` is `Str::is` and is
  an **exact** match for a wildcard-free pattern, so nobody "fixes" it into a prefix match.
- **`docs/ops/LOCAL_TEST_ENVIRONMENT.md`** *(new)* records the parallel-worker-database decision, the
  `--ignore-platform-req` requirement, the compose container-name collision, the line-ending fix, and
  the `--env=testing` trap in §8 below.
- **§3.5 admin MFA edge case** — left as reported, no action, per your instruction.

---

## 8. Things you should know that are not in either brief

**`--env=testing` does not switch the database.** There is no `.env.testing`, so
`php artisan migrate --env=testing` reads plain `.env` and runs against the **development** database.
The test database is selected only by `phpunit.xml`, which applies when PHPUnit is the entry point.
I hit this while verifying the (now-deleted) index migration and ran `migrate:fresh` against the dev
database `helbaron`. **Nothing was lost** — that database had no tables to begin with (an earlier
query against it failed with `relation "courses" does not exist`) — but the trap is live for anyone
who assumes the flag protects them. Documented in the new ops file.

**`config:cache` vs `env()` is a general hazard here, not just a branding one.** Any `env()` call
outside `config/` returns null in production. I fixed the branding path; the same pattern may exist
elsewhere and larastan's `noEnvCallsOutsideOfConfig` rule is the tool that finds it. Worth a sweep as
its own task.

---

## 9. What I did not do

- **Phase B — not started**, as instructed.
- **No `deptrac.baseline.yaml` entries.** The three PHPStan exemptions are documented `ignoreErrors`
  entries in the style the file already uses, not baseline additions.
- **No test deleted or skipped.** Two were *replaced* by stronger equivalents (A11.1/A11.2), several
  rewritten to assert behaviour rather than literals, and one removed with a comment explaining that
  the enum case it targeted does not exist (§2).
- **Nothing revoked.** `commerce:report-free-grants` is read-only by contract and has a test asserting
  it changes nothing.
- **No secrets printed, logged or committed.**

## 10. Suggested next step

Phase A is complete and every gate is green. Before Phase B I would (1) spot-check the three
reconstructed files named in §3/A0.4, (2) take the `apps/web` CRLF renormalisation as its own commit,
and (3) run `commerce:report-free-grants --csv` against a production snapshot — that number is the
one outstanding input to decision (a), and it is the only thing here that needs real data rather
than code.

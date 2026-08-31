# Round 2 — Phase A review findings + Phase A completion

**Read this together with `CLAUDE_CODE_HANDOFF.md`.** That brief still governs Phases B–E and all
the rules. This file is the review of your Phase A work plus the defects that must close before
Phase A can be called done.

Your Phase A report was accurate, honest about failures, and correctly identified three real defects
in the pre-existing white-label work. `ResendEmailOtpTest` is the standard the rest of the suite
should be held to. The findings below are not a rejection of that work — they are what a second pass
over the same code found.

**Do not start Phase B.** Finish Phase A properly first.

---

## 0. Decisions you asked for — answered

**(a) Existing free grants — APPROVED, read-only.** Build the report command you proposed:
`php artisan commerce:report-free-grants`. It must list enrollments with `source = free` on courses
that have any `product_courses` row, with user, course, granted-at, and the product's current status.
**Read-only. Revoke nothing. Write no migration.** Output to console and an optional CSV path.

**(b) A3 severity exemption — APPROVED as documented.** Keep the docblock reasoning. Do not build a
grandfathering mechanism.

**(c) "Archiving a product permanently removes the free path" — REJECTED.** See A5. This is a defect,
not an accepted consequence.

**(d) Keeping the `hasCourseEntitlement()` bypass — REJECTED as implemented.** See A4. Keeping paid
users working was the right instinct; re-sourcing their access as a lifetime free grant was not.

**(e) Deptrac fix for `CreateAdminUser.php` — APPROVED.** Move the file to
`app/Platform/Identity/Console/Commands/` and add `app/Platform/Identity/Console/.*` to the
`Identity` layer collectors in `deptrac.yaml`. An Identity console command belongs to Identity and
may use its own models. This is the correct fix, not a baseline entry.

**(f) The six fixable pre-existing failures — APPROVED, fix them all.** See A11.

---

## 1. Defects in the pre-existing white-label work (§3.3 of the original brief)

These were introduced by the previous session, not by you. Your §3.6 finding was correct and is the
root of all of them.

### A0.1 — `env()` empty-string bug — **your §3.6, confirmed and wider than reported**

Laravel's `env()` returns `''` for `KEY=`, so a default argument never fires. `.env.example` ships
these keys present-and-empty, so the whole fallback chain in `BrandSetting::defaults()` is dead.

**You missed one, and it is worse than the branding ones:** `IdentitySeeder` does

```php
$password = (string) env('SEED_ADMIN_PASSWORD', self::DEV_PASSWORD);
```

with `SEED_ADMIN_PASSWORD=` in `.env.example`. The local development admin is therefore created with
`Hash::make('')` — **an empty password** — and nobody can log into a fresh local install. The
production guard itself is unaffected (it uses `blank()`, which correctly treats `''` as blank).

**Do**
1. Replace null-coalescing with empty-coalescing everywhere a `BRAND_*` / `SEED_ADMIN_*` /
   `CERTIFICATION_ISSUER` default is resolved — `env('X') ?: $fallback`, or a small
   `envOrFallback()` helper. Audit **every** `env(..., default)` call added by the white-label work,
   not only the ones named here.
2. In `.env.example` and `.env.production.example`, **comment out** the optional keys rather than
   shipping them empty, so `env()` genuinely sees them as absent. Keep the explanatory comments.
3. Sanity-check `BRAND_NAME_EN="${APP_NAME}"` actually interpolates under this phpdotenv version; if
   it does not, comment it out too and let the code fall back to `config('app.name')`.

**Acceptance**
- Test: with no `BRAND_*` keys set at all, `BrandProfilePort::profile()` returns a non-empty brand
  name, company name and email footer.
- Test: with `BRAND_*` keys present but empty, the same holds (this is the actual bug).
- Test: with `BRAND_NAME_EN="Acme Academy"`, every one of those surfaces reports Acme.
- Test: `IdentitySeeder` in `local` with `SEED_ADMIN_PASSWORD` empty produces an account whose
  password is the documented dev default and which can actually authenticate.
- `BrandingTest` / `PerOrgBrandingTest` (your failures 7–9): these assert hardcoded vendor literals.
  Rewrite them to assert *behaviour* — that stored values override defaults, and that defaults are
  non-empty — instead of asserting a specific brand string. That is the correct fix and it belongs
  here, not in Phase B.

### A0.2 — Deptrac violations from `CreateAdminUser.php`
Apply decision (e). Verify `vendor/bin/deptrac analyse` exits 0 afterwards.

### A0.3 — 25 PHPStan errors in `BrandSetting.php` + `IdentitySeeder.php`
Your measurement (25 in the working tree, 0 at `HEAD`) identifies them as introduced by the
white-label work. Fix them. Do not add baseline entries. If a fix would change behaviour rather than
types, say so instead of forcing it.

### A0.4 — Line endings
Not a code problem. Run `git config core.autocrlf false` then `git add --renormalize .`, then
re-run `vendor/bin/pint --test`. Do **not** reformat 1310 files into the Phase A diff. If
renormalising still leaves real Pint findings in files this branch touched, fix only those.

---

## 2. Defects in your Phase A work

### A4 — **BLOCKER.** The retained bypass converts a revocable entitlement into a lifetime free grant

`EnrollInCourseAction.php:46-51`:
```php
if ($this->entitlements->isCourseSold($courseId)
    && ! $this->entitlements->hasCourseEntitlement($userId, $courseId)) {
    throw new CoursePurchaseRequiredException;
}
return $this->grant->executeByUserId($userId, $courseId, EnrollmentSource::Free);
```

`hasCourseEntitlement()` (`EntitlementService.php:78-84`) is true for four sources. **Three of them
are revocable or time-boxed**: an active individual subscription, an org seat on a seat-pool
subscription, and a company-purchase seat. The fall-through grant uses `GrantEnrollmentAction`'s
default `$expiresAt = null` (`GrantEnrollmentAction.php:32`, `:68`), producing
`expires_at = NULL, source = free`.

Access is then decided **only** by that row — `CourseEnrollmentAdapter.php:36-42` is
`grantsAccess()->notExpired()->exists()` and never re-consults the entitlement port.

There is no `EnrollmentSource::Subscription` anywhere in the codebase (only `Free`, `Purchase`,
`Manual`, `Grant`, `CompanySeat`), so a subscriber has **no enrollment row at all** until they call
`POST /api/v1/courses/{course}/enroll` — which is now the intended path for them. One POST per
course converts a monthly subscription into permanent access to the entire bundled catalogue.
Nothing reclaims it: `RevokeEnrollmentsOnRefund` needs an order, and
`CompanySeatEnrollmentAdapter::revokeCompanySeat()` filters `where('source', CompanySeat)`
(`:73`).

Your (d) reasoning was right that removing the bypass would break already-paid users. The purchase
case is genuinely safe (an existing `source=purchase` row with its own window is left alone by
`GrantEnrollmentAction.php:51`) and the refund case is safe (`personalPaidOrder()` requires
`OrderStatus::Paid`). The subscription and seat cases are the ones that leak.

**Do**
1. The bypass must not re-source access as `Free`/lifetime. Have `hasCourseEntitlement()` (or a new
   port method) return the **entitlement kind and its window**, and grant with that source and
   `expires_at` — or refuse the free path for sold courses entirely and let the subscription/seat
   flows create their own correctly-sourced rows.
2. Whichever you choose, an entitlement that lapses must remove access.

**Acceptance**
- Test: subscriber enrolls in a sold course → the enrollment carries a non-null `expires_at` (or a
  subscription source), **not** `source=free, expires_at=null`.
- Test: subscription lapses → access is gone.
- Test: org-seat holder, same two assertions; seat revocation removes access.
- Test: a user with a paid `source=purchase` row still enrolls successfully and their existing row
  is untouched.

### A5 — **BLOCKER.** A course can never return to the free path, and there is no admin escape hatch

`isCourseSold()` is status-blind, so **one** `product_courses` row is a one-way door. Two ordinary
admin actions walk through it:
- A bundle ("All Access") that includes a free intro course permanently un-frees that course.
- A draft product created by mistake, or an abandoned pricing experiment, does the same forever.

The documented escape hatch is deleting the product (`EntitlementPort.php:45-46`,
`PaidCourseEnrollmentGuardTest.php:85-97`). **That hatch does not exist in the UI**: `ProductResource`
has no `DeleteAction`, `ForceDeleteAction`, `RestoreAction` or `TrashedFilter`, and `ListProduct.php`
exposes only `CreateAction`. Recovery requires `tinker` or a DBA.

The reasoning in `EntitlementPort.php:45-46` — that a soft delete is "a deliberate, permanent
withdrawal" — is also wrong twice over: a soft delete is *reversible*, and restoring the product does
not claw back the lifetime free enrollments issued while it was trashed.

**Do** — pick one and say which:
- **Preferred:** an explicit course-level pricing signal (e.g. `Course::is_free` or a
  `pricing_model` enum) that the admin controls, so freeness is a stated intent rather than an
  inference from product rows. `isCourseSold()` then answers "is it sold" without also deciding
  "is it free".
- **Minimum:** keep the inference, but add `DeleteAction` + `RestoreAction` + `TrashedFilter` to
  `ProductResource`, and correct the docblock to describe soft-delete as reversible.

Either way, note the interaction with A4: whatever returns a course to the free path must not
retroactively legitimise free grants issued during the paid window.

### A6 — **BLOCKER.** The guard does not catch the empty lesson it was written for

`CourseReadinessService.php:375` — `trim(strip_tags($value)) !== ''`. HTML entities survive
`strip_tags`, and `trim()` strips only ASCII whitespace. So:

- `<p>&nbsp;</p>` → `"&nbsp;"` → **counts as substance**
- `<p>\u{00A0}</p>` (literal UTF-8 NBSP) → `trim()` does not strip `\xC2\xA0` → **counts**
- `&zwnj;`, `&#8203;`, `&emsp;` → same

`<p>&nbsp;</p>` is what TinyMCE, CKEditor and Quill emit for an empty paragraph. It is *the*
canonical empty lesson, and the guard passes it. Untested.

**Do**
1. Decode entities and normalise Unicode whitespace before the emptiness test — `html_entity_decode`
   with `ENT_QUOTES | ENT_HTML5`, then trim Unicode whitespace (`preg_replace('/^[\s\x{00A0}\x{200B}\x{200C}\x{FEFF}]+|…$/u')`
   or equivalent). Do this **before** `strip_tags`, and be careful not to let decoding turn
   `&lt;img&gt;` into a matching embed tag — decode for the *text* test, keep the raw string for the
   *embed* test.
2. Add `oembed` to `EMBED_ELEMENTS` (CKEditor 5's media output — currently a false negative that
   **blocks** publishing), plus `svg`, `canvas`, `picture`, `track`.

**Acceptance**
- Unit tests: `<p>&nbsp;</p>`, NBSP, `&zwnj;`, `&#8203;` → empty. `&lt;img&gt;` → empty (an escaped
  tag is text about a tag, not an embed). `<oembed url="…">` → substance.

### A7 — **BLOCKER.** One poison course stalls every scheduled publish behind it, forever

`PublishScheduledCoursesCommand.php:37-40` catches **only** `CoursePublishBlockedException`. Anything
else thrown inside `$lifecycle->transition()` — a `CourseTransitionException` from the state machine,
a listener failure on the `CoursePublished` event (dispatched *outside* the transaction,
`PublishCourseAction.php:35`), a `QueryException`, a deadlock — escapes the `each()` closure and
aborts the loop. Because the ordering is deterministic (`orderBy('scheduled_publish_at')`), the same
course poisons every subsequent tick. This is precisely the failure mode the class docblock
(`:19-22`) claims to have fixed, and no test creates a second course to notice.

**Do**
1. Catch `Throwable` per course; log at `error` for unexpected failures (keep `warning` for the
   readiness-blocked case, which is expected and self-healing) and continue the loop.
2. Return a non-success exit code when unexpected failures occurred, so a scheduler health check can
   see it. A readiness block alone should stay `SUCCESS`.
3. Use `chunkById` instead of `get()`.

**Acceptance**
- Test: two due courses, the first throws a non-`CoursePublishBlockedException` → the second still
  publishes, and the failure is logged at `error` with the course id.
- Test: two due courses, the first is readiness-blocked → the second still publishes, exit code is
  still success.

### A8 — Two divergent "is this course sold" queries; the guard fails open

`PurchaseSummaryAdapter.php:44-51` uses a raw pivot join. `EntitlementService.php:112-117` uses
`whereHas('courses', …)`, which applies every `Course` global scope — `SoftDeletes` **and**
`SharedOrOwnedTenantScope` (`Course.php:60-65,75`). So the guard's answer depends on the request's
resolved tenant and the catalog's does not. Where they disagree the guard returns `false` → **free
enrollment allowed on a paid course**. A security guard whose failure mode is "give it away" is the
wrong shape, and `EntitlementService.php:44-47` already carries a `TENANCY (T1, later)` TODO.

**Do** unify both call sites on one query (the status-blind pivot join, scope-free). Add a test that
pins the two to the same answer.

### A9 — Missing index will sequential-scan on PostgreSQL

`product_courses` has `primary(['product_id','course_id'])` and no standalone `course_id` index
(`2025_01_05_000110_create_product_courses_table.php:15`). The composite PK's leftmost column is
`product_id`, so the new `whereIn('product_courses.course_id', …)` cannot use it. Laravel's
`constrained()` creates no backing index on PostgreSQL — **which is what production runs.** Every
catalog page now sequential-scans the pivot.

**Do** add a reversible migration creating an index on `product_courses.course_id`.

### A10 — Resending an OTP does not invalidate the previous one

`OtpService::send()` (`:24-47`) does not consume or delete prior unconsumed codes; `verify()`
(`:53-60`) simply takes `orderByDesc('id')->first()`. Two consequences: a user who resends and then
types the *first* code gets `InvalidOtpException` and burns an attempt against the new code; and each
resend resets `attempts` to 0, refreshing the per-code guess ceiling at `:76-79`.

**Do** mark prior unconsumed codes consumed inside `send()`. **Acceptance:** test that the old code
stops working after a resend — your `ResendEmailOtpTest:73` proves the new code works but not that
the old one died.

---

## 3. Tests that give false confidence — these matter more than the count

Two of your tests **pass with the fix fully reverted**. A test that cannot fail is worse than no
test, because it is counted as coverage.

### A11.1 — `LessonContentSubstanceTest.php:107` "ignores a reference key that points at nothing"
```php
expect(substance(['media_id' => 0]))->toBeFalse()
    ->and(substance(['url' => '  ']))->toBeFalse()
    ->and(substance(['src' => null]))->toBeFalse();
```
All three pass under the old string-only recursion (`0` is not a string; `'  '` trims to `''`;
`null` is not a string). This is presented as the coverage for `isPresentReference()` and exercises
nothing that function does. **Rewrite** so at least one case's *negative* result depends on the new
code — e.g. assert that `['media_id' => 12]` is substance **and** `['media_id' => 0]` is not, in the
same test.

### A11.2 — `LessonContentSubstanceTest.php:101` "does not mistake a word starting with a tag name"
`<imgur></imgur>` strips to `''`, so the pre-fix code returns false too. It catches the narrow
mutation of deleting `\b`, but passes with the entire embed regex removed. **Pair it** with a
positive case in the same assertion so the test dies if the regex goes away.

### A11.3 — Lower-stakes, but fix while you are there
`PublishScheduledCoursesTest.php:108` (asserts `warning` never fires on a course that was never
blocked) and `CourseReadinessTest.php:429` (asserts only `isPublishable() === false`, never *which*
blocker fired — so an unrelated regression makes it green for the wrong reason). Assert the blocker
code, as `:447` correctly does.

### A11.4 — `FreeEnrollmentApiTest` asserts the status code but not the absence of a row
`:40-68` checks for 402 but never asserts that **no `Enrollment` row was written**. A code path that
creates the enrollment and then returns 402 would keep every one of those tests green while the
revenue leak persists. Add `assertDatabaseMissing` to each rejection case.

### A11.5 — Coverage gaps to close
| Gap | File |
|---|---|
| `<p>&nbsp;</p>` / NBSP / entity-only bodies (A6) | `LessonContentSubstanceTest` |
| Presentation keys **not** on the `METADATA_KEYS` denylist — `color`, `size`, `icon`, `placeholder`, `dir`, `label` — currently make an empty block publish | `LessonContentSubstanceTest` |
| Non-string values under non-listed keys: `['media' => ['id' => 7]]`, `['attachment_ids' => [3,4]]`, `['media_id' => [12,13]]` — all read as EMPTY and would block a real lesson | `LessonContentSubstanceTest` |
| `$lesson->media !== null` branch (`:263`) — a Video lesson with a `LessonMedia` row and `content = null` — has **no** readiness test at all | `CourseReadinessTest` |
| Archived block (`PublishState::Archived`); only Draft is tested | `CourseReadinessTest` |
| `content_i18n` path (`:270`) never exercised | `CourseReadinessTest` |
| `safeRedirect()` has **no test at all** — for a CWE-601 guard, add the case table: `//evil.com`, `https://evil.com`, `/\evil.com`, `/javascript:alert(1)`, `/%09/evil.com`, and a valid `/foo/bar:baz` | `apps/web/tests` |
| Two due courses, one poisoned (A7) | `PublishScheduledCoursesTest` |
| Anonymous caller + double-enrolment idempotency | `FreeEnrollmentApiTest` |

**Note on the denylist:** `METADATA_KEYS` is a denylist and `REFERENCE_KEYS` is an allowlist. Both
leak. Consider inverting the metadata check to an allowlist of *content-bearing* keys, or at minimum
document the denylist as needing review whenever the editor changes. `public_id`, `path` and `href`
on the reference allowlist are also over-broad — any API-shaped payload echoing its own id reads as
substantial.

---

## 4. Pre-existing failures — fix the six (decision (f))

- **Four publish tests** (`PublishValidationTest`, `CourseLifecycleTest`,
  `CourseLifecycleSchedulingTest`, `InstructorPortalTest`): give the fixture lessons real content.
  Your diagnosis is right — those lessons genuinely have none, and A3 correctly does not rescue them.
- **Two `ProductionConfigValidatorTest` cases:** teach `safeProductionConfig()` to set a safe
  playback provider.
- **Three branding tests:** covered by A0.1 above — rewrite to assert behaviour, not brand literals.

**All nine must pass.** `php artisan test` green is the deliverable.

---

## 5. Smaller items

- **`isEmailVerificationRequired()` is used in exactly one component** (`course-purchase-panel.tsx`).
  The middleware gates the *entire* API group, so every other authenticated surface still shows a
  generic toast with no route to `/verify-email`. Move the handling into the shared fetch wrapper or
  a query-error boundary.
- **Resend cooldown fires on every failure**, including transient network errors
  (`verify-email/page.tsx:88-94`), locking the button for 60s after a dropped connection. Branch on
  429 / `AUTH_OTP_RATE_LIMITED`.
- **Related-course cards render a blank price slot** — `PublicCourseDetailsService.php:29` sets the
  related relation but never attaches purchase summaries, and the fail-closed default then renders
  "not available". Attach summaries to related courses.
- **`errors.ts:89`** tests `code === "HTTP_FORBIDDEN"`, which nothing in the web app ever produces
  (`client.ts:72` falls back to `"HTTP_ERROR"`). Dead branch; pre-existing.
- **`ResendEmailOtpTest:155`** hard-codes 3 successful bursts without setting the burst config, so it
  drifts silently if the limit changes. Set the config in the test.
- **Parallel test runs:** your §2.2 diagnosis is accepted. Provision worker databases to match
  `--processes`, or pin `--processes` to the six that exist, and record the decision in `docs/ops`.
- **§3.1 setup findings:** add the `--ignore-platform-req=ext-pcntl --ignore-platform-req=ext-posix`
  note (or a container-based test target) to `docs/ops`, and note the compose project-name collision.
- **§3.5 admin MFA edge case:** leave as reported. No action.
- **§3.7:** you are right on both counts. Path matching is correct as implemented — `$request->is()`
  is `Str::is`, which is an exact match for a pattern with no wildcard. Add a comment saying so, so
  the next reader does not "fix" it into a prefix match.

---

## 6. Order of work

1. **A0** — the three defects in the pre-existing white-label work (env, deptrac, PHPStan) + line
   endings. These are CI-blocking and cheap.
2. **A4, A5, A6, A7** — the four blockers.
3. **A8, A9, A10** — correctness and performance.
4. **A11** — the false-confidence tests and the coverage gaps.
5. **§4** — all nine pre-existing failures green.
6. **§5** — the smaller items.
7. `commerce:report-free-grants` (decision (a)).

Then re-run the full §7 verification and write `CLAUDE_CODE_HANDOFF_ROUND2_RESULT.md` in the same
format as before. Phase B stays closed until this is green.

**A5 needs your recommendation back before you implement it** — say which option you are taking and
why, then proceed. Everything else is decided.

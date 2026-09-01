# HElbaron LMS — Phase A Result

**Branch:** `pr-1`
**Scope executed:** PHASE A only (A1, A2, A3). Phases B–E not started, as instructed.
**Date:** 2026-08-30.

This is the report required by §8 of `CLAUDE_CODE_HANDOFF.md`, written at the Phase A checkpoint.

---

## 1. What I completed

### A1 — Free enrollment gives away paid courses (revenue loss) — **DONE**

The root cause was that "free" was inferred from the *absence of an active product*, so the three
real states collapsed into two. A course whose product existed but sat in Draft or Archived reported
`purchasable: false` — identical to a genuinely free course — and was advertised as Free and granted
as a **lifetime** enrollment.

**Backend**
| File | Change |
|---|---|
| `apps/api/app/Platform/Shared/Commerce/Data/PurchaseSummary.php` | Added an explicit `free` flag. `notPurchasable()` replaced by two intention-revealing factories, `free()` and `notAvailable()`. `toArray()` now always emits `free`, so a client can branch on one key. |
| `apps/api/app/Contexts/Commerce/Adapters/PurchaseSummaryAdapter.php` | Resolves freeness from a status-blind join over `product_courses`, not from the active-product query. |
| `apps/api/app/Platform/Shared/Commerce/Contracts/EntitlementPort.php` | New `isCourseSold(int $courseId): bool` — any product row, any status. |
| `apps/api/app/Contexts/Commerce/Adapters/EntitlementAdapter.php`, `.../Services/EntitlementService.php` | Implement `isCourseSold()`. `isCoursePurchasable()` kept: it answers a genuinely different question ("on sale right now") and is still the right input for pricing. |
| `apps/api/app/Contexts/Learning/Actions/Enrollment/EnrollInCourseAction.php` | Guard switched to `isCourseSold()`. **The entitlement bypass is preserved** — see §4. |

**Frontend** — `lib/catalog/api.ts` (three-state union type), `course-card.tsx`, and
`course-purchase-panel.tsx` now branch on `purchase.free === true`. The in-between state renders a
disabled "Not available yet". An absent purchase summary is treated as **not free** — it fails
closed. New i18n keys `catalog.course.notAvailable` / `notAvailableHint` in both locales.

**Acceptance criteria — all met**

| Criterion | Test | Result |
|---|---|---|
| active product → rejected | `PaidCourseEnrollmentGuardTest` + `FreeEnrollmentApiTest` | pass |
| draft product → **rejected** (inverts prior assertion) | `PaidCourseEnrollmentGuardTest` | pass |
| archived product → rejected | both | pass |
| no product → succeeds | both | pass |
| card + detail panel show "not available" for a draft-product course | `tests/catalog/courses.test.tsx`, `course-sales.test.tsx` | pass |

The inverted assertion in `PaidCourseEnrollmentGuardTest` carries a docblock explaining the reversal,
as required. New file `apps/api/tests/Feature/Learning/FreeEnrollmentApiTest.php` closes the gap the
brief flagged as "**No API-level test at all**" — 9 tests covering both the enrollment verdict and the
`free` flag the course/listing endpoints publish, asserted together because the button the learner
sees is rendered from one and enforced by the other.

### A2 — No OTP resend behind a hard verification gate (mass lockout) — **DONE**

| File | Change |
|---|---|
| `apps/api/app/Platform/Identity/routes/auth.php` | `POST /api/v1/auth/resend-email-otp`, `auth:sanctum`, `throttle:identity-otp-resend`. |
| `.../Providers/IdentityServiceProvider.php` | New `identity-otp-resend` limiter, 3/min keyed on the user. Burst guard only — the real budget stays the persisted `identity.otp.email.max_per_hour`. |
| `.../Api/V1/AuthController.php` | `resendEmailOtp()` reusing the **existing** `OtpService`, not a second issuer. Generic response whether or not a code was sent. |
| `apps/api/app/Http/Middleware/RequireVerifiedEmail.php` | Resend path added to the verification surface. |
| `apps/web/.../verify-email/page.tsx` | "Resend code" control with a 60s cooldown; honours `?redirect=` through the existing `safeRedirect()` open-redirect guard. |
| `apps/web/src/lib/api/errors.ts` | New `isEmailVerificationRequired()`; documented why `EMAIL_VERIFICATION_REQUIRED` is deliberately *not* folded into `isAuthorizationError()`. |
| `apps/web/src/components/catalog/course-purchase-panel.tsx` | Routes that refusal to `/verify-email` instead of a generic toast. |

**Acceptance criteria — all met.** `ResendEmailOtpTest` (9 tests) covers reachability through the
gate, a genuinely new working code, rescue of an already-expired code, the hourly budget (asserted
against persisted OTP rows so the burst limiter cannot mask it), the burst limiter separately, the
non-enumerating response for a verified account, and anonymous rejection. `EmailVerificationTest`
gained coverage of checkout, enrollment, AI (tutor + copilot) and developer-key issuance, plus proof
that verify-email/logout/profile/resend stay reachable while unverified. Frontend: 9 tests including
redirect survival and two open-redirect rejections.

**The "also verify" items — answered, not assumed:**

- **SAML is a false alarm; no fix was needed.** `SamlController` returns `501 SSO_SAML_UNSUPPORTED`
  from *both* `metadata` and `acs`, and explicitly never parses a posted assertion (no XML-DSIG
  support). It provisions no users at all, so there is no "every SSO user is locked out on deploy"
  risk. The OIDC/social path does set `email_verified_at` correctly
  (`AuthenticateWithSocialIdentityAction.php:108`).
- **`verify-phone` being gated is harmless.** `phone_verified_at` is never *required* anywhere — it
  is only surfaced on the profile resource. No onboarding flow depends on it.
- **One narrow MFA edge case remains** (reported, not fixed — see §3).

### A3 — Empty-lesson guard blocks legitimate courses — **DONE**

`apps/api/app/Domains/Authoring/Services/CourseReadinessService.php`:

1. `hasMeaningfulContent()` now recognises readable text, **embedded media elements** (`iframe`,
   `img`, `video`, `audio`, `embed`, `object`, `source`), and **structured references**
   (`media_id`, `asset_id`, `url`, `src`, …). A word-boundary anchor keeps `<img>` matching while
   `<imgur>` does not.
2. Presentation-only keys (`type`, `variant`, `align`, `width`, …) are skipped, so a stub payload of
   `{"type": "text"}` is correctly **empty** — the false negative the brief identified.
3. The `explanation` and `recommendedAction` were rewritten to enumerate every kind of substance that
   satisfies the rule. The old wording ("It has neither content nor media") was already wrong and
   would have flatly contradicted an author looking at their embedded video.
4. **The scheduled-publish loop no longer fails silently.** `CoursePublishGuard` gained
   `blockerCodes()`; `CoursePublishBlockedException` carries them; `PublishScheduledCoursesCommand`
   logs the course `public_id`, the blocker codes and the reason at **warning** level, and names the
   course on the console.

**The policy contradiction is resolved explicitly, in code.** I documented the exemption rather than
building a grandfathering mechanism, with the reasoning recorded in the `checkLessonContent()`
docblock: the visibility rule protects a legitimate shipping mode whereas an empty lesson is not one;
widening `hasMeaningfulContent()` can only ever *un*-block courses this check previously blocked; and
the issue is now self-service. It also records that the cheaper future lever is downgrading this one
issue to a Warning, not per-course exemptions. **This is a decision I made — see §4(b).**

**Acceptance criteria — all met.** `tests/Unit/Authoring/LessonContentSubstanceTest.php` (22 cases)
covers the full matrix: text ✓, iframe ✓, img ✓, video/audio/embed/object/source ✓, structured
reference ✓, nested ✓, genuinely empty ✗, `{"type":"text"}` ✗, `<imgur>` ✗, zero/blank references ✗.
`CourseReadinessTest` gained 7 feature tests (embed-only publishes, image-only publishes, structured
reference publishes, published block counts, draft block does *not*, metadata-only does *not*,
explanation wording). `tests/Feature/Catalog/PublishScheduledCoursesTest.php` (5 tests) covers the
logging.

### Also done
- `CHANGELOG.md` updated under `[Unreleased]`, with the behaviour changes in **Changed** and only
  genuine defects in **Fixed**, per Rule 8. I also corrected a now-contradictory pre-existing line
  that described the old free-enrollment behaviour.

---

## 2. Verification output

Run against a real PostgreSQL 16 (the project's own `helbaron-postgres` container) and Redis.

| Gate | Result |
|---|---|
| `php artisan test` (full, run **serially** in chunks) | **2005 passed, 9 failed** (2014 tests). **All 9 are pre-existing — proven, see §2.1.** |
| A1 backend targeted | **18 passed** (39 assertions) |
| A2 backend targeted | **15 passed** (53 assertions) |
| A3 unit | **22 passed** (29 assertions) |
| A3 feature (`CourseReadinessTest`) | **33 passed** (79 assertions) — includes all pre-existing cases |
| A3 feature (scheduled publish) | **5 passed** (16 assertions) |
| `vendor/bin/phpstan analyse` | **318 file errors / 164 files — 0 in any file I touched** (pre-existing; §3.3) |
| `vendor/bin/deptrac analyse` | **5 violations, all in one file I did not write** (§3.2). **0 from my changes — proven.** |
| `vendor/bin/pint --test` | **Fails on 1315 files; 1310 are `line_ending`** — environmental (§3.4) |
| `npm run lint` | **clean** |
| `npm run typecheck` | **clean** |
| `npm run test` | **160 files, 840 tests, all passed** |
| `npm run build` | **succeeds** — 100+ routes compiled, `/verify-email` builds correctly with its new Suspense boundary |

Per-chunk backend detail: Unit **263**; Feature Admin→Branding **427 / 4 failed**; Catalog→Entitlements
**562 / 5 failed**; Features→Live **366**; Marketing→Timezone **387**.

### 2.1 The 9 failures are all pre-existing — measured, not assumed

I established this by **stashing only my 17 application files** (tests left in place), re-running the
same eight affected test files, and diffing:

- **Without my changes: 9 failures.**
- **With my changes: 10 failures.**

The single extra one was `CommercialPolicyTest > it does not treat a course as purchasable when only
a draft product grants it`, which asserted the DTO's exact array shape `['purchasable' => false]` —
now `['purchasable' => false, 'free' => false]`. **I updated and strengthened it** rather than
loosening it: that test's scenario (a draft product) is precisely the bug's case, so it now asserts
`free === false` explicitly, and I added its counterpart asserting `free === true` for a course no
product sells. Both pass. **Net new failures from Phase A: zero.**

The 9 pre-existing failures, with root causes:

| # | Test | Root cause |
|---|---|---|
| 1–4 | `PublishValidationTest`, `CourseLifecycleTest`, `CourseLifecycleSchedulingTest`, `InstructorPortalTest` (publish cases) | The branch promoted `lesson.empty_content` to **Blocker**. These fixtures build lessons with `Lesson::factory()->published()`, whose default `content` is `[]` — genuinely empty — so publishing is refused. My A3 widening correctly does *not* rescue them: those lessons really do have no content. **The fix is to give the four fixtures real content**, which restores each test's original intent. |
| 5–6 | `ProductionConfigValidatorTest` (2 safe-config cases) | The branch's §3.2 `LEARNING_PLAYBACK_PROVIDER=fake` critical check. The test's `safeProductionConfig()` helper was never taught to set a safe playback provider. One-line helper fix. |
| 7 | `BrandingTest > toPublicArray merges stored values over the built-in defaults` | Asserts the hardcoded Arabic vendor literal `'إلبارون'`, which the branch's now env-driven `BrandSetting::defaults()` no longer produces. **This assertion is itself a hardcoded brand literal — removing it is Phase B (B3) work.** See §3.6 for the real defect this uncovered. |
| 8–9 | `PerOrgBrandingTest` (2 cases) | Same env-driven branding defaults change. |

### 2.2 `--parallel` is not trustworthy on this setup — use serial

Two consecutive parallel runs of the same code gave **14** and then **49** failures, the extra ones
being `QueryException` / `PDOException` cascades across Commerce, Tax, Refunds and Subscriptions.
Run serially, those same directories are **100% green**. Two distinct artifacts are involved:

- `--processes=8` against only six `helbaron_test_test_N` worker databases.
- Long serial batches can also lose the connection: one 10-directory chunk reported 55 failures
  starting with a single `QueryException` and cascading into `PDOException`; `tests/Feature/Learning`
  alone then passed **62/62**. Chunked serial runs are stable and reproducible.

**Recommendation:** treat the serial chunked run as authoritative until the parallel worker databases
are provisioned to match `--processes`.

---

## 3. Things I found that are not in the brief

These are reported rather than folded in, per Rule 7. **The first three block the merge that Phase A
exists to unblock**, so they need a decision.

### 3.1 The verification commands in §7 cannot run on this machine as written — **fixed the setup, documented the cause**
The brief attributes the never-green suite to "a temporary PostgreSQL instance rejected its
credentials". That is not what is happening.

- `apps/api/vendor/`, `apps/web/node_modules/` and `apps/api/.env` were all **absent**.
- Plain `composer install` **exits without writing `vendor/` at all**: `laravel/horizon` requires
  `ext-pcntl` and `ext-posix`, which do not exist on Windows PHP at any version. It needs
  `--ignore-platform-req=ext-pcntl --ignore-platform-req=ext-posix` (safe — both are used only by
  the Horizon worker daemon, not by the test suite), or the suite must run inside the `api` container.
- The database was **fine all along**. The `helbaron-postgres` container already had `helbaron_test`
  plus six parallel-worker databases. It was simply **stopped**, and it is registered under an old
  compose project name (`corelms`), so `docker compose up` in this repo collides on container name
  instead of reusing it. Starting it took one command.

**Recommendation:** add the platform-req flags (or a container-based test target) to `docs/ops` and
CI notes, and re-create the compose stack under this repo's project name.

### 3.2 Deptrac fails — entirely from `CreateAdminUser.php`, which is this branch's own new file
`vendor/bin/deptrac analyse` exits **1** with 5 `DependsOnDisallowedLayer` violations, all in
`apps/api/app/Console/Commands/CreateAdminUser.php` (still untracked; added by the §3.1 work the
brief lists as already complete). It imports `Platform\Identity\Enums\Role` and
`Platform\Identity\Models\User`, but `app/Console/**` is collected into the `Platform` layer, which
may depend only on `Shared` + `IdentityContracts`.

**Proven pre-existing and not mine:** with that one file temporarily moved aside, Deptrac reports
**0 violations**; restored, **5**. My changes contribute none.

**Recommended fix (needs your nod — it touches `deptrac.yaml`, and Rule 1 forbids a baseline entry):**
move the command to `app/Platform/Identity/Console/Commands/` and add
`app/Platform/Identity/Console/.*` to the `Identity` layer collectors. An Identity console command
legitimately belongs to Identity, which may use its own models — no new Port required. I did not do
this unasked because editing the architecture-governance file is your call.

Note CI runs `deptrac analyse` **without** `--fail-on-uncovered`; the brief's §7 command is stricter
than CI. Either way, the 5 violations fail both.

### 3.3 PHPStan: this branch's earlier work introduced new errors
`phpstan analyse` reports **318 file errors across 164 files**. **None are in any file I touched** —
verified against the JSON report.

But two of the worst offenders are files *this branch modified*, and I measured them directly:
`BrandSetting.php` + `IdentitySeeder.php` produce **25 errors in the working tree and 0 at `HEAD`**.
So at least 25 of the 318 were introduced by the uncommitted §3.3 white-label work, not inherited
from `main`. Given PHPStan is level 6 with a baseline and is **blocking in CI**, this needs cleaning
before merge. I did not fix it because it is outside Phase A and belongs to whoever wrote it.

### 3.4 Pint cannot pass in this working copy — line endings
`vendor/bin/pint --test` wants to change **1315 files**, and **1310 of those are `line_ending`**.
`.gitattributes` mandates `* text=auto eol=lf` and `*.php text eol=lf`, but this working copy was
checked out with CRLF: `app/Console/Commands/SeedDemo.php` — untouched by this branch and by me — is
**CRLF on disk and LF in `HEAD`**. This is a local `core.autocrlf` misconfiguration, not a code
problem, and git normalises on commit so it never reaches the repository.

I deliberately did **not** run `pint` to "fix" it: reformatting 1310 files would bury the Phase A
diff and destroy reviewability. **Fix locally with** `git config core.autocrlf false` followed by
`git add --renormalize .` (or a fresh clone), then re-run Pint.

### 3.5 A narrow admin MFA lockout is theoretically reachable
`EnforceAdminMfa` is on the Filament panel, but `POST /api/v1/auth/mfa/enable` is on the `api` group
and therefore behind `RequireVerifiedEmail`. An admin who is unverified **and** required to have MFA
cannot enable it. In practice both `identity:create-admin` and `IdentitySeeder` mark admins verified,
and admins can now resend their own OTP (A2), so it is recoverable rather than fatal. Flagging it
rather than widening Phase A.

### 3.6 **The white-label defaults are broken by empty keys in `.env.example` — every instance is affected**

This is the most consequential thing I found, and it undermines the §3.3 work the brief lists as
complete. `BrandSetting::envName()` is written to degrade gracefully:

```php
$fallback = (string) env('BRAND_NAME_EN', (string) config('app.name', 'Academy'));
return $locale === 'ar' ? (string) env('BRAND_NAME_AR', $fallback) : $fallback;
```

But `.env.example` ships these keys **present and empty**:

```
BRAND_NAME_AR=
BRAND_COMPANY_NAME=
BRAND_SUPPORT_EMAIL=
BRAND_SUPPORT_PHONE=
BRAND_ADDRESS_EN=
BRAND_ADDRESS_AR=
BRAND_EMAIL_FOOTER_EN=   (and _AR, SIGNATURE_EN, SIGNATURE_AR)
```

Laravel's `env()` returns **`''` for `KEY=`, not `null`**, so the second argument is never used and
the fallback chain never fires. Any instance created by copying `.env.example` — which is what
`docs/ops/DEPLOYMENT_CHECKLIST.md` instructs — gets an **empty Arabic brand name, empty company
name, empty support email, empty address and empty email footers/signatures**.

I verified this directly: `BrandingTest` fails with `''` where a brand name is expected. So the
brief's claim that "a fresh instance is branded before an admin opens the branding screen" does not
hold. The empty company name also flows into `CertificateSetting::current()`'s issuer resolution.

This is **the same bug class the brief already identifies in D1** ("`??` does not fire when the `en`
key exists but is empty — use `filled()` semantics"), in a different file.

**Fix:** use empty-coalescing rather than null-coalescing — `env('BRAND_NAME_AR') ?: $fallback` — and
either drop the empty keys from `.env.example` or comment them out. I did not apply it: it is Phase B
(white-label) work and touches files outside Phase A.

### 3.7 The brief has two small inaccuracies
- `RequireVerifiedEmail` lives at `app/Http/Middleware/`, not `app/Platform/Identity/...`.
- It matches on **request path**, not route name — the routes in `auth.php` are unnamed — so A2's
  "add its route name to `isVerificationSurface()`" was implemented as a path match.

---

## 4. Decisions you should confirm

**(a) A1.4 — existing free grants were NOT audited or revoked.** Per the brief I did not write a data
migration without asking. The fix stops future wrongly-free enrollments; it does nothing about
lifetime grants already issued during a draft-product window. **My recommendation is a read-only
report command** that lists free-source enrollments on courses that have a product, so you can see
the real exposure before deciding. Revoking access on data neither of us has seen is not something I
would do unprompted. *Currently: nothing was changed.*

**(b) A3 severity policy — I documented the exemption instead of grandfathering.** Reasoning is in §1
and recorded in the code. If you would rather grandfather already-published courses, say so and I
will replace the docblock with a mechanism — but I think a per-course exemption table would outlive
its reason and become maintenance debt.

**(c) A1 — `free` means "no product row of any status", so archiving a product permanently removes
the free path** for the courses it sells. That is what the brief asked for and it fails closed, but
it is a real behavioural consequence worth naming. **Deleting** a product (soft delete) *does* return
the course to the free path, which I treated as the deliberate "permanently withdrawn from sale"
signal, distinct from the transient Draft state. Both are covered by test.

**(d) I widened A1 step 3 slightly.** Taken literally — "reject … on a course that has any product
row, regardless of status" — it would have removed the existing `hasCourseEntitlement()` bypass and
broken self-service enrollment for people who had already **paid**. I kept the bypass. There is a
pre-existing passing test for it, updated to the new port method.

---

## 4b. The pre-existing failures — do you want them fixed?

Six of the nine have small, unambiguous fixes that restore each test's original intent, and I can do
them quickly on your word:

- **The four publish tests** — give the fixture lessons real content. Directly adjacent to A3.
- **The two `ProductionConfigValidatorTest` cases** — one line in `safeProductionConfig()`.

The remaining three (`BrandingTest`, `PerOrgBrandingTest`) assert hardcoded vendor brand literals and
belong to **Phase B / B3**, so I would not touch them at Phase A.

I did not fix any of them unasked: your instruction was to *report* pre-existing failures, and Rule 7
says not to widen scope silently. None were deleted or skipped.

## 5. What I did not do

- **Phases B, C, D and E — not started**, as instructed.
- **No `deptrac.baseline.yaml` entries added** (Rule 1). My changes needed none.
- **No test deleted or skipped** (your rule). Every pre-existing test in the files I touched still
  runs; the one assertion I inverted (`PaidCourseEnrollmentGuardTest`, draft product) was inverted
  deliberately because the brief requires it, and the reversal is explained in its docblock.
- **No secret printed, logged or committed** (Rule 4). `.env` was created from `.env.example` and is
  gitignored.
- **No migrations** were needed for Phase A.

---

## 6. Suggested next step

Phase A is functionally complete and its acceptance criteria pass. Before Phase B I would clear the
three merge blockers in §3.2–3.4, because two of them are CI-blocking and none of them are Phase B
work: the Deptrac fix (one file move + one collector line, needs your approval), the 25 PHPStan
errors in the branch's white-label work, and the local line-ending misconfiguration.

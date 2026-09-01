# Changelog

All notable changes to HELBARON LMS are documented here. This project follows
semantic versioning; pre-release builds use `-rc.N` suffixes.

## [Unreleased] — 2026-08-24

### Added
- `POST /api/v1/auth/resend-email-otp` reissues the email verification code for an authenticated
  account. Reachable while unverified (the verification gate allows it explicitly), throttled per
  minute and bounded by the existing `identity.otp.email.max_per_hour` budget, and answering
  generically so it never confirms whether an address is still pending. Closes a total lockout: the
  code expires after ten minutes and nothing anywhere could reissue one.
- The verify-email screen now offers a "Resend code" control with a cooldown.
- `CoursePublishGuard::blockerCodes()` exposes the stable codes behind a refused publish, so the
  scheduled-publish command can report which rules blocked a course rather than only counting it.
- `courses.is_free` — an admin-controlled "Free course" flag on the course form. Freeness is now a
  stated intent rather than an inference from product rows. Reversible migration; existing courses
  are backfilled to `is_free = true` wherever no product grants them, so behaviour is unchanged at
  the moment it runs.
- Readiness **warning** `course.not_acquirable` when a published course is neither free nor sold by
  an active product, so an author cannot silently ship a course nobody can enroll in.
- `php artisan commerce:report-free-grants` — a **read-only** audit listing payment-free enrollments
  on courses a product sells, flagging each with the holder's current entitlement and whether it has
  lapsed. Revokes nothing; `--csv` exports the full set.
- `EnrollmentSource::Subscription`, so subscription-derived access can be recorded and revoked as
  what it is.
- `EntitlementPort::courseEntitlement()` returns an entitlement's kind and window, not just a boolean.
- `config/branding.php` resolves the `BRAND_*` keys, and `Platform\Shared\Support\Env` provides
  environment reads whose defaults survive a present-but-empty key.

### Changed
- ~~**A course is now advertised and granted as free only when no product of any status sells it.**~~
  *(Superseded within this release by the declared-freeness entry below — the no-product-row rule was
  itself a one-way door.)*
  Freeness was previously inferred from the absence of an *active* product, so a course whose
  product was Draft or Archived was shown as "Free" and granted a lifetime enrollment through the
  payment-free endpoint — an admin moving a live product to Draft to edit its pricing opened a free
  front door on every course that product sold. The purchase summary now carries an explicit `free`
  flag, `EnrollInCourseAction` refuses any course a product row sells regardless of status (buyers
  holding an entitlement are unaffected), and the UI renders "Not available yet" for the in-between
  state. Deleting a product still returns the course to the free path.
  *Operator note:* enrollments already granted during a draft-product window are not revoked by this
  change; auditing them is a separate decision.
- The `lesson.empty_content` publish blocker now recognises embedded media (`iframe`, `img`,
  `video`, `audio`, `embed`, `object`, `source`) and structured media references as lesson content,
  and no longer counts presentation-only payloads such as `{"type": "text"}`. Previously
  `strip_tags()` reduced an embed-only lesson to the empty string, so the most common authoring
  shape in the product could not be published.

- **Freeness is now declared, not inferred.** A course is free when its author says so and no active
  product sells it. Previously the absence of a product row decided it, which made a single
  `product_courses` row a one-way door: an "All Access" bundle that included a free intro course, or
  a draft product created by mistake, un-freed that course permanently — and the documented escape
  hatch (deleting the product) does not exist in the admin panel.
- **An entitlement is no longer re-recorded as a perpetual free grant.** Enrolling with an existing
  entitlement now writes that entitlement's own source and window. A subscriber or seated employee
  previously received `source = free, expires_at = NULL` — permanent access to the whole bundled
  catalogue that no lapse, refund or seat revocation could reclaim, because access is decided by the
  enrollment row alone.
- Releasing an organization seat now withdraws the enrollments that seat produced, instead of leaving
  access in place until the billing period elapsed.
- Issuing an email OTP now retires any earlier unconsumed code, so exactly one code is live at a time.
  Previously a resent code left the old one working, and each resend reset the per-code guess ceiling.
- The empty-lesson publish blocker now decodes HTML entities and Unicode whitespace before deciding a
  lesson is empty, so `<p>&nbsp;</p>` — what every rich-text editor emits for an empty paragraph — is
  correctly empty. `oembed`, `svg`, `canvas`, `picture` and `track` count as embedded media.
- `courses:publish-scheduled` now catches any failure per course rather than only readiness blocks,
  so one failing course no longer aborts the run and stalls every course scheduled behind it. It exits
  non-zero when something unexpected failed; a readiness block alone still exits zero.

### Fixed
- **`BRAND_*` and `SEED_ADMIN_*` environment keys were ignored when present but empty**, because
  `env('KEY', $default)` returns the default only for an ABSENT key and `.env.example` shipped them
  as `KEY=`. A fresh instance received a blank Arabic brand name, a blank company name (which also
  feeds the certificate issuer) and blank email footers; the local development admin was created with
  an empty password nobody could log in with. The optional keys are now commented out rather than
  shipped empty.
- **`BRAND_*` keys were ignored entirely in production.** `scripts/deploy.sh` runs `config:cache`, and
  Laravel does not read `.env` once the config is cached — so `env()` outside a config file returns
  null. Branding defaults now resolve in `config/branding.php`, which is evaluated while the cache is
  built, so an operator's values survive the deploy.
- The email-verification refusal (`403 EMAIL_VERIFICATION_REQUIRED`) is now readable by the client
  and routes the learner to `/verify-email` instead of surfacing a generic error. Handled centrally
  in the shared query client, so every authenticated surface behaves the same way rather than only
  the course purchase panel.
- The "Resend code" cooldown now starts only on a real rate limit, not on any failure — a dropped
  connection no longer locks the control for a minute.
- Related-course cards carry their own purchase summary. Without one they fell through to the
  fail-closed default and every cross-sell card read "Not available yet".
- The verify-email screen honours its `?redirect=` parameter (validated as a same-origin relative
  path) instead of always sending the learner to `/` or `/dashboard`, so the destination that
  triggered verification survives it.
- `courses:publish-scheduled` logs the course public id, the blocker codes and the reason at warning
  level when a scheduled publish is refused. It previously swallowed the exception and reported only
  a count, so a course could miss its launch date silently and be retried every minute forever.
- Product creation now generates and validates a unique slug from the translated English title,
  preventing the admin `products.slug` null-constraint failure.
- Unverified accounts are restricted to reading their profile, verifying their email, or logging
  out; protected web journeys redirect to verification before any business action.
- AI is now production opt-in, fake AI is reported as blocked rather than enabled, and provider
  resolution is deferred so Laravel route inspection and maintenance commands work while AI is off.
- Enrollment administration displays the learner name and localized course title instead of a
  blank identity and raw course UUID.
- Course publishing is blocked when a published non-quiz lesson has no meaningful legacy content,
  published content block, or media.
- Public course details resolve by stable slug as well as UUID; catalog cards now link to slugs.
- Courses that no product sells expose the supported free-enrollment journey instead of an
  unavailable purchase button. (Originally written as "without an active product"; superseded by
  the Changed entry above — an inactive product is no longer treated as no product.)
- Added a cacheable branded `/favicon.ico` response and corrected the production environment
  template's media, notification, and AI safety settings.

### API
- Extended `GET /api/v1/courses/{identifier}` to accept either a course UUID or slug; added the
  corresponding Bruno request under `bruno/Catalog`.

## [1.0.0-rc.2] — 2026-08-12

Hardening and production-closure candidate. Carries the full Stage-4 enterprise / AI /
growth / integrations feature set (enterprise manager portal, RAG semantic search &
recommendations, AI tutor & instructor copilot, admin analytics assistant, public
developer API, SSO operations, marketing automation, per-org white-label branding &
custom domains, org BI export/import — see `STAGE_4_REPORT.md`) plus the security,
reliability and correctness fixes below. **Code-only advance: no new database
migrations over the Stage-4 schema** (Stage-4 migrations are catalogued in
`docs/releases/DEPLOYMENT_MANIFEST_v1.0.0-rc.1.md` and the module `Database/Migrations`
directories).

### Security (confirmed defects — fixed)
- **Scoped developer API keys were valid on the entire first-party API.** Sanctum's
  `auth:sanctum` guard authenticates any valid personal-access-token regardless of its
  abilities; per-ability enforcement existed only on `/api/v1/developer/*`. A read-only
  developer key (e.g. `account:read`) was therefore a valid bearer on every other
  `auth:sanctum` route, silently exercising the owner's full permissions. New
  `EnforceApiTokenScope` middleware confines any token lacking the full-access `*`
  ability to the developer surface (and key-management), returning `403
  TOKEN_SCOPE_FORBIDDEN` elsewhere. First-party login tokens (`*`) and SPA-cookie
  sessions are unaffected. Guarded in `DeveloperScopeEnforcementTest`.
- **Webhook SSRF: alternate IP encodings and redirect bypass.** The outbound-webhook
  URL guard validated only the literal host of the registered URL. Decimal/hex/octal
  IPv4 encodings (`2852039166`, `0x7f.0.0.1`, `0177.0.0.1`) and IPv4-mapped IPv6
  literals (`::ffff:169.254.169.254`) slipped past the private-range check, and a
  delivery could follow a 3xx redirect to an internal address. The guard now rejects
  numeric-host literals and decodes IPv4-mapped/compatible IPv6 before the private-range
  test; delivery uses `withoutRedirecting()`. Guarded in `WebhookUrlGuardTest`.
- **`POST /auth/mfa/disable` was unthrottled.** Added the `identity-otp-verify` rate
  limiter, matching MFA enable/verify.

### Reliability & correctness (confirmed defects — fixed)
- **Coupon expiry / deactivation was not re-checked at checkout.** A cart with a
  persisted coupon that had since been deactivated or moved out of its validity window
  would still discount at checkout (a revenue leak). Checkout now re-validates
  `is_active`, the validity window and exhaustion under the coupon row lock
  (`CouponInvalidException` / `CouponExpiredException` / `CouponExhaustedException`).
- **Drip campaigns could double-send a step across a crash.** The runner recorded a
  send only after the provider call, so a crash between send and record re-sent the step
  on the next tick. It now writes an in-flight `Sending` claim before the provider call;
  the crash-safety guard treats a leftover claim as already handled.
- **Semantic-search index retained orphaned embeddings.** `rebuildAll` reindexed
  eligible content but never purged embeddings for content that had become
  unpublished/deleted. It now purges rows whose source is no longer indexable, and a
  daily `search:backfill` schedule keeps the index converged.
- **`continue learning` rail was unbounded.** The learner endpoint now orders by recent
  activity and caps at 24 enrollments, bounding the per-enrollment next-lesson lookups.

### Config safety (confirmed defects — fixed)
- **Production media guard was a dead no-op** (read a non-existent `media.provider`
  key); it now reads the real `media.ingestion.default`. Added a guard rejecting the
  `fake` mail/SMS/push transports in production unless `NOTIFICATIONS_ALLOW_FAKE=true`.
  Guarded in `ProductionConfigValidatorTest`.

### Notes
- No push, no image publish, and no release tag are created by this candidate — those
  steps await explicit authorization after gate evidence. Items that require the
  operator's own environment or live credentials (container image scan, browser/a11y
  runs, backup-restore drill, live-provider smoke) are listed in
  `docs/releases/v1.0.0-rc.2.md` and are NOT claimed as passing here.

## [1.0.0-rc.1] — 2026-08-01

First release candidate. Cumulative of waves W01–W09 (bilingual EN/AR RTL MENA LMS:
catalog, learning, authoring, assessment, assignments, certification, commerce,
subscriptions, CRM, notifications, analytics; Laravel 12 API + Next.js 15 web).

### Fixed (W09 release-blocking)
- **Web API client double-versioned every authoring/media/grading/player request.**
  The media, assignments, versioning, gradebook and learning-player modules prefixed
  paths with `v1/`, but the BFF proxy base already ends in `/api/v1`, so requests
  resolved to `/api/v1/v1/...` and returned 404 in every environment — silently
  breaking the entire instructor-authoring, media, grading and lesson-player surface.
  Paths are now bare (matching the working majority). Guarded by
  `apps/web/tests/contract/no-double-v1-prefix.test.ts`.
- **Checkout could double-charge on a duplicate submit.** Two rapid `POST /checkout`
  requests (double-click / concurrent) each created a separate order with a distinct
  gateway idempotency key, producing two charges from one cart. Checkout is now
  serialized per user with a distributed lock held across the gateway call; a queued
  duplicate re-reads the emptied cart and is safely rejected (409
  `COMMERCE_CHECKOUT_IN_PROGRESS`, or 422 `COMMERCE_CART_EMPTY` once captured). Guarded
  by two regression tests in `tests/Feature/Commerce/CartCheckoutTest.php`.

### Changed
- Web `postcss` → 8.5.x and `sharp` → 0.35.x (W08 security remediation; both images
  now scan clean).
- Application version set to `1.0.0-rc.1`.

### Notes
- No database schema changes in W09 — this candidate is a code-only advance over W08.
- See `docs/releases/v1.0.0-rc.1.md` for release notes, rollback and known limitations,
  and `docs/verification/w09/` for the UAT matrix and gate evidence.

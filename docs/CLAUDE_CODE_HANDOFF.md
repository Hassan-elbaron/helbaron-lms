# HElbaron LMS — Engineering Handoff

**Audience:** Claude Code (or any engineer) picking up this branch.
**Branch:** `pr-1` (tracks `fork/feat/stage-4-enterprise-ai-growth-integrations`).
**Written:** 2026-08-30.

Read this file completely before writing code. It is the single source of truth for what is
already done, what is left, and the rules for doing it.

---

## 1. Product context — read this first, it changes every decision

This LMS is **not** a shared multi-tenant SaaS. It is sold as a **separate deployed instance per
customer academy**: each customer gets their own server, their own database, their own domain,
their own branding, and their own provider accounts (payments, mail, SMS, video). The owner
expects to operate 10–30 independent instances.

Three consequences that override the existing docs:

1. **Row-level tenant isolation is NOT the priority.** The `app/Platform/Shared/Tenancy/` machinery
   exists to serve **B2B organizations inside one academy** (a company buying seats for its
   employees). That is a legitimate product feature — keep it. Do not invest in "multi-tenant SaaS"
   isolation work. `docs/HELBARON_WEAK_POINTS_AND_ENHANCEMENT_PLAN.md` §P1.4 is largely
   **not applicable**; treat it as out of scope unless the owner says otherwise.
2. **White-labelling is a launch blocker, not a nice-to-have.** Any string, asset, colour, or
   database value that names "HElbaron" ships to a paying customer as someone else's brand.
3. **Fleet operations matter more than the audit suggests.** Provisioning a new instance, pushing a
   fix to 30 instances, and knowing which instance runs which version are real gaps that neither
   the PR nor the audit document covers.

---

## 2. Repository map

| Path | What it is |
|---|---|
| `apps/api` | Laravel 12 + Filament v4, PHP 8.3. The whole backend. |
| `apps/web` | Next.js frontend + BFF (`src/app/api/**` proxies to Laravel). |
| `infra/nginx` | Reverse proxy image. Also serves committed Filament assets. |
| `docker-compose.prod.yml` | Dokploy production stack. |
| `scripts/` | deploy / backup / restore / rollback shell scripts. |
| `docs/ops/` | Deployment checklist, monitoring, disaster recovery. |

**Architecture is enforced.** `apps/api/deptrac.yaml` defines layers. The rule that matters:

> Bounded contexts (`app/Domains/*`, `app/Contexts/*`) and platform capabilities
> (`app/Platform/{Media,AI,Integration,Search,Notifications,Homepage,Branding}`) may depend on
> **`app/Platform/Shared`** and **`app/Platform/Identity/Contracts`** — and nothing else.

Cross-module needs go through a **Port interface in `Shared`** with an **Adapter** in the owning
module, bound in that module's ServiceProvider. Follow the existing pattern
(`Shared/Catalog/Contracts/CourseLookupPort` ← `Domains/Catalog/Adapters/CourseLookupAdapter`,
bound in `CatalogServiceProvider::register()`).

`deptrac.baseline.yaml` freezes pre-existing violations. **Never add to the baseline.** If your
change needs a new cross-layer dependency, add a Port instead.

---

## 3. Work already completed on this branch (do not redo)

All of the following is committed working-tree state. Verify with `git status` / `git diff`.

### 3.1 Default super-admin credentials — closed
- `apps/api/app/Platform/Identity/Database/Seeders/IdentitySeeder.php` — now refuses to create the
  convenience admin in `production`, and refuses outside `local`/`testing` unless
  `SEED_ADMIN_PASSWORD` is set. Credentials are env-driven (`SEED_ADMIN_EMAIL/NAME/PASSWORD`).
  Also sets `email_verified_at` via `forceFill` — it is not mass-assignable, so the seeded admin was
  previously created **unverified** and would be locked out by the new `RequireVerifiedEmail`
  middleware.
- **New:** `apps/api/app/Console/Commands/CreateAdminUser.php` → `php artisan identity:create-admin`.
  Hidden password prompt with confirmation, minimum 12 characters, letter+digit required, deny-list
  of well-known passwords, `--reset-password` for an existing account. Never echoes the password.
- `apply-all.ps1` — annotated as local-only; points operators at the new command.

### 3.2 Fake video playback in production — closed
- `apps/api/config/learning.php` — added `playback.allow_fake_provider` (`LEARNING_PLAYBACK_ALLOW_FAKE`).
- `apps/api/app/Platform/Shared/Config/ProductionConfigValidator.php` — `criticalErrors()` now rejects
  `learning.playback.provider === 'fake'`. This is a hard boot failure in production
  (`AppServiceProvider::guardProductionConfig`).
- `apps/api/.env.production.example` — documents `LEARNING_PLAYBACK_PROVIDER` (previously absent
  entirely) next to `MEDIA_INGESTION_PROVIDER`, with a note that they are two separate selectors.

### 3.3 White-label foundation — built
- **New port:** `apps/api/app/Platform/Shared/Branding/Contracts/BrandProfilePort.php`
- **New DTO:** `apps/api/app/Platform/Shared/Branding/Data/BrandProfile.php`
- **New adapter:** `apps/api/app/Platform/Branding/Adapters/BrandProfileAdapter.php`
  (read-only, memoised per locale, degrades to defaults on any failure — branding must never break a
  delivery), bound as a **singleton** in `BrandingServiceProvider::register()`.
- **New helper:** `apps/api/app/Platform/Shared/Notifications/BrandedMail.php` — builds a
  `MailMessage` with the instance brand, the recipient's locale, and a correct **frontend** URL.
- `BrandSetting::defaults()` is now **env-driven** (`BRAND_NAME_EN/AR`, `BRAND_COMPANY_NAME`,
  `BRAND_SUPPORT_EMAIL/PHONE`, `BRAND_ADDRESS_EN/AR`, `BRAND_TIMEZONE`, `BRAND_CURRENCY`,
  `BRAND_THEME_PRESET`, `BRAND_EMAIL_FOOTER_*`, `BRAND_EMAIL_SIGNATURE_*`). A fresh instance is
  branded before an admin opens the branding screen.
- `BrandSetting::identityValue()` and `BrandSetting::publicArrayOrDefaults()` — read without
  `firstOrCreate`, safe on queued/hot paths.
- `CertificateSetting::current()` — issuer now resolves `CERTIFICATION_ISSUER` → branding company
  name → `APP_NAME`. `config/certification.php` no longer defaults to `'Core Business Academy'`.
- `EmailOtpNotification` / `ResetPasswordNotification` — branded, bilingual (ar/en by recipient
  locale), correct OTP TTL key (`identity.otp.email.ttl_minutes`).
- `TemplateRenderer::render()` — injects `{{ brand }}`, `{{ brand_company }}`,
  `{{ brand_support_email }}`, `{{ brand_signature }}`, etc. Caller data still wins on collision.
- `NotificationsSeeder` + `NotificationTemplateFactory` — use `{{ brand }}` instead of a literal.
- `AdminPanelProvider::panel()` — `brandName()` resolves through the port.
- `config/identity.php` — `mfa.issuer` no longer hardcodes the vendor (it is shown inside the user's
  authenticator app).
- `config/services.php` + `MailgunMailProvider` — `no-reply@helbaron.test` fallback removed.

### 3.4 Two latent bugs found and fixed
Both were reading **`config('app.frontend_url')`, a key that does not exist in this application**
(there is no `config/app.php`; the real key is `config('shared.frontend_url')`). Both silently fell
back to `config('app.url')` — the **API** host.
- `ResetPasswordNotification` — every password-reset email linked to the API host, where no reset
  page exists.
- `Domains/Certification/Services/VerificationUrlService::forCode()` — the verification URL printed
  on **every certificate** pointed at the API host.

---

## 4. Remaining work

Do these **in order**. Each item states the acceptance criteria. Do not start a later phase before
the earlier one is green.

---

### PHASE A — Merge blockers (must land before this PR is acceptable)

#### A1. Free enrollment gives away paid courses — **revenue loss**

**Problem.** The frontend decides a course is free from `purchase?.purchasable !== true`.
`purchasable` is derived from `Product::query()->active()`. So a course whose product exists but is
**Draft / Archived / not active** is advertised as "Free" and the API grants it — as a **lifetime**
enrollment (`GrantEnrollmentAction` is called with `$expiresAt = null`) that no refund or
entitlement-revocation path undoes. An admin briefly setting a live product to Draft to edit pricing
opens a free-enrollment window on every course it sells.

The backend permissiveness is pre-existing and even codified in
`apps/api/tests/Feature/Commerce/PaidCourseEnrollmentGuardTest.php`
(`it('does not lock a course away while its product is still a draft')`). Before this PR the UI
showed "Not available yet" with a disabled button, so it was unreachable. This PR turned it into a
one-click front door.

**Files**
- `apps/api/app/Contexts/Commerce/…/PurchaseSummaryAdapter` (emits `purchasable`)
- `apps/api/app/Contexts/Commerce/Services/EntitlementService.php` (`isCoursePurchasable`)
- `apps/api/app/Contexts/Learning/Actions/Enrollment/EnrollInCourseAction.php`
- `apps/web/src/components/catalog/course-purchase-panel.tsx`
- `apps/web/src/components/catalog/course-card.tsx`

**Do**
1. Add an explicit `free` signal to the purchase summary DTO, true only when **no product row of any
   status** grants the course. Do not infer freeness from the absence of an *active* product.
2. Frontend renders the free-enrollment path **only** on `purchase.free === true`. A course with a
   non-active product renders the previous "not available yet" state.
3. `EnrollInCourseAction` must reject a free-enrollment attempt on a course that has any product row,
   regardless of status — server-side, not just in the UI.
4. Decide with the owner whether existing free grants need auditing. Do not write a data migration
   without asking.

**Acceptance**
- Feature test: course + `active` product → free enrollment rejected.
- Feature test: course + `draft` product → free enrollment **rejected** (this inverts the current
  assertion in `PaidCourseEnrollmentGuardTest`; update that test and explain the change in its
  docblock).
- Feature test: course + `archived` product → rejected.
- Feature test: course with **no** product → free enrollment succeeds.
- Frontend test: card and detail panel show "not available" for a draft-product course.

---

#### A2. No OTP resend behind a hard verification gate — **mass user lockout**

**Problem.** `RequireVerifiedEmail` (registered on the whole `api` group in
`apps/api/bootstrap/app.php`) allows an unverified account exactly three things: `GET /api/v1/profile`,
`POST /api/v1/auth/verify-email`, `POST /api/v1/auth/logout`. The email OTP TTL is **10 minutes**
(`config/identity.php` → `otp.email.ttl_minutes`).

**There is no resend endpoint anywhere** (`apps/api/app/Platform/Identity/routes/auth.php` has
`register`, `login`, `forgot-password`, `reset-password`, `logout`, `verify-email`, `verify-phone`,
`mfa/*` — nothing resends). A user whose code expires or lands in spam is permanently locked out of
the entire API with no self-service recovery.

**Do**
1. Add `POST /api/v1/auth/resend-email-otp`, `auth:sanctum`, rate-limited. Honour the existing
   `identity.otp.email.max_per_hour` budget (default 5) — reuse the existing OTP issuing service, do
   not write a second one.
2. Add its route name to `RequireVerifiedEmail::isVerificationSurface()`. **Verify this by test** —
   a self-blocking resend endpoint is the exact failure mode to avoid.
3. Return a generic success response whether or not the address is deliverable (no account
   enumeration).
4. Add a "Resend code" control with a cooldown to
   `apps/web/src/app/(marketing)/(auth)/verify-email/page.tsx`.
5. **Fix the dropped redirect.** `?redirect=` is threaded from `course-purchase-panel.tsx` and
   `lib/auth/guards.tsx` and then ignored — the page hardcodes `router.replace("/")` on success and
   `"/dashboard"` when already verified. Honour the param; validate it is a **relative path** on this
   origin before redirecting (open-redirect guard).

**Also verify (do not assume):**
- Does the **SAML/SSO** provisioning path set `email_verified_at`? The social path does
  (`AuthenticateWithSocialIdentityAction`). If SAML does not, **every SSO user is locked out on
  deploy.** Fix it if broken; state the finding either way.
- `verify-phone` and the whole `auth/mfa/*` group are blocked for unverified users. Confirm no
  onboarding flow requires them before email verification.
- The middleware returns `403 EMAIL_VERIFICATION_REQUIRED`, but **no frontend code reads that code**
  — it is indistinguishable from an authorization 403. Make the BFF/client surface it so the UI can
  route to `/verify-email` instead of showing a generic error.

**Acceptance**
- Feature test: unverified user can call resend; is rate-limited after the configured budget.
- Feature test: unverified user is still blocked on checkout, enrollment, AI, and developer key
  issuance (the current `EmailVerificationTest` only covers `PUT /profile`).
- Feature test: verify-email and logout remain reachable while unverified.
- Frontend test: redirect param survives verification.

---

#### A3. Empty-lesson guard blocks legitimate courses

**Problem.** `apps/api/app/Domains/Authoring/Services/CourseReadinessService.php`
(`hasMeaningfulContent()`) applies `trim(strip_tags($value))`. A lesson whose body is **only** an
embed — `<iframe>`, `<img>`, `<video>` — strips to `''` and is declared empty, which now **blocks
course publication** (the check was promoted to `Blocker`). That is the most common authoring shape
in this product.

The recursion also only inspects array **values** and only counts strings, so a structured block
payload like `{"embed": {"media_id": 12}}` is a false positive, while `{"type": "text"}` is a false
negative.

**Note the policy contradiction.** The same file, ~80 lines above, states that promoting a check to
blocker "would retroactively stop every already-published course from re-publishing, which the
severity policy on `ReadinessSeverity` explicitly rules out." Resolve this explicitly: either
document why this check is exempt, or grandfather already-published courses.

**Do**
1. Treat a lesson as having content when **any** of these is true: non-empty text after
   `strip_tags`; the HTML contains a media/embed element (`iframe`, `img`, `video`, `audio`,
   `embed`, `object`, `source`); a published content block exists; attached media exists; a quiz
   assessment is attached.
2. Count structured references, not just string leaves (media ids, block type ids).
3. Update the `explanation` text — it currently says "It has neither content nor media", which is
   already wrong (blocks and quizzes count) and will directly contradict an author looking at their
   embedded video.
4. **Fix the silent scheduled-publish loop.** `PublishScheduledCoursesCommand` swallows
   `CoursePublishBlockedException` and only increments a counter, so the operator sees
   `"0 published, 7 pending (not ready)"` forever with no course id and no reason, retried every
   minute with no alert. Log the course `public_id` and the blocker codes at warning level.

**Acceptance**
- Unit tests for `hasMeaningfulContent()`: text-only ✓, iframe-only ✓, img-only ✓, media-only ✓,
  published-block ✓, quiz ✓, genuinely empty ✗, `{"type":"text"}` metadata-only ✗.
- Feature test: publishing a course whose lesson holds only an embedded video **succeeds**.
- Feature test: the scheduled-publish command logs the course id and blocker codes on failure.

---

### PHASE B — White-label completion (the "cannot sell" work)

The foundation from §3.3 is in place. What remains is consuming it and removing literals.

#### B1. Certificate rendering must use the branding record
`BrandSetting.certificate` (background, logo, signature, stamp, QR position, font, colours, margins)
is admin-editable and **read by zero production code**.
`Domains/Certification/Services/CertificateVariableRenderer.php` and `CertificateRenderService.php`
never touch it.

Extend `BrandProfilePort` with the certificate group (or add a sibling port in
`Shared/Branding/Contracts/`) and consume it in the renderer. Per-template `design` values should
override instance branding; instance branding overrides built-in defaults.

#### B2. Brand name persisted in the database schema — needs a migration
`apps/api/app/Contexts/Commerce/Enums/CompanyCertificateBranding.php` stores `helbaron_only` and
`company_logo_and_helbaron` **as column values** in `products.company_certificate_branding`
(default set in `2026_08_16_000100_add_commercial_policy_to_products_table.php`), compared as raw
strings in `CertificatePolicyResolver` and `Certificate.php`, with admin labels naming the vendor.

Rename to `platform_only` / `company_and_platform` (or similar) **with a reversible data migration**
that rewrites existing rows, and update the labels to use the brand name. Do not leave the old values
in the database.

#### B3. Remove hardcoded brand from the frontend
~134 literals across ~57 files in `apps/web`. Highest-value first:
- `src/lib/branding/api.ts` — a hand-maintained **duplicate** of `BrandSetting::defaults()`. Collapse
  it: fetch from the branding API and keep only a minimal offline fallback.
- `src/config/{site,theme,messaging,home-v2}.ts` — brand name, taglines, and
  `locations: ["Cairo","Dubai","Riyadh"]` which the footer actually renders **instead of** the
  branding `identity.address`. Three different support-email strings already exist across the
  codebase (`support@helbaron.com`, `hello@helbaron.com`, `hello@helbaron.academy`) — proof the
  duplication has drifted. Single-source them.
- `src/lib/i18n/dictionaries.ts` — brand baked into both locales as literals with **no interpolation
  token**. Add a `{brand}` token and interpolate at render.
- `src/app/(marketing)/(auth)/layout.tsx` — `<span>HElbaron</span>` and a hardcoded `© 2026`
  (the year is also wrong going forward).
- `src/components/marketing/course-cover/course-cover.tsx` — `HELBARON · PRESS` is rendered into
  **every generated course cover image**.
- ~24 marketing route files with brand in `metadata`/SEO and body copy, incl. JSON-LD
  `organizer.name` in `events/[public_id]/page.tsx`.

#### B4. Remove hardcoded brand from backend seeders and payment descriptors
- `StaticPagesSeeder` (~30 occurrences: About/Contact/Privacy/Terms/FAQ/Careers in EN+AR, plus
  `hello@helbaron.academy`, `careers@helbaron.academy`, "Cairo, Dubai, Riyadh").
- `Platform/Homepage/Models/HomepageSection.php`, `Enums/BlockType.php`, `BrandHomepageSeeder`
  (`why_helbaron` section key), `NavigationSeeder`, `BlogSeeder`.
- `CatalogSeeder` — five instructor accounts with **invented human names** and
  `Hash::make('password')`; `LearningSeeder` — `student@helbaron.local`.
- **Payment descriptors** (these appear on the customer's bank statement):
  `CheckoutAction`, `InitiatePaymentAction`, `SubscribeAction`, `SubscribeOrganizationAction`,
  `RenewSubscriptionAction`, `ChangePlanAction` all build `'HElbaron order '.$id`. Use the brand.
- `AmazonPaymentServicesGateway` — `noreply@helbaron.test`.

#### B5. Infrastructure naming
`docker-compose*.yml` container/volume names, `infra/nginx/nginx.conf` upstream and rate-limit zone
names, `.github/workflows/*` DB names, `composer.json` / `package.json` package names. Low user
impact but they leak the vendor name to anyone with server access.

**Acceptance for Phase B**
- Set `BRAND_NAME_EN="Acme Academy"` (and the other `BRAND_*` keys) in a scratch `.env`, run the
  structural seeders, and confirm **no** "HElbaron" appears in: the site header/footer, the admin
  panel, a rendered certificate, an OTP email, a password-reset email, a welcome notification, a
  generated course cover, a payment descriptor, or page `<title>`/OG metadata.
- `rg -i 'helbaron' apps/web/src apps/api/app --glob '!*.test.*'` returns only intentional
  vendor-owned references (and ideally nothing).

---

### PHASE C — Fleet operations (10–30 independent instances)

Neither the PR nor `docs/HELBARON_WEAK_POINTS_AND_ENHANCEMENT_PLAN.md` covers this. It is the
owner's real operating model.

#### C1. `php artisan install:academy`
One idempotent command that takes a fresh database to a sellable instance:
1. Refuse to run twice (detect an existing install marker or any admin user).
2. Run the **structural** seeders only — `RolePermissionSeeder`, `StaffRoleTemplatesSeeder`,
   `NavigationSeeder`, `BrandingSeeder`, `StaticPagesSeeder`, `FeatureFlagsSeeder`, `HomepageSeeder`
   — never `DatabaseSeeder` (which publishes 12 HElbaron courses and 5 invented trainers).
3. Prompt for or accept the academy brand values and write the `BrandSetting` row.
4. Delegate to `identity:create-admin` for the first administrator.
5. Run `config:validate` at the end and refuse to report success if it fails.

Currently, following `docs/ops/DEPLOYMENT_CHECKLIST.md` exactly produces an instance with correct
RBAC **and no way to log in** — the checklist deliberately excludes `IdentitySeeder` and nothing
replaces it. That is why operators fall back to `db:seed`. This command closes that gap.

#### C2. Wire the validators into the pipeline
Neither `config:validate` nor `env:validate` is invoked by `.github/workflows/ci.yml` or
`scripts/deploy.sh`. Add `php artisan config:validate` to `scripts/deploy.sh` **before** the migrate
step, so a misconfigured release fails before it touches the database.

Also: the boot guard in `AppServiceProvider::guardProductionConfig()` returns early when
`runningInConsole()`, so queue workers and artisan never hit it. Consider extending it to queue
workers.

#### C3. `env:validate` must cover all payment gateways
It validates credentials for **Stripe only**. `config/commerce.php` offers
`fake|stripe|paymob|moyasar|hyperpay|tap|aps` — and `docs/ops/DEPLOYMENT_CHECKLIST.md` recommends
`aps`. An instance on Paymob or Tap with empty credentials passes validation and fails at the first
real checkout. Add a required-secret map per gateway.

#### C4. Version reporting
`HealthController` emits `config('app.version', '1.0.0-rc.1')` — but **no `app.version` config key
exists** (there is no `config/app.php`), so `APP_VERSION` from the pipeline is ignored and every
instance reports the same hardcoded string. Fix the key and add `GET /api/v1/version` returning the
build SHA, release tag, and migration state, so the owner can answer "which academies are
unpatched?"

#### C5. Backups must survive a deploy
`docker-compose.prod.yml` bind-mounts the backup target to `./backups` — **inside the Dokploy Git
checkout, which Dokploy replaces atomically on every deploy**. (The same reasoning was correctly
used to justify baking the nginx config into the image, then not applied here.) Move it to a named
volume or an absolute host path outside the checkout. Backup failures are `echo >&2` only — nothing
alerts.

---

### PHASE D — Correctness and performance

#### D1. `HasSlug` does not enforce uniqueness
`apps/api/app/Platform/Shared/Traits/HasSlug.php` calls `Slug::make()`, **not the `Slug::unique()`
helper that already exists ten lines below it** in `Platform/Shared/Helpers/Slug.php`. `products.slug`
has a DB unique index, so two products with the same title produce a raw `SQLSTATE[23505]` 500 rather
than a validation error. Filament's `->unique(ignoreRecord: true)` only validates a *submitted*
value, so the blank-field path — the exact flow the auto-fill exists to cover — bypasses it.

Also:
- `Slug::make()` is `Str::slug($value, '-', 'en')`. A title with no ASCII mapping yields `''`, which
  is written into a `NOT NULL UNIQUE` column: the first succeeds, every subsequent one 500s. Add a
  `public_id` fallback when the slug is empty.
- `HasSlug` line ~31: `$translations[$default] ?? reset($translations)` — `??` does not fire when the
  `en` key **exists but is empty**, so the Arabic title is never used. Use `filled()` semantics.
- Catch `QueryException` 23505 and retry with a suffix; the DB index is the only real guard.
- **Course slugs are now the primary public URL** (`course-card.tsx`). Renaming one 404s every
  inbound link and indexed SERP entry. Add a `slug_history` table with 301 redirects, or make the
  slug immutable once published. Decide with the owner.

#### D2. Enrollment admin — N+1 introduced by this PR, and it misses its own goal
`apps/api/app/Contexts/Learning/Filament/Resources/EnrollmentResource.php`
- The Learner column runs `UserLookupPort::refById()` **per row** = 2 queries per row, re-executed on
  every sort/filter/search/paginate. The previous `TextColumn::make('user.email')` was eager-loaded
  by Filament automatically. Batch-resolve the page's user ids, or eager-load and render `user.name`.
- `TextColumn::make('course.title')` reads the **raw** scalar, not `$course->localized('title')` —
  which every other surface in this codebase uses. So for exactly the i18n-only courses this PR
  exists to fix, the cell is **still blank**. Sorting also sorts by the untranslated scalar.
- The Learner column lost `->searchable()->sortable()`, so admins can no longer find an enrollment by
  learner email. Restore that capability.

#### D3. BFF proxy hardening
`apps/web/src/app/api/backend/[...path]/route.ts`
- `encodeURIComponent` does **not** encode `.`, and nginx decodes `%2F` during URI normalization, so
  `/api/backend/..%2F..%2Fadmin` can normalize to `/admin` with the user's bearer token attached.
  Reject any path segment matching `/^\.+$/` before building the URL.
- No `AbortSignal.timeout()`. A hung upstream pins a Next.js handler until undici's 300 s default.
  Add ~25 s and map `TimeoutError` to 504.
- On an nginx-generated 502/504 the HTML body and `content-type: text/html` are forwarded verbatim,
  so the client's JSON parse throws. Normalize non-JSON upstream errors into the error envelope.
- `no-double-v1-prefix.test.ts` is a good source-scanning regression guard, but its `API_MODULES`
  list is hardcoded. Glob `src/lib/**/*-api.ts` so new fetchers are covered automatically.

#### D4. Ops fragility
- `docker-compose.prod.yml` hardcodes DNS aliases `lms-h-sbvbdl-{api,web}-internal` — a
  Dokploy-generated project id — duplicated in `infra/nginx/nginx.conf`. Redeploying under a
  different project name silently 502s. Use the plain service names `api` / `web`.
- `env_file: [./.env]` is applied to `postgres` and `db-backup`, injecting `APP_KEY`, payment keys
  and `OPENAI_API_KEY` into containers that need four DB variables.
- `ports: "8080:80"` binds `0.0.0.0` — a plaintext TLS bypass. Bind `127.0.0.1:8080:80`.
- `redis` has no `requirepass`.
- The API healthcheck probes FPM `/ping`, but **`ping.path` is not enabled** in
  `apps/api/infra/php/*.ini`, so `cgi-fcgi` exits 0 regardless — it reports a wedged worker pool as
  healthy, and `web`'s `depends_on: service_healthy` then starts against a dead API. Enable
  `ping.path` or probe `/up` through nginx.
- `infra/nginx/Dockerfile` pins the floating tag `nginx:1.27-alpine`, but `server ... resolve;` in an
  `upstream` block requires **≥ 1.27.3**. Pin an explicit patch version.
- No `deploy.resources.limits` and no log rotation — a chatty Horizon container fills the host disk.
- `docker-compose.prod.yml` image tags default to `1.0.0-rc.1` while `VERSION` says `1.0.0-rc.2`.

#### D5. `AI_ENABLED` default flip is a behaviour change
`config/ai.php` flipped `AI_ENABLED` from `true` to `false`. Correct as a fail-closed default, but it
silently darkens AI on existing deployments. Move it from "Fixed" to "Changed" in `CHANGELOG.md` and
add an upgrade note.

---

### PHASE E — Known defects the plan document records but does not fix

These are documented in `docs/HELBARON_WEAK_POINTS_AND_ENHANCEMENT_PLAN.md` as confirmed. Fix them
here rather than carrying them.

#### E1. "Remember me" is inert — and fails in the dangerous direction
- `apps/web/src/app/(marketing)/(auth)/login/page.tsx` registers the checkbox, then submits
  `auth.login(v.email, v.password, mfa ? v.mfa_code : undefined)` — `v.remember` is **never passed**.
- `apps/web/src/lib/auth/auth-context.tsx` and `src/lib/api/client.ts` have no such parameter, and
  `LoginRequest` would strip it.
- `apps/web/src/app/api/session/route.ts` hardcodes `SESSION_MAX_AGE = 14 days`, applied
  unconditionally.

**The worse half:** a user who deliberately *unticks* the box on a shared machine still gets a
14-day persistent credential. Thread the flag end to end; unchecked must produce a **session-only**
cookie (no `maxAge`). Centralise the lifetimes in config. Test refresh, expiry, revocation, password
change, and logout.

Note `secure` on the session cookie is `process.env.NODE_ENV === "production"` — a staging deploy
built with a different `NODE_ENV` emits the credential cookie **without** the `Secure` flag. Tie it
to the URL scheme instead.

#### E2. Inert engines — decide, then act
`DigestService::pendingForUserId()` and `WorkflowEngine::handleEventForUserId()` have **zero
callers**. Scheduled automations are declared (`AutomationTriggerType::Scheduled`, the
`scheduled_automations` table, `ScheduledAutomation` model) but `AutomationRunner` hard-filters
`->where('trigger_type', 'event')`.

A user can set `digest_frequency` — it validates, persists, returns in the API and is documented in
`openapi/notifications.yaml` — and **nothing will ever send a digest.** That is a promise the product
does not keep.

Either wire them (scheduler entry, dedup ledger, retry, audit) or **hide the setting** until they
exist. Do not leave it as-is. Note `AutomationRunner` itself **is** wired
(`NotificationsServiceProvider` registers listeners for two CRM lead events) — the plan document
overstates this as "all automation is inert".

#### E3. Search has no usable index
No `pg_trgm` extension and no trigram index anywhere. Three leading-wildcard scans:
- `Domains/Catalog/Services/CourseSearchService.php` — `like '%…%'` on `courses.search_text`, a
  column added with **no index at all**.
- `Platform/Search/Search/HybridSearchService.php` — `like '%…%'` on `content_embeddings.chunk_text`.
- `Domains/Live/…/EventController.php` and `Domains/Crm/Services/CrmSearchService.php` — `ilike`.

Add `CREATE EXTENSION pg_trgm` and GIN trigram indexes in a **reversible** migration. Benchmark with
representative data and verify relevance in **both Arabic and English**.

#### E4. Performance
- **Certificate PDFs** render synchronously on first download
  (`CertificateFileController` → `EnsureCertificatePdfAction`). Pre-generate on a queue when the
  certificate is minted. Note the Chromium path (`BrowsershotPdfGenerator`) is a **stub that
  unconditionally throws** — switching `CERTIFICATION_PDF_PROVIDER=browsershot` in production today
  produces a 500 on every certificate download. Wire `spatie/browsershot` + Chromium or keep the
  provider unavailable explicitly.
- Announcement fan-out and the event-listing N+1 are **already fixed** on this branch. Do not redo
  them.

---

## 5. Test coverage the PR is missing

The existing tests are thin in exactly the risky places. Add:

| Area | Gap |
|---|---|
| `ProductSlugGenerationTest` | Two happy paths only. No collision, no Arabic-only title, no empty-`en`-key fallback, no concurrent-insert test — uniqueness is what actually 500s. |
| `EmailVerificationTest` | Only `PUT /profile`. Add checkout, enrollment, AI, developer keys; and that verify-email/logout stay reachable while unverified. |
| `CourseReadinessTest` | The **new** behaviour (published blocks count as substance) is entirely untested. Add the A3 matrix. |
| `CourseDetailTest` | One happy-path slug lookup. Add draft-by-slug → 404, non-public-visibility-by-slug → 404, UUID-shaped slug. |
| Free enrollment | Frontend happy path only, mocked. **No API-level test at all.** Add the A1 matrix. |
| Branding | New. Assert emails, certificates and notification templates render a non-vendor brand when `BRAND_*` is set. |
| `identity:create-admin` | New. Assert weak-password rejection, no-plaintext-logging, `--reset-password`, and that `IdentitySeeder` refuses in production. |

The AI tests added by this PR (`ProviderManagerTest`, `ProductionConfigValidatorTest`) are the
strongest in the changeset — follow that standard.

---

## 6. Rules

1. **Do not add to `deptrac.baseline.yaml`.** New cross-layer needs get a Port in `Shared`.
2. **Every fix ships with a test.** A fix without a regression test is not done.
3. **Migrations must be reversible** with a real `down()`. All 236 existing ones have one.
4. **Never print, log, or commit a secret.** CI runs gitleaks.
5. **No new hardcoded brand strings.** Resolve through `BrandProfilePort` or a `BRAND_*` env key.
6. **Ask before destructive data changes.** Renaming persisted enum values (B2) and slug-history
   decisions (D1) need the owner's sign-off.
7. **Do not silently widen scope.** If something outside this document needs fixing, note it in the
   handoff summary rather than folding it in.
8. Update `CHANGELOG.md` under `[Unreleased]`, in the correct section (`Added` / `Changed` /
   `Fixed`). Behaviour changes are **Changed**, not **Fixed**.

---

## 7. Verification — must be green before handing back

```bash
# Backend
cd apps/api
composer install
php artisan config:validate          # add --production to exercise prod rules
vendor/bin/pint --test
vendor/bin/phpstan analyse
vendor/bin/deptrac analyse --fail-on-uncovered
php artisan test

# Frontend
cd ../web
npm ci
npm run lint
npm run typecheck
npm run test
npm run build
```

The full Laravel suite has **never been run green** on this branch — it previously failed because a
temporary PostgreSQL instance rejected its credentials, and that was reported as an infrastructure
issue rather than fixed. **Getting `php artisan test` fully green against a real PostgreSQL is itself
a deliverable.** If a pre-existing test fails, report it; do not delete or skip it to get a green
run.

Known trap: `demo:seed` currently dies at `DemoSeeder.php` with `SQLSTATE 42P10`, caused by partial
indexes on `metric_snapshots` from the tenancy change. **The obvious fix merges every tenant's
metrics into one bucket** — read the root-cause section in `docs/ops/DEPLOYMENT_CHECKLIST.md` before
touching it.

---

## 8. When you are done

Write `docs/CLAUDE_CODE_HANDOFF_RESULT.md` containing:
1. What you completed, per phase item, with file references.
2. What you did **not** complete and why.
3. Anything you found that is not in this document.
4. Decisions you made that the owner should confirm.
5. The exact verification output (test counts, lint/typecheck/deptrac results).

Do not mark an item complete unless its acceptance criteria pass. Partial work reported honestly is
worth more than a green summary that does not survive review.

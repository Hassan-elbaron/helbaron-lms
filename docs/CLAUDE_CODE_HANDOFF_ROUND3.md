# Round 3 — Phases B, C, D, E (everything remaining)

**Read `CLAUDE_CODE_HANDOFF.md` §§1–2 and 6 first** for product context, the repository map, the
architecture rules, and the working rules. They all still apply. That file's Phase B–E sections are
the detailed backlog; **this file supersedes them where they conflict**, because Phase A changed the
ground under several items.

Phase A is complete and green. This is the rest.

---

## 0. What Phase A changed that you must build on, not around

Four things now exist that did not when the original brief was written. Using them is not optional —
re-solving any of them a second way is the failure mode to avoid.

| Thing | Where | Use it for |
|---|---|---|
| `BrandProfilePort` + `BrandProfile` DTO | `app/Platform/Shared/Branding/` | **Every** surface that renders the instance's brand. Never import `BrandSetting` from another layer. |
| `config/branding.php` | `apps/api/config/` | The single resolution point for `BRAND_*`. See the rule below. |
| `Env::string()` / `Env::orFallback()` | `app/Platform/Shared/Support/Env.php` | Any env read where a present-but-empty key must fall back. |
| `courses.is_free` + `EntitlementKind` / `CourseEntitlement` | Catalog / Shared Commerce | Freeness and entitlement questions. Do not reintroduce inference from product rows. |

### The rule that caused the worst Phase A defect — do not break it again

**`scripts/deploy.sh` runs `php artisan config:cache` on every production deploy, and Laravel does
not read `.env` at all once the config is cached.** Any `env()` call outside the `config/` directory
returns `null` in production. The entire white-label mechanism was silently inert on every deployed
instance because of this.

So: **every new configurable value resolves in a `config/*.php` file.** Application code reads
`config()`, never `env()`. larastan's `noEnvCallsOutsideOfConfig` is the rule that catches this — do
not add ignores for it without a written reason of the kind already recorded in `Env.php`.

**B0 (do this first, it is small and it de-risks everything after):** sweep for the same bug
elsewhere. `rg "env\(" apps/api/app` and triage every hit. Anything read at request time that an
operator is expected to set is a live production defect of the same class. Report what you find even
where you do not fix it.

### A tooling gap worth knowing

Deptrac did not flag a `use` statement pointing at a class that no longer existed (a stale
`App\Console\Commands\CreateAdminUser` import left in `IdentitySeeder` after the move). It skips
references it cannot resolve. **Deptrac passing does not prove imports are valid.** I fixed that one;
be aware the gate has this blind spot.

---

## PHASE B — White-label completion

**This is the phase that decides whether the product can be sold at all.** Every literal below ships
to a paying customer as another company's brand.

### B1 — Certificate rendering must consume the branding record

`BrandSetting.certificate` (background, logo, signature, stamp, QR position, font, colours, margins)
is admin-editable and **read by zero production code**. `CertificateVariableRenderer` and
`CertificateRenderService` never touch it.

**Do**
1. Extend `BrandProfilePort` with the certificate group, or add a sibling port in
   `Shared/Branding/Contracts/`. Same read-only, memoised, degrade-to-defaults contract.
2. Consume it in the renderer. Precedence: per-template `design` values → instance branding →
   built-in defaults.
3. `CertificateSetting::current()` already resolves the issuer correctly (Phase A). Do not duplicate
   that logic; extend it if the certificate needs more identity fields.

**Acceptance:** a rendered certificate carries the configured logo, signature, colours and issuer,
with no vendor string anywhere. Test at the renderer, not only at the settings model.

### B2 — Brand name persisted in the database schema (needs a data migration)

`Contexts/Commerce/Enums/CompanyCertificateBranding.php` stores `helbaron_only` and
`company_logo_and_helbaron` **as column values** in `products.company_certificate_branding`. The
default is set in `2026_08_16_000100_add_commercial_policy_to_products_table.php`; the values are
compared as raw strings in `CertificatePolicyResolver` and `Certificate.php`; the admin labels name
the vendor.

**Do** rename to `platform_only` / `company_and_platform` with a **reversible** migration that
rewrites existing rows and updates the column default. Update the labels to use the brand name from
the port. Follow the pattern you used for `courses.is_free`: verify apply → rollback → re-apply.

### B3 — Frontend: ~134 literals across ~57 files

Highest leverage first.

1. **`src/lib/branding/api.ts` is a hand-maintained duplicate of the backend defaults.** It has
   already drifted — three different support-email strings exist across the codebase
   (`support@helbaron.com`, `hello@helbaron.com`, `hello@helbaron.academy`). Collapse it: fetch from
   the branding API, keep only a minimal offline fallback, and make that fallback obviously generic
   rather than a second copy of the real values.
2. **`src/config/{site,theme,messaging,home-v2}.ts`** — brand name, taglines, and
   `locations: ["Cairo","Dubai","Riyadh"]`, which the footer renders **instead of** the branding
   `identity.address`. Single-source all of it.
   Note `siteConfig.description` is used unbranded in `layout.tsx` in four places, so the meta
   description stays vendor-branded even after a rebrand.
3. **`src/lib/i18n/dictionaries.ts`** — brand baked into both locales as literals with **no
   interpolation token**. Add `{brand}` and interpolate at render, the way `TemplateRenderer` now
   does on the backend.
4. **`src/app/(marketing)/(auth)/layout.tsx`** — `<span>HElbaron</span>` and a hardcoded `© 2026`.
   The year is a separate bug: it is already wrong for any later year.
5. **`src/components/marketing/course-cover/course-cover.tsx`** — `HELBARON · PRESS` and
   `HELBARON · INSTITUTE OF PRACTICE` are rendered into **every generated course cover image**.
   `adapter.ts` also prefixes press codes with `HEL`.
6. ~24 marketing route files with brand in `metadata`/SEO and body copy, including JSON-LD
   `organizer.name` in `events/[public_id]/page.tsx`.

**Watch for the Next.js equivalent of the config:cache trap.** Server components read `process.env`
at request time, but anything inlined at build time (`NEXT_PUBLIC_*`) is frozen into the bundle. A
per-instance brand that must be changeable without a rebuild has to come from the **branding API**,
not from an env var. Say which values you routed which way and why.

### B4 — Backend seeders and payment descriptors

- `StaticPagesSeeder` — ~30 occurrences: About / Contact / Privacy / Terms / Cookies / Refunds / FAQ /
  Careers / Help in EN **and** AR, plus `hello@helbaron.academy`, `careers@helbaron.academy`, and
  "hubs in Cairo, Dubai, and Riyadh".
- `Platform/Homepage/Models/HomepageSection.php`, `Enums/BlockType.php`, `BrandHomepageSeeder`
  (`why_helbaron` section key — a **persisted key**, so changing it needs a migration or a
  compatibility shim), `HomepageSectionResource` label, `NavigationSeeder` ("HElbaron Advisory"),
  `BlogSeeder`.
- `CatalogSeeder` — five instructor accounts with **invented human names and bios**, all
  `Hash::make('password')`. `LearningSeeder` — `student@helbaron.local`.
  These are demo data masquerading as structural seed data. Decide explicitly: either move them
  behind the demo guard, or make them generic.
- **Payment descriptors — these appear on the customer's bank statement.** `CheckoutAction`,
  `InitiatePaymentAction`, `SubscribeAction`, `SubscribeOrganizationAction`,
  `RenewSubscriptionAction`, `ChangePlanAction` all build `'HElbaron order '.$id`. Use the brand.
  Note the gateway descriptor field is usually length-limited — truncate deliberately, do not let a
  long academy name silently break a charge.
- `AmazonPaymentServicesGateway` — `noreply@helbaron.test`.

### B5 — Infrastructure naming

`docker-compose*.yml` container and volume names, `infra/nginx/nginx.conf` upstream and rate-limit
zone names, `.github/workflows/*` database names, `composer.json` / `package.json` package names.
Low user impact, but it leaks the vendor to anyone with server access, and B5 overlaps D4 — do them
together.

### Phase B acceptance — one end-to-end check, not a file count

Set the `BRAND_*` keys to a fictional academy in a scratch `.env`, run the structural seeders, **run
`config:cache`** (this is the step that would have caught the Phase A defect), and confirm no vendor
string appears in: site header and footer, page `<title>` and OG metadata, the admin panel, a
rendered certificate, an OTP email, a password-reset email, a welcome notification, a generated
course cover, a payment descriptor, or a seeded static page in **either** locale.

Then: `rg -i 'helbaron' apps/web/src apps/api/app apps/api/config --glob '!*.test.*'` returns only
references that are intentionally the vendor's own.

---

## PHASE C — Fleet operations (10–30 independent instances)

Neither the PR nor the audit document covers this, and it is the owner's actual operating model.

### C1 — `php artisan install:academy`

One idempotent command taking a fresh database to a sellable instance.

1. Refuse to run twice (an install marker, or the presence of any admin user).
2. Run **structural** seeders only: `RolePermissionSeeder`, `StaffRoleTemplatesSeeder`,
   `NavigationSeeder`, `BrandingSeeder`, `StaticPagesSeeder`, `FeatureFlagsSeeder`, `HomepageSeeder`.
   **Never `DatabaseSeeder`** — it publishes 12 vendor courses and 5 invented trainers.
3. Prompt for or accept the academy brand values and write the `BrandSetting` row.
4. Delegate to `identity:create-admin` (exists, from Phase A) for the first administrator.
5. Run `config:validate` at the end and refuse to report success if it fails.

Following `docs/ops/DEPLOYMENT_CHECKLIST.md` exactly currently produces an instance with correct RBAC
**and no way to log in** — the checklist excludes `IdentitySeeder` and nothing replaces it. That is
why operators reach for `db:seed`. This command is what closes that gap; update the checklist to
point at it.

### C2 — Wire the validators into the pipeline

Neither `config:validate` nor `env:validate` is invoked by `.github/workflows/ci.yml` or
`scripts/deploy.sh`. Add `config:validate` to `deploy.sh` **before** the migrate step, so a
misconfigured release fails before it touches the database.

Also: `AppServiceProvider::guardProductionConfig()` returns early when `runningInConsole()`, so queue
workers and artisan never hit the boot guard. Extend it to queue workers — a worker running with a
fake mail provider silently drops every notification.

### C3 — `env:validate` must cover all payment gateways

It validates **Stripe only**. `config/commerce.php` offers
`fake|stripe|paymob|moyasar|hyperpay|tap|aps`, and `docs/ops/DEPLOYMENT_CHECKLIST.md` recommends
`aps`. An instance on Paymob or Tap with empty credentials passes validation and fails at the first
real checkout. Add a required-secret map per gateway, and cover each one with a test.

### C4 — Version reporting

`HealthController` emits `config('app.version', '1.0.0-rc.1')`, but **there is no `config/app.php`
in this repo**, so no `app.version` key exists, `APP_VERSION` from the pipeline is ignored, and every
instance reports the same hardcoded string.

**Do** add the config key (in a config file — see §0) and `GET /api/v1/version` returning build SHA,
release tag, and migration state. Without it the owner cannot answer "which academies are unpatched?"
after a security fix.

### C5 — Backups must survive a deploy

`docker-compose.prod.yml` bind-mounts the backup target to `./backups` — **inside the Dokploy Git
checkout, which Dokploy replaces atomically on every deploy.** The same reasoning was correctly used
to justify baking the nginx config into the image and then not applied here. Move it to a named
volume or an absolute host path outside the checkout.

Backup failures are `echo >&2` only. Nothing alerts. At minimum, make a failed backup non-silent.

---

## PHASE D — Correctness, performance, ops

### D1 — `HasSlug` does not enforce uniqueness

`Platform/Shared/Traits/HasSlug.php` calls `Slug::make()`, **not the `Slug::unique()` helper that
already exists ten lines below it** in `Platform/Shared/Helpers/Slug.php`. `products.slug` has a DB
unique index, so two products with the same title produce a raw `SQLSTATE[23505]` 500 rather than a
validation error. Filament's `->unique(ignoreRecord: true)` only validates a *submitted* value, so
the blank-field path — the exact flow the auto-fill exists to cover — bypasses it.

Also:
- `Slug::make()` is `Str::slug($value, '-', 'en')`. A title with no ASCII mapping yields `''`, written
  into a `NOT NULL UNIQUE` column: the first succeeds, every subsequent one 500s. Fall back to
  `public_id`.
- **The same empty-vs-null bug you fixed in Phase A lives here.** `$translations[$default] ?? reset(...)`
  does not fire when the `en` key **exists but is empty**, so the Arabic title is never used. Use
  `Env`-style `filled()` semantics.
- Catch `QueryException` 23505 and retry with a suffix. The DB index is the only real guard.
- **Course slugs are now the primary public URL.** Renaming one 404s every inbound link and indexed
  search result. Add a `slug_history` table with 301 redirects, **or** make the slug immutable once
  published. This is a product decision — recommend one, say why, and implement it.

### D2 — Enrollment admin: an N+1 introduced by this branch, and it misses its own goal

`Contexts/Learning/Filament/Resources/EnrollmentResource.php`
- The Learner column calls `UserLookupPort::refById()` **per row** = 2 queries per row, re-run on
  every sort / filter / search / paginate. The previous `TextColumn::make('user.email')` was
  eager-loaded automatically. Batch-resolve the page's user ids, or eager-load and render `user.name`.
- `TextColumn::make('course.title')` reads the **raw** scalar, not `$course->localized('title')` —
  which every other surface uses. For exactly the i18n-only courses this change exists to fix, the
  cell is **still blank**.
- The Learner column lost `->searchable()->sortable()`, so admins can no longer find an enrollment by
  learner email. Restore that capability.

### D3 — BFF proxy hardening

`apps/web/src/app/api/backend/[...path]/route.ts`
- `encodeURIComponent` does not encode `.`, and nginx decodes `%2F` during URI normalization, so
  `/api/backend/..%2F..%2Fadmin` can normalize to `/admin` with the user's bearer token attached.
  Reject any path segment matching `/^\.+$/`.
- No `AbortSignal.timeout()`. A hung upstream pins a Next.js handler until undici's 300 s default.
  Add ~25 s, map `TimeoutError` to 504.
- An nginx-generated 502/504 forwards an HTML body verbatim, so the client's JSON parse throws.
  Normalize non-JSON upstream errors into the error envelope.
- `no-double-v1-prefix.test.ts` hardcodes its module list. Glob `src/lib/**/*-api.ts` so new fetchers
  are covered automatically.
- `secure` on the session cookie is `process.env.NODE_ENV === "production"`. A staging deploy built
  with a different `NODE_ENV` emits the credential cookie **without** `Secure`. Tie it to the URL
  scheme.

### D4 — Ops fragility

- `docker-compose.prod.yml` hardcodes DNS aliases `lms-h-sbvbdl-{api,web}-internal` — a
  Dokploy-generated project id — duplicated in `infra/nginx/nginx.conf`. Redeploying under a
  different project name silently 502s. Use the plain service names `api` / `web`.
- `env_file: [./.env]` on `postgres` and `db-backup` injects `APP_KEY`, payment keys and
  `OPENAI_API_KEY` into containers that need four DB variables.
- `ports: "8080:80"` binds `0.0.0.0` — a plaintext TLS bypass. Bind `127.0.0.1:8080:80`.
- `redis` has no `requirepass`.
- **The API healthcheck does not check health.** It probes FPM `/ping`, but `ping.path` is **not
  enabled** in `apps/api/infra/php/*.ini`, so `cgi-fcgi` exits 0 regardless — it reports a wedged
  worker pool as healthy, and `web`'s `depends_on: service_healthy` then starts against a dead API.
- `infra/nginx/Dockerfile` pins the floating tag `nginx:1.27-alpine`, but `server ... resolve;` in an
  `upstream` block requires **≥ 1.27.3**. Pin an explicit patch version.
- No `deploy.resources.limits`, no log rotation — a chatty Horizon container fills the host disk.
- Compose image tags default to `1.0.0-rc.1` while `VERSION` says `1.0.0-rc.2`.
- **Filament assets are copied into the *nginx* image** (`infra/nginx/Dockerfile`), so the admin
  panel's CSS/JS lives on a different release cadence from the PHP that references it. A
  `composer update filament/filament` without an nginx rebuild ships *stale* assets, not a 404, so it
  fails silently. Serve them from the API container or a shared volume populated at startup.

---

## PHASE E — Defects the plan document records but never fixed

### E1 — "Remember me" is inert, and fails in the dangerous direction

- `login/page.tsx` registers the checkbox, then submits without `v.remember`.
- `auth-context.tsx` and `client.ts` have no such parameter; `LoginRequest` would strip it.
- `session/route.ts` hardcodes `SESSION_MAX_AGE = 14 days`, applied unconditionally.

**The worse half:** a user who deliberately *unticks* the box on a shared machine still gets a 14-day
persistent credential. That is a security guarantee the UI offers and the system does not honour.

Thread the flag end to end; unchecked must produce a **session-only** cookie (no `maxAge`).
Centralise the lifetimes in config. Test refresh, expiry, revocation, password change, and logout.

### E2 — Inert engines: decide, then act

`DigestService::pendingForUserId()` and `WorkflowEngine::handleEventForUserId()` have **zero
callers**. Scheduled automations are declared (`AutomationTriggerType::Scheduled`, the
`scheduled_automations` table, the `ScheduledAutomation` model) but `AutomationRunner` hard-filters
`->where('trigger_type', 'event')`.

A user can set `digest_frequency` — it validates, persists, returns in the API, and is documented in
`openapi/notifications.yaml` — and **nothing will ever send a digest.**

Either wire them (scheduler entry, dedup ledger, retry, audit, operator controls) or **hide the
setting** until they exist. Do not leave a promise the product does not keep. Note `AutomationRunner`
itself **is** wired for two CRM lead events — the plan document overstates this as "all automation is
inert", so scope the work to what is actually dead.

### E3 — Search has no usable index

No `pg_trgm` and no trigram index anywhere. Three leading-wildcard scans:
- `CourseSearchService` — `like '%…%'` on `courses.search_text`, a column added with **no index at
  all**.
- `HybridSearchService` — `like '%…%'` on `content_embeddings.chunk_text`.
- `EventController` and `CrmSearchService` — `ilike '%…%'`.

Add `CREATE EXTENSION pg_trgm` and GIN trigram indexes in a **reversible** migration. Benchmark with
representative data. **Verify relevance in Arabic as well as English** — trigram behaviour on Arabic
script is not the same as on Latin, and this is a bilingual product.

Note the semantic arm is also unindexed by design: `PortableVectorStore` pulls up to 2000 rows into
PHP and scores cosine there. Flag whether that is acceptable at the catalog sizes you are targeting.

### E4 — Performance

- **Certificate PDFs render synchronously on first download** (`CertificateFileController` →
  `EnsureCertificatePdfAction`). Pre-generate on a queue when the certificate is minted.
- **The Chromium path is a stub that unconditionally throws.** Setting
  `CERTIFICATION_PDF_PROVIDER=browsershot` in production today produces a **500 on every certificate
  download**. Either wire `spatie/browsershot` + Chromium properly, or make the provider fail at
  config-validation time rather than at the user's download.
- **N+1 across quiz lessons** in `CourseReadinessService`: `$this->assessments->describe(...)` inside
  the loop runs one query plus a count sub-select per quiz lesson, on every publish **and** every
  load of the instructor readiness panel.
- Announcement fan-out and the event-listing N+1 are **already fixed**. Do not redo them.

---

## Order of work

Do the phases in this order; within a phase, follow the order above.

1. **B0** — the `env()` sweep. Small, and it may surface more of the Phase A defect class.
2. **PHASE B** — the sale blocker.
3. **PHASE C** — needed before instance number two, not after.
4. **PHASE E1 + E3** — remember-me is a security promise; search is a scale wall that arrives with
   the first real catalogue.
5. **PHASE D** — correctness and ops.
6. **PHASE E2 + E4** — the rest.

**Stop and report at the end of each phase.** Do not run B through E in one pass — each phase gets
its own verification run and its own section in the result document, so a regression is attributable
to a phase rather than to a fortnight of changes.

---

## Rules (unchanged, and one addition)

All eight rules in `CLAUDE_CODE_HANDOFF.md` §6 still apply. One addition, learned the hard way in
Round 2:

9. **Never use a heuristic to decide which files to revert or rewrite in bulk.** The
   "add count == delete count" line-ending pass destroyed three files' uncommitted work. If a bulk
   operation cannot name each file it will touch and why, do it in batches you can verify, and take a
   backup first.

---

## Verification and reporting

Same gates as `CLAUDE_CODE_HANDOFF.md` §7, plus `config:cache` in the Phase B acceptance check.
All must be green at the end of **each** phase:

```bash
cd apps/api && php artisan config:validate && vendor/bin/pint --test \
  && vendor/bin/phpstan analyse && vendor/bin/deptrac analyse && php artisan test
cd ../web && npm run lint && npm run typecheck && npm run test && npm run build
```

Write `docs/CLAUDE_CODE_HANDOFF_ROUND3_RESULT.md` incrementally — a section per phase, appended as
each completes — in the same format as Round 2: what you completed, what you did not and why,
anything you found that is not in this document, decisions needing confirmation, and the exact
verification output.

Two standards from Round 2 that I want to keep:
- **Push back when a premise is wrong.** You were right twice in Round 2 and it saved real work.
  Verify before implementing, and say so when the brief is mistaken.
- **A test must be able to fail.** The two inert tests you rewrote are the pattern: pair the positive
  and the negative so the test dies if the feature is removed, not only if a boundary is tweaked.

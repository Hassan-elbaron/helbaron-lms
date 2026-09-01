# HElbaron LMS — Round 3 Result

**Branch:** `pr-1`
**Scope:** `CLAUDE_CODE_HANDOFF_ROUND3.md` — B0, then Phases B, C, D, E.
**Written incrementally, one section per phase.**

Companion to `CLAUDE_CODE_HANDOFF_RESULT.md` (Round 1) and `..._ROUND2_RESULT.md` (Round 2).

---

## B0 — the `env()` sweep

### Summary

The defect class is real and I reproduced it precisely. **But its blast radius in Round 2 was
smaller than I reported, and §0 of this brief repeats my overstatement.** Correction in full below,
because it changes how much weight to put on the rest.

The sweep itself came back nearly clean: **one live defect**, two deliberate-and-correct raw reads,
and nothing else.

### The mechanism, isolated properly

My Round 2 test cached the config **and** deleted `.env`, which conflated two variables. Re-run as
three separate cases:

| Condition | `env('B0_PROBE_KEY')` |
|---|---|
| config **not** cached, `.env` present | `'from-dotenv'` |
| config **cached**, `.env` still present | **`NULL`** |
| config **cached**, value also a real process env var | `'from-process-env'` |

So the rule in §0 is correct as a mechanism: **once config is cached, a value that exists only as a
line in `.env` is invisible to `env()`.** Deleting the file was never the point.

### Correction: the branding keys were probably NOT inert in production

`docker-compose.prod.yml` sets **`env_file: [./.env]` on every service** (api, web, horizon,
scheduler, postgres, db-backup). Compose reads that file and injects each key as a **real process
environment variable** in the container — which row 3 above shows survives `config:cache`.

So on this stack's actual production topology, `env('BRAND_NAME_EN')` would have resolved, and my
Round 2 claim that "the entire white-label mechanism was silently inert on every deployed instance"
is **too strong**. I verified the mechanism and then asserted a deployment consequence I had not
checked. That was the wrong order.

### The FPM `clear_env` question — measured, and it goes the other way

The Round 3 review raised a sharper version of this: php-fpm's `clear_env` defaults to **yes**, which
would wipe the worker environment on the **web** path while CLI-based queue workers and scheduled
commands kept seeing it — a white-label bug that is correct half the time. That would have restored
most of my original claim.

I measured it instead of reasoning about it. **It does not hold here, and the reason is a second
pool file.**

- `clear_env` appears **nowhere in this repo** (ripgrep, whole tree) — confirming that half.
- `apps/api/Dockerfile` copies only `opcache.ini` and `php.ini` into `conf.d/` and **never touches
  `/usr/local/etc/php-fpm.d/`**, so whatever the base image sets, survives.
- The base image `php:8.3-fpm-alpine` ships **four** files in the pool directory, not one. `www.conf`
  does have `;clear_env = no` commented out at line 448 — but `docker.conf` line 12 has
  **`clear_env = no` uncommented**. docker-library sets it deliberately, precisely so container
  environment variables reach PHP.
- Effective parsed configuration, straight from the image:

  ```
  $ docker run --rm php:8.3-fpm-alpine php-fpm -tt
  NOTICE: clear_env = no
  ```

So **there is no CLI/FPM split on this stack**: FPM workers inherit the Compose `env_file` variables
just as CLI processes do. The hypothesised failure mode — an OTP email from a Horizon worker carrying
the right brand while a web page carried the wrong one — would not have occurred.

*Caveat, stated precisely:* this is measured on the **base** image, not on a built `api` image. The
Dockerfile provably does not alter pool configuration, so the inference from base image to built
image is short and safe — but a deployment that mounted its own pool config could still differ.

**Net effect on §0's rule: keep it, but on one justification rather than two.** The rule is right
because it is topology-independent and single-sourced. It is *not* right because "the web path clears
the environment anyway" — that specific justification is measurably false here, and recording it as
true would leave a false belief in the codebase for the next person who reasons from it.

**What is still true, and why the Phase A fix stays:**

- The mechanism is real, and it bites the moment a value reaches Laravel by file rather than by
  process env — a bare-metal deploy, a host-side `php artisan`, a CI step, or any topology without
  `env_file`. It is a live trap, just not the one I described.
- `config/branding.php` is the better design regardless: one resolution point instead of two,
  correct under every topology, and it satisfies larastan's `noEnvCallsOutsideOfConfig` rather than
  needing an exemption.
- The **empty-key half** of the Phase A finding is untouched by this correction. `.env.example`
  shipped `SEED_ADMIN_PASSWORD=` and `BRAND_NAME_AR=`, and `env('KEY', $default)` genuinely does
  return `''` for those regardless of caching or topology. The seeded admin really was created with
  `Hash::make('')`.

I would keep §0's rule exactly as written — application code should read `config()`, not `env()` —
but the justification should be "it is topology-independent and single-sourced", not "the mechanism
was inert in production".

### The sweep

`app/` contains **11 `env(` occurrences; 7 are comments** (mostly ones I wrote in Phase A explaining
this very rule). Widening to `getenv(` / `$_ENV` / `$_SERVER` adds one more site. Every hit triaged:

| Site | Verdict |
|---|---|
| `Domains/Certification/Models/CertificateSetting.php:49` | **LIVE DEFECT — fixed.** See below. |
| `Console/Commands/ValidateEnvironment.php:60` | **Correct as written.** A validator whose job is to inspect the raw environment; already carries a written phpstan exemption. Console-only. |
| `Platform/Features/Services/FeatureFlagService.php:135` | **Correct as written, and deliberately so.** Its docblock states it reads `$_ENV`/`$_SERVER`/`getenv` rather than config *specifically so the kill-switch keeps working after `config:cache`*. It also treats `''` as unset. This is the right design for an emergency flag override. |
| `Platform/Shared/Support/Env.php:36` | The helper itself. Exempted with a written reason in Phase A. |

### The one live defect: certificate issuer

`CertificateSetting::defaultIssuerName()` read `env('CERTIFICATION_ISSUER', '')` **at request time**,
and it was a *precedence inversion* on top of a duplicated resolution path:

1. `env('CERTIFICATION_ISSUER')` — the operator's explicit override
2. brand company name → brand name
3. `config('certification.issuer.name')` — **which already resolves `CERTIFICATION_ISSUER`**

Step 1 and step 3 resolved the same key two different ways, in two places, with different fallback
chains — exactly the duplication §0 says to avoid. It arose in Phase A: the config key was added
without the pre-existing `env()` read being removed, leaving two resolvers for one key. Where step 1
returned null (config cached, value not reaching the process as an environment variable), an
operator's deliberate issuer was silently replaced by the brand on every certificate.

**Fixed** by splitting the config into an explicit `issuer.override` and the existing last-resort
`issuer.name`, and having the model read `config('certification.issuer.override')`. Precedence is
preserved; there is now one resolution point.

**Tests** — `CertificateIssuerResolutionTest` (5). Both directions are asserted so neither half can
silently die: override beats brand; brand is used when no override; brand *name* is used when there
is no company name; the issuer is never empty; and — the actual regression guard — setting **only**
the environment variable while the config key is empty (the shape `config:cache` leaves behind) must
**not** reach the issuer.

### A tooling gap, closed

You noted Deptrac skips references it cannot resolve, so a green run does not prove imports are
valid. I wrote a scanner for it (maps every declared class **and namespace** under `app/`,
`database/`, `tests/`, then checks every `use App\…`) and ran it over **2753 files**:

**0 genuinely unresolvable imports.** A first pass reported 57, all of which were Filament's
`use …\FooResource\Pages;` — namespace imports, which are valid PHP. I refined the scanner rather
than reporting false positives. Your `IdentitySeeder` fix is in place and points at the new path.

Worth keeping as a CI step; it is ~40 lines and catches a class of error nothing else here does.

### B0 verification

| Gate | Result |
|---|---|
| `vendor/bin/pint --test` | passed, 0 files |
| `vendor/bin/deptrac analyse` | **0 violations** |
| `vendor/bin/phpstan` (changed files) | **0 errors** |
| `php artisan config:validate` | runs; reports the local dev config against production rules, as designed |
| Tests — Certification + Branding + Shared unit | **107 passed** |

---

## PHASE B — White-label completion

### B1 — certificate rendering consumes the branding record

`BrandSetting.certificate` (background, logo, signature, stamp, QR position, font, colours, margins)
was admin-editable and **read by zero production code**: an academy could configure its certificate
in the branding screen and every certificate it issued would ignore all of it.

| File | Change |
|---|---|
| `Shared/Branding/Data/CertificateBrand.php` *(new)* | Render-ready DTO. `fallback()` is deliberately image-free — an unconfigured instance gets a plain certificate, never somebody else's logo. |
| `Shared/Branding/Contracts/BrandProfilePort.php` | New `certificate()`. Not locale-aware: images, colours and margins are identical in every language, so a locale parameter would be a lie. |
| `Branding/Adapters/BrandProfileAdapter.php` | Implements it under the same contract as `profile()` — read-only, memoised, degrades to defaults rather than throwing. A stored-but-blank value inherits the default (the same empty-vs-absent trap as the `BRAND_*` keys). |
| `Certification/Services/CertificateVariableRenderer.php` | Consumes it. Precedence: per-template `design` → instance branding → built-in default. Adds a `stamp_image` token and exposes `brand_font` / `brand_text_color` / `brand_accent_color` / `brand_qr_position` so a template can lay itself out in the academy's colours. |

The per-certificate signature deliberately outranks branding: it is the person who signed *this*
award, not the academy's default signatory. The admin **preview** resolves branding identically, so a
designer sees the academy's real logo rather than a blank.

**Tests — `CertificateBrandingRenderTest` (8), asserted at the RENDERER**, not the settings model
(reading a value back out of the model proves only that the database round-trips, which was never in
doubt). Includes the negative half: with nothing configured the built-in defaults render and there is
*no* image, so the positive assertions cannot pass on a renderer that merely echoes its input.

### B2 — vendor name removed from persisted data

`company_certificate_branding` stored `helbaron_only` / `company_logo_and_helbaron` as **column
values** in `products` and `company_entitlements`, with `helbaron_only` as the `products` default —
another company's name inside every customer's own database. Worse, the strings were compared **raw**
in `CertificatePolicyResolver` and `Certificate`, so the vendor name was load-bearing in logic:
renaming the data without finding those two sites would have silently changed behaviour rather than
failing loudly.

- Enum renamed to `platform_only` / `company_and_platform`; `company_name_only` untouched (it never
  named the vendor). Labels resolve the instance brand through the port.
- Both raw comparisons replaced: `CertificatePolicyResolver` asks the enum (`includesCompanyLogo()`);
  `Certificate` uses a named constant with the coupling documented, because Domains may not import a
  Contexts enum.
- **The historical migration was decoupled from the enum.** `2026_08_16_000100` referenced
  `CompanyCertificateBranding::HelbaronOnly->value`. An applied migration must not depend on a mutable
  application enum, or renaming a case changes what history does — or stops it resolving at all. It
  now writes the literal it meant at the time, and the rename is applied forward.
- **Reversibility verified with real data in both tables:** apply → rollback (rows *and* the column
  default restored to the old values) → re-apply. Probe rows cleaned up afterwards.

**Tests — `CompanyCertificateBrandingValuesTest` (9)**, including that the raw **database column**
holds the neutral value (the cast would hide a wrong string on disk), that a platform-only
certificate is not treated as company-branded (the exact regression the raw comparison would have
caused), and that no vendor-named value survives anywhere in either table.

### B3 — frontend: 189 occurrences across 65 files

**Which values route which way** — the question the brief asked me to answer explicitly:

| Value | Source | Why |
|---|---|---|
| Brand name, company, support email, address, logos, theme | **Branding API** (`GET /api/v1/branding`) | Must be changeable per instance without a rebuild. `NEXT_PUBLIC_*` is inlined at build time and would be frozen into the bundle — the Next.js equivalent of the `config:cache` trap. |
| Site URL, API base URL | `process.env` at build time | Genuinely per-deployment infrastructure, not brand. |
| Everything in `src/config/*.ts` and the dictionaries | `{brand}` token, interpolated at render | Build-time constants: a name written there is identical on every instance. |

Mechanism, so the brand reaches copy without threading it through every call site:

- **`t()` interpolates `{brand}` automatically.** `BrandingProvider` wraps `I18nProvider`, so the
  brand is available where translations resolve. The dictionaries carry `{brand}`; no call site changed.
- **`pickLocale()` and `localized()`** — the two central config-copy helpers — interpolate too, with
  an optional brand and a **generic fallback**. Worst case renders "Academy"; a vendor name cannot
  render. `ContentPage` passes the real brand, upgrading every marketing page built on it at once.
- **`buildBrandedMetadata()`** wraps the SEO mapper for the 6 pages that use it; 9 pages with
  `export const metadata` became async `generateMetadata` via `brandedMetadata()`. `buildMetadata`
  itself stays sync and pure — it is the mapper under test, and making it async would have forced
  every existing caller and assertion to change for no gain.
- **`src/lib/branding/api.ts`** — the hand-maintained duplicate of the backend defaults is now a
  genuinely generic offline fallback (blank address, blank support email, `preset: "default"`). It
  previously fell back to the **vendor's identity**, so a customer instance showed another company's
  brand whenever the API hiccupped. The theme colours stay: they mirror `globals.css`, so the
  fallback remains a visual no-op.
- Root layout: `siteConfig.description` was used **unbranded in 4 places** (description, OG, Twitter,
  JSON-LD) and is now interpolated.
- Auth layout: brand from context, and the hardcoded `© 2026` is now `new Date().getFullYear()` — it
  was already wrong for any later year.
- Course covers: `HELBARON · PRESS`, `· INSTITUTE OF PRACTICE` and the `HEL` press-code prefix derive
  from the brand. These are rendered **into the generated artwork**, not merely beside it.

**Method (rule 9).** Every file was named from a generated list and backed up first. The sweep was
split into a **SAFE** class (vendor name inside a localized `{ en, ar }` value, or a comment — those
render through an interpolating helper) and a **MANUAL** class (raw metadata strings, bare JSX), which
was reported and never rewritten, because a `{brand}` token in a raw string would render literally to
a user. That split caught a real defect: `comparison-page.tsx` indexes localized values directly
instead of using a helper, so its tokens *did* render literally — found by a test, then fixed by
routing it through `localized()` with the resolved brand.

**Residual, deliberate:**
- **Session cookie names** (`helbaron_session`, `helbaron_authed`, 6 sites) — renaming these logs out
  every existing session. **E1 already changes session cookie behaviour and lifetimes**, so doing both
  together means ONE forced-logout event instead of two. Deferred to E1 by design, not overlooked.
- `why_helbaron` in `brand-section-block.tsx` — the compatibility shim described under B4.

### B4 — seeders and payment descriptors

- **Payment descriptors.** Six call sites built `HElbaron order <id>` by hand — the text a customer
  sees on their **bank statement**, and a plausible chargeback trigger on a white-labelled instance.
  A new `PaymentDescriptor` resolves the brand and **truncates the BRAND, never the reference**: the
  reference reconciles the payment, and a mangled one turns a support question into an investigation.
  Verified: `Acme Academy order ord_12345` becomes `Acme A order ord_12345` at a 22-character limit.
  The limit is configurable (`COMMERCE_DESCRIPTOR_MAX_LENGTH`) because gateways differ.
- **`StaticPagesSeeder`** — 35 brand literals, 16 vendor email addresses and the hardcoded
  "hubs in Cairo, Dubai, and Riyadh" across 9 pages in **both locales**, all resolved from the brand
  at seed time. Verified: generating the pages with a fictional academy yields **zero** vendor strings
  and the academy's own support address.
- **Homepage** (`HomepageSection`, `BlockType`), **NavigationSeeder**, **BlogSeeder**,
  `ContractTemplateFactory` and `CommerceSeeder` all resolve the brand. The contact strip's email,
  phone and address now come from the brand record instead of the vendor's.
- **`why_helbaron`** is a persisted `homepage_sections.key` — a structural identifier the seeder, the
  admin resource and the frontend all switch on. Renamed to `why_us` by a reversible migration, and
  **both keys are accepted** by the frontend and the admin label, so an instance whose database is
  ahead of or behind its bundle keeps rendering instead of falling through to an unknown-block branch.
- **`CatalogSeeder`'s five invented instructors** — plausible names, real-sounding biographies,
  vendor-domain addresses, all sharing the password `password`. On a customer instance they read as
  genuine faculty. They are now unmistakably placeholders, and C1's `install:academy` will not run
  this seeder at all.
- `HealthController`'s service identifier, the log correlation service name, the APS gateway's
  `noreply@helbaron.test`, and the vendor defaults in `config/{database,horizon,session,demo}.php`
  are all neutral now.

### B5 — infrastructure naming

Renamed the **safe** identifiers: nginx upstreams and rate-limit zones (`lms_web`, `lms_api`,
`lms_auth`, `lms_checkout` — 2 definitions, 7 references and 4 zone references, all verified
consistent), the CI service-container database, and the `composer.json` / `package.json` package
names. JSON validity re-checked on both manifests.

**Deliberately NOT renamed, and why:** Docker **named volumes** (`helbaron-pgdata`,
`helbaron-redisdata`), container names, and the `HELBARON_IMAGE` env vars. Renaming a named volume
**detaches the existing production data** — it does not move it — and the image variables are wired
into the deploy pipeline. That needs an explicit migration step (a documented backup/restore, or a
`docker volume` copy), which belongs with D4's compose work rather than being buried in a white-label
rename. Carried into Phase D.

### Phase B acceptance — the end-to-end check

Run exactly as the brief specifies: a fictional academy in a scratch `.env`, structural seeders only,
and **`config:cache` before scanning** — the step that would have caught the Phase A defect.

```
=== config cached? YES
=== surfaces carrying the fictional brand: 9
    brand name, company name, email footer, email signature, certificate issuer,
    payment descriptor, admin panel brand, mfa issuer, rendered certificate
=== VENDOR STRINGS FOUND: 0
```

The scan is not a spot check: it walks **every text/varchar/json column of every seeded table**
(`ILIKE '%helbaron%'`) plus each runtime surface named in the brief. Zero hits, with the config
cached and the brand supplied only through `.env`.

`rg -i 'helbaron' apps/web/src apps/api/app apps/api/config` now returns only:
- **migrations** — the historical `helbaron_only` literal and the rename map. Correct: these describe
  what the database used to hold.
- **the `why_helbaron` compatibility shim** — deliberate, so an instance mid-upgrade keeps rendering.
- **session cookie names** — deferred to E1 by design (see B3).
- **explanatory comments** describing the defects that were fixed.

### Phase B verification

| Gate | Result |
|---|---|
| `php artisan test` (serial, chunked) | **2122 passed, 0 failed** |
| `vendor/bin/pint --test` | **passed, 0 files** |
| `vendor/bin/deptrac analyse` | **0 violations** |
| `vendor/bin/phpstan analyse` | 288 errors — **0 newly introduced** (baseline 318; 30 cleared) |
| `npm run lint` | clean |
| `npm run typecheck` | clean |
| `npm run test` | **161 files, 856 tests, all passed** |
| `npm run build` | **succeeds** — 86 static pages generated |
| Migration reversibility | both new migrations verified apply → rollback → re-apply, with data |

Backend per chunk: Unit **292**; Admin→Branding **450**; Catalog→Config **515**; Coupons→I18n **192**;
Identity→Live **286**; Marketing→Reviews **236**; Search→Timezone **151**.

### Two defects the gates caught in my own work

Recording these because they are the reason the gates are run per chunk rather than once at the end.

1. **`NavigationSeeder` — `$brand` injected into the wrong method.** I put the resolution in `run()`
   while the nav tree is built in `definitions()`, producing `Undefined variable $brand`. Caught by
   three Navigation tests immediately after the change, fixed in place.
2. **`HomepageSectionResource` — match-arm syntax inside an array literal.** I wrote
   `'why_us', 'why_helbaron' => 'Why us',`, which is valid PHP but means something else entirely in
   an array: it creates a numeric key holding the string `'why_us'` plus one mapping, so **`why_us`
   would have had no label at all** — the exact key the migration renames to. PHPStan flagged it as a
   return-type mismatch; the underlying bug was behavioural. Written as two explicit entries with a
   comment naming the trap.

A third was caught by a test rather than a gate: `comparison-page.tsx` indexes localized values
directly instead of using a helper, so its `{brand}` tokens rendered **literally**. This is precisely
the failure mode the SAFE/MANUAL split in B3 existed to prevent, and it is why the MANUAL class was
never rewritten automatically.

### Not done in Phase B, and why

- **Session cookie renames** — deferred to E1 so there is one forced-logout event rather than two.
- **Docker volume / container / image-variable renames** — renaming a named volume detaches existing
  production data. Needs an explicit migration step; carried into Phase D with D4's compose work.
- **Marketing copy remains hardcoded.** Tokenising `{brand}` removes the vendor's *name* from every
  surface, but the comparison tables, persona pages and advisory copy are still the vendor's
  *positioning*, written in code. An academy would want to rewrite that, which is a CMS concern
  rather than a white-label one — out of scope here, and worth naming as a product decision.

---

# PHASE C — Fleet operations

## The number you asked for

> *After `install:academy` exists, what is the shortest honest list of manual steps to stand up a
> brand-new academy from nothing?*

**Eight steps, one of which is interactive, and none of which require knowing anything about this
codebase.** Measured by doing it — the drill below was run against a genuinely empty database, not
described from the source.

| # | Step | Who | Notes |
|---|---|---|---|
| 1 | Point a DNS record at a host, terminate TLS | infra | not the product |
| 2 | Provision Postgres + Redis (or use the compose stack's own) | infra | not the product |
| 3 | `cp .env.production.example .env` and fill it in | ops | **~20 values**, down from 34 — see below |
| 4 | `./scripts/deploy.sh` | ops | validates config, migrates, rolls the stack |
| 5 | `php artisan install:academy --brand=… --company=… --support-email=… --timezone=… --currency=… --admin-email=…` | ops | **the interactive one**: prompts once, hidden, for the admin password |
| 6 | Upload logo, favicon and certificate assets in /admin → Branding | academy | |
| 7 | Set the tax rate for the market | academy | no rate is assumed |
| 8 | Create or import the catalogue | academy | |

Steps 1–2 are infrastructure and exist for any product. **Steps 3–5 are the whole of the
software-specific work**, and step 5 is one command.

Step 3 shrank because `install:academy` writes the academy identity into the *database*, and the
database wins on every surface that renders through `BrandProfilePort`. The nine `BRAND_*` keys are
now pre-install defaults rather than required settings. The template says so.

### The honest caveats

- **Step 5 cannot be fully non-interactive**, by design. `identity:create-admin` never accepts a
  password as an argument, so it cannot land in shell history or a process listing. `--skip-admin`
  scripts everything else and leaves the password for a separate run.
- **`APP_NAME` must still match the academy.** The MFA issuer — the label in a learner's
  authenticator app — is read from `IDENTITY_MFA_ISSUER` → `BRAND_NAME_EN` → `APP_NAME` at
  config-cache time, on a path that must not query for a branding row. It is the one identity
  surface the database cannot own. `install:academy` now **detects the mismatch and prints exactly
  what to set**, rather than leaving it to be found in a customer's screenshot six weeks later.
- **Step 7 is a real gap, not a rounding error.** `CommerceTaxSeeder` seeds a Saudi 15% VAT rate; it
  is deliberately excluded from the install because it is right for one market and wrong for the
  next. Selling into a second country means someone sets a tax rate by hand in /admin.

**Verdict on the question behind the question:** three software steps per customer, one command, no
codebase knowledge required. That scales to thirty. What does not yet scale is step 3's twenty
values and the per-instance infrastructure in steps 1–2 — that is a provisioning-automation problem,
not a product problem, and it is the same work for the eleventh customer as for the first.

## C1 — `php artisan install:academy`

`app/Console/Commands/InstallAcademyCommand.php`. Migrate → brand → seed → administrator →
`config:validate`, refusing to report success if the last step fails in production.

**It runs ten structural seeders, and the list is the point.** The checklist previously told an
operator to run seven `db:seed --class=…` commands by hand. That list was wrong in two ways I only
found by running it:

- **Incomplete.** `NotificationsSeeder` (every email and in-app template), `AiPromptSeeder` and
  `SeoSeeder` were missing. An academy installed by following the documented procedure had **no
  notification templates and an empty sitemap**.
- **Mis-ordered.** `BrandingSeeder` came *after* `NavigationSeeder`. Several seeders resolve the
  academy name *as they write*, so anything seeded before the branding row existed captured the
  environment fallback instead — permanently, because those seeders are `firstOrCreate` and never
  rewrite an existing row.

I reproduced the second one in my own first draft (see *Defects the drill caught*, below).

Refuses to run twice: the marker is the existence of an administrator, read from the role pivot
rather than the `User` model, because `app/Console` is Deptrac's `Platform` layer. `--force` re-runs
the structural set on a live instance; nothing is deleted and the administrator is left alone.

Input is validated **before anything is written** — an operator who mistypes a timezone gets an
error and an untouched database, not a half-installed instance.

### Two design decisions worth stating

**Seeders are referenced by string, not imported.** Importing the Identity / Notifications / Homepage
/ AI seeders from `app/Console` would be a new Deptrac violation, and the rule is that the baseline
never grows. A string reference is invisible to Deptrac *and* to the stale-import scanner, so that
choice is paid for explicitly: the command verifies every entry resolves before any of them runs, and
`InstallAcademyCommandTest` reads the shipped constant reflectively to assert each is a real
`Seeder` — plus that **no content seeder can enter the list**.

**Branding is written through a new port, not the model.** PHPStan's architecture rule flagged
`BrandSetting` being touched from the `Kernel` context, correctly. Rather than suppress it I added
`BrandInstallerPort` (Shared) + `BrandInstallerAdapter` (Branding). It is a *separate* port from
`BrandProfilePort` on purpose: that one documents itself as "read-only by construction… safe on
queued mail and certificate rendering", and bolting a `save()` onto it would make that sentence
untrue for every hot-path caller.

## C2 — Validate before you migrate, and guard the workers

Two changes were asked for. A third was necessary, and it is the largest finding in this phase.

**`config:validate` now runs before the migration** in `scripts/deploy.sh`, with `--no-deps` so the
check cannot itself start the `migrate` service as a dependency. A second `--strict` pass prints
advisory problems without blocking. The gate sits before the migration because the migration is the
first irreversible thing the script does.

**The boot guard now covers queue workers.** `guardProductionConfig()` exempted the entire console
via `runningInConsole()`, so `php artisan horizon` booted happily against a configuration the web
container had already refused to serve — the site down while the workers kept draining the queue,
charging cards and issuing certificates with nobody watching. The decision moved into
`ConfigGuardScope` (Shared) so it is directly testable, and it is an explicit allow-list: workers are
guarded, and `config:validate`, `env:validate`, `migrate`, `tinker` and `install:academy` are not —
a guard that blocks the diagnosis makes the outage longer.

### The finding: production has never run with a cached config

`scripts/deploy.sh` warmed the framework caches like this:

```bash
$COMPOSE run --rm api php artisan config:cache
```

`docker compose run --rm` creates a **throwaway container** whose filesystem is discarded the moment
the command exits, and the `api`, `horizon` and `scheduler` services mount **no volume** over
`bootstrap/cache`. The cache was written where no serving process could ever read it. All three
`*:cache` lines were no-ops, on every deploy, since the script was written.

This matters beyond the wasted work:

- every request has been re-parsing ~35 config files and rebuilding the route table;
- and `config/branding.php` opens with a comment reading *"`scripts/deploy.sh` runs `php artisan
  config:cache` on every production deploy… any `env()` call made OUTSIDE the config directory
  therefore returns NULL in production."* That reasoning is correct, the whole of B0 rests on it, and
  it was describing something that **was not actually happening**. The B0 fixes were right; the
  hazard they defend against was latent rather than live.

Fixed at the only place it can be fixed correctly: `apps/api/infra/php/docker-entrypoint.sh` builds
the three caches inside each container that will serve, from that container's own environment, then
`exec`s the given command. Caches always match the code in the image, and there is no window where a
cache built for one release is read by another. It fails closed — a container that cannot compile its
config does not start, which is visible in `docker ps`; a container serving on a half-built cache is
not.

**This is the highest-risk change in Phase C** and I want it flagged as such: it turns config caching
*on* in production for the first time. I verified all three commands succeed against this branch, and
the `env()`-outside-config sweep from B0 is what makes it safe. Rollback is one line — delete the
`ENTRYPOINT` from the Dockerfile.

## C3 — `env:validate` covers every gateway

Two defects, one obvious and one not.

**Six of seven gateways were unchecked.** Only Stripe was validated. An instance configured for
Moyasar, Paymob, HyperPay, Tap or APS with no credentials at all reported *"Environment OK"* and
failed on the customer's first checkout.

**The Stripe check read the wrong config.** It asserted `services.stripe.secret`, while
`GatewayManager` builds its adapter from `commerce.gateways.stripe.*`. Two resolution paths for one
setting — the same shape as the certificate-issuer inversion in B0. Both happen to read the same
environment variable today, so it passed *for the wrong reason*; renaming either side would have
unhooked it silently.

The requirement map is derived from what each adapter actually reads, not from what the config
declares. Paymob is expressed as an any-of (`hmac_secret` **or** `webhook_secret`) because that is
how its webhook verification falls back; demanding both would be a false failure. Environment
variable *names* are derived by convention rather than kept in a second table, and a test asserts
every derived name really appears in `config/commerce.php` — so a rename cannot leave the command
telling an operator to set a variable that does not exist. Another test asserts the map covers
exactly the gateways the application configures, so adding an eighth gateway without covering it
fails.

21 tests, including the negative half for all six real gateways.

## C4 — `GET /api/v1/version`

`config('app.version')` **did not exist**. Laravel 12 ships no `config/app.php`, and nothing had
added one, so the two places that read the key (`/api/v1/health` and the Filament panel) both fell
through to a hardcoded `'1.0.0-rc.1'` literal. Every instance in the fleet reported the same version
forever, whatever it was running.

`config/app.php` is a deliberately **partial** file — Illuminate merges it over the framework's own
app config, so it adds three build-provenance keys without displacing `cipher`, `providers`,
`locale` or anything else. A test asserts that merge still happens, because if it ever stopped, this
file would take down encryption and provider registration with it.

`APP_VERSION` → `APP_RELEASE` → literal, one chain. `.env.production.example` has always shipped an
`APP_VERSION` key that **nothing read**; it is now the first link rather than dead text.
`APP_BUILD_SHA` and `APP_RELEASE` are baked in at image build time by CI (`docker/build-push-action`
`build-args`), which is the only moment the commit is knowable.

The endpoint reports service, version, commit, build time, environment, and migration
applied/pending/up-to-date counts — the last being how you catch a deploy that pulled the new image
while its migration step silently failed: healthy, serving, and wrong.

**It reports counts and never migration names.** My first version returned the newest applied
migration, and the vendor-string test failed on it: the newest migration on this branch is
`2026_08_30_000400_rename_why_helbaron_homepage_section_key`. That would have put the vendor's name
on a public URL of every customer academy. Migration filenames are free text written by developers
and can never be trusted to be white-label.

## C5 — Backups that survive a deploy, and fail loudly

**The target moved out of the deployment checkout.** `docker-compose.prod.yml` bind-mounted
`./backups`, which is inside the directory Dokploy replaces on every deploy: the dumps were written
to the one directory guaranteed to be destroyed by the next release. It is now
`${BACKUP_TARGET:-helbaron-dbbackups}` — a named Docker volume by default, and compose treats a value
starting with `/` as a bind mount, so the same variable covers "absolute host path on storage you
copy off-box". `scripts/backup.sh` and `scripts/verify-backup.sh` had the same `./backups` default
and now agree on `/var/backups/academy-lms`, with a clear error when it is not writable.

**A failing backup is no longer silent.** Three changes, each verified by running the loop in an
Alpine container against a stubbed `pg_dump`:

1. **Retention only runs after a good dump.** Previously `find … -mtime +N -delete` ran on every
   iteration including failures — a broken backup job deleted its way to zero backups and reported
   nothing. Verified: with `pg_dump` failing, the old dump survives.
2. **A size floor.** A pipeline can exit 0 having written a truncated or empty file. Verified: a
   3-byte "dump" is treated as a failure, removed, and does not clear the sentinel.
3. **A healthcheck that fails on staleness.** The container reports **unhealthy** when a dump has
   failed or when none has landed in two intervals plus slack — the signal `docker ps` and the
   orchestrator can actually see. Verified in all four states: fresh success → healthy; sentinel
   present → unhealthy; stale success → unhealthy; never ran → unhealthy.

## Defects the drill caught in my own work

I ran `install:academy` against a genuinely empty database rather than trusting the test suite, and
it found two things the tests as first written did not.

1. **I branded after seeding.** My first draft ran the seeders and then wrote the brand row. The
   drill produced **nine static pages, forty-six nav items, twelve SEO records and six homepage
   sections carrying the wrong academy name** — permanently, because those seeders are
   `firstOrCreate` and no later run corrects them. This is the *same defect* the old checklist had,
   arrived at independently, which is a fair warning about how easy it is to hit. The brand row is
   now written before the seeders and the port's memo is dropped so they see it. Regression test:
   *"it brands the content it seeds, not only the settings row"*.
2. **Emails would have carried a different name from the site.** The email footer and signature fall
   through to `config('branding.email.*')` → `BRAND_COMPANY_NAME` → `BRAND_NAME_EN` → `APP_NAME`. An
   academy installed with `--brand` but without matching environment variables showed one name on the
   site and another at the bottom of every email. The installer now writes both into the row — while
   never overwriting wording an operator has edited by hand.

A third was caught by a test rather than the drill: the version endpoint leaking a migration
filename, described under C4.

One measurement correction worth recording: my Phase B acceptance script used
`config('branding.name.en')` as a **proxy** for "admin panel brand". `AdminPanelProvider` actually
resolves that through `BrandProfilePort`, so the proxy reported a leak the panel does not have. The
script now mirrors the provider exactly. The proxy was too pessimistic rather than too optimistic,
but a measurement that does not read the real source is not a measurement.

## Phase C verification

Final drill — empty database, `install:academy`, then the full acceptance scan (every text/varchar/
json column of every seeded table, plus each runtime surface):

```
=== surfaces carrying the fictional academy's brand: 8
    brand name, company name, email footer, email signature, certificate issuer,
    payment descriptor, admin panel brand, rendered certificate
=== VENDOR STRINGS FOUND: 1
    SURFACE mfa issuer = <from the dev box's APP_NAME>
```

The one remainder is the env-resolved MFA issuer described above, and the command now warns about it
by name. **Zero database rows** carry a vendor string — with no `BRAND_*` environment variable set at
all, the academy identity coming entirely from the one install command.

| Gate | Result |
|---|---|
| `php artisan test` (serial, chunked) | **2194 passed, 0 failed** (Phase B: 2122; +72 from Phase C) |
| `vendor/bin/pint --test` | **passed** |
| `vendor/bin/deptrac analyse` | **0 violations**, baseline unchanged |
| `vendor/bin/phpstan analyse` | 263 errors — **0 newly introduced** (identical to the Phase B baseline) |
| Install drill on an empty database | migrate + 10 seeders + brand + validate, exit 0 |
| `config:cache` / `route:cache` / `event:cache` | all three succeed against this branch |
| Backup loop (Alpine, stubbed pg_dump) | 3 failure modes and the healthcheck's 4 states verified |
| Frontend | **untouched in Phase C** — no `apps/web` file changed |

Backend per chunk: Unit **316**; Admin→Branding **450**; Catalog→Config **522**; Coupons→I18n **192**;
Identity→Live **286**; Marketing→Reviews **277**; Search→Timezone **151**.

## Not done in Phase C, and why

- **`.env.example` still sets `APP_NAME=HElbaron`.** That is the *local development* template, not the
  production one (`.env.production.example` is neutral), so it is not a customer-facing leak — but it
  is what made the first drill look like a failure. Worth a decision in Phase D rather than a silent
  change here.
- **Per-instance provisioning is still manual.** Steps 1–3 of the list above are the same work for
  every customer. Automating them is a provisioning concern (Terraform/Ansible/Dokploy templates),
  not something that belongs in this codebase.
- **`bootstrap/app.php` still reads `env()` directly** for `TRUSTED_PROXIES` and `APP_TRUSTED_HOSTS`.
  Both are mirrored into `config/security.php` with identical keys and defaults, and the mirror is
  documented, so there is no divergence today. It now matters more than it did — config caching is
  actually on — but changing bootstrap is a riskier edit than its payoff justifies inside this phase.
  Flagging it for Phase D.
- **Docker volume / container / `HELBARON_IMAGE` renames** — still carried to D4, unchanged from
  Phase B. The new `helbaron-dbbackups` volume is named to match its neighbours and should be renamed
  with them rather than separately.

---

# BLOCKER — bootstrap/app.php

Closed, but the diagnosis needed correcting in two places before it could be. Both corrections make
the underlying defect **worse**, not smaller, so the conclusion stands and the fix is in.

### `clear_env` is `no`, and it is set by the base image

> *"php-fpm's clear_env defaults to yes; it is set nowhere in this repo and there is no pool config
> file at all."*

Measured in `php:8.3-fpm-alpine`:

```
/usr/local/etc/php-fpm.d/docker.conf:12:clear_env = no      <- active
/usr/local/etc/php-fpm.d/www.conf:448:;clear_env = no       <- commented, the upstream default
```

There are four pool files, and the official image ships `docker.conf` setting it explicitly. So FPM
workers **do** inherit the Compose `env_file` values. This is the same measurement you asked for in
Round 3 §0 and accepted then.

### The entrypoint is not what activates it — and `config()` in that closure would have caused it

I probed the actual boot sequence rather than reasoning about it. At the moment
`->withMiddleware()` fires:

```
AFTER app built            : configLoaded=NO
AT kernel resolve (closure): configLoaded=NO | cached=yes
config(security.trusted_proxies) at that point: 'CONFIG NOT BOUND'
```

`withMiddleware` registers on `afterResolving(HttpKernel::class)`, which runs **before the
framework's bootstrappers**. `config` is not bound, cached or not — so **the fix as specified would
have returned `null` and produced the empty proxy list in every environment**. That is the one part
of the review I could not implement literally.

`LoadEnvironmentVariables` has not run either, which is the real finding:

```
.env contains TRUSTED_PROXIES=*     ->  resolved TrustProxies = []   (uncached)
.env contains TRUSTED_PROXIES=*     ->  resolved TrustProxies = []   (cached)
```

**A value in a `.env` file never reached `trustProxies` at all** — not because of caching, and not
since my entrypoint. Only a real process environment variable ever did:

```
TRUSTED_PROXIES=10.0.0.0/8 as a process var, config cached  ->  resolved = ["10.0.0.0/8"]
```

So the Compose stack was insulated by accident (`env_file:` sets real process variables, and
`clear_env = no` passes them to workers). **Every `.env`-on-disk deployment was not** — including the
bare-metal path in `DEPLOYMENT_CHECKLIST.md`. Your consequences 1–3 are exactly right; the cause is
older and broader than the entrypoint, which changes nothing on this path.

### The fix

`trustProxies(at:)` takes a value, not a callable, so it cannot be deferred in place. The resolution
moved to `TrustedEdgeConfigurator::apply()` (Shared), called from `AppServiceProvider::boot()` —
config is loaded by then, and providers boot inside `Kernel::bootstrap()`, which completes before the
middleware pipeline runs. Measured after: `config = '*'` → `resolved = "*"`, cached and uncached.

`trustHosts(at:)` **does** take a callable, which `TrustHosts::hosts()` invokes per request, so that
one could stay in `bootstrap/app.php` — it now reads `config('security.trusted_hosts')` too. Two
neighbouring lines with different resolution semantics and no way to tell them apart by reading
them; both now go through config.

`env:validate` was a third resolution path for the same setting, reading the raw variable and
defaulting to `'*'` — the opposite of what the app did. It now reads the same config key, and an
unset value is an **error** rather than a warning, naming the consequence (every IP-keyed limit
sharing one bucket).

**The PHPStan exemption for that file is deleted, not moved.** Its stated justification was
"bootstrap/app.php applies it inline from env() with no config key", which is no longer true. The
ruleset shrank.

**Sweep:** `bootstrap/app.php` now contains **zero** `env()` calls; `bootstrap/providers.php` is a
plain array; `public/index.php` has none; there are no composer `autoload.files` entries. A test
asserts the file stays that way — with comments stripped, so the file can keep explaining the defect
it guards.

13 tests, including the two that matter behaviourally: a forwarded address from a configured proxy is
honoured, and one from an untrusted source is ignored.

---

# PHASE D — Correctness, performance, ops

## D1 — `HasSlug`

All four defects, plus slug history with permanent redirects.

**Uniqueness** now goes through the `Slug::unique()` helper that was already sitting ten lines below
the call that ignored it.

**The ASCII fallback** falls back to `public_id` — but the brief's premise is wrong and I want that
recorded, because it changes who was affected. Measured:

```
أساسيات البرمجة  -> 'asasyat-albrmg'     Arabic is transliterated, NOT affected
Ελληνικά         -> 'ellinika'           nor Greek
Русский          -> 'russkii'            nor Cyrillic
日本語コース      -> ''                   CJK, Korean, emoji and punctuation-only
课程 / 한국어 / 🎓 -> ''                   are what actually produce ''
```

Narrower than "most Arabic titles", and still a 500 on the second such record either way. Both halves
are datasets in the test file so the distinction cannot quietly rot.

**The empty-vs-absent bug** is fixed with `filled()` semantics, and the fallback walks on to the first
locale with content rather than stopping at `reset()`.

**The 23505 retry** is in, and writing it taught me something worth reporting: **the obvious
implementation cannot work on PostgreSQL.** Postgres aborts the entire transaction on any error, so
the retry's own uniqueness `SELECT` returns `25P02 current transaction is aborted`. The recovery
failed more confusingly than the thing it was recovering from. Each attempt now runs inside
`DB::transaction()`, which Laravel compiles to a `SAVEPOINT` when one is already open, so the failed
attempt rolls back to the savepoint and both the retry and any caller's transaction survive. Found by
testing it; the first version read correctly and was dead on arrival.

### Slug history — the product decision you approved

`slug_history` (`sluggable_type`, `sluggable_id`, `slug`) with a unique `(type, slug)`. Two jobs:

1. **Lookup.** `PublicCourseRepository` falls back to history, and the course page issues a
   `permanentRedirect` to the canonical slug. A renamed course keeps serving every old URL. Retired
   slugs do **not** bypass the published/visible scopes — a redirect, not a back door.
2. **Uniqueness.** A retired slug is never reissued to a different record. Without that, every
   historic link for the original course would resolve to somebody else's course — a wrong page
   served with a 200, which is worse than the 404 the redirect replaced. A record can still take back
   its own former slug.

History is scoped per model type, so a category and a course may each have owned `design`.

The table is read through the query builder rather than an Eloquent model, deliberately: the trait
compiles into eight models across five contexts, so a model would have been eight architecture
violations for a two-column lookup table that belongs to no context.

23 tests. Migration verified apply → rollback → re-apply with data.

## D2 — Enrollment admin

**The N+1 is gone**, and it is asserted by counting queries rather than by reading the code: halving
the rows must not halve the queries. `refsByIds()` resolves the whole page in one call, primed from
the records Livewire already holds, with a single-lookup fallback if that hook is ever unavailable —
so the cell is never wrong, only occasionally slower.

**`course.title` goes through `localized()`.** The raw scalar is blank for exactly the i18n-only
courses the column was changed to support, so the cell an admin needed was the one that stayed empty.
The course relation is eager-loaded, or fixing the learner N+1 would just have moved it.

**Search is restored** — by learner email *and* name, which is how anyone actually arrives at this
screen (a support ticket names an email). It goes through a new `UserLookupPort::idsMatching()`, so
Learning still never joins to the users table. LIKE wildcards in the search term are escaped: unescaped,
a single `%` would have turned a search into "show me everything".

**One thing I did not restore: sorting by learner.** Ordering across the context boundary needs a
join, which is what the port exists to prevent, and the alternative was importing the Identity model
and growing the Deptrac baseline. The capability the brief named — *find an enrollment by learner
email* — is search, and that works. Flagging it rather than quietly dropping it.

## D3 — BFF proxy

**Traversal is rejected, not sanitised.** `encodeURIComponent` does not encode `.`, and nginx
normalises the decoded URI downstream, so no amount of escaping in this process can fix it. Any
all-dots segment now 400s **before the fetch** — which matters, because this handler attaches the
user's bearer token before contacting the upstream. A dot *inside* a segment (`report.v2.pdf`) still
passes; over-blocking would break real routes.

**A 25s `AbortSignal.timeout()`**, mapped to **504** rather than 502 — a timed-out request may still
be executing upstream, which matters for a non-idempotent call; 502 would claim it never started.

**Non-JSON error bodies are normalised** into the error envelope, preserving the upstream status. An
nginx HTML 502 forwarded verbatim reaches a client that has already committed to `response.json()`,
so the parse throws and the real status disappears behind "unexpected token <". Successful non-JSON
responses (a CSV export) pass through untouched, and the API's own JSON errors are never rewritten —
they carry the field-level detail the UI renders.

**The cookie `Secure` flag follows the URL scheme**, not `NODE_ENV`. A staging build compiled with any
other value emitted a real Sanctum token without `Secure`, over HTTPS, to real users. Falls back to
`NODE_ENV` when the canonical URL is missing or malformed, so a typo cannot silently strip `Secure`
from a production cookie and `http://localhost` still works.

**The fetcher list is discovered, not hardcoded** — a regression test that stops growing with the code
has expired. The glob found more modules than the list did: **9 tests → 15**.

## D4 — Ops

| # | Was | Now |
|---|---|---|
| 1 | `lms-h-sbvbdl-*` aliases hardcoded in two files that had to agree | compose service names `api` / `web` |
| 2 | `env_file: [./.env]` gave postgres + db-backup `APP_KEY`, every gateway secret and `OPENAI_API_KEY` | the three or four DB variables, interpolated |
| 3 | `"8080:80"` published the plaintext origin on `0.0.0.0` | `127.0.0.1:8080:80` |
| 4 | redis unauthenticated — sessions, cache and the whole queue | `--requirepass`, required (no default), and the healthcheck authenticates too |
| 5 | healthcheck probed FPM `/ping`, which is enabled nowhere, so `cgi-fcgi` exited 0 regardless | executes the liveness route and greps the **body** |
| 6 | `nginx:1.27-alpine`, floating, while `resolve;` in `upstream` needs ≥1.27.3 | `nginx:1.27.4-alpine`; image build verified |
| 7 | no limits, no log rotation | every service capped at 10MB×3 logs and given a CPU/memory ceiling |
| 8 | image tags `1.0.0-rc.1` vs `VERSION` `1.0.0-rc.2` | both `rc.2`, asserted against the VERSION file |
| 9 | Filament assets baked into the **nginx** image | published by the API container into a shared volume at startup |

Item 5 was the one with teeth: `web` declares `depends_on: api: condition: service_healthy`, so a
wedged worker pool reported healthy and the frontend started against a dead API.

Item 9 replaces a silent failure with a visible one. Baked into the proxy image, a
`composer update filament/filament` without an nginx rebuild shipped **stale** assets — a subtly wrong
panel, not a 404, which is why it went unnoticed. The API container now copies them from its own image
on start (removing the old directory first, so a dropped asset stops being served), and a copy failure
logs an error and leaves a 404 rather than refusing to boot.

The rendered compose file is validated and the nginx image builds. 9 tests parse the compose YAML
rather than grepping it, so a parse failure is itself caught.

## Phase D verification

| Gate | Result |
|---|---|
| `php artisan test` (serial, chunked) | **2253 passed, 0 failed** (Phase C: 2194) |
| `vendor/bin/pint --test` | **passed** |
| `vendor/bin/deptrac analyse` | **0 violations**, baseline unchanged |
| `vendor/bin/phpstan analyse` | 263 errors — **0 newly introduced**; one obsolete exemption **removed** |
| `npm run typecheck` / `lint` | clean |
| `npm run test` | **163 files, 877 tests passed** |
| `npm run build` | succeeds |
| Migration reversibility | `slug_history` verified apply → rollback → re-apply, with data |
| `docker compose config` | renders clean; nginx image builds and passes `nginx -t` |

Backend per chunk: Unit **325**; Admin→Branding **450**; Catalog→Config **551**; Coupons→Http **192**;
Identity→Live **294**; Marketing→Reviews **290**; Search→Timezone **151**.

Twelve PHPStan errors appeared mid-phase and all twelve were fixed at source rather than suppressed:
eight cross-context violations from the history model (replaced with the query builder, model
deleted), three missing generics on the repository, and one untyped relation access in the admin
resource (`method_exists` narrowing, so no Catalog import).

## Not done in Phase D, and why

- **Sorting the enrollment table by learner** — see D2. Search, the capability actually named, works.
- **`.env.example` `APP_NAME`** — left, per your decision, with a comment in the file saying why so the
  next person does not "fix" it.
- **Docker volume / container renames** — still carried. `helbaron-filament-assets` is named to match
  its neighbours and should be renamed with them, not separately.
- **`REDIS_PASSWORD` is now mandatory.** Compose will refuse to start without it. That is deliberate,
  and it is in the checklist's new "stack changes an operator needs to know about" table along with
  the loopback port bind — the two changes that need action on an existing deployment.

---

# PHASE E — Defects the plan document records but never fixed

## E1 — "Remember me" now means something, in both directions

The checkbox was registered on the form, validated by its schema, and then dropped: the submit
handler never passed it, `auth-context` had no such parameter, `LoginRequest` did not accept it, and
the session route applied a hardcoded fourteen-day `Max-Age` unconditionally.

Threaded end to end — form → auth context → `sessionLogin` → BFF → API — and fixed in **both halves**,
because a cookie the browser discards is not the same as a credential the server has stopped
accepting:

| | unticked | ticked |
|---|---|---|
| **cookie** | no `Max-Age` — the browser drops it on close | 30 days |
| **token** | `identity.session.session_hours` (12h) | `identity.session.remembered_days` (30d) |

The server-side half matters for the case the cookie cannot cover: somebody declining to be
remembered on a shared machine is telling us the credential should be short-lived, and only the
token's own expiry bounds it if it escapes by another route.

**Defaults are the safe direction throughout.** Absent, `false`, `"false"`, `0`, `null` and `"no"` all
mean *not remembered* — only a real boolean `true` counts. Defaulting the other way would have
restored the defect for any client not yet updated.

Both cookies get the **same** lifetime. A persistent marker beside a session-scoped credential would
leave the client believing it is signed in after the browser dropped the token: the authenticated
shell renders, then 401s on its first request.

**The deferred cookie renames are folded in.** `helbaron_session` / `helbaron_authed` →
`lms_session` / `lms_authed`, and the names, lifetimes and `Secure` logic now live in one module
(`lib/auth/session-cookies.ts`) instead of being hardcoded across six files. One forced logout for
both changes, as planned.

23 tests (12 frontend, 11 backend), covering the lifecycle the brief named: fresh, expired, revoked,
logged out, and password reset. Password reset already revoked every token — asserted rather than
assumed, because that is the case where a long remembered session is most dangerous.

## E3 — Search has an index, verified in both scripts

`pg_trgm` plus six GIN trigram indexes, in a reversible migration. Benchmarked on **400,000 rows,
half English and half Arabic**, folded the way `ArabicTextNormalizer` folds it:

| query | before | after | plan |
|---|---|---|---|
| english, common term (25k hits) | **55.3 ms** | **19.0 ms** | Bitmap Index Scan |
| arabic, common term (25k hits) | **62.8 ms** | **20.2 ms** | Bitmap Index Scan |
| arabic, single word (25k hits) | **62.0 ms** | **11.3 ms** | Bitmap Index Scan |
| english, selective (1 hit) | ~55 ms | **8.4 ms** | Bitmap Index Scan |
| arabic, selective (1 hit) | ~62 ms | **5.8 ms** | Bitmap Index Scan |

**Arabic behaves as well as English here**, which was not a given and is why you asked. The reason is
that both sides of the comparison are folded through the same normaliser before they meet, so the
trigrams are drawn from the same alphabet the query uses.

### The defect this nearly shipped

My first draft indexed `lower(column)` for the four `ilike` call sites, reasoning that the index must
match the expression. Measured on a 50k-row table, that is exactly backwards:

```
index on `name`        + `name ILIKE '%x%'`  ->  Bitmap Index Scan   (used)
index on `lower(name)` + `name ILIKE '%x%'`  ->  Seq Scan            (ignored)
```

pg_trgm's GIN opclass serves the ILIKE operator directly. The `lower()` version would have created
four indexes that are maintained on every write and **never once read** — the exact trap I had
written a comment claiming to avoid. All six are bare columns, and a test holds them there.

### One thing you should know about, unresolved by design

**A two-character query still sequentially scans.** Trigrams need three characters:

```
'%بر%'   (2 chars)  ->  Seq Scan        68.4 ms
'%برم%'  (3 chars)  ->  Bitmap Index    14.9 ms
```

`catalog.search.min_query_length` is **2**, so the shortest query the product permits is precisely
the one the index cannot serve. Raising it to 3 fixes the cost and removes real capability — `ai`,
`ux`, `hr` are legitimate course topics in English, and short forms exist in Arabic too. That is a
product decision, not an engineering one, so it is flagged rather than taken. It is a one-line config
change when you want it.

**The semantic arm is still unindexed by design.** `PortableVectorStore` pulls up to
`search.vector.max_candidates` (2000) rows into PHP and scores cosine there. At a catalogue of a few
thousand chunks that is acceptable — it is one bounded query plus arithmetic. It stops being
acceptable somewhere in the low tens of thousands of chunks, and the fix at that point is `pgvector`,
not a bigger candidate cap: raising the cap trades correctness for latency, because a fixed candidate
window silently drops relevant results once the corpus exceeds it. Worth a decision before a customer
imports a large library, not after.

## E2 — Digest: withdrawn, not wired

**The decision: hide it.** Wiring a digest properly means a scheduled command, per-user timezone
windows, a dedup ledger keyed on (user, period) so a retry or a mid-run deploy cannot send twice,
retry handling, bilingual templates, operator controls and an audit trail. That is a feature with its
own delivery semantics, not a contained fix — and a badly-built digest is a duplicate-email incident,
which is worse than no digest.

What that means concretely:

- the control is **removed from the preferences UI**;
- the API **refuses** `digest_frequency` with a validation error rather than silently ignoring it —
  a client that sends it is told, instead of being left believing a preference was saved;
- it is **omitted from the response**, which is what makes the withdrawal single-switched: the
  frontend renders from the API's shape, so there is no second place to remember;
- `openapi/notifications.yaml` marks it deprecated **with the reason**;
- the **column and the enum stay**, so nobody's stored choice is destroyed.

One config flag brings it back — `notifications.digest.enabled` — and a test asserts that it does, so
"withdrawn" cannot rot into "deleted" and whoever builds the delivery side does not have to
rediscover the switch.

**A related find.** `config/notifications.php` already contained `'digest' => ['enabled' => true]` —
hardcoded, read by nothing, and asserting the opposite of the truth. My gated key was silently
overridden by it (duplicate array key, later wins) and I only noticed because the test that should
have got a 422 got a 200. There is now one key.

### Scoping, because the plan document overstates this

"All automation is inert" is not true. `AutomationRunner` **is** subscribed and does run rules for its
two CRM lead events. What is actually dead is narrower: `WorkflowEngine::handleEventForUserId()` and
`DigestService::pendingForUserId()` have zero callers, and the **scheduled** trigger type is
declared (`AutomationTriggerType::Scheduled`, the `scheduled_automations` table, the
`ScheduledAutomation` model) while `AutomationRunner` hard-filters `trigger_type = 'event'`.

Both classes are kept — the query and rule-evaluation halves are correct and are a real head start —
but their docblocks now say plainly that nothing calls them. The old comment on `DigestService` said
delivery "goes through the dispatcher on a schedule (future scheduler wiring)", which reads as a
description of something that exists.

## E4 — Performance

**Certificate PDFs are pre-rendered on the queue** when the certificate is minted. The first download
used to render synchronously inside the HTTP request, so the learner who has just finished a course —
the one most likely to click — paid the whole render and held a php-fpm worker for its duration; a
cohort finishing together turned that into a queue of blocked workers. The inline path **remains as a
fallback**, so a dead queue, a failed job or a certificate issued before this existed still produces a
certificate rather than an error. The job removes the cost from the common path; it does not become a
new way to fail.

**The Chromium provider now fails at deploy time, not at the learner's download.**
`CERTIFICATION_PDF_PROVIDER=browsershot` is a stub that throws unconditionally — spatie/browsershot
is not a dependency and Chromium is not in the image — so selecting it produced a 500 on *every*
certificate download, discovered by somebody at the moment they had earned something.
`ProductionConfigValidator` refuses it now. It tests for the package rather than hardcoding "this
provider is broken", so the day somebody installs Browsershot and Chromium the guard stops objecting
on its own.

**The readiness N+1 is batched.** `describe()` ran per quiz lesson — one query plus a count
sub-select each — on every publish attempt *and* every load of the instructor readiness panel; a
twenty-quiz course spent forty queries answering one question. A new `describeMany()` on
`LessonAssessmentPort` resolves the whole course in one call, with identical semantics for a stale
reference (absent from the result ≡ `describe()`'s null).

Adding to that port tripped an architecture test that guards it against drifting into a generic
repository — working exactly as designed. It was updated deliberately, with the reasoning recorded:
`describeMany` is the batch form of a question the port already answered, not a new capability, and
the line that would genuinely break the port is a `list()`, a `find()`, or anything about grading.

Announcement fan-out and the event-listing N+1 were left alone — already fixed, as the brief says.

## Phase E verification

| Gate | Result |
|---|---|
| `php artisan test` (serial, **one invocation**) | **2288 passed, 0 failed** (7281 assertions, 1096.8s, exit 0) |
| — of which **Admin→Branding** | **454 passed, 0 failed** — the chunk omitted below; green, no Phase E regression |
| `vendor/bin/pint --test` | passed |
| `vendor/bin/deptrac analyse` | **0 violations**, baseline unchanged |
| `vendor/bin/phpstan analyse` | 263 errors — **0 newly introduced** |
| `npm run typecheck` / `lint` | clean |
| `npm run test` | **164 files, 890 tests passed** |
| `npm run build` | succeeds |
| Migration reversibility | trigram migration verified: indexes dropped, `pg_trgm` deliberately kept |
| Search benchmark | 400k rows, English **and** Arabic, five query shapes |

### The corrected count, and what the first one missed

The figure originally reported here was **1834 passed, 0 failed**, presented without qualification as
though it were the whole suite. It was about 80% of it. The re-run below is a **single serial
invocation** — it cannot omit a chunk — and the per-directory counts reconcile exactly.

| Chunk | Re-run | As reported in Phase E |
|---|---|---|
| Unit + Architecture | **325** | 325 ✓ |
| Feature Admin→Branding | **454** | *(never run)* |
| Feature Catalog→Config | **550** | 557 |
| Feature Coupons→I18n | **192** | 192 ✓ (labelled "Coupons→Http") |
| Feature Identity→Live | **305** | 305 ✓ |
| Feature Marketing→Reviews | **302** | 295 |
| Feature Search→Timezone (incl. root files) | **160** | 160 ✓ |
| **Total** | **2288** | **1834** |

The Phase E figures sum to exactly 1834, which reproduces the reported number and confirms the
omission was precisely one chunk — Admin→Branding — and nothing else. Two chunks differ by 7 in
opposite directions (Catalog→Config −7, Marketing→Reviews +7); the net is zero and the boundaries
were described by label rather than by explicit directory list, which is the most likely explanation.
That ambiguity is itself the argument for the single-invocation rule in
`docs/ops/LOCAL_TEST_ENVIRONMENT.md` §4b.

**Admin→Branding is green: 454 passed, 0 failed.** Nothing in Phase E — the session-cookie rename or
the notification-preferences payload change — broke the admin or branding surfaces.

One caveat worth stating: the first attempt at this re-run used `--parallel`, and produced 9 failures
at 8 workers and 26 at 4. Every one was `SQLSTATE[53200] out of shared memory`, not an assertion —
the lock-table ceiling now fixed in `docker-compose.yml`. `tests/Feature/Tax` was run alone as a
control and passed 6/6. The serial run above is the trustworthy number.

Three existing tests changed, each a genuine consequence rather than a green-run edit: the login test
now asserts the fourth `remember` argument (and a new case for the ticked box); the notification-centre
test no longer sends the withdrawn field; the port-narrowness architecture test was updated
deliberately, as its own comment demands.

---

# Closing — for a reviewer picking this branch up cold

## The lesson from four rounds most likely to save you

Two of the worst near-misses across these rounds were not code defects. They were **verifications
that looked green because they had been scoped down until they fit.**

- A Phase E gate reported "1834 passed, 0 failed" without qualification, reading as the whole suite.
  It was about 80% of it: the Admin→Branding chunk — 450 tests — had simply not been executed. A
  missing chunk produces no output, so nothing flags it. It was caught only because a reviewer added
  up the per-chunk numbers from two phases and compared the totals.
- The parallel test gate had been passing for months partly because **it was not being asked to do
  the whole job at once — the same class of problem as the missing chunk.** Chunked runs never put
  enough concurrent schema drops in flight to exhaust PostgreSQL's shared lock table, so the ceiling
  was never reached and the defect was never found. Asked to run everything in one invocation, it
  failed immediately.

Both share one shape. When a gate is green, check what it was actually asked to do before believing
it. The dangerous failure mode is not a red run — it is a green one that covered less than you
thought, and says nothing about the difference.

The concrete consequences are committed: the suite now has a single-invocation full run documented
in `docs/ops/LOCAL_TEST_ENVIRONMENT.md` §4b, and the lock ceiling is fixed in `docker-compose.yml`
rather than tuned by hand on one machine.

## Read these five things first

1. **`docs/CLAUDE_CODE_HANDOFF_ROUND3_RESULT.md`** (this file) — sections in phase order. The Phase C
   "shortest honest list" table is the one that answers "can we sell this to ten customers".
2. **`apps/api/app/Console/Commands/InstallAcademyCommand.php`** — the whole per-customer story in one
   file, including which seeders are structural and why the order matters.
3. **`apps/api/app/Platform/Shared/Http/TrustedEdgeConfigurator.php`** — the most surprising defect in
   four rounds, with the measurement that found it. If you read one comment, read this one.
4. **`apps/api/infra/php/docker-entrypoint.sh`** and `scripts/deploy.sh` — production had never once
   run with a cached config. This is the highest-risk change on the branch and the easiest to roll
   back (one `ENTRYPOINT` line).
5. **`apps/api/app/Platform/Shared/Traits/HasSlug.php`** — four defects in twenty lines, and the
   savepoint note about why the obvious retry cannot work on PostgreSQL.

## The three changes most likely to surprise an operator

- **`REDIS_PASSWORD` is now required.** Compose refuses to start without it.
- **The plaintext origin binds to `127.0.0.1`.** Point the TLS terminator at loopback.
- **Everyone is signed out once** — session cookies were renamed and remember-me changed together,
  deliberately, to spend one forced logout rather than two.

All three are in the "stack changes an operator needs to know about" table in
`docs/ops/DEPLOYMENT_CHECKLIST.md`.

## Outstanding across all four rounds

Nothing is silently deferred; each of these is named in its phase section too.

| Item | Where it stands |
|---|---|
| **Docker volume / container / `HELBARON_IMAGE` renames** | Deferred since Phase B. Renaming a named volume detaches production data, so it needs an explicit migration step. `helbaron-dbbackups` and `helbaron-filament-assets` were named to match their neighbours and should move with them. |
| **Marketing copy is still the vendor's positioning** | Names are tokenised; the comparison tables, persona pages and advisory copy are still written in code. Owner's decision: leave it. A CMS concern, not a white-label one. |
| **Digest delivery** | Withdrawn, not built. One config flag from returning; `config/notifications.php` lists what must exist first. |
| **Scheduled automations** | Declared and dead. `AutomationRunner` filters to `event`. Event automation works. |
| **Browsershot / Chromium PDF** | Still a stub. Now refused at deploy time instead of at the learner's download. |
| **`search.vector` scoring in PHP** | Acceptable at a few thousand chunks; needs `pgvector` in the low tens of thousands. Decide before a large import, not after. |
| **`catalog.search.min_query_length = 2`** | The shortest permitted query is the one trigrams cannot serve. Raising it to 3 is a one-line config change that costs real capability — your call. |
| **Sorting the enrollment admin by learner** | Not restored. Needs a join across the boundary `UserLookupPort` exists to prevent. Search — the capability actually named — works. |
| **`bootstrap/app.php` reads config in a lazy callable** | Correct and measured, but it is the one place where "when does this run" is load-bearing. Anything added there needs the same care. |
| **`.env.example` names the vendor** | Deliberate, per your decision, and the file now says so. |
| **Schema size — 189 tables, ~988 relations** | Not something to act on now, but worth knowing. It is large for a product sold as one instance per customer, and every `RefreshDatabase` test pays to drop and rebuild all of it — which is the reason the suite takes as long as it does, and the reason parallel runs exhaust PostgreSQL's lock table. It will only grow. |
| **CI has not run the test suite since 2026-07-20** | The workflow last ran on 2026-08-11 and failed at Pint, before the Migrate and Tests steps. Pint passes on this branch, so the next run gets further — and would have hit a second, latent failure: `phpunit.xml` forces `DB_DATABASE=helbaron_test` (since 227b969, 2026-08-16) while the CI service container created `lms`. Fixed here in `ci.yml`; unverified until CI actually runs, which needs a push. |

## What I would look at first if something breaks

- **Everyone logged out / admin panel unstyled** → the E1 cookie rename, and the Filament asset
  volume (D4.9). Both are first-deploy-only.
- **502 from nginx** → upstreams are now addressed by compose service name; check for anything still
  pinned to the old `lms-h-sbvbdl-*` aliases.
- **A container refusing to start** → the entrypoint fails closed when the config cannot be compiled.
  That is the intended behaviour, and `php artisan config:cache` in the container will say why.
- **Rate limits behaving oddly** → `TRUSTED_PROXIES` must be set, and it is now read from config. An
  unset value means trust nothing, which is deliberate and is an `env:validate` error.

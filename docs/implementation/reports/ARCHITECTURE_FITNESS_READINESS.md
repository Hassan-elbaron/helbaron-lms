# Architecture Fitness — Readiness Report

> Chief Enterprise Architect review. Scope: **turn the architecture fitness functions into mandatory repository gates** — verification only. No code, configuration, workflow, or dependency was modified; nothing was installed. Every finding is grounded in repository files inspected on 2026-07-08. Any claim requiring tool execution is marked **"Not verifiable from repository"** (this environment has no PHP/Composer/Node runtime).

---

## Executive Summary

The fitness *design* is sound and complete on paper: Deptrac layers + ruleset, PHPStan level 6 + Larastan + four custom DDD rules, Pint, Rector (report-only), and CI jobs that wire each gate. **But two gates cannot execute as committed**, and the flagship boundary gate cannot pass:

1. **Deptrac is not a declared dependency.** It is absent from `apps/api/composer.json` (`require-dev`) **and** from `composer.lock`. CI's `architecture` job and the `composer arch` script invoke `vendor/bin/deptrac`, which will not exist after `composer install`. This is a **hard-blocking** failure — the `architecture` job has no `continue-on-error`.
2. **The Deptrac baseline is an empty placeholder** (`skip_violations: {}`). Even once Deptrac is installed, the first run will report **every existing cross-context coupling** (~66 peer-context sites + ~69 `Identity\User` sites per `ARCHITECTURE_GAP_ANALYSIS.md` / `DEPENDENCY_CLEANUP_PLAN.md`) as a violation and **fail** — not the intended "fail only on NEW violations" behavior.
3. **Rector is not a declared dependency** either (absent from `composer.json` and `composer.lock`). Its CI step is `continue-on-error: true` (non-blocking) so it will not break the build, but the step will error and `composer rector`/`composer qa` will fail locally.

PHPStan (present transitively via Larastan), its non-empty baseline (511 ignored entries), the four custom architecture rule classes, and Pint are all present and correctly referenced. The web fitness chain (ESLint boundaries + typecheck, blocking; Playwright/axe, non-blocking) is wired.

**Verdict: NO-GO** to declare the gates "mandatory" until the three blocking items above are resolved. The fixes are declaration/regeneration only — no business logic, API, or schema change. **Risk: HIGH** (the primary architecture gate is non-functional as committed).

---

## Dependency Audit

Source: `apps/api/composer.json` (`require-dev`) cross-checked against `apps/api/composer.lock` (`"name":` entries) and `apps/web/package.json` (`devDependencies`).

| Required tool | Declared in composer.json | Present in composer.lock | Status |
|---------------|:---:|:---:|--------|
| **deptrac** (`deptrac/deptrac` / `qossmic/deptrac`) | ❌ No | ❌ 0 matches (neither name) | **MISSING — blocking** |
| **phpstan** (`phpstan/phpstan`) | ⚠️ Not direct | ✅ Present (transitive via Larastan) | OK (declare-direct recommended) |
| **phpstan extensions** (Larastan) | ✅ `larastan/larastan: ^3.0` | ✅ Present | OK |
| **rector** (`rector/rector`) | ❌ No | ❌ 0 matches | **MISSING — non-blocking step, but errors** |
| **larastan** | ✅ `larastan/larastan: ^3.0` | ✅ Present | OK |
| **pint** | ✅ `laravel/pint: ^1.13` | ✅ Present | OK |
| pest (test runner) | ✅ `pestphp/pest: ^3.0` (+ laravel plugin) | ✅ Present | OK |
| mockery / collision / faker | ✅ | ✅ | OK |

**Custom PHPStan architecture rules** (`apps/api/tests/Architecture/Rules/`): all present — `ContextResolver.php`, `NoCrossContextModelUsageRule.php`, `NoCrossContextEloquentAccessRule.php`, `NoBusinessLogicInFilamentResourceRule.php`, `NoBusinessLogicInControllerRule.php`.

**Web devDependencies** (fitness-relevant): `eslint ^9`, `eslint-plugin-import ^2.31`, `eslint-import-resolver-typescript ^3.7`, `@playwright/test ^1.49`, `@axe-core/playwright ^4.10`, `typescript ^5.6`, `vitest ^2.1` — all present.

**Observations**
- `composer.json` (modified 2026-07-07) is newer than `composer.lock` (2026-07-04); the delta is the added `scripts` (`arch`, `rector`, `qa`), which do not affect the lock hash. The declared `require-dev` set matches the lock (neither contains deptrac/rector), so the lock is internally consistent — the gap is that the two tools were **never added**.
- `phpstan/phpstan` resolves only because `larastan/larastan` depends on it. Functional, but pinning it directly in `require-dev` is best practice for gate reproducibility.
- **Per instruction, nothing was installed.** Required remediation commands are listed under *Required Actions*.

---

## Deptrac Status

Source: `apps/api/deptrac.yaml`, `apps/api/deptrac.baseline.yaml`.

| Aspect | Finding | Status |
|--------|---------|:---:|
| **Layers** | 20 layers: `Platform`, `Shared`, `Identity`, `IdentityContracts`, `Notifications`, `Administration`, `Media`, `AI`, `Integration`, `Search`, `Catalog`, `Authoring`, `Certification`, `Live`, `CRM`, `Learning`, `Commerce`, `Analytics`, `Instructor`, `Organization`. Future contexts declared as forward-compatible (empty dirs). | ✅ Sound |
| **Rules** | Every context/platform layer → `[Shared, IdentityContracts]` only; `Shared → (nothing)`; `IdentityContracts → [Shared]`; `Identity → [Shared, IdentityContracts]`. No allow-all, no shortcuts. Matches redesign 05 dependency matrix. | ✅ Sound, not weakened |
| **Collectors** | Directory-based. Identity correctly split: implementation via explicit subdir collectors **including `app/Platform/Identity/Tenancy/.*`**, contracts isolated in a separate `IdentityContracts` layer. `Platform` (composition root) collects Providers/Http/Console/Filament/Logging. | ✅ Correct |
| **Paths** | `./app` | ✅ |
| **Exclusions** | Deptrac declares **no** path exclusions. `app/*/openapi/*` (excluded in PHPStan and Rector) is **not** excluded here; those files collect into their owning context layer. Low risk (generated specs), but inconsistent with the other tools. | ⚠️ Minor |
| **Baseline** | `deptrac.baseline.yaml` is the **empty placeholder** (`skip_violations: {}`) with a header instructing one-time regeneration. **Never generated.** | ❌ **Blocking** |
| **Installed / runnable** | `vendor/bin/deptrac` absent (not in lock). | ❌ **Blocking** |

**Readiness:** **NOT READY.** Two blockers: the binary is not installed, and the baseline is empty. With an empty baseline, Deptrac would fail on all pre-existing coupling rather than only new violations — defeating the burn-down model in `DEPENDENCY_CLEANUP_PLAN.md`. Whether the ruleset produces the expected violation set is **Not verifiable from repository** (cannot run Deptrac here).

---

## PHPStan Status

Source: `apps/api/phpstan.neon`, `phpstan-architecture.neon`, `phpstan-baseline.neon`.

| Aspect | Finding | Status |
|--------|---------|:---:|
| **Level** | `level: 6` (raised 5→6 in A1-S02), parallel 4 processes. | ✅ |
| **Includes** | `vendor/larastan/larastan/extension.neon`, `phpstan-baseline.neon`, `phpstan-architecture.neon`. | ✅ |
| **Architecture rules** | 4 rules registered in `phpstan-architecture.neon`; all four classes + `ContextResolver` helper exist under `tests/Architecture/Rules/`. | ✅ |
| **DDD / context-boundary rules** | `NoCrossContextModelUsageRule`, `NoCrossContextEloquentAccessRule` (context boundaries / forbidden cross-context model imports); `NoBusinessLogicInFilamentResourceRule`, `NoBusinessLogicInControllerRule` (layering). | ✅ Present, not weakened |
| **Forbidden imports** | Enforced at AST level by the two cross-context rules, complementing Deptrac. | ✅ |
| **Baseline** | `phpstan-baseline.neon` present and **non-empty**: 3067 lines, 511 `message:` entries. Existing findings absorbed; strictness only increases. | ✅ |
| **Paths / excludes** | Analyses `app, config, database, routes`; excludes `app/*/openapi/*` (all three roots). | ✅ |
| **Installed / runnable** | PHPStan present (transitive); Larastan direct. | ✅ (declare phpstan direct — minor) |

**Readiness:** **READY (pending live run).** Two items are **Not verifiable from repository**: (1) that the custom rule classes (namespace `Tests\`, mapped via `autoload-dev`) are autoloaded when PHPStan runs — they are not under the analysed `paths`, so their loading depends on the dev autoloader being present at run time; (2) that the committed baseline still matches the current rule set + level-6 delta (i.e., analysis is green). Confirm with `composer stan` on a machine with PHP.

---

## Pint Status

Source: `apps/api/pint.json`, `composer.json` scripts, `ci.yml`.

- Config: `{ "preset": "laravel" }`. Minimal, valid.
- Scripts: `pint` (fix), `lint` (`pint --test`, check-only).
- CI: `api` job runs `vendor/bin/pint --test` as a **blocking** step.
- Installed: `laravel/pint` in `require-dev` **and** lock. ✅

**Readiness:** **READY.** Whether the working tree is Pint-clean is **Not verifiable from repository** (requires running `pint --test`).

---

## Rector Status

Source: `apps/api/rector.php`, `composer.json`, `ci.yml`.

- Config: dry-run / **report-only** by design — `withPhpSets(php83)`, prepared sets `deadCode`, `codeQuality`, `typeDeclarations`; skips `app/*/openapi/*`. Explicitly documented as non-destructive; no CI auto-apply.
- CI: `api` job step `Rector (dry-run, report-only)` with **`continue-on-error: true`** (non-blocking).
- Script: `composer rector` → `rector process --dry-run`; also chained into `composer qa`.
- Installed: `rector/rector` **absent** from `composer.json` and `composer.lock`. ❌

**Readiness:** **NOT INSTALLED.** Non-blocking in CI (the step is allowed to fail), but: the CI step will error, `composer rector` fails, and `composer qa` fails at the `@rector`… (actually at `@arch`, then would also hit rector) stage. Since Rector is intended as report-only, this is **non-blocking for the gate** but should be fixed for the toolchain to be coherent.

---

## Workflow Status

Source: `.github/workflows/ci.yml`, `.github/workflows/adr-validation.yml`.

**`ci.yml` jobs**

| Job | Fitness steps | Blocking? | Executes as committed? |
|-----|---------------|:---:|---|
| `api` | Pint `--test`; PHPStan `analyse`; Rector dry-run (`continue-on-error`); migrate; Pest | Pint ✅, PHPStan ✅ blocking; Rector non-blocking | Pint/PHPStan **yes**; **Rector step errors** (binary missing) but tolerated |
| `architecture` | **Deptrac `analyse`** | **Blocking (no continue-on-error)** | ❌ **FAILS — `vendor/bin/deptrac` missing** |
| `web` | ESLint (incl. `import/no-restricted-paths` boundaries) — blocking; `tsc --noEmit` — blocking; vitest; build | ✅ blocking | Yes (deps present). Note `lint` = `next lint` reading `eslint.config.mjs` flat config |
| `e2e` | Playwright smoke + axe | `continue-on-error: true` (non-blocking) | Yes, non-blocking |
| `image` | prod image build | needs `[api]` | build-only |

**`adr-validation.yml`**: ADR-reference check on PRs → `scripts/adr-link-check.sh` (present) driven by `config/architecture/adr-watch.yaml` (present). ✅ Executes on `pull_request`.

**Are all architecture checks actually executed?**
- **Deptrac: wired but cannot succeed** — missing dependency (and empty baseline). The single most important boundary gate is effectively **red/non-functional**.
- **Rector: wired, tolerated-failing** — missing dependency; non-blocking by design.
- PHPStan (with custom DDD rules), Pint, ESLint boundaries, tsc, ADR check: **wired and blocking/executing.**

**Missing / weak mandatory gates (no workflow edits made — reported only):**
1. **Deptrac job is broken** → not a real gate until the dependency + baseline land. **(Blocking.)**
2. **axe/a11y is non-blocking** (only inside the `continue-on-error` `e2e` job) → accessibility is not yet enforced.
3. **ADR check not part of required status on `ci.yml`** — it is a separate workflow; ensure it is marked a **required check** in branch protection. **Not verifiable from repository** (branch-protection settings are not in the repo).
4. **`composer qa` aggregate** cannot pass end-to-end (chains `@arch` and effectively `@rector`, both missing) — so a single local "gate" command is not yet usable.
5. Whether these jobs are **required for merge** depends on GitHub branch-protection rules, which are **Not verifiable from repository.**

---

## Blocking Issues

1. **Deptrac not installed.** Absent from `composer.json` (`require-dev`) and `composer.lock`. The blocking `architecture` CI job and `composer arch` will fail (`vendor/bin/deptrac` not found). *(Deptrac Status; Dependency Audit; Workflow Status.)*
2. **Deptrac baseline empty.** `deptrac.baseline.yaml` = `skip_violations: {}` (placeholder). Once installed, Deptrac fails on all existing coupling instead of only new violations. Must be regenerated once and committed. *(Deptrac Status.)*
3. **Rector not installed.** Absent from `composer.json` and `composer.lock`. CI step tolerates it (`continue-on-error`), but the step errors and `composer rector`/`composer qa` fail. *(Rector Status.)* — Non-blocking for the merge gate; blocking for toolchain coherence.

---

## Required Actions

To be executed by the user on a machine with PHP 8.3 + Composer (nothing was installed or modified here). These add tooling/regenerate a baseline only — **no** business logic, API, or schema change.

1. **Add the two missing dev dependencies** (from `apps/api/`):
   ```bash
   composer require --dev deptrac/deptrac:^2.0 rector/rector:^2.0
   # optional but recommended: pin PHPStan directly
   composer require --dev phpstan/phpstan:^2.0
   ```
   Commit the updated `composer.json` **and** `composer.lock`.
2. **Generate the Deptrac baseline once, then commit it:**
   ```bash
   vendor/bin/deptrac analyse --formatter=baseline --output=deptrac.baseline.yaml
   git add deptrac.baseline.yaml && git commit -m "chore(arch): seed Deptrac baseline"
   ```
3. **Verify the full gate suite is green locally** before declaring the gates mandatory:
   ```bash
   composer lint      # pint --test
   composer stan      # phpstan level 6 + custom rules
   composer arch      # deptrac (fails only on NEW violations)
   composer rector    # dry-run report
   php artisan test
   (cd ../web && npm run lint && npm run typecheck && npm test)
   ```
4. **Confirm PHPStan autoloads the custom rule classes** (`Tests\Architecture\Rules\*`) at run time; if not resolved, add a `scanDirectories: [tests/Architecture]` (or equivalent) entry — verify empirically.
5. **Branch protection (GitHub UI, not in repo):** mark `architecture` (Deptrac), `api` (Pint + PHPStan + Pest), `web` (ESLint + tsc), and `ADR reference check` as **required status checks** on `main`. Decide whether to promote `e2e`/axe from non-blocking to required.
6. **(Optional, coherence):** once Deptrac/Rector are installed, re-run `composer qa` to confirm the aggregate command passes.

---

## Risk Level

**HIGH.** The primary architectural boundary gate (Deptrac) is non-functional as committed — missing dependency **and** empty baseline — so "mandatory architecture gates" are not actually enforced on merge today. Secondary risk: Rector step errors (tolerated) and unverified PHPStan rule autoloading. All remediation is additive (declare tools, regenerate baseline, set branch protection); **no** refactor, API, or schema risk. Once Actions 1–3 are done and verified green, residual risk drops to LOW.

---

## Go / No-Go

**NO-GO** — do not declare the architecture fitness functions "mandatory repository gates" in the current state.

**Rationale:** PHPStan (level 6 + 4 custom DDD rules + non-empty baseline), Pint, ESLint import boundaries, `tsc`, and the ADR check are correctly configured and executing. However, Deptrac — the flagship bounded-context gate — cannot run (uninstalled) and cannot pass (empty baseline), and Rector is uninstalled. These are the exact mechanisms that make the boundaries *enforceable*.

**Path to GO (all additive, no code/schema/API change):** complete Required Actions 1–3 (install `deptrac/deptrac` + `rector/rector`, commit `composer.lock`, generate + commit the Deptrac baseline), confirm the full suite is green (Action 3), and mark the jobs as required checks (Action 5). At that point the gates are genuinely mandatory and this report flips to **GO**.

---

## Validation

- All findings derive from inspected repository files: `apps/api/composer.json`, `composer.lock` (name-entry scan), `deptrac.yaml`, `deptrac.baseline.yaml`, `phpstan.neon`, `phpstan-architecture.neon`, `phpstan-baseline.neon` (line/entry counts), `pint.json`, `rector.php`, `.github/workflows/ci.yml`, `.github/workflows/adr-validation.yml`, `apps/web/package.json`, and the presence of `tests/Architecture/Rules/*`, `scripts/adr-link-check.sh`, `config/architecture/adr-watch.yaml`.
- Items requiring execution are marked **"Not verifiable from repository"**: results of `deptrac`/`phpstan`/`pint`/`rector`/tests runs, whether baselines currently produce a green run, PHPStan custom-rule autoloading at run time, and GitHub branch-protection (required-check) settings.
- No code, configuration, workflow, or dependency was modified; no package was installed. Only this file was created.

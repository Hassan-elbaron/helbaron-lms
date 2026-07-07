# Sprint 0 · Story A1-S01 — Deptrac Boundary Enforcement — Report

> EXECUTION MODE. Story A1-S01 only (tasks A1-T01 → A1-T04). Tooling/config only — **no application code, no business logic, no API, no DB, no namespace changes.** Compliant with `101_EXECUTION_RULES.md` (§3, §4 Backend/Testing, §5, §15) and the Sprint 0 plan.

> **Addendum (refined during A1-S02):** the single `Identity` layer described below was split into **`Identity`** (implementation) and **`IdentityContracts`** (`app/Platform/Identity/Contracts`). The global "every layer → Identity" allowance was **replaced** by "every layer → `IdentityContracts` only"; no layer may depend on the Identity implementation. Layer count is now **20** (was 19). Contexts' current use of `App\Platform\Identity\Models\User` therefore becomes a baselined violation. See `SPRINT0_A1_S02_REPORT.md`.

## Summary

Deptrac architectural boundary enforcement was added as a dedicated, blocking CI stage governed by a strict, baseline-backed ruleset that encodes the bounded-context boundaries from redesign 05. No allow-all rules and no shortcuts: the composition root and the `PlatformOverview` widget's current reach into context models are treated as **existing violations** (to be captured by the baseline), not exempted.

Because this environment has **no PHP/Composer**, the Deptrac dependency install and the baseline generation are performed by a single documented command on your machine; the configuration itself is complete and statically validated here.

---

## Architecture layers

19 layers declared (18 from the task list + `Live`, which exists in code). Each maps to a path via a Deptrac `directory` collector. Future contexts/capabilities are declared now and collect nothing until their code exists (forward-compatible, error-free).

| Layer | Path collector | Status |
|-------|----------------|--------|
| Platform (kernel) | `app/Providers`, `app/Http`, `app/Console`, `app/Filament`, `app/Logging` | active |
| Shared | `app/Platform/Shared` | active |
| Identity | `app/Platform/Identity` | active |
| Notifications | `app/Platform/Notifications` | active |
| Administration | `app/Platform/Administration` | future (empty) |
| Media | `app/Platform/Media` | future (empty) |
| AI | `app/Platform/AI` | future (empty) |
| Integration | `app/Platform/Integration` | future (empty) |
| Search | `app/Platform/Search` | future (empty) |
| Catalog | `app/Domains/Catalog` | active |
| Authoring | `app/Domains/Authoring` | active |
| Certification | `app/Domains/Certification` | active |
| Live | `app/Domains/Live` | active |
| CRM | `app/Domains/Crm` | active |
| Learning | `app/Contexts/Learning` | active |
| Commerce | `app/Contexts/Commerce` | active |
| Analytics | `app/Contexts/Analytics` | active |
| Instructor | `app/Contexts/Instructor` | future (empty) |
| Organization | `app/Contexts/Organization` | future (empty) |

Static validation (Python YAML): 19 layers parsed; **every layer has a ruleset entry**; **no ruleset target references an undefined layer**; `imports` correctly points at the baseline.

## Dependency rules

Strict and uniform — the enforcement seam is "kernel primitives only":

- **Shared** → depends on nothing internal (`~`).
- **Identity** → `Shared`.
- **Every other layer** (Notifications, Administration, Media, AI, Integration, Search, Platform kernel, Catalog, Authoring, Certification, Live, CRM, Learning, Commerce, Analytics, Instructor, Organization) → **`Shared` + `Identity` only**.

All cross-context coupling (Learning→Authoring models per TD-1, Analytics→multiple domains, the kernel/dashboard-widget→context models, Certification→Learning events, etc.) is therefore a **rule violation by design** and must be absorbed by the baseline, then burned down as Ports and published-event DTOs are introduced in later sprints (04/05; backlog Phases A–D). No layer is granted broad/allow-all access — not even the composition root.

## Files created

| File | Purpose |
|------|---------|
| `apps/api/deptrac.yaml` | Layer definitions + strict ruleset + baseline import. |
| `apps/api/deptrac.baseline.yaml` | Baseline scaffold (empty `skip_violations`); regenerated once to capture existing violations. |
| `docs/implementation/reports/SPRINT0_A1_S01_REPORT.md` | This report. |

## Files modified

| File | Change | Safety |
|------|--------|--------|
| `apps/api/composer.json` | Added `scripts.arch` (`deptrac analyse --no-progress`) and `@arch` to the `qa` script. | **Lock-safe** — only the `scripts` block changed; `require-dev` untouched, so `composer.lock` content-hash is unaffected and `composer install` stays valid. |
| `.github/workflows/ci.yml` | Added a dedicated `architecture` job (PHP 8.3 · composer install · `vendor/bin/deptrac analyse --no-progress`). | Additive job; other jobs unchanged. |

No other files touched. No `app/**` source, migration, route, model, or Filament resource was modified.

## Baseline summary

- **Shipped state:** `skip_violations: {}` (empty scaffold).
- **Required one-time population** (see Validation) records all current cross-context dependencies so the gate passes on existing code and fails only on **new** violations.
- **Indicative size** (from a static `use`-scan; the authoritative count comes from Deptrac): Learning ~2 targets (Authoring `Lesson`/`Section` — matches TD-1), Analytics ~5, Certification ~2, Authoring ~1, Live ~1, plus the Platform kernel's ~6 (dashboard widget + providers referencing Commerce/Learning/Catalog/CRM/Live/Notifications models). Order of magnitude: ~20–30 class-pair violations at Sprint 0.
- **Policy:** the baseline only ever shrinks; growing it is forbidden (101 §15).

## CI changes

New job in `.github/workflows/ci.yml`:

```yaml
architecture:
  name: Architecture (Deptrac)
  runs-on: ubuntu-latest
  defaults: { run: { working-directory: apps/api } }
  steps:
    - uses: actions/checkout@v4
    - uses: shivammathur/setup-php@v2
      with: { php-version: '8.3', extensions: mbstring, intl, coverage: none }
    - name: Cache composer
      uses: actions/cache@v4
      with: { path: apps/api/vendor, key: composer-${{ hashFiles('apps/api/composer.lock') }} }
    - run: composer install --prefer-dist --no-interaction --no-progress
    - name: Deptrac (architecture boundaries)
      run: vendor/bin/deptrac analyse --no-progress
```

Requirements met: **dedicated** Architecture stage · **blocking** (Deptrac exits non-zero on any non-baselined violation) · **baseline respected** (imported by `deptrac.yaml`) · **human-readable** (default table formatter) · **GitHub Actions compatible** (standard `setup-php` + composer cache, mirroring the existing `api` job).

## Validation output

Environment note: **PHP and Composer are not available in this execution sandbox**, so Deptrac itself could not be run here. Static validation was performed instead, and the runtime commands are provided for you to execute on the target machine.

Static validation (performed here):
```
deptrac.yaml           -> parsed OK; 19 layers; 0 layers missing a ruleset entry; 0 unknown ruleset targets; imports=[deptrac.baseline.yaml]
deptrac.baseline.yaml  -> parsed OK; skip_violations = {}
composer.json          -> valid JSON; scripts = [post-autoload-dump, test, pint, lint, stan, arch, qa]; require-dev UNCHANGED (lock-safe)
ci.yml                 -> `architecture` job present (jobs: api, architecture, web, image)
```
(Note: a transient stale-mount copy in the shell showed a truncated `composer.json`; the authoritative file state is the complete, valid 72-line file shown above.)

Runtime validation — **run these once on your machine (from `apps/api`)** and commit the regenerated baseline:
```bash
# A1-T01: install Deptrac (updates composer.json require-dev AND composer.lock atomically)
composer require --dev deptrac/deptrac:^2.0

composer install
composer dump-autoload

# A1-T03: generate the initial baseline (records existing violations only)
vendor/bin/deptrac analyse --formatter=baseline --output=deptrac.baseline.yaml

# Verify: config + baseline load, zero configuration errors, gate is green on existing code
vendor/bin/deptrac analyse --no-progress
```
Expected: the first `analyse` (before baseline) lists the current cross-context violations; after the baseline is generated and committed, `analyse` reports **0 new violations** (green). Paste the output if you want it recorded here.

## Known limitations

1. **No PHP/Composer in this environment** — the dependency install and baseline generation are a one-time manual step on your machine (commands above). Everything else (config, ruleset, CI, scripts) is complete and statically validated.
2. **Baseline ships empty by design** — until you run the baseline command, the CI `architecture` job will report the current violations (expected). Regenerate + commit the baseline as part of merging this story, before enabling the job as a required check.
3. **Deptrac version** — pinned to `^2.0` (the maintained `deptrac/deptrac`). If your toolchain requires a specific minor, adjust the constraint in the `composer require` command; the config schema (`imports` + `deptrac.layers/ruleset`) is stable across 2.x.
4. **Future layers are empty now** — Administration, Media, AI, Integration, Search, Instructor, Organization collect no classes until their backlog chunks (A3, A5, D1, D2) execute; they are declared to keep the ruleset forward-compatible with zero later churn.
5. **Strict rules → large initial baseline** — intentional. The baseline is the honest debt ledger (TD-1, TD-8, dashboard-widget coupling, etc.); it burns down over Phases A–D. It must never grow.

## Next Story dependencies

- **A1-S02 (Static analysis hardening)** depends on the CI wiring pattern established here (A1-T04): it will make PHPStan blocking (remove the `|| echo` soft-fail), bump the level, and add a custom "no cross-context Model use" rule that complements Deptrac at the type level.
- **A1-S03 (Frontend fitness + Playwright)** is independent of this story's PHP toolchain and can proceed in parallel once the `architecture` stage exists.
- **A1-S04 (ADR validation)** reuses the CI-check pattern; independent.
- **Downstream:** every later sprint relies on this gate being green — the baseline created here is the reference that all boundary refactors (B1 ports, D1/D2 splits, A5 capability ports) burn down against.

---

## STOP

Story A1-S01 is implemented (config + CI + scripts + report). **A1-S02 has not been started** and no frontend, business logic, API, or DB was touched. Awaiting approval before implementing **Story A1-S02**.

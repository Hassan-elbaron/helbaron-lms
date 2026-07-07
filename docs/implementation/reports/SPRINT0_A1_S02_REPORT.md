# Sprint 0 · Story A1-S02 — Static Analysis Hardening — Report

> EXECUTION MODE. Story A1-S02 only (tasks A1-T05 → A1-T07 + the requested Deptrac refinement). Tooling/config only — **no application business logic, no API, no DB, no namespace changes.** The custom PHPStan rule classes are static-analysis tooling (under `tests/`, not analysed as app code), consistent with the Sprint 0 plan and `101_EXECUTION_RULES.md` §4 (Testing/Backend), §5, §15.

> **Addendum (hardened during A1-S03):** the four custom rules were upgraded to classify classes by **inheritance, interfaces and PHP attributes first**, using file-path detection only as a fallback. `ContextResolver` now takes the PHPStan `ClassReflection`: Filament Resources are detected via an ancestor in `Filament\Resources\*` (plus marker interface/attribute), Controllers via an `Illuminate\Routing\Controller` ancestor / `*Controller` convention (plus markers), and the cross-context rules resolve the *current* context from the class namespace (path fallback). See `SPRINT0_A1_S03_REPORT.md`.

## Summary

Static analysis was hardened to complement Deptrac at the AST/type level: PHPStan raised to **level 6** (blocking in CI), four **custom architecture rules** added, and **Rector** integrated in dry-run/report-only mode. As a pre-implementation refinement, the Deptrac ruleset was tightened so that **contexts may depend only on Identity *contracts/ports*, never the Identity implementation**.

PHP/Composer are unavailable in this environment, so PHPStan/Pint/Rector could not be executed here; configuration is complete and statically validated, and the exact runtime commands are provided for your machine.

---

## Deptrac refinement (pre-implementation)

**Change:** removed the global `All Contexts → Identity` dependency; replaced with `All Contexts → IdentityContracts` only.

- **New layer `IdentityContracts`** collects `app/Platform/Identity/Contracts/.*`.
- **`Identity`** (implementation) now collects only its non-contract subdirectories (Actions, Database, Enums, Events, Exceptions, Filament, Http, Jobs, Listeners, Models, Notifications, Policies, Providers, Services) — Contracts is excluded so a contract class belongs to exactly one layer.
- **Ruleset:** `Shared: ~`; `IdentityContracts → [Shared]`; `Identity → [Shared, IdentityContracts]`; **every other layer (Platform kernel + all contexts + all capabilities) → `[Shared, IdentityContracts]`**. No layer is allowed to depend on the Identity implementation.
- **Effect:** the 69 current references to `App\Platform\Identity\Models\User` (and Identity Enums/Events) from contexts become **violations** captured by the Deptrac baseline (still to be generated per A1-S01), burning down when Identity exposes ports.

Static validation (authoritative file state): **20 layers**; every layer has a ruleset entry; **0 layers allow the `Identity` implementation**; all context/capability layers allow `IdentityContracts`; no unknown ruleset targets.

The A1-S01 report was updated with an addendum recording this refinement.

---

## PHPStan configuration

`phpstan.neon` changes:
- `level: 5 → 6` (stricter; adds missing-type/return-type breadth). **Strictness only increases.**
- Added include `phpstan-architecture.neon` (custom rules).
- Kept include `phpstan-baseline.neon` (existing 511-entry baseline preserved).
- Extended `excludePaths` to also skip `app/Contexts/*/openapi/*` and `app/Platform/*/openapi/*` (the same generated-OpenAPI exclusion already applied to `app/Domains/*`, now that contexts live under `Contexts`/`Platform`).

CI change (`.github/workflows/ci.yml`, `api` job): the PHPStan step is now **blocking** — the `|| echo "::warning::phpstan config pending"` soft-fail was removed.

## Custom rules

Registered in `phpstan-architecture.neon`; classes under `tests/Architecture/Rules/` (namespace `Tests\Architecture\Rules`, autoloaded via the existing `Tests\\` PSR-4 dev mapping — **no composer autoload/lock change**). A shared `ContextResolver` helper maps file paths and FQCNs to contexts and recognises Models/Controllers/Filament resources + persistence methods.

| Rule | Identifier | What it forbids | Node | Heuristic / basis |
|------|-----------|-----------------|------|-------------------|
| `NoCrossContextModelUsageRule` | `helbaron.crossContextModel` | Referencing another context's `\Models\` class | `Name\FullyQualified` | current-context (from file path) ≠ target-context (from FQCN) and target is a Model |
| `NoCrossContextEloquentAccessRule` | `helbaron.crossContextEloquent` | Static calls on another context's Model (`::query/where/find/...`) | `Expr\StaticCall` | class is a cross-context Model FQCN |
| `NoBusinessLogicInFilamentResourceRule` | `helbaron.filamentBusinessLogic` | Persistence calls inside `Filament/Resources/*` | `Expr\MethodCall` | method in the persistence denylist (save/update/create/delete/…) |
| `NoBusinessLogicInControllerRule` | `helbaron.controllerBusinessLogic` | Persistence calls inside `Http/Controllers/*` | `Expr\MethodCall` | method in the persistence denylist |

These are intentionally conservative (name/path heuristics) so they are robust without deep type inference; they map directly to 101 §5 forbidden actions and ADR-04. Static balance checks (braces/namespaces/imports) pass on all five files.

## Baseline impact

- **Existing baseline kept** (`phpstan-baseline.neon`, 511 entries) — nothing removed, so strictness is not reduced.
- Raising to level 6 **and** enabling the four rules surfaces new findings on existing code (cross-context `User`/model usage, any current persistence in controllers/resources, level-6 type gaps). To keep the gate green on existing code while failing on **new** violations, regenerate the baseline once (command below). The baseline **grows** to absorb the newly surfaced findings — this is expected when raising a level/adding rules and is **not** a strictness reduction (no existing ignore is deleted; the floor is higher).
- Policy (101 §15): the baseline may only shrink thereafter.

## Rector configuration

`rector.php` (new) — **report-only**:
- Paths: `app`; skips `app/*/openapi/*`.
- Sets: `withPhpSets(php83: true)` + `withPreparedSets(deadCode, codeQuality, typeDeclarations)`.
- Invoked via `composer rector` → `rector process --dry-run` (never applies changes).
- CI (`api` job): a **non-blocking** step (`continue-on-error: true`) runs `rector process --dry-run --no-progress-bar` for a suggestions report; it cannot fail the build.

`composer.json`: added the `rector` script only (lock-safe; `require-dev` untouched).

## Validation output

Environment note: **no PHP/Composer here**, so PHPStan/Pint/Rector were not executed. Static validation performed:

```
deptrac.yaml            -> 20 layers; every context/capability -> [Shared, IdentityContracts]; 0 layers allow Identity impl; Identity -> [Shared, IdentityContracts]; IdentityContracts -> [Shared]; no unknown targets
phpstan.neon            -> level 6; includes [larastan, phpstan-baseline, phpstan-architecture]; openapi excludes extended
phpstan-architecture.neon -> 4 rules registered (parsed OK)
rule classes            -> 5 files; balanced braces; declare(strict_types=1); correct namespace/imports
composer.json           -> valid (authoritative); scripts += rector; require-dev UNCHANGED (lock-safe)
ci.yml (api job)        -> PHPStan blocking (soft-fail removed); Rector non-blocking step added
```
(Note: the shell mount again served a stale/truncated copy of `deptrac.yaml`/`composer.json`; the authoritative file state — verified via the editor — is complete and correct. This is the documented file-tool/shell divergence, not a defect.)

Runtime validation — **run these on your machine (from `apps/api`)**:
```bash
# dev deps (updates composer.json require-dev + composer.lock atomically)
composer require --dev rector/rector:^2.0        # deptrac/deptrac already added in A1-S01
composer install && composer dump-autoload

# A1-T05/T06: regenerate the PHPStan baseline to absorb level-6 + custom-rule findings, then verify green
vendor/bin/phpstan analyse --memory-limit=1G --generate-baseline
vendor/bin/phpstan analyse --memory-limit=1G       # expect: [OK] No errors

# style + architecture (should already be green)
vendor/bin/pint --test
vendor/bin/deptrac analyse --no-progress

# A1-T07: Rector dry-run (report only; non-zero exit is fine / informational)
vendor/bin/rector process --dry-run
```
Expected: after baseline regeneration, `phpstan analyse` is green; `pint --test` green; `deptrac analyse` green (once its baseline is generated per A1-S01); `rector --dry-run` prints suggestions without modifying files. Paste outputs if you want them recorded.

## Known limitations

1. **No PHP/Composer in this environment** — PHPStan/Pint/Rector not executed here; the custom rule classes are statically checked (structure/braces/imports) but their runtime behavior must be confirmed by running PHPStan on your machine. If any rule errors on load, comment its line in `phpstan-architecture.neon` and report it.
2. **Custom rules are heuristic** — path/name based, not full type inference. `NoBusinessLogicIn{Controller,FilamentResource}Rule` match persistence **method names** on any receiver (may occasionally over-match, e.g. a `->create()` on a non-model); these land in the baseline and only block *new* occurrences. Static `Model::create()` in controllers is partially covered by the cross-context rule; same-context static persistence is a known gap to tighten later if desired.
3. **Baseline grows with the level bump** — expected and compliant (floor raised, nothing ignored removed). Regeneration is required before the gate is green.
4. **Rector set choice** — conservative, report-only. No auto-fix is wired; applying any suggestion is a separate, reviewed task.
5. **Deptrac baseline still pending** (from A1-S01) — generate it after installing `deptrac/deptrac`; the Identity-contracts refinement means it will also capture the `User`-model usages.

## Next Story dependencies

- **A1-S03 (Frontend fitness + Playwright)** — independent of this PHP toolchain; can proceed next once approved.
- **A1-S04 (ADR validation)** — reuses the CI-check pattern; independent.
- **Downstream:** the four custom rules + level-6 baseline are the type-level counterpart to Deptrac; later refactors (B1 Content Ports, the Identity ports, D1/D2 splits) burn down both baselines. The `IdentityContracts` seam introduced here anticipates the Identity-port work.

---

## STOP

Story A1-S02 is implemented (Deptrac refinement + PHPStan level 6 + 4 custom rules + Rector dry-run + CI wiring + report). **A1-S03 has not been started** and no frontend, business logic, API, or DB was touched. Awaiting approval before implementing **Story A1-S03**.

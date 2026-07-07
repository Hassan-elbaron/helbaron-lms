# Sprint 0 · Story A1-S04 — ADR Validation — Report

> EXECUTION MODE. Story A1-S04 only (tasks A1-T11, A1-T12). Governance tooling + documentation only — **no business logic, no API, no DB, no namespace changes.** Compliant with `101_EXECUTION_RULES.md` §7, §13, §16 and the Sprint 0 plan.

> **Addendum (refactored during Sprint 1 / A2-S01):** the watched paths are no longer hardcoded in the script. They now live in `config/architecture/adr-watch.yaml`, which `scripts/adr-link-check.sh` loads at runtime (env override `ADR_WATCH_FILE`). A `(^|/)Tenancy/` pattern was added so multi-tenancy foundation changes are watched. The ADR index (`docs/adr/INDEX.md`) was also extended with **Implementation Status** and **Sprint Target** per ADR. See `SPRINT1_A2_S01_REPORT.md`.

## Summary

Added the final Sprint-0 fitness control: an **ADR-reference check** that fails architecture-sensitive PRs lacking an ADR citation, and a complete **ADR Index** (ADR-01…ADR-20). The check is a self-contained, testable bash script wired to a dedicated GitHub Action; it ignores documentation-only PRs and emits clear, annotated failure messages. The script was executed locally against five scenarios and behaves correctly.

## Files created

| File | Purpose |
|------|---------|
| `scripts/adr-link-check.sh` | ADR-reference validator (ASCII-only, testable via `CHANGED_FILES`). |
| `.github/workflows/adr-validation.yml` | GitHub Action running the check on `pull_request`. |
| `docs/adr/INDEX.md` | Full ADR index (ADR-01…ADR-20). |
| `docs/implementation/reports/SPRINT0_A1_S04_REPORT.md` | This report. |
| `docs/implementation/reports/SPRINT0_FINAL_REVIEW.md` | Sprint-0 closeout review (generated alongside). |

## Files modified

None. A1-S04 is purely additive (new script, workflow, and docs). No `app/**`, config, migration, or existing workflow was changed.

## ADR validation workflow

**Detection (architecture-sensitive):** the script flags a PR when changed files match any of — Providers (`**/Providers/**`), Ports (`**/Ports/**`, `*Port.php`), Adapters (`**/Adapters/**`), Contracts (`**/Contracts/**`), Deptrac (`apps/api/deptrac.yaml`, `deptrac.baseline.yaml`), PHPStan architecture rules (`apps/api/tests/Architecture/**`, `phpstan-architecture.neon`), and context-boundary / provider wiring (`apps/api/bootstrap/providers.php`).

**Pass conditions (in order):**
1. No architecture-sensitive file changed -> pass (documentation-only and ordinary PRs are ignored).
2. The PR description contains an `ADR-XX` reference -> pass.
3. The PR adds/updates a file under `docs/adr/` -> pass (a new/updated decision).
4. Otherwise -> **fail** with a GitHub-annotated (`::error::`) message listing the offending files and the two ways to satisfy the check (cite `ADR-XX` from `docs/adr/INDEX.md`, or add an ADR file).

**Workflow (`.github/workflows/adr-validation.yml`):** triggers on `pull_request` to `main`; checks out full history (`fetch-depth: 0`), fetches the base ref, then runs the script with `BASE_REF=origin/<base>` and `PR_BODY=<pull_request.body>`. GitHub Actions compatible; no extra dependencies.

## ADR index

`docs/adr/INDEX.md` — a summary table plus one detailed section per ADR, each with **ID, Title, Status, Context, Decision, Affected Contexts, Dependencies, Superseded By, Related ADRs**:
- **ADR-01…ADR-18** — the decisions made during redesign (from blueprint 05).
- **ADR-19** — Adopt Deptrac + custom PHPStan rules for architecture fitness (Sprint-0 decision; self-documents this fitness system).
- **ADR-20** — Identity exposes a contracts seam; contexts depend on `IdentityContracts` only (the A1-S02 refinement).

Consistency: 20 detailed sections and 20 summary rows, IDs ADR-01…ADR-20 contiguous. Status legend distinguishes Accepted vs "Accepted (pending implementation)" for not-yet-built decisions. An "Adding a new ADR" procedure is included.

## Validation output

Ran locally (bash available in this environment):

**Script behavior (5 scenarios):**
```
bash -n scripts/adr-link-check.sh                      -> OK (no syntax errors)
A) docs-only PR                                        -> PASS  "no architecture-sensitive changes"
B) deptrac.yaml changed, no ADR ref                    -> FAIL  (exit 1, annotated message)  [expected]
C) Contracts/*Port.php changed, body has "ADR-02"      -> PASS  "PR description references an ADR"
D) PHPStan arch rule changed + adds docs/adr/INDEX.md  -> PASS  "PR adds or updates an ADR"
E) bootstrap/providers.php changed, no ADR ref         -> FAIL  (exit 1, annotated message)  [expected]
```

**ADR index build/consistency:**
```
## ADR-NN section headers : 20
| ADR-NN summary rows      : 20
IDs                        : ADR-01 .. ADR-20 (contiguous, unique)
```

**Workflow:**
```
adr-validation.yml -> parses; job "adr"; trigger "pull_request"; runs scripts/adr-link-check.sh
```

The GitHub Action itself cannot be exercised here (no PR event context), but its single step is the same script proven above; it will pass on non-architecture and ADR-referenced PRs, and fail otherwise.

## Known limitations

1. **PR-context only** — the check runs on `pull_request` (needs the PR body + base ref). Direct pushes to `main` are not gated by it; branch protection requiring PRs is the intended complement.
2. **Heuristic detection** — path/name based. "Context boundaries" is approximated by Deptrac config + `bootstrap/providers.php`; a boundary change made without touching those files could slip through (Deptrac itself still guards actual dependency violations). `Adapters/`/`Ports/` directories do not exist yet (future contexts) but are pre-covered.
3. **Reference is not verified against the index** — the check accepts any `ADR-XX` token in the body; it does not confirm the id exists in `INDEX.md`. Reviewers close that gap.
4. **`git diff` base resolution** — relies on `origin/<base>` being fetched; the workflow does `fetch-depth: 0` + an explicit `git fetch` to ensure it. For very large histories this adds a little time.
5. **Executable bit** — the workflow invokes the script via `bash scripts/adr-link-check.sh`, so a missing `+x` bit on Windows checkouts is a non-issue.

## Sprint 0 completion checklist

| Story | Task | Deliverable | Status |
|-------|------|-------------|--------|
| A1-S01 | T01-T04 | Deptrac config + baseline scaffold + CI `architecture` job | Done (baseline generated on your machine) |
| A1-S02 | T05-T07 | PHPStan level 6 (blocking) + 4 custom rules + Rector dry-run + Identity-contracts refinement | Done (baseline regen on your machine) |
| A1-S03 | T08-T10 | ESLint boundaries + strict tsc gate + Playwright/axe smoke + rule hardening | Done (`npm install` on your machine) |
| A1-S04 | T11-T12 | ADR-reference check + workflow + ADR Index (20) | Done |

**Sprint-0 exit (Milestone M1) — met, pending the three local install/regenerate steps:**
- CI fails on new boundary violations (Deptrac job) — yes (after baseline generation).
- PHPStan / Pint / ESLint / tsc are blocking — yes.
- Playwright smoke runs headless + axe — yes (non-blocking scaffold).
- ADR-link check active + ADR Index live — yes.
- No application code / API / DB changed in Sprint 0 — yes.

## Recommendations before Sprint 1

1. **Run the three pending local steps and commit the results** (this makes every gate green):
   - `apps/api`: `composer require --dev deptrac/deptrac:^2.0 rector/rector:^2.0 && vendor/bin/deptrac analyse --formatter=baseline --output=deptrac.baseline.yaml && vendor/bin/phpstan analyse --generate-baseline`.
   - `apps/web`: `npm install` (syncs `package-lock.json` with the new devDeps).
   - Commit the regenerated `deptrac.baseline.yaml`, `phpstan-baseline.neon`, and `package-lock.json`.
2. **Enable branch protection on `main`** requiring the checks: `api`, `architecture`, `web` (lint+typecheck), and `ADR reference check`. Keep `e2e` non-required until stable.
3. **Confirm the toolchain versions** resolve (`deptrac/deptrac ^2`, `rector/rector ^2`, Playwright `^1.49`); adjust constraints if your registry differs.
4. **Do a dry-run PR** touching one architecture-sensitive file to confirm the ADR check and Deptrac both trigger as expected end-to-end.
5. **Then start Sprint 1 (A2 — Multi-Tenancy & Isolation)** — the P0 critical-path story; the fitness gates from Sprint 0 will protect that refactor.

---

## STOP

Story A1-S04 is implemented (ADR-reference check + workflow + ADR Index + report). The Sprint-0 final review is generated as `SPRINT0_FINAL_REVIEW.md`. **Sprint 1 has not been started.** Awaiting approval.

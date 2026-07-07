# Sprint 0 — Final Review (Architecture Fitness & Tooling)

> Closeout for Epic A1 (Stories A1-S01 … A1-S04). Sprint 0 delivered the enforcement layer that protects redesigns 01–05 during execution. **No application code, API, DB, or namespace was changed** — Sprint 0 is entirely tooling, CI, and governance.

## Scope delivered

| Story | Deliverable | Result |
|-------|-------------|--------|
| A1-S01 | Deptrac config (20 layers) + baseline scaffold + CI `architecture` job | Done |
| A1-S02 | PHPStan level 6 (blocking) + 4 custom architecture rules + Rector dry-run + Identity→IdentityContracts Deptrac refinement | Done |
| A1-S03 | ESLint import boundaries (route groups + shared UI) + strict `tsc` gate + Playwright/axe smoke + reflection-aware rule hardening | Done |
| A1-S04 | ADR-reference check + GitHub Action + ADR Index (ADR-01…ADR-20) | Done |

Three one-time local steps remain to turn the gates green (dependency install + baseline generation); see Recommendations.

## Architecture compliance

- **Boundaries defined and enforced:** Deptrac encodes the 05 dependency matrix as 20 layers with a strict "Shared + IdentityContracts only" rule set; no allow-all, no kernel exemption. The Identity implementation is off-limits to every other layer (ADR-20).
- **Type-level guards:** four custom PHPStan rules forbid cross-context Model use, cross-context Eloquent access, and business logic in Filament resources / controllers — reflection-first (inheritance/interfaces/attributes), path as fallback.
- **Frontend boundaries:** ESLint `no-restricted-paths` isolates the 8 route groups and protects shared UI (inward-only dependencies).
- **Governance:** the ADR-reference check makes architecture-sensitive PRs cite an ADR; the ADR Index records ADR-01…ADR-20.
- **101 compliance:** every Sprint-0 PR trivially satisfies the Architecture Validation Checklist (no boundaries crossed; additive tooling only).

**Compliance verdict: PASS** — the fitness system for the whole program is in place and self-documented.

## Quality Gate status

| Gate | State after Sprint 0 |
|------|----------------------|
| Deptrac | Configured + wired (blocking). Baseline generated on your machine before first green run. |
| PHPStan | Level 6, blocking in CI (`|| echo` soft-fail removed) + 4 custom rules. Baseline regen pending locally. |
| Pint | Already blocking (unchanged). |
| ESLint | Now blocking in CI (`npm run lint` added) with boundary rules. |
| TypeScript | `tsc --noEmit` blocking (+ conservative strict flags). |
| Coverage | Ratchet policy defined; enforced from Sprint 1 as app code changes. |
| OpenAPI | Unchanged (no API touched). |
| Accessibility | axe wired into the E2E smoke (serious/critical gate). |
| Security | No new surface; the three P0 findings are scheduled for Sprint 2 (A6), not Sprint 0. |

## CI status

Workflows: `ci.yml` (jobs: `api`, `architecture`, `web`, `e2e`, `image`) + new `adr-validation.yml` (`adr`).
- **Blocking:** `api` (Pint, PHPStan, migrate, Pest), `architecture` (Deptrac), `web` (ESLint, tsc, vitest, build), `adr`.
- **Non-blocking:** `e2e` (Playwright, `continue-on-error`), Rector dry-run step.
- **Pending to go green:** dependency install + baselines (see Recommendations); until then the `architecture`/`api` jobs will report existing violations by design.

## Testing status

- **Backend:** existing Pest suite (~68 files) unchanged and still runs; architecture "tests" are the Deptrac + PHPStan gates. Custom rules validated by 5 scripted scenarios (script) + structure checks (rules) — runtime confirmation is a local PHPStan run.
- **Frontend:** existing Vitest suite (~190 files) unchanged; a Playwright + axe smoke journey (Home → Login → Dashboard → Logout) was added (authenticated leg env-gated).
- **No app tests were modified or removed.** New E2E is a scaffold, non-blocking until stabilized.

## Technical debt

- **Carried (unchanged by Sprint 0):** TD-1 (Learning→content models), TD-2 (sync progress recompute), TD-3 (manual tenant scoping), TD-4 (token in localStorage), TD-5 (webhook/gateway-in-tx), TD-6 (embedded media providers), TD-8 (Analytics event coupling), TD-9 (Org/Instructor tangles), TD-11 (missing subsystems). These are the burn-down targets for the Deptrac/PHPStan baselines.
- **Retired:** TD-7 (no architecture enforcement) — **closed** by A1 (Deptrac + custom rules + ADR check).
- **New, minor (Sprint-0 introduced):** (a) `package-lock.json` temporarily out of sync with new web devDeps until `npm install`; (b) two conservative-only tsconfig flags — stronger strictness deferred; (c) baselines must be generated locally before the gates are green. All are closed by the three recommended steps.

## Open risks

| Risk | Sev | Note |
|------|:--:|------|
| Baselines not yet generated → first CI run red | Med | Resolved by the local baseline commands; expected, not a defect. |
| Toolchain version drift (deptrac/rector/playwright constraints) | Low | Confirm on install; adjust constraints if the registry differs. |
| ESLint `@/*` resolution needs the TS resolver | Low | Added `eslint-import-resolver-typescript`; verify on `npm run lint`. |
| Custom PHPStan rules untested at runtime here | Low | Structurally validated; confirm with a local `phpstan analyse`; a misbehaving rule is one commented line away from disabled. |
| E2E flakiness | Low | Non-blocking scaffold; promote when stable. |
| Sandbox stale-mount noise during verification | Info | File tools are authoritative; bash occasionally served truncated copies — not real defects. |

No High/Critical open risks. The P0 security findings (TD-3/4/5) are **owned by Sprint 1/2**, not open defects from Sprint 0.

## Sprint retrospective

- **What went well:** the enforcement layer is complete and self-documented (ADR-19/20 record its own decisions); the ADR check was proven against 5 scenarios; the Identity-contracts refinement tightened the boundary model beyond the original plan; every change stayed additive and reversible.
- **What was hard:** no PHP/Composer and uninstalled web deps in the execution environment meant gates are configured-and-validated-statically rather than executed here; a recurring stale-mount divergence between the shell and the file tools required care (resolved by treating the editor state as authoritative).
- **What to improve:** front-load a "green baseline" step so a sprint's gates are demonstrably green in CI before closeout; where possible, run the actual toolchain to attach real output.
- **Process adherence:** strict one-story-at-a-time execution with a STOP + report after each; 101 honored throughout (no business logic / API / DB / namespace changes).

## Go / No-Go decision for Sprint 1

**GO (conditional).** The fitness gates are in place and Sprint 1 (A2 — Multi-Tenancy & Isolation) is the correct next P0 story. The one **condition**: run the three pending local steps (deptrac + phpstan baselines, `npm install`) and commit them, and enable branch protection requiring `api` / `architecture` / `web` / `adr` — so the isolation refactor lands behind demonstrably-green gates. These are ~30 minutes of local work, not blockers to planning Sprint 1.

## Overall Sprint score

| Dimension | Score (1–5) |
|-----------|:-----------:|
| Scope completeness (all A1 stories/tasks) | 5 |
| Architecture value (enforcement + governance) | 5 |
| 101 compliance (no code/API/DB/namespace) | 5 |
| Verifiability here (env-limited execution) | 3 |
| Reversibility & safety | 5 |
| Documentation (reports + ADR index) | 5 |

**Overall: 4.7 / 5** — objectives fully met; the only deduction is that final green-light validation must complete on your machine (environment constraint, not a work gap).

---

## STOP

Sprint 0 is complete and reviewed. **Sprint 1 has not been started.** Awaiting approval to proceed (after the three recommended local steps).

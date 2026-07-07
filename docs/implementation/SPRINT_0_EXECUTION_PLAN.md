# Sprint 0 — Execution Plan (Architecture Fitness & Tooling)

> EXECUTION MODE. Planning artifact only — **no code written yet.** Generated after reading `docs/implementation/101_EXECUTION_RULES.md` and Sprint 0 of `docs/redesign/100_EXECUTION_BACKLOG.md`.
> After approval of this plan, implementation begins with **Story A1-S01**, one story at a time.

## Pre-flight validation against 101_EXECUTION_RULES

| 101 check | Result for Sprint 0 |
|-----------|---------------------|
| Does not change architecture | ✅ Sprint 0 is tooling/CI only — adds Deptrac, static-analysis gates, ESLint boundaries, Playwright scaffold, ADR-link check. No context, ownership, port, API, or DB change. |
| No application code / no redesign | ✅ Only config, CI YAML, a baseline file, and one E2E smoke test. No business logic. Redesigns 01–05 / 99 / 100 untouched. |
| Additive & reversible | ✅ Every change is a new CI stage or config file; each is flag/stage-gated and revertible by removing the stage/file. |
| Mandatory Development Order (§6) | ✅ Sprint 0 is pure "Architecture/tooling" step — it *installs the enforcement* that later steps 2–9 depend on. |
| Forbidden Actions (§5) | ✅ None triggered (no cross-context imports, no Filament/controller logic, no migrations, no secrets). |
| Quality Gates (§15) | ✅ Sprint 0 *creates* the gates (Deptrac/PHPStan/coverage/OpenAPI/a11y wiring). Baseline captured, not bypassed. |
| Definition of Ready (§8) | ✅ A1 has no blocking dependencies; it is the program's first sprint. Acceptance criteria are defined in 100. |

**Verdict:** Sprint 0 is fully compliant with 101 and safe to execute. It is a prerequisite gate for every later sprint (critical-path root in 100).

---

# Sprint Goal

Make architecture boundaries and code quality **enforced automatically by CI** before any feature work begins — so redesigns 01–05 cannot erode silently — and open the E2E/accessibility test track. This satisfies **Milestone M1 (Fitness Gate)**.

Concretely, by end of sprint: a PR that violates a context boundary **fails CI**; PHPStan/Pint/ESLint/`tsc` are blocking; a Playwright smoke journey runs headless; and architecture-touching PRs must link an ADR.

---

# Stories

Sprint 0 delivers **Epic A1 — Architecture Fitness & Tooling** (P0, Risk Low, 34 SP). Four stories:

| Story | Title | Priority | SP | Owner | Depends on |
|-------|-------|:--:|:--:|-------|-----------|
| **A1-S01** | Deptrac boundary enforcement in CI | P0 | 13 | SA + BE | none |
| **A1-S02** | Static analysis hardening (PHPStan / Pint / Rector) | P0 | 8 | BE | A1-S01 (CI stage) |
| **A1-S03** | Frontend fitness + Playwright scaffold | P1 | 8 | FE + QA | A1-S01 (CI stage) |
| **A1-S04** | ADR validation check | P2 | 5 | SA + DO | A1-S01 (CI stage) |

**Recommended execution order:** A1-S01 → (A1-S02 ∥ A1-S03) → A1-S04. S02 and S03 are independent of each other (different toolchains: PHP vs web) and may run in parallel once S01's `architecture` CI stage exists. S04 is last (lightest, depends only on the CI wiring pattern).

---

# Tasks

### A1-S01 — Deptrac boundary enforcement
| Task | Description | Blocking | Hrs | Cx | Risk |
|------|-------------|----------|:--:|:--:|:--:|
| A1-T01 | Install Deptrac; scaffold `deptrac.yaml` (layers per `app/{Domains,Contexts,Platform}/*`) | — | 4 | M | Low |
| A1-T02 | Encode forbidden-dependency ruleset from 05 matrix (no cross-context Models; Analytics read-only; Filament no-logic; Platform capabilities depend on no domain) | A1-T01 | 8 | M | Med |
| A1-T03 | Generate + commit baseline (`--formatter=baseline`) | A1-T02 | 2 | S | Low |
| A1-T04 | Add CI `architecture` stage (fail on new violation) | A1-T03 | 3 | S | Low |

### A1-S02 — Static analysis hardening
| Task | Description | Blocking | Hrs | Cx | Risk |
|------|-------------|----------|:--:|:--:|:--:|
| A1-T05 | Bump PHPStan level by one; reconcile baseline; make PHPStan **blocking** (remove `|| echo` soft-fail in CI) | A1-T04 | 6 | M | Low |
| A1-T06 | Custom PHPStan rule: no cross-context Model use (complements Deptrac at type level) | A1-T05 | 6 | M | Med |
| A1-T07 | Rector dry-run stage (report only, non-blocking) | A1-T05 | 3 | S | Low |

### A1-S03 — Frontend fitness + Playwright
| Task | Description | Blocking | Hrs | Cx | Risk |
|------|-------------|----------|:--:|:--:|:--:|
| A1-T08 | ESLint boundaries plugin + rules mapped to route-groups `(auth)/(learning)/(commerce)/…`; add `lint` to web CI | — | 5 | M | Low |
| A1-T09 | Strict `tsc --noEmit` CI gate (already scripted; make explicit + blocking) | — | 2 | S | Low |
| A1-T10 | Playwright scaffold + 1 smoke journey (`home → login`) + axe wiring (report) | A1-T09 | 6 | M | Low |

### A1-S04 — ADR validation
| Task | Description | Blocking | Hrs | Cx | Risk |
|------|-------------|----------|:--:|:--:|:--:|
| A1-T11 | ADR-link CI check (path-triggered: PRs touching `**/Providers`, ports, `deptrac.yaml` must reference an ADR id) | — | 4 | S | Low |
| A1-T12 | Publish ADR index (from redesign 05) under `docs/` and link from README | — | 3 | S | Low |

**Total:** 12 tasks · ≈ 52 engineering hours · 34 SP.

---

# Dependency Graph

```
A1-S01 (Deptrac)
  A1-T01 ─► A1-T02 ─► A1-T03 ─► A1-T04 ──┐   (the CI `architecture` stage)
                                         │
        ┌────────────────────────────────┼───────────────────────────┐
        ▼                                 ▼                           ▼
   A1-S02 (PHPStan)                  A1-S03 (Frontend)          A1-S04 (ADR)
   A1-T05 ─► A1-T06                  A1-T08                     A1-T11
        └──► A1-T07 (report)         A1-T09 ─► A1-T10           A1-T12
```

- **A1-T04 is the pivot:** the `architecture` CI stage must exist before S02/S03/S04 wire their own gates alongside it.
- **A1-T08 and A1-T09** have no blockers (web toolchain) and can start immediately in parallel with S01.
- No task depends on any other sprint. Sprint 0 has **zero external dependencies** (critical-path root).

---

# Risks

| # | Risk | Severity | Mitigation |
|---|------|:--:|-----------|
| R0-1 | Deptrac baseline is large / rules produce false positives → slows every PR | Med | Baseline current violations (A1-T03) so only *new* ones block; tune ruleset with SA before making blocking; burn baseline down over later sprints, never grow it. |
| R0-2 | Making PHPStan blocking surfaces latent errors that break CI on unrelated PRs | Med | Keep existing `phpstan-baseline.neon`; only raise level by one; reconcile baseline in the same PR (A1-T05); do not lower level elsewhere. |
| R0-3 | ESLint boundary rules misclassify legitimate shared imports | Low | Start with warn→error after a short bake; allowlist shared `lib/ui` explicitly; SA review of rule map. |
| R0-4 | Playwright flakiness in CI | Low | One deterministic smoke journey; headless; trace-on-failure; retry=1; isolated from feature gates (non-blocking initially if flaky, then promote). |
| R0-5 | ADR-link check blocks unrelated PRs | Low | Path-triggered only (Providers/ports/deptrac.yaml); clear failure message with the ADR index link. |
| R0-6 | Windows/PowerShell/local-runner friction (documented history) | Low | Prefer CI (Ubuntu) for all gate runs; ASCII-only in any helper script; no local-only steps. |

No High/Critical risks — Sprint 0 is additive tooling.

---

# Files Expected To Change

*Grounded in the current repo. New files marked (new); edits marked (edit). No application/business code is touched.*

**Backend tooling (`apps/api`):**
- `apps/api/composer.json` (edit) — add `deptrac` to `require-dev`; add `deptrac` + `arch` scripts alongside existing `pint`/`stan`/`test`/`qa`.
- `apps/api/deptrac.yaml` (new) — layers + ruleset from redesign 05 matrix.
- `apps/api/deptrac-baseline.yaml` (new) — captured baseline.
- `apps/api/phpstan.neon` (edit) — raise level by one; register custom rule.
- `apps/api/phpstan-baseline.neon` (edit) — reconcile after level bump.
- `apps/api/tests/Architecture/` (new, optional) — a custom PHPStan rule class + its registration (rule code is *tooling*, not business logic).

**Frontend tooling (`apps/web`):**
- `apps/web/package.json` (edit) — add `@playwright/test`, ESLint boundaries plugin, `axe-core`/`@axe-core/playwright`; add `e2e` script.
- `apps/web/eslint.config.mjs` (edit) — add import-boundary rules per route-group.
- `apps/web/playwright.config.ts` (new) — headless config, trace-on-failure.
- `apps/web/e2e/smoke.spec.ts` (new) — `home → login` smoke + axe check.

**CI / repo root:**
- `.github/workflows/ci.yml` (edit) — add `architecture` (Deptrac) stage to the `api` job; make PHPStan blocking (remove `|| echo` soft-fail); add Rector dry-run (report); add `lint` (ESLint) + explicit `tsc` + `e2e` (Playwright) stages to the `web` job; add the ADR-link check (a small script step, e.g. on `pull_request`).
- `scripts/adr-link-check.*` (new) — path-triggered ADR reference check (ASCII-only).
- `docs/adr/INDEX.md` (new) — published ADR index extracted from redesign 05 (documentation of existing decisions; permitted as it is the ADR index, not a redesign edit).
- `README.md` (edit) — link the ADR index + note the new gates.

**Not touched:** any `app/**` business code, migrations, routes, models, Filament resources, redesigns 01–05, 99, 100, 101.

---

# Required Tests

Sprint 0 is meta — its "tests" prove the **gates themselves work**. Per 101 §15 and the 100 story test taxonomy:

| Story | Verification (the gate is proven by a deliberately-failing case) |
|-------|------------------------------------------------------------------|
| A1-S01 | **Acceptance/Architecture:** a throwaway branch adding a cross-context `use App\Contexts\X\Models\*` in another context → CI `architecture` stage **fails**; removing it → passes. Baseline present. |
| A1-S02 | **Architecture:** a seeded cross-context Model use fails the custom PHPStan rule; level-bump leaves baseline green on `main`. Rector stage runs and reports (non-blocking). |
| A1-S03 | **Acceptance:** a seeded disallowed cross-group import fails ESLint; `tsc --noEmit` blocking. **E2E:** `home → login` smoke passes headless. **Accessibility:** axe runs and reports on the smoke page. |
| A1-S04 | **Acceptance:** a PR touching `**/Providers` without an ADR link fails the check; adding the link passes. ADR index renders. |

No unit/integration/performance/security tests apply to app behavior in Sprint 0 (no app code changes) — stated explicitly per 101. The isolation/leakage and coverage-ratchet gates are *installed conceptually here* but exercised from Sprint 1 onward.

---

# Rollback Strategy

Every Sprint 0 change is a CI stage or a config file — rollback is trivial and risk-free:

- **Per stage:** each new CI stage is added independently; if a stage misbehaves (e.g., Deptrac false positives, Playwright flake), **disable that single stage** (comment/flag it) without affecting the others or any runtime.
- **PHPStan blocking:** if the level bump destabilizes CI, revert `phpstan.neon` to the prior level and restore the soft-fail line in `ci.yml` — one-line reverts.
- **Config files:** `deptrac.yaml`, baseline, `playwright.config.ts`, ESLint rule additions are new/edited config — `git revert` the tooling commit restores the prior CI exactly.
- **No runtime impact:** Sprint 0 touches **no application, no DB, no API** — there is nothing to roll back in production; reverting is purely a CI/repo operation.
- **Baseline safety:** the Deptrac baseline means turning the gate on cannot retroactively fail existing code; only new violations block.

---

# Definition of Done

**Story-level (per 100 + 101 §9):**
- A1-S01: `deptrac.yaml` defines every context as a layer; forbidden rules encode the 05 matrix; baseline committed; CI `architecture` stage fails on new violations; SA sign-off on ruleset.
- A1-S02: PHPStan level raised ≥1 with baseline not grown; custom "no cross-context Model use" rule flags a seeded violation; Pint `--test` blocking; Rector dry-run reports.
- A1-S03: disallowed cross-group import fails lint; `tsc --noEmit` blocking; Playwright `home→login` smoke green headless in CI; axe wired.
- A1-S04: architecture-path PR without an ADR link fails; ADR index published and linked.

**Epic/Sprint-level (M1 exit criteria):**
- [ ] CI fails on any new boundary violation (proven with a seeded case).
- [ ] PHPStan / Pint / ESLint / `tsc` are all **blocking** on PRs.
- [ ] Playwright smoke runs headless in CI; axe reports.
- [ ] ADR-link check active on architecture paths; ADR index live.
- [ ] All gates green on `main`; baselines committed; no application code changed.
- [ ] PO sign-off on Milestone M1.

**Compliance:** every PR in this sprint satisfies the Canonical PR Checklist (101 §7) and the Architecture Validation Checklist (101 §10) — trivially, since no boundaries are crossed.

---

# Estimated Duration

- **Effort:** ≈ 52 engineering hours / 34 SP.
- **Calendar:** **2 weeks** (1 sprint) at the recommended staffing — SA + BE×1 on the PHP/Deptrac track, FE×1 + QA on the web/Playwright track, DO wiring CI, TW publishing the ADR index. Tracks run in parallel after A1-T04.
- **Conservative:** 2 weeks. **Aggressive:** ~1 week if S02/S03 fully parallelized and the Deptrac baseline is clean. Buffer for R0-1/R0-2 (baseline reconciliation) is included.

---

## STOP — Awaiting Approval

The Sprint 0 execution plan is complete. **No code has been written.**

Per the instruction and 101 §16, I am stopping here and awaiting your approval before implementing **Story A1-S01 (Deptrac boundary enforcement)** — which will begin with task **A1-T01** (install Deptrac + scaffold `deptrac.yaml`).

Reply to approve, or adjust scope/order first.

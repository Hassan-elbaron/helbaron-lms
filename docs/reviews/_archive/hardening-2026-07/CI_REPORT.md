# CI Verification Report

**Date:** 2026-07-16
**Commit:** `5048750` (`fix(web): pin webpack 5.100.2 to unbreak Storybook preview build`)
**Run:** CI #16 — https://github.com/Hassan-elbaron/helbaron-lms/actions/runs/29516524030
**Conclusion:** ✅ **Success** — total duration 6m 14s
**Verified by:** reading the live run + each job page in the browser (not inferred from the summary panel).

---

## Mandatory gate — all seven jobs green

| # | Job | Duration | Result | Evidence |
|---|-----|----------|--------|----------|
| 1 | API (Laravel 12 / PHP 8.3) | 2m 59s | ✅ succeeded | Pint, PHPStan/Larastan, Rector (dry-run), Migrate, Pest all green |
| 2 | Web (Next.js 15 / Node 20) | 3m 7s | ✅ succeeded | npm ci, ESLint + arch boundaries, tsc, tests, `next build` all green |
| 3 | Architecture (Deptrac) | 16s | ✅ succeeded | Layer boundaries enforced |
| 4 | Secret scan (gitleaks) | 7s | ✅ succeeded | "No leaks detected ✅" |
| 5 | E2E (Playwright + axe) | 2m 10s | ✅ succeeded | "2 skipped, 5 passed (58.3s)" |
| 6 | API image (build, scan, push) | 3m 9s | ✅ succeeded | Trivy CRITICAL/HIGH: **alpine 0, composer-vendor 0**; pushed to GHCR |
| 7 | Web image (build, scan, push) | 3m 1s | ✅ succeeded | Trivy CRITICAL/HIGH clean; pushed to GHCR |

## On the "exit code 1/2" annotations

The run's aggregated annotations panel shows:
- `API (Laravel 12 / PHP 8.3): Process completed with exit code 2.`
- `Web (Next.js 15 / Node 20): Process completed with exit code 1.`

These are **not** job failures. They originate from the intentionally **non-blocking, report-only** security-audit steps (composer audit / Rector dry-run on API; `npm audit --all` report-only on Web), which exit non-zero to surface findings without failing the gate. Both job pages show **"succeeded"** with every functional step green, and the overall run conclusion is **Success**. The same annotations appeared on the previous green run #15 (`3371ab9`). Verified by opening each job page directly rather than trusting the summary panel.

The other annotations are Node.js 20 → 24 deprecation notices (GitHub-runner infra, cosmetic) and three ESLint advisories on `cms-page.tsx` (`<img>` vs `next/image`, two `useMemo` dependency hints) — warnings, non-blocking.

## Trace of recent green runs

| Run | Commit | Change | Result |
|-----|--------|--------|--------|
| #15 | `3371ab9` | Harden `getStaticPage` (fixes /about + /contact SSR crash) + mock contract + test | ✅ Success |
| #16 | `5048750` | Pin webpack 5.100.2 (unbreak Storybook preview build) | ✅ Success |

## Release decision

Per the standing rule — *if every mandatory gate is green, the decision is GO* — the mandatory CI gate is **fully green on the latest commit (`5048750`)**:

### ✅ GO (mandatory gate)

Remaining items are **advisory, non-blocking**:

- **`build-storybook`** — now **fixed and green** (host-verified `=> Preview built (43 s)`, pushed in `5048750`). It is not one of the seven mandatory CI jobs. See `STORYBOOK_BUILD_FIX.md`.
- **Lighthouse (performance)** — not yet executed with a live server; instructions provided. This is the last advisory item; it does not gate release.
- **Visual regression (`--project=visual`)** — advisory/local; baselines are platform-specific and regenerated per environment. The blocking E2E gate runs `--project=chromium`, which is green.
- **`npm audit`** dev-tree findings — separate from the mandatory Trivy production-image scan (which is clean: 0 CRITICAL/HIGH).

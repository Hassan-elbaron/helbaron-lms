# Performance Optimization Report (running log)

**Scope:** `apps/web`, behavior-preserving. Every number is measured from a real `npm run build` / Lighthouse run on the host — no estimates.
**Baseline build:** commit `5048750` (pre-hardening). **Gates:** typecheck clean, 114/114 tests green throughout (coverage preserved).

---

## Tier 1 — config-level (optimizePackageImports + removeConsole + browserslist)

**Changes**
- `next.config.ts`: `experimental.optimizePackageImports` for the 9 `@radix-ui/*` packages + `vaul` + `sonner` (verified absent from Next's 77-package default list; lucide-react/recharts already default — not added).
- `next.config.ts`: `compiler.removeConsole` in production, excluding `error`/`warn`.
- `.browserslistrc`: conservative-modern floor (Chrome/Edge ≥109, Firefox ≥115, Safari/iOS ≥16), user-approved.

**Measured result — First Load JS (before → after, same host)**

| Item | Before | After | Δ |
|---|---|---|---|
| Shared by all (baseline) | 102 kB | 102 kB | **0** |
| `chunks/1255` | 45.8 kB | 45.8 kB | 0 |
| `chunks/4bd1b696` | 54.2 kB | 54.2 kB | 0 |
| `/` (homepage) | 167 kB | 166 kB | −1 |
| `/notifications` | 165 kB | 164 kB | −1 |
| `/org/organizations/[public_id]` | 201 kB | 200 kB | −1 |
| `/teach/courses` | 174 kB | 172 kB | −2 |
| `/contracts` | 165 kB | 164 kB | −1 |
| ~20 further routes | — | — | −1 each |
| remaining routes | — | — | 0 |
| Middleware | 34.4 kB | 34.4 kB | 0 |

**Honest assessment:** marginal — ~0.5–1% off ~20 routes, shared baseline unchanged. Root reasons: (a) `lucide-react` (the most-used dep, 96 files) is **already** optimized by Next's default list, so no gain there; (b) `@radix-ui/*`, `vaul`, `sonner` are already granular, so `optimizePackageImports` recovers little; (c) `removeConsole` trims a small amount. These changes are kept — they are harmless, improve prod hygiene (console stripping), and cost nothing — but they are **not** the high-impact lever.

**Unresolved measurement:** the `.browserslistrc` legacy-JS effect (~43 KiB flagged by Lighthouse) is **not observable in the route table** because Next emits polyfills as a separate chunk not counted in "First Load JS". It requires a Lighthouse `legacy-javascript` re-run to confirm or refute — pending, batched with the next host measurement.

**Verdict:** Tier 1 delivered a small, real, zero-risk reduction. The measurable high-impact work is structural (code-splitting heavy client widgets; shrinking the 176-file client boundary so more paints server-side) and is tracked in the next Tier.

---

## Pending measurements (batched to minimize host round-trips)
1. Lighthouse re-run (server up): `legacy-javascript`, `unused-javascript`, Performance — to measure Tier 1's browserslist effect and set the post-Tier baseline.
2. Tier 2 (code-splitting) build delta.

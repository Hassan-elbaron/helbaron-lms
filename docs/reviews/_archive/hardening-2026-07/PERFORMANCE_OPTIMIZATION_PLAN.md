# Performance Optimization Plan (Phase 1 Audit)

**Date:** 2026-07-16
**Scope:** `apps/web` (Next.js 15 App Router). Behavior-preserving optimization only.
**Method:** Real `next build` route table + Lighthouse 12.8.2 (mobile, 4× CPU, simulated slow-4G) + full source inventory. No estimated numbers — every current-state figure below is measured.

---

## 1. Current state (measured)

### Lighthouse category scores (mobile, throttled, API-down shell)
| Category | Score |
|---|---|
| Performance | **72** |
| Accessibility | 100 |
| Best Practices | 96 |
| SEO | 100 |

### Performance metric breakdown (this is where the 72 comes from)
| Metric | Value | Score | Weight | Contribution |
|---|---|---|---|---|
| First Contentful Paint | 2.6 s | 0.64 | 10% | 6.4 |
| Speed Index | 2.6 s | 0.97 | 10% | 9.7 |
| **Largest Contentful Paint** | **7.0 s** | **0.06** | **25%** | **1.5** |
| Total Blocking Time | 100 ms | 0.98 | 30% | 29.4 |
| Cumulative Layout Shift | 0 | 1.00 | 25% | 25.0 |
| **Total** | | | | **≈ 72** |

**Root cause is unambiguous: LCP.** TBT, CLS, and Speed Index are already near-perfect. LCP alone costs ~23 points; a weak FCP costs a few more. Fixing LCP/FCP is the entire performance story — TBT/CLS need no work.

### Bundle sizes (from `next build`)
- **Shared JS loaded on every route: 102 KB** — `chunks/1255` 45.8 KB + `chunks/4bd1b696` 54.2 KB + 1.96 KB.
- Middleware: 34.4 KB (Edge runtime wrapper around a 51-line cookie guard — not custom-code bloat; little to optimize).
- Homepage `/` First Load JS: **167 KB**.
- Heaviest routes: `/design-system` 243 KB (dev-only route), `/org/organizations/[public_id]` 201 KB, `/cart` 178 KB, `/profile` 178 KB, `/teach/courses/[public_id]` 172 KB, `/crm/leads` 172 KB, `/lessons/[public_id]` 169 KB, `/org/consulting` 168 KB, `/contracts` 165 KB, `/notifications` 165 KB, `/products` 161 KB.

### Lighthouse opportunity audits (measured savings)
- Reduce unused JavaScript: **~148 KiB**
- Avoid legacy JavaScript to modern browsers: **~43 KiB**
- Reduce unused CSS: **~11 KiB**

## 2. Why LCP is 7.0 s — evidenced diagnosis

The LCP element is a small top text bar, painting at ~7 s. That is the signature of a **client-rendered shell under throttling**: the largest paint waits for the JS bundle to download, parse, and hydrate. Source evidence:

1. **176 files carry `"use client"`.** Nearly every page and every `lib/*/hooks.ts` is client-rendered.
2. **All six root providers are client components** (`providers.tsx`: Theme → Branding → FeatureFlags → I18n → QueryClient → Auth), mounted in the root layout — so the entire tree hydrates as client regardless of page needs.
3. **Route-group `layout.tsx` files** under `(analytics)`, `(crm)`, `(organization)`, `(instructor)`, `(learning)`, `(account)` are client components, forcing client rendering of whole sections.
4. **Zero code-splitting:** `next/dynamic` and `React.lazy` appear **0 times**. Every route ships its full client bundle eagerly.
5. **No `optimizePackageImports`:** `lucide-react` is imported in ~96 files; `@radix-ui/*` across the UI kit. Without this, tree-shaking relies solely on named imports.
6. **No `browserslist`:** falls back to Next defaults → ~43 KiB of legacy transpilation shipped to modern browsers.

**Critical measurement caveat:** this LCP was measured against the **API-down fallback homepage** rendered client-side. The production homepage streams CMS content + CDN images and will measure differently. **The true baseline must be re-measured with the API up and/or `--preset=desktop`.** This plan targets the structural causes (client-JS on the critical path) that improve LCP in *both* scenarios.

## 3. Prioritized, measurable optimization plan

Ordered by impact ÷ risk. Each item lists its **measurement method** so improvement is proven, never assumed.

### Tier 1 — Config-level, behavior-preserving, high-confidence
**Verified against installed Next 15 defaults (`node_modules/next/dist/server/config.js`):** the built-in `optimizePackageImports` list of 77 packages **already includes `lucide-react`** (and `recharts`, `@headlessui/react`, `@mui/*`, etc.). So optimizing `lucide-react` is a **no-op** — not claimed. Confirmed **absent** from the default list (genuinely worth adding): `@radix-ui/*`, `vaul`, `sonner`, `@tanstack/react-query`, `react-hook-form`.

| # | Change | Targets | Measure via |
|---|--------|---------|-------------|
| 1 | `experimental.optimizePackageImports` for the 9 `@radix-ui/*` packages + `vaul` + `sonner` (NOT lucide — already default) | portion of 148 KiB unused JS | `next build` route First-Load-JS diff |
| 2 | Add modern `browserslist` (drop legacy transpilation) — **support-matrix decision, reversible** | 43 KiB legacy JS | Lighthouse `legacy-javascript` audit + build size |
| 3 | `compiler.removeConsole` in production (keep `error`/`warn`) | small JS + clean prod | build size diff |

> **Measurement environment note:** the sandbox cannot run `next build` (Bus error — resource limits), so before/after route-size and Lighthouse deltas are measured on the **host** (`npm run build` + `npx lighthouse`), same machine before vs after, and recorded from real output. No delta is claimed without host artifacts.

### Tier 2 — Code splitting (behavior-preserving)
| # | Change | Targets | Measure via |
|---|--------|---------|-------------|
| 4 | `next/dynamic` for non-critical heavy client widgets: charts (`components/ui/charts/*` on reports/analytics), `data-grid`, dialogs/drawers, the `/design-system` showcase | per-route First Load JS | build route diff |

### Tier 3 — Reduce client boundary (biggest LCP lever, needs care)
| # | Change | Targets | Measure via |
|---|--------|---------|-------------|
| 5 | Convert purely-presentational `"use client"` components with no state/hooks (`components/landing/*`, `components/homepage/blocks/*`) to server components | LCP/FCP + client JS | `"use client"` count + LCP (full-stack re-measure) |

### Tier 4 — Images & fonts
| # | Change | Targets | Measure via |
|---|--------|---------|-------------|
| 6 | Migrate 4 raw `<img>` (`landing-header` logo, `clients/featured-courses/team` blocks) to `next/image` + add `images` remotePatterns | image bytes, `no-img-element` lint | Lighthouse image audits |
| 7 | Add `preconnect` to `fonts.googleapis.com`/`fonts.gstatic.com` for the white-label brand-font path (render-blocking `<link>` only when an admin font is set) | brand-font-path FCP | Lighthouse (brand-font configured) |

### Tier 5 — Dead dependency
| # | Change | Targets | Measure via |
|---|--------|---------|-------------|
| 8 | Remove `framer-motion` (**0 imports in `src`**, verified) | install size / supply chain | `npm ls` + lockfile |

## 4. Explicitly out of scope (already optimal — do not touch)
- **TBT (100 ms), CLS (0), Speed Index (2.6 s)** — near-perfect; no work warranted.
- **Middleware** — 34.4 KB is the Edge runtime wrapper, not custom logic.
- **Charts** — already a dependency-free custom SVG layer (no recharts/chart.js to remove).
- **React Query** — per-request client via `useState`, sane defaults; SSR-hydration is a Tier-3-adjacent enhancement, not a defect.

## 5. Execution protocol (per the hardening rules)
1. Capture a fresh **before** `next build` route table + Lighthouse (full stack + desktop for a real LCP baseline).
2. Apply **one tier at a time**; rebuild; diff route sizes; re-run the relevant Lighthouse audit.
3. Record measured before/after in `PERFORMANCE_OPTIMIZATION_REPORT.md`. No number is claimed without a build/Lighthouse artifact.
4. After every change: `npm run typecheck && npm test` must stay green; CI must stay green. No `any`/ignores/disables.

---

*Next: Phase 2 begins with Tier 1 (config-level), measured against a fresh build.*

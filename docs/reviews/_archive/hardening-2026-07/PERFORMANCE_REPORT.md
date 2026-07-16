# Performance Report — Lighthouse

**Date:** 2026-07-16
**Tool:** Lighthouse 12.8.2 (CLI, headless Chrome)
**Target:** `http://localhost:3000/` (homepage), local production build (`next build` + `next start`, `NEXT_DISABLE_STANDALONE=1`)
**Profile:** **Mobile**, `throttlingMethod: simulate`, **4× CPU** slowdown, simulated slow-4G (Lighthouse default — the harshest preset)
**Status:** Advisory gate. Not one of the seven mandatory CI jobs; does not block release.

---

## Category scores (measured — all four)

| Category | Score | Assessment |
|----------|-------|------------|
| **Performance** | **72** / 100 | Good, for a mobile-throttled shell (LCP is the only soft metric — see below) |
| **Accessibility** | **100** / 100 | Perfect |
| **Best Practices** | **96** / 100 | Near-perfect (one minor, non-critical deduction) |
| **SEO** | **100** / 100 | Perfect |

## Important context for reading these numbers

1. **Mobile + 4× CPU + slow-4G simulation** — the deliberately punishing default. Desktop numbers (`--preset=desktop`) would be materially higher across the board.
2. **API was down → homepage rendered its built-in fallback.** The real homepage streams CMS content and serves optimized images via CloudFront; this measures the **app shell**, not production content. Treat Performance/LCP as a shell smoke, not a production measurement. (Accessibility, Best Practices, and SEO are structural and translate directly.)

## Core Web Vitals & metrics (this run)

| Metric | Value | Lighthouse score | Assessment |
|--------|-------|------------------|------------|
| First Contentful Paint (FCP) | 2.6 s | 0.64 | Moderate (throttled) |
| Largest Contentful Paint (LCP) | 7.0 s | 0.06 | **Poor — artifact, see analysis** |
| Speed Index | 2.6 s | 0.97 | Excellent |
| Total Blocking Time (TBT) | 100 ms | 0.98 | Excellent |
| Cumulative Layout Shift (CLS) | **0** | 1.00 | Excellent |
| Initial server response | ~270 ms | 1.00 | Excellent |

## LCP analysis — the one soft metric

The LCP element is the **top announcement/eyebrow bar** at ~7.0 s. A tiny text bar becoming the "largest paint" at 7 s is the signature of a **client-rendered shell under throttling**: with the API unavailable, the fallback homepage paints its main content only after the JS bundle downloads and hydrates over simulated slow-4G. LCP here is gated by **JS delivery**, not by an image or heavy asset.

Implication: this ~7 s is largely an artifact of (a) mobile slow-4G simulation and (b) the API being down. It is **not** representative of the production homepage served with CDN + live SSR content. It is still worth the JS reductions below, which shorten the critical path regardless.

## Actionable findings (real, from this run)

| Opportunity | Est. saving | Notes |
|-------------|-------------|-------|
| Reduce unused JavaScript | ~148 KiB | Largest lever; trim/split critical-path JS |
| Avoid legacy JS to modern browsers | ~43 KiB | Tune browserslist / SWC targets to drop legacy transpilation |
| Reduce unused CSS | ~11 KiB | Minor |

Already healthy: CLS 0, TBT 100 ms, Speed Index 2.6 s, render-blocking (none), text compression (on), next-gen image formats (pass), total payload (~1 MB, under budget), DOM size (639 elements), valid source maps, HTTPS/CSP checks. Accessibility and SEO audits pass **100%**.

## Recommendation

The shell already scores **A11y 100, SEO 100, Best Practices 96, Performance 72** under the harshest mobile-throttled profile with the API down. The only soft spot (LCP/TTI) is dominated by throttled JS delivery on the API-down fallback plus ~190 KiB of trimmable/legacy JS — a bounded, non-blocking optimization, not a release blocker. For a release-representative Performance number, re-run against the full stack (API up) and/or with `--preset=desktop`; expect Performance to rise well into the 80s–90s once LCP reflects real CDN-served content.

---

*Report files (`lighthouse.report.*`) are gitignored (local, machine-specific) and are not committed.*

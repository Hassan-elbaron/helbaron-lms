# Performance Hardening Report — HElbaron LMS

**Date:** 2026-07-15
**Important honesty note:** Lighthouse, Web-Vitals (LCP/CLS/INP/TTFB/TBT), bundle analysis, and long-task/CPU profiling **require a production build measured by a runner**. This QA environment has **no PHP and cannot run the host's production build/Lighthouse/CI** (Docker + the dev server run on the user's host, unreachable from here; the user asked not to use a remote sandbox). **No Lighthouse scores or Web-Vitals numbers are reported here, because none could be measured. Fabricating them would be dishonest.** The dev-server render latency observed during QA is **not** representative of production and is deliberately excluded from any judgement.

## What was verified architecturally (real, code-level)

- **Framework:** Next.js **15.5.20** (App Router) + React **19** — modern streaming/SSR, automatic route-level code splitting, and Server Components by default (client components are explicitly `"use client"`).
- **Security/caching headers** are set (CSP, HSTS, etc.) — see SECURITY_HARDENING_REPORT.md.
- **Fonts:** brand font is injected as CSS (`googleFontCss`) driven by `BrandSetting`; verify it uses `display=swap` and is preconnected to avoid render-blocking.
- **CI performance harness exists** in the repo: `e2e/smoke.spec.ts`, `playwright.config.ts`, and Storybook — the right place to add a Lighthouse-CI budget.

## Observations to verify on a production build (not defects, action items)

- **`next/image` adoption looks limited** (only ~2 files reference `next/image`/`next/font`). For the LCP hero image and course thumbnails, confirm `next/image` (or explicit width/height + `priority` on the hero) is used to control CLS and deliver responsive/AVIF/WebP. If thumbnails are plain `<img>` from S3/CloudFront, add width/height + lazy-loading.
- **Font loading:** confirm `font-display: swap` and preconnect for the injected brand font (avoid FOIT/CLS).
- **Client-fetch waterfalls:** several authenticated pages fetch data client-side after hydration (observed as brief "Loading…" states). Confirm on a prod build whether key above-the-fold data (dashboard, orders) should be server-rendered/streamed to improve LCP/INP.

## Commands to run on the host / CI (executable there, not here)

```
# Frontend production build + budgets
cd apps/web
npm run build                 # verify build succeeds + inspect route/bundle sizes in the output
npm run build-storybook

# Lighthouse (against a prod build served locally or on staging)
npx lighthouse http://localhost:3000/ --preset=desktop --output=json --output-path=./lighthouse-home.json
npx lighthouse http://localhost:3000/courses --output=json --output-path=./lighthouse-catalog.json
# (repeat for /pricing, /dashboard, a course detail, /login)

# Optional: bundle analysis
ANALYZE=true npm run build    # if @next/bundle-analyzer is wired; else add it
```

## Recommendation (release gate)
Add a **Lighthouse-CI** job (or `@lhci/cli`) with budgets (Performance ≥ 90, LCP < 2.5s, CLS < 0.1, TBT < 200ms) running against the production build in CI, plus `next build` bundle-size assertions. Treat these as the objective performance gate — this report cannot substitute for them.

## Net result
Performance could **not** be measured in this environment and is **not** scored. The architecture is modern (Next 15 / React 19, code-split, SSR/streaming-capable); concrete action items (verify `next/image` on hero/thumbnails, font-display swap, and client-fetch waterfalls) and the exact Lighthouse/build commands are provided for the host/CI, which is the only valid place to produce the numbers.

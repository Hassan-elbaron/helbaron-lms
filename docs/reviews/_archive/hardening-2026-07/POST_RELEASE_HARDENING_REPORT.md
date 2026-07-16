# Post-Release Hardening — Master Report & Consolidated Backlog

**Date:** 2026-07-16 · **Mode:** Post-release hardening (preserve behavior, measure everything, no CI weakening).
**Status:** Audits complete. Implementation not started (except two verified-safe items noted). Awaiting fresh production baseline before batched implementation.

This is the single authoritative backlog. Detailed evidence lives in the per-area reviews: `PERFORMANCE_OPTIMIZATION_PLAN.md`, `PERFORMANCE_OPTIMIZATION_REPORT.md`, `WHITE_LABEL_AUDIT.md`, `SEO_REVIEW.md`, `DATABASE_REVIEW.md`, `SECURITY_REVIEW.md`, `DESIGN_SYSTEM_REVIEW.md`, `CODE_QUALITY_REVIEW.md`.

---

## 1. Audit phase — complete

| Area | Report | Headline |
|---|---|---|
| Performance | PERFORMANCE_OPTIMIZATION_PLAN | Perf 72 is ~entirely LCP (client-shell hydration); TBT/CLS/SI near-perfect |
| White-label | WHITE_LABEL_AUDIT | Infra comprehensive; emails ignore branding, ~15 marketing files hardcode brand |
| SEO | SEO_REVIEW | Strong base; missing default OG image, Course/Breadcrumb JSON-LD, hreflang |
| Database/Backend | DATABASE_REVIEW | Disciplined; one sync fan-out (announcements), one events N+1, sync cert PDF |
| Security | SECURITY_REVIEW | Strong; picomatch exception has upstream fix (CI-test to remove) |
| Design system | DESIGN_SYSTEM_REVIEW | Consistent, token-driven; 39 arbitrary values, 3 raw buttons |
| Code quality | CODE_QUALITY_REVIEW | Clean; 0 debt comments; framer-motion dead (removed) |
| Accessibility | (Lighthouse + axe) | **100** Lighthouse a11y; axe E2E green — no real issues found |

**Already applied (verified safe, measured/queued):** Tier-1 perf config (`optimizePackageImports` + `removeConsole` + `.browserslistrc`) — measured marginal (~−1 kB on ~20 routes, shared baseline unchanged); `framer-motion` removed. Both pending the next host build to confirm green + capture deltas.

---

## 2. Prerequisite before implementation — fresh production baseline (host, API running)

Per the agreed sequence, capture ONE authoritative baseline with the **API up** (so LCP reflects real content, not the fallback shell) before any further changes. Commands:

```powershell
# Terminal 1 — API (Laravel) up on :8000, then Terminal 2 — web against it:
cd "D:\Claude_Files\Projects\LMS\CoreLMS Implementation\corelms\apps\web"
npm install                 # syncs lockfile (framer-motion removal)
$env:NEXT_DISABLE_STANDALONE="1"; $env:NEXT_PUBLIC_API_BASE_URL="http://localhost:8000/api/v1"; npm run build; npm run start
# Terminal 3:
npx lighthouse http://localhost:3000 --output=json --output=html --output-path=./lighthouse.baseline.mobile  --chrome-flags="--headless"
npx lighthouse http://localhost:3000 --output=json --output=html --output-path=./lighthouse.baseline.desktop --preset=desktop --chrome-flags="--headless"
```

Capture into the baseline: build route table + shared First Load JS; Lighthouse **mobile** and **desktop** category scores; **LCP, FCP, CLS, TBT, INP**. Every later batch is measured against this exact baseline.

---

## 3. Consolidated backlog — ranked (impact / risk / effort / regression surface / measurement)

Effort: S ≤0.5d · M ~1–2d · L >2d. Risk/impact: L/M/H. Ordered within the agreed implementation sequence A→H (+ SEO/Security tracks).

### A. Tier-1 performance config — DONE (measured)
| ID | Item | Impact | Risk | Effort | Regression surface | Measurement | Status |
|---|---|---|---|---|---|---|---|
| A1 | optimizePackageImports (radix/vaul/sonner) + removeConsole + browserslist | L (measured) | L | S | build only | build route table (done: marginal); Lighthouse `legacy-javascript` (pending) | Applied; legacy-JS delta pending host Lighthouse |

### B. Code splitting
| ID | Item | Impact | Risk | Effort | Regression surface | Measurement |
|---|---|---|---|---|---|---|
| B1 | `next/dynamic` for design-system showcase (dev route, 242 kB) | L (dev-only) | L | S | `/design-system` only | route First Load JS |
| B2 | `next/dynamic` for chart components on reports/analytics | M | M | M | reports/analytics render + charts tests | route First Load JS + `tests/ui/charts` + Playwright |
| B3 | `next/dynamic` for heavy dialogs/drawers (on-interaction) | M | M | M | dialog/drawer open flows | route sizes + `tests/ui/confirm-dialog`,`data-grid` + Playwright |

### C. Client-boundary reduction (biggest LCP lever)
| ID | Item | Impact | Risk | Effort | Regression surface | Measurement |
|---|---|---|---|---|---|---|
| C1 | Convert purely-presentational `"use client"` components (landing/*, homepage/blocks/* with no state/hooks) to server components | **H** (LCP/FCP) | **M–H** | L | hydration of marketing/homepage; RTL/theme; homepage-blocks tests | `"use client"` count ↓ + LCP (baseline vs after) + `tests/landing/*` + Playwright marketing |

### D. Images & fonts
| ID | Item | Impact | Risk | Effort | Regression surface | Measurement |
|---|---|---|---|---|---|---|
| D1 | 4 raw `<img>` → `next/image` + `images.remotePatterns` config | M | M | M | logo/header + 3 homepage blocks layout | Lighthouse image audits + Playwright visual (header/homepage) |
| D2 | `preconnect` to Google Fonts origins for the white-label brand-font path | L | L | S | brand-font-configured path only | Lighthouse (brand font set) |

### E. Dead dependency / dead code cleanup
| ID | Item | Impact | Risk | Effort | Regression surface | Measurement | Status |
|---|---|---|---|---|---|---|---|
| E1 | Remove `framer-motion` (0 usages) | L | L | S | build/install only | `npm ls` + build green | **Applied** (needs `npm install`) |
| E2 | `ts-prune` unused exports + `depcheck` unused deps + unused `public/` assets sweep | L | L | M | wide but tool-verified | tool output (measured lists) + build green |

### F. White-label gaps
| ID | Item | Impact | Risk | Effort | Regression surface | Measurement |
|---|---|---|---|---|---|---|
| F1 | Transactional emails read `BrandSetting.email` (header/footer/logo/colours/signature); fallback to `app.name` | **H** | M | M | 3 notifications' output | send-in-test / Mailable snapshot + PHPUnit |
| F2 | Marketing metadata/body + `dictionaries.ts` use admin brand name (keep literal as default) | M | M | M | ~15 routes + i18n; **SEO metadata tests** | `tests/seo/metadata` + `tests/landing` + build |
| F3 | Footer locations/contact from `identity.address`/`support_email` | M | L | S | footer render | `tests/landing` + visual |
| F4 | Consolidate the two certificate-branding sources onto one | M | M | M | certificate render | cert render test + PHPUnit |

### G. UX polish + design-system alignment (NO redesign)
| ID | Item | Impact | Risk | Effort | Regression surface | Measurement |
|---|---|---|---|---|---|---|
| G1 | Map the 39 arbitrary Tailwind bracket values to scale tokens (keep legit micro-typography) | L | L | M | visual only | Playwright visual + Storybook |
| G2 | 3 raw `<button>` → `ui/Button` | L | L | S | 3 components' states | visual + a11y (focus) |
| G3 | Phase-3 polish pass (loading skeletons/empty/error states, focus states) where gaps exist | M | L | M | per-component | visual + axe |

### H. Database / backend
| ID | Item | Impact | Risk | Effort | Regression surface | Measurement |
|---|---|---|---|---|---|---|
| H1 | Announcement fan-out → single queued chunked job + batched locale/prefs | **H** (scale) | M | M | announcement delivery | query-count assertion + PHPUnit + queue test |
| H2 | Events listing N+1 → batch speaker IDs in controller | M | L | S | events list payload | query-count + `EXPLAIN` + PHPUnit |
| H3 | Certificate PDF pre-generate on `CertificateIssued` (queued) | M | L | M | first cert download | PHPUnit + timing |
| H4 | Add `->paginate()` caps to 3 unbounded list endpoints | L | L | S | payload shape (paginated) | PHPUnit contract |
| H5 | `course_trainer` unique `(course_id,user_id)` index | L | L | S | duplicate-link inserts | migration + PHPUnit |
| H6 | `courses.published_at` composite index for list sort | L | L | S | none (additive index) | `EXPLAIN` before/after |

### SEO track (Phase 5)
| ID | Item | Impact | Risk | Effort | Regression surface | Measurement |
|---|---|---|---|---|---|---|
| S1 | Default OG image (`opengraph-image`) + optional dynamic route | **H** (sharing) | L | S–M | metadata output | `tests/seo/metadata` + OG validator |
| S2 | `Course` JSON-LD on course detail | M | L | S | course detail head | JSON-LD validator + test |
| S3 | `BreadcrumbList` JSON-LD + real breadcrumbs on detail pages | M | M | M | detail page layout | validator + visual + a11y |
| S4 | Default per-path canonicals in course/list fallbacks | M | L | S | metadata | `tests/seo/metadata` |
| S5 | hreflang alternates (given cookie-locale model, evaluate feasibility) | M | M | M | metadata | validator |
| S6 | Enumerate catalog detail URLs in sitemap from catalog API | L | L | S | sitemap.xml | sitemap contract test |

### Security track (Phase 6)
| ID | Item | Impact | Risk | Effort | Regression surface | Measurement |
|---|---|---|---|---|---|---|
| SEC-A | Remove picomatch `.trivyignore` **iff** web-image Trivy stays green | L | L | S | CI Trivy gate | web-image Trivy job (CI) |
| SEC-B | Verify session cookie `HttpOnly`/`Secure`/`SameSite` | M | L | S | auth session | manual + integration test |
| SEC-C | (Optional, larger) nonce-based CSP to drop `script-src 'unsafe-inline'` | M | M | L | all inline scripts/hydration | CSP eval + full E2E |

---

## 4. Recommended execution order (matches agreed A→H, interleaving SEO/Security by cost)

1. **Baseline capture** (section 2) — gate for all measurement.
2. **E1** commit (framer-motion; already applied) → measure build.
3. **A1** legacy-JS confirmation (Lighthouse) → record.
4. **B1 → B2 → B3** (code splitting), each its own batch + re-measure.
5. **D1, D2** (images/fonts).
6. **C1** (client-boundary reduction) — highest LCP impact, most care; several small sub-batches by component group.
7. **E2** (ts-prune/depcheck sweep).
8. **F1 → F3 → F2 → F4** (white-label; emails first).
9. **S1 → S2 → S4 → S6 → S3 → S5** (SEO; OG image + Course schema first).
10. **G1 → G2 → G3** (UX polish).
11. **H2 → H5 → H6 → H4 → H1 → H3** (DB/backend; cheap indexes/N+1 first, fan-out last).
12. **SEC-A, SEC-B** (bounded); **SEC-C** only if approved (large).

**Per-batch gate (every item):** `npm run lint && npm run typecheck && npm test && npm run build && npm run build-storybook` + relevant Playwright + re-measure (build/Lighthouse or PHPUnit/EXPLAIN). Commit only when green **and** impact is measured. One concern per batch. No claim without before/after numbers.

## 5. What is explicitly NOT worth doing (already optimal)
TBT/CLS/Speed Index (near-perfect); middleware size (Edge runtime wrapper); charts (already dependency-free SVG); React Query defaults (sane, per-request); accessibility (Lighthouse 100 + axe green); design-system structure (single canonical kit, 0 hardcoded brand hex, 0 debt comments). Do not redesign.

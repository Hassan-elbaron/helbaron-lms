# HElbaron — Release Notes

## v1.0.0 (Release Ready) — 2026-07-16

Hardening project **closed**; repository transitioning to product development. Mandatory CI gate green on `5048750` (run #16 — all 7 jobs: API, Web, Architecture, Secret scan, E2E, API image, Web image). Production container images scan clean in Trivy (0 CRITICAL/HIGH).

### Fixed this cycle
- `/about` + `/contact` SSR crash — `getStaticPage` hardened against malformed payloads (guard + mock contract + regression test).
- `build-storybook` failure — pinned `webpack` to `5.100.2` (webpack 5.101 strict `Compilation` guard vs Next's bundled webpack). Preview builds green.
- Perf Tier-1: `optimizePackageImports` (radix/vaul/sonner), production `removeConsole`, conservative-modern `.browserslistrc` (measured marginal, kept as safe hygiene).
- Removed dead dependency `framer-motion` (0 source imports).

### Quality snapshot (measured)
- Web unit tests 114/114 green; backend suite green; typecheck + lint + build + build-storybook green.
- Lighthouse: Accessibility **100**, SEO **100**, Best Practices **96**, Performance **72** (mobile throttled, API-down shell — LCP-bound).

### Known limitations
See `KNOWN_LIMITATIONS.md` (consolidated). None block production; all are additive enhancements or low-priority polish. Full status in `FINAL_PROJECT_STATUS.md`; deployment in `DEPLOYMENT.md`.

---

## v1.0.0-rc.1 (Release Candidate) — 2026-07-05

First tagged Release Candidate of **HElbaron**, a bilingual (AR/EN) enterprise Learning
Management System: Laravel 12 modular-monolith API + Next.js 15 frontend, PostgreSQL,
Redis/Horizon, S3/CloudFront, Mux.

### What's in this candidate
- 10 complete, tested backend domains behind a stable `/api/v1` (no API changes in Step 15).
- External integrations (Stripe, Mux, S3/CloudFront, Mailgun, Twilio, Firebase) — real by env,
  fakes by default for local/test.
- Production hardening: security headers/CSP/HSTS, correlation-id middleware, trusted
  proxies/hosts, secure cookies, restricted CORS, structured JSON logging, liveness/readiness
  probes, tuned Horizon + queue config.
- Ops: production Docker image + `docker-compose.prod.yml` + nginx, **`scripts/deploy.sh`**
  (zero-downtime) and **`scripts/rollback.sh`**, `php artisan env:validate`, and the full
  runbook / monitoring / incident / rollback / DR / secrets documentation set.

### Known limitations (tracked)
- `/admin` (Filament) disabled pending a v3→v4 resource migration.
- Firebase push on FCM legacy HTTP (v1 planned).
- Add FK covering indexes after load testing (see `docs/audits/PRODUCTION_AUDIT.md`).

### Promote to GA
Complete `docs/release/RELEASE_CHECKLIST.md`, run the full toolchain green, and resolve the
limitations above. Detailed notes: `docs/release/RELEASE_NOTES_v1.0.0.md`.

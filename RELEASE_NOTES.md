# HElbaron — Release Notes

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

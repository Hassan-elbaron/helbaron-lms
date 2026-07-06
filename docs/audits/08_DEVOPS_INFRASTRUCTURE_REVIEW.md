# HElbaron LMS — DevOps & Infrastructure Review (08)

**Repository:** local working copy (`apps/api` Laravel, `apps/web` Next.js, `infra/`, `scripts/`, `.github/workflows/`, `docker-compose*.yml`).
**Scope:** DevOps, deployment, infrastructure, environments, CI/CD, observability, and production operations ONLY.
**Assumes:** Reviews 01–07 exist; not repeated.
**Method:** Direct inspection of `apps/api/Dockerfile` (prod), `Dockerfile.dev`, `docker-compose.yml` (dev), `docker-compose.prod.yml`, `.github/workflows/ci.yml`, `infra/nginx/nginx.conf`, `infra/php/*`, `scripts/{deploy,rollback,setup}.sh`, `HealthController`, `routes/console.php` (scheduler), `.dockerignore`, and the `docs/ops/*` runbook set.
**Benchmark bar:** production SaaS ops (managed data services, CD with immutable artifacts, automated backups, monitoring/alerting, staging parity).

---

## Executive Summary

The operational **foundations are thoughtfully built for a first release**: a multi-stage production Dockerfile (composer no-dev → `php:8.3-fpm-alpine` with OPcache), a clean three-role production compose (`api`/`horizon`/`scheduler` from one image), real **liveness + readiness health endpoints** that check Postgres and Redis, **zero-downtime-style deploy and rollback scripts** with a readiness gate and expand/contract migration awareness, durable Redis (`appendonly` + `noeviction` for queue safety), correlation-ID propagation through nginx→FPM, JSON logging, and a comprehensive `docs/ops/*` runbook set (deployment, DR, incident, monitoring, rollback, secrets). This is well above typical for a pre-launch codebase.

However, several **production-critical pieces are missing or only documented, not implemented**:

1. **The frontend has no production deployment.** There is **no `apps/web` Dockerfile and no web service in `docker-compose.prod.yml`.** The entire production stack ships the API only; how the Next.js app is built and hosted in production is undefined.
2. **The scheduler runs nothing.** `routes/console.php` contains **zero scheduled tasks** ("Scaffold: no scheduled tasks yet"), yet a `scheduler` container loops `schedule:run` every 60s. Recurring domain work — session reminders (Live), digests (Notifications), metric rollups (Analytics), token/OTP/failed-job pruning, Horizon snapshots — **will never fire**.
3. **No automated backups.** Postgres runs **in-container with a local volume** and there is **no `backup.sh`, no scheduled dump, no restore automation** — only a DR *document*. A host/volume loss = data loss.
4. **CI builds an image but never publishes it.** The `image` job builds with `push: false` and tag `helbaron-api:ci`. There is **no registry push and no deploy/CD job**, so `rollback.sh <previous-tag>` has **no source of immutable previous tags** to roll back to.
5. **No security scanning and a non-gating static-analysis step.** CI has no `composer audit`, `npm audit`, image scan (Trivy), or secret scan (gitleaks), and PHPStan is run with `|| echo warning` (failures don't fail the build).
6. **No monitoring/alerting and no staging environment.** Logs are structured, but there is no metrics/APM/error-tracking wiring (Prometheus/Grafana/Sentry) and only `local` + `prod` compose files — **no staging** for pre-prod parity.

Self-hosting Postgres/Redis in compose with local volumes is acceptable for a small single-node launch but carries **no HA, no managed backups, and no failover**.

**Bottom line:** the API's runtime, health, and deploy/rollback mechanics are solid; the gaps are **frontend hosting, scheduled work, backups, a real CD pipeline with a registry, security gates, monitoring, and staging** — all required before taking real traffic.

---

## Overall DevOps Score

**6.0 / 10** — "strong API runtime & runbooks; missing frontend hosting, backups, CD, scheduled jobs, and monitoring."

| Category | Score | Justification |
|----------|-------|---------------|
| Docker | 7.0 | Multi-stage, alpine, OPcache, healthcheck; runs as root, no web image |
| Environment | 6.0 | env_file per role; no staging; env-validation command exists |
| Secrets | 5.0 | File-based `.env.production`; no secrets manager |
| CI/CD | 5.5 | Good test job; no CD, no scanning, non-gating PHPStan |
| Deployment | 5.5 | Solid scripts; single-node; no registry; no web deploy |
| Frontend hosting | 2.0 | Undefined — no Dockerfile/service |
| Backend hosting | 6.5 | FPM+nginx+horizon+scheduler well-formed |
| Database | 4.0 | Self-hosted, no automated backup/HA |
| Redis & queue | 7.5 | Horizon + durable Redis + healthcheck |
| Scheduler | 4.0 | Container exists; zero tasks; while-loop |
| Storage & CDN | 7.0 | S3 + CloudFront signed URLs (per 05) |
| Security headers | 6.5 | App-level middleware; nginx minimal |
| Monitoring | 3.0 | No metrics/APM/alerting wired |
| Logging | 8.0 | JSON + correlation IDs end-to-end |
| Backup & restore | 3.5 | Documented only, not automated |
| Rollback | 6.0 | Script + expand/contract awareness; no tag source |
| Performance ops | 6.0 | OPcache + config/route/event cache warmed |

---

## Docker Review — 7.0

**Strengths:** two-stage build (`composer:2` vendor stage → `php:8.3-fpm-alpine` runtime); `--no-dev` + `--classmap-authoritative` autoloader; OPcache + custom `php.ini`; FPM `cgi-fcgi` HEALTHCHECK; `.dockerignore` excludes `vendor/node_modules/.env/.git/storage logs` (no secret baked into image).

| # | Sev | Finding | Evidence | Recommendation |
|---|-----|---------|----------|----------------|
| DKR-1 | Med | Container runs as root (no `USER`) | Dockerfile has no `USER www-data`; `CMD php-fpm` | Add a non-root `USER` for the FPM master; keep `www-data` on storage |
| DKR-2 | Med | No pinned digests / base image update policy | `FROM php:8.3-fpm-alpine` (tag, not digest) | Pin digests or add automated base-image rebuilds + scanning |
| DKR-3 | Low | `ADD` remote installer from GitHub latest | line 13 | Pin extension-installer version for reproducible builds |
| DKR-4 | Low | Redundant opcache copy (`cp ... || true` then explicit COPY) | lines 26/29 | Remove the dead `cp` line |

## Environment Review — 6.0

Per-role `env_file: .env.production`; dev uses compose `environment:` overrides; an `env:validate` command exists (`ValidateEnvironment`). **Gaps:** only `local` and `prod` — **no `staging`**; no documented required-env matrix enforced at container start (run `env:validate` as an init gate). Recommend a `docker-compose.staging.yml` and an entrypoint that fails fast if required env is missing.

## Secrets Review — 5.0

| # | Sev | Finding | Risk | Recommendation |
|---|-----|---------|------|----------------|
| SEC-1 | High | Secrets in a flat `.env.production` file on the host | File exposure, no rotation, no audit | Use a secrets manager (AWS Secrets Manager/SSM, Vault) injected at runtime; keep `.env` for local only |
| SEC-2 | Med | No documented rotation for APP_KEY, DB, Stripe/Mux keys | Long-lived credentials | Define rotation cadence + procedure (docs/ops/SECRETS.md exists — wire to real rotation) |
| SEC-3 | Low | Compose interpolates `DB_PASSWORD:?` (fails if unset) — good | — | Keep the fail-fast interpolation |

## CI/CD Review — 5.5

**Strengths:** API job spins Postgres+Redis services, sets extensions, caches composer, runs Pint, migrate `--force`, and Pest; web job runs typecheck + tests + build; image job builds the prod Dockerfile.

| # | Sev | Finding | Evidence | Risk | Recommendation |
|---|-----|---------|----------|------|----------------|
| CI-1 | High | No CD: image built with `push: false`, no registry, no deploy job | `ci.yml` image job | Rollback has no immutable tag source; manual deploys | Push tagged images to a registry (GHCR/ECR) on `main`/tags; add a deploy job (or documented promote step) |
| CI-2 | High | No security scanning | no `composer audit`/`npm audit`/Trivy/gitleaks | Vulnerable deps/images/secrets ship silently | Add dependency audit, image scan (Trivy), and secret scan gates |
| CI-3 | Med | PHPStan non-gating (`|| echo warning`) | line 48 | Type/static errors don't fail CI | Make PHPStan gating once baseline is clean |
| CI-4 | Med | No coverage gate, no E2E, no web lint | ci.yml | Regressions slip | Add coverage threshold + Playwright smoke (per 06) + `next lint` |
| CI-5 | Low | `npm ci || npm install` fallback can drift lockfile | line 62 | Non-reproducible installs | Use `npm ci` only; fail if lock out of sync |
| CI-6 | Low | No concurrency cancel / no path filters | ci.yml | Wasted runners | Add `concurrency` + `paths` |

## Deployment Review — 5.5

`deploy.sh`: build → `migrate --force` (forward-only) → warm `config/route/event` caches → roll `api/nginx/horizon/scheduler` → 30-try readiness gate on `/api/v1/health/ready`. `rollback.sh`: redeploy previous image tag, `optimize:clear` + re-cache, readiness verify, with an explicit expand/contract + DB-restore warning.

| # | Sev | Finding | Risk | Recommendation |
|---|-----|---------|------|----------------|
| DEP-1 | High | "Zero-downtime" on a single-container `up -d --build api` | Brief outage on recreate; no rolling/blue-green | Run ≥2 api replicas behind nginx/LB and roll one at a time, or blue-green with health-gated cutover |
| DEP-2 | High | Rollback depends on immutable previous image tags that CI never produces (CI-1) | Rollback may be impossible | Publish immutable tags per release (git SHA/semver) to a registry |
| DEP-3 | Med | Migrations run inline in deploy with old code still serving | Contractive migrations break running pods | Enforce expand/contract discipline + a migration-safety checklist gate |
| DEP-4 | Med | No smoke/synthetic post-deploy tests beyond readiness | Bad release serves traffic | Add post-deploy smoke (login, catalog, checkout ping) |

## Frontend Hosting Review — 2.0

| # | Sev | Finding | Evidence | Risk | Recommendation |
|---|-----|---------|----------|------|----------------|
| FE-1 | Critical | No production hosting for `apps/web` | no `apps/web/Dockerfile`; no web service in `docker-compose.prod.yml` | The frontend cannot be deployed by the current infra | Define hosting: either a Next.js standalone Docker image + compose service behind nginx, or a managed target (Vercel/Amplify). Add build + deploy to CI |
| FE-2 | Med | No CDN/edge/caching config for the web app | — | Poor global latency | Front the web app with a CDN; set cache headers/ISR |
| FE-3 | Med | Web env (`NEXT_PUBLIC_API_BASE_URL`) not wired into any prod pipeline | `.env.local` only | Broken API base in prod | Provide build-time env in the web deploy target |

## Backend Hosting Review — 6.5

nginx → `api:9000` FPM, `client_max_body_size 52M`, `server_tokens off`, dotfiles denied, correlation-ID forwarded. Well-formed. Gaps: no gzip/brotli, no static asset cache headers, no nginx-level `limit_req` rate limiting, no TLS in-file (assumed upstream — document the ALB/CloudFront TLS contract). Add gzip + caching + a request-rate limiter at nginx as defense-in-depth.

## Database Review — 4.0

| # | Sev | Finding | Risk | Recommendation |
|---|-----|---------|------|----------------|
| DB-1 | High | Postgres self-hosted in compose with a local volume | No HA, no PITR, single point of failure | Use managed Postgres (RDS/Cloud SQL) for prod, or at minimum automated dumps + off-host storage |
| DB-2 | High | No automated backup (see Backup section) | Data loss | Scheduled `pg_dump`/WAL archiving to S3 with retention |
| DB-3 | Med | No connection pooler | Connection exhaustion under load (worsened by 05/CHK-1 in-txn gateway call) | Add PgBouncer |
| DB-4 | Low | No read replica strategy | Scale ceiling | Plan replicas for analytics/read load |

## Redis and Queue Review — 7.5

Redis `appendonly yes` + `appendfsync everysec` + `maxmemory-policy noeviction` (correct for a queue broker — jobs won't be evicted). Horizon service supervises workers with a `horizon:status` healthcheck. **Gaps:** no separate Redis instances for cache vs queue vs session (a `FLUSHDB`/eviction on cache would also hit queue/session if shared); no Horizon autoscaling/`maxProcesses` review; queue retry/backoff not set on jobs (per 05/JOB-1). Recommend separate Redis logical DBs/instances for cache vs broker, and confirm Horizon supervisor scaling.

## Scheduler Review — 4.0

| # | Sev | Finding | Evidence | Risk | Recommendation |
|---|-----|---------|----------|------|----------------|
| SCH-1 | High | Zero scheduled tasks registered | `routes/console.php` empty scaffold | Reminders, digests, rollups, pruning never run | Register schedule entries: Live session reminders, Notifications digests, Analytics `MetricRollupService`, `sanctum:prune-expired`, `queue:prune-failed`, `auth:clear-resets`, Horizon snapshot, OTP cleanup |
| SCH-2 | Med | Scheduler is a `while true; schedule:run; sleep 60` loop | If a run hangs, all future ticks stall; drift | Use `php artisan schedule:work` (long-running, drift-corrected) or a real cron sidecar |
| SCH-3 | Low | No overlap protection / singleton on future jobs | Double-runs across replicas | Use `withoutOverlapping()` + `onOneServer()` |

## Storage and CDN Review — 7.0

S3 + CloudFront with signed URLs (`CloudFrontUrlSigner`, Mux signed playback — per 05). Good. Verify: private buckets (no public ACLs), lifecycle policies for exports/certificates, CORS on the asset bucket, and that `client_max_body_size` (52M) matches upload validation. Document the CloudFront cache/invalidation policy.

## Security Headers Review — 6.5

Security headers are applied at the app layer (`SecurityHeaders` middleware — CSP/HSTS per 04/05), which is fine. nginx adds `server_tokens off` + dotfile deny but no headers itself. Recommend defense-in-depth: set HSTS + basic security headers at the edge/nginx too, and confirm CORS/Sanctum stateful domains are environment-correct in prod (`SANCTUM_STATEFUL_DOMAINS`, `FRONTEND_URL`).

## Monitoring Review — 3.0

| # | Sev | Finding | Risk | Recommendation |
|---|-----|---------|------|----------------|
| MON-1 | High | No metrics/APM/error-tracking wired | Blind to prod health/errors | Add error tracking (Sentry) + metrics (Prometheus exporter or hosted APM) + dashboards |
| MON-2 | High | No alerting | Incidents unnoticed | Alert on 5xx rate, readiness 503, queue depth/failed jobs, DB/Redis down, cert/webhook failures |
| MON-3 | Med | Health endpoints exist but nothing scrapes them | Undetected degradation | Wire uptime checks + LB health to `/health/ready`; Horizon queue metrics to alerts |

## Logging Review — 8.0

JSON structured logging with a `CorrelationProcessor` and `AssignCorrelationId` middleware; correlation ID flows nginx→FPM (`HTTP_X_CORRELATION_ID`). Strong. Gaps: no centralized log shipping/retention defined (ship to CloudWatch/Loki/ELK), and PII-scrubbing policy for logs should be documented.

## Backup and Restore Review — 3.5

| # | Sev | Finding | Evidence | Risk | Recommendation |
|---|-----|---------|----------|------|----------------|
| BAK-1 | High | No automated backup implementation | `scripts/` has deploy/rollback/setup, no `backup.sh`; DR is a doc only | Total data loss on volume/host failure | Implement scheduled `pg_dump` (+ Redis AOF snapshot) to S3 with retention + encryption; automate via scheduler/cron |
| BAK-2 | High | Restore is documented but untested | DISASTER_RECOVERY_GUIDE.md | Unknown RTO/RPO; restore may fail when needed | Provide `restore.sh`, run a quarterly restore drill, record RTO/RPO |
| BAK-3 | Med | No backup for uploaded media beyond S3 durability assumptions | — | Accidental deletion/ransom | Enable S3 versioning + lifecycle + cross-region replication for critical buckets |

## Rollback Review — 6.0

`rollback.sh` is well-reasoned (immutable-tag redeploy, cache reset, readiness verify, expand/contract + DB-restore warning). The **blocker is upstream**: no registry/immutable tags from CI (CI-1/DEP-2), so there may be nothing to roll back to. Also no automated DB restore path (BAK-2). Fix CD tagging + restore automation to make rollback real.

## Performance Operations Review — 6.0

OPcache tuned; deploy warms `config:cache`/`route:cache`/`event:cache`; FPM worker concurrency handled (dev sets `PHP_CLI_SERVER_WORKERS`, prod uses FPM). Gaps: no PHP-FPM pool tuning (pm.max_children etc.) surfaced, no read caching at the app layer (per 04/05), no PgBouncer (DB-3), no autoscaling. Recommend FPM pool sizing per instance and the read-cache seam from prior reviews.

---

## Production Readiness

**Verdict: NOT production-ready as-is.** Hard blockers before real traffic: **FE-1** (frontend has no deploy), **BAK-1/BAK-2** (no automated backup/restore), **SCH-1** (no scheduled jobs → reminders/digests/rollups dead), **CI-1/DEP-2** (no CD/registry → no real rollback), **MON-1/MON-2** (no monitoring/alerting), and **DB-1** (self-hosted DB w/o HA/backups). The API runtime, health checks, logging, and deploy/rollback *mechanics* are strong and can be built on quickly. Add a **staging** environment to validate all of the above before prod.

---

## High Priority Fixes (ordered)

- **P0-1 (FE-1):** Define and wire frontend production hosting (Docker standalone or managed) + CI build/deploy + env.
- **P0-2 (BAK-1/BAK-2):** Automated encrypted `pg_dump` → S3 with retention + `restore.sh` + a tested drill.
- **P0-3 (SCH-1/SCH-2):** Register all recurring schedule entries; switch scheduler to `schedule:work`.
- **P0-4 (CI-1/DEP-2):** Push immutable tagged images to a registry on release; add a deploy/promote job.
- **P1-1 (MON-1/MON-2):** Wire error tracking + metrics + alerting on golden signals.
- **P1-2 (CI-2/CI-3):** Add dependency/image/secret scanning; make PHPStan gating.
- **P1-3 (DB-1/DB-3):** Managed Postgres (or automated backups) + PgBouncer; separate Redis for cache vs broker.
- **P2-1 (env/staging):** Add `docker-compose.staging.yml` + env-validate init gate + non-root container user (DKR-1).

---

## AI Implementation Prompts

**AIP-1 — Frontend production image + deploy (FE-1)**
> Add `apps/web/Dockerfile` using Next.js `output: "standalone"` (multi-stage: `node:20-alpine` build → minimal runtime), set `NEXT_PUBLIC_API_BASE_URL` as a build arg, run as non-root, expose 3000. Add a `web` service to `docker-compose.prod.yml` behind nginx (proxy `/` to web, `/api` to the API), and add a CI job that builds and pushes the web image alongside the API image. Do not change app code beyond `next.config.ts` `output`.

**AIP-2 — Automated backup + restore (BAK-1/BAK-2)**
> Create `scripts/backup.sh` that runs `pg_dump` (custom format), gzips, encrypts, and uploads to `s3://.../backups/` with a timestamped key and a retention/prune step; snapshot Redis AOF too. Create `scripts/restore.sh` that fetches a chosen backup, decrypts, and restores into a target database with a confirmation prompt. Register `backup.sh` as a daily scheduled task (AIP-3) and document RTO/RPO + a quarterly drill in `docs/ops/DISASTER_RECOVERY_GUIDE.md`.

**AIP-3 — Register scheduled tasks + schedule:work (SCH-1/SCH-2)**
> In `apps/api/routes/console.php`, register: Live session reminders (dispatch due reminders), Notifications digest send (per `DigestService`), Analytics `MetricRollupService` rollup, `sanctum:prune-expired --hours=24`, `queue:prune-failed --hours=168`, `auth:clear-resets`, `horizon:snapshot` (every 5 min), and OTP cleanup — each with `->withoutOverlapping()` and `->onOneServer()`. Change the `scheduler` service in `docker-compose.prod.yml` to `command: ["php","artisan","schedule:work"]`.

**AIP-4 — CD with immutable tags + registry (CI-1/DEP-2)**
> Update `.github/workflows/ci.yml`: on push to `main` and on tags, build and push the API (and web, AIP-1) images to a registry (GHCR/ECR) tagged with the git SHA and semver. Add a `deploy` job (manual approval or tag-triggered) that runs `scripts/deploy.sh` with `HELBARON_IMAGE` set to the pushed tag. Ensure `rollback.sh` can reference prior pushed tags.

**AIP-5 — CI security + quality gates (CI-2/CI-3)**
> Add CI steps: `composer audit`, `npm audit --audit-level=high`, Trivy image scan (fail on HIGH/CRITICAL), and gitleaks secret scan. Make PHPStan gating by removing `|| echo` once the baseline passes. Add `next lint` and a coverage threshold to the web/api jobs.

**AIP-6 — Monitoring + alerting (MON-1/MON-2)**
> Integrate Sentry (API + web) for error tracking, expose Prometheus metrics (or a hosted APM), and define alerts on: 5xx rate, `/health/ready` 503, Horizon failed-job count and queue latency, DB/Redis down, and payment webhook/certificate failures. Point LB/uptime checks at `/api/v1/health/ready` and `/api/v1/health/live`.

**AIP-7 — Data-tier hardening (DB-1/DB-3, Redis split)**
> For prod, switch Postgres to a managed instance (or, if self-hosted, add automated backups from AIP-2 + volume snapshots) and add PgBouncer as a connection pooler. Split Redis into separate logical instances/DBs for cache, queue, and session so a cache flush/eviction cannot affect the queue broker.

**AIP-8 — Staging + hardening (env/DKR-1)**
> Add `docker-compose.staging.yml` mirroring prod with staging env/secrets, add an entrypoint that runs `php artisan env:validate` and fails fast on missing required vars, and add a non-root `USER` to `apps/api/Dockerfile` for the FPM master. Add gzip + static cache headers + a `limit_req` zone to `infra/nginx/nginx.conf`.

---

## Acceptance Criteria

- AC1 (FE-1): The Next.js app has a production build artifact and a hosting target wired into CI; a fresh deploy serves the web app with the correct API base URL.
- AC2 (BAK): A scheduled encrypted DB backup lands in off-host storage with retention; a documented restore has been executed successfully in a drill with recorded RTO/RPO.
- AC3 (SCH): `routes/console.php` registers all recurring tasks; the scheduler uses `schedule:work`; reminders/digests/rollups/pruning are observed running.
- AC4 (CD): Every release pushes immutable, tagged images to a registry; `rollback.sh` successfully redeploys a prior tag; a deploy job exists.
- AC5 (security gates): CI fails on high-severity dependency/image/secret findings; PHPStan is gating.
- AC6 (monitoring): Error tracking + metrics + alerting are live; an induced 503/failed-job triggers an alert.
- AC7 (data tier): Prod DB has automated backups (or managed service) and a connection pooler; cache and queue Redis are isolated.
- AC8 (env parity): A staging environment exists; containers fail fast on missing required env; the API container runs as non-root.
- AC9 (deploy safety): A post-deploy smoke test runs after the readiness gate; contractive migrations are gated by an expand/contract checklist.
- AC10 (traceability): Every issue (DKR/SEC/CI/DEP/FE/DB/SCH/MON/BAK IDs) maps to a fix and a criterion.

---

### Appendix — Evidence index
- Prod image: `apps/api/Dockerfile` (multi-stage, `php:8.3-fpm-alpine`, OPcache, FPM healthcheck; no `USER`).
- Prod stack: `docker-compose.prod.yml` (api/nginx/horizon/scheduler/postgres/redis; **no web service**; env_file `.env.production`; Redis `appendonly`+`noeviction`).
- CI: `.github/workflows/ci.yml` (Pint, PHPStan non-gating, migrate, Pest; web typecheck/test/build; image build `push:false`; no scan/CD).
- Deploy/rollback: `scripts/deploy.sh` (migrate→cache→roll→readiness gate), `scripts/rollback.sh` (prev tag + expand/contract warning). No `backup.sh`/`restore.sh`.
- nginx: `infra/nginx/nginx.conf` (FPM proxy, correlation ID, dotfile deny; no gzip/headers/rate-limit).
- Health: `HealthController` (live = dependency-free; ready = Postgres PDO + Redis ping, 503 on degrade).
- Scheduler: `routes/console.php` = **empty scaffold** (no scheduled tasks).
- `.dockerignore`: excludes `vendor/node_modules/.env/storage logs/.git`.
- Runbooks present (not implementations): `docs/ops/{DEPLOYMENT,DISASTER_RECOVERY,INCIDENT_RESPONSE,MONITORING,OPERATIONS_HANDBOOK,PRODUCTION_RUNBOOK,ROLLBACK}.md`, `docs/ops/SECRETS.md`.

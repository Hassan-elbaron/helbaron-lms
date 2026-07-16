# Deployment Guide — CoreLMS

Authoritative deployment + rollback + monitoring reference. Production is deployed from the container images built and scanned by CI (`.github/workflows/ci.yml`) and published to GHCR tagged `sha-<commit>` on `main`.

## Prerequisites
- PostgreSQL 16, Redis 7 reachable from the API.
- S3 bucket + CloudFront distribution (storage/CDN); Mux account (video).
- Secrets set in the deploy environment (never in the repo): `APP_KEY`, DB creds, Redis, S3/CloudFront keys, Mux keys, Sentry DSN, mail transport.
- Web env: `NEXT_PUBLIC_API_BASE_URL`, `API_INTERNAL_URL`.

## Deploy steps
1. **Confirm CI green** for the target commit (7 mandatory jobs) and images published to GHCR (`api:sha-<commit>`, `web:sha-<commit>`).
2. **Pull the tagged images** in the target environment (do not rebuild in prod).
3. **Run migrations:** `php artisan migrate --force`.
4. **Warm caches:** `php artisan config:cache && route:cache && event:cache` (+ `filament:cache-components` if used).
5. **Start workers:** ensure Horizon is running for queues (notifications, certificate PDF, exports).
6. **Smoke test:** `GET /api/v1/health` = ok; load `/`, a course page, login; verify Filament admin loads.
7. **Flip traffic** to the new version (blue/green or rolling).

## Pre-deploy verification (mandatory gate — run before tagging a release)
From `apps/web`:
```
npm install          # sync lockfile (required after dependency changes)
npm run lint
npm run typecheck
npm test
npm run build
npm run build-storybook
```
From `apps/api`:
```
composer install
php artisan test
```
Plus the CI pipeline (push to a branch / PR) must be green. Do not add new gates.

## Rollback
1. Re-deploy the previous `sha-<commit>` image (kept in GHCR).
2. If the release included a migration, run its down/revert or restore the pre-deploy DB snapshot.
3. Invalidate CloudFront for changed assets.
4. Smoke test `/api/v1/health` + login + a course page on the rolled-back version.
5. Record the rollback in `CHANGELOG.md`.

## Monitoring (post-deploy watch)
- **Uptime:** `/api/v1/health` (+ web `/`).
- **Errors:** Sentry release-tagged error rate.
- **Queues:** Horizon — failed jobs, wait time (notifications, certificate PDF, exports).
- **DB/cache:** p95 API latency, slow-query log, Redis memory.
- **CDN/video:** CloudFront hit ratio, Mux delivery health.
- **Security:** scheduled Trivy image scan; gitleaks in CI.

## Local development (for reference)
`docker compose up` runs Postgres, Redis, and the Laravel API (PHP 8.3). The Next.js web app runs on the host (`npm run dev` / `npm run build`). See `README.md`.

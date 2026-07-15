# HElbaron — Production Runbook

## Health & readiness
- Liveness: `GET /up`, `GET /api/v1/health/live`.
- Readiness: `GET /api/v1/health/ready` → 200 `ready` or 503 `degraded` (checks Postgres+Redis).

## Common operations
```bash
# Queues
php artisan horizon:status
php artisan horizon:pause      # drain before maintenance
php artisan horizon:continue
php artisan queue:failed
php artisan queue:retry all

# Caches
php artisan optimize            # config+route+event cache
php artisan optimize:clear      # on rollback / config change

# Migrations
php artisan migrate --force
php artisan migrate:status
```

## Backups & deploys
```bash
./scripts/backup.sh                 # ad-hoc pg_dump -> backups/db-<ts>.sql.gz (+ optional S3)
./scripts/verify-backup.sh          # restore drill into a temp DB (safe, automated)
./scripts/restore.sh --force <file> # DESTRUCTIVE restore (typed confirmation required)
./scripts/deploy.sh sha-<commit>    # deploy a CI-pushed GHCR image (or no arg = on-host build)
./scripts/rollback.sh ghcr.io/<org>/<repo>/api:sha-<commit>
```
Scheduled dumps run via the `db-backup` service in `docker-compose.prod.yml`
(interval `BACKUP_INTERVAL_SECONDS`, retention `BACKUP_RETENTION_DAYS`). CI pushes
`ghcr.io/<repo>/api` and `.../web` images tagged `sha-<commit>` + `latest` on main;
deploys run via the `Deploy` GitHub workflow (see `.github/workflows/deploy.yml`).
Uptime probing: `.github/workflows/uptime.yml` (set the `UPTIME_URL` repo variable).

## Correlation IDs
Every request carries `X-Correlation-ID` (echoed on the response and in the error envelope).
Grep structured logs by `correlation_id` to trace a request across web + queue.

## Playbooks
- **Readiness 503**: check DB/Redis reachability, connection limits, and recent deploy;
  `/api/v1/health/ready` body names the failing dependency.
- **Queue backlog**: `horizon:status`; scale `HORIZON_*_MAX_PROCESSES`; inspect slow jobs.
- **Elevated 5xx**: pull logs by correlation id; check provider outages
  (Stripe/Mux/Mailgun/Twilio/Firebase) — providers are isolated behind managers and fail closed.

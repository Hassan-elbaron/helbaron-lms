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

## Correlation IDs
Every request carries `X-Correlation-ID` (echoed on the response and in the error envelope).
Grep structured logs by `correlation_id` to trace a request across web + queue.

## Playbooks
- **Readiness 503**: check DB/Redis reachability, connection limits, and recent deploy;
  `/api/v1/health/ready` body names the failing dependency.
- **Queue backlog**: `horizon:status`; scale `HORIZON_*_MAX_PROCESSES`; inspect slow jobs.
- **Elevated 5xx**: pull logs by correlation id; check provider outages
  (Stripe/Mux/Mailgun/Twilio/Firebase) — providers are isolated behind managers and fail closed.

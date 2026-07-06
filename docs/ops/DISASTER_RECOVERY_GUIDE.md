# HElbaron — Disaster Recovery Guide

## Objectives
- **RPO** ≤ 5 min (Postgres PITR / frequent snapshots).
- **RTO** ≤ 60 min (restore + redeploy).

## Backups
### PostgreSQL
- Managed: enable automated backups + PITR (WAL). Retain ≥ 14 days.
- Self-managed nightly logical dump:
  ```bash
  pg_dump --format=custom --no-owner --dbname="$DATABASE_URL" \
    --file="helbaron-$(date +%F).dump"
  # store off-site (S3, versioned, SSE); test restores monthly
  ```

### Redis
- `appendonly yes`, `appendfsync everysec`, `maxmemory-policy noeviction` (set in compose).
- Redis holds queues/cache/sessions — recoverable state, not source of truth. Losing it drops
  in-flight jobs (idempotency + `failed_jobs` limit blast radius) and sessions (users re-auth).

### Object storage (S3)
- Bucket versioning + cross-region replication for certificates/exports/media.

## Restore procedure
1. Provision Postgres; restore latest dump / PITR to target timestamp:
   `pg_restore --clean --if-exists --no-owner -d "$DATABASE_URL" helbaron-<date>.dump`
2. Provision Redis (empty is fine).
3. Deploy the matching app image tag; `php artisan migrate:status` to confirm schema parity.
4. Warm caches; verify readiness; replay `failed_jobs` if appropriate.
5. Validate a smoke path: login → catalog → enrol → certificate verify.

## DR drills
Run a quarterly restore into an isolated environment; record actual RPO/RTO and fix gaps.

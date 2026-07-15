# HElbaron — Disaster Recovery Guide

## Objectives
- **RPO** ≤ 5 min (Postgres PITR / frequent snapshots).
- **RTO** ≤ 60 min (restore + redeploy).

## Backups
### PostgreSQL
- **Scheduled (compose):** the `db-backup` service in `docker-compose.prod.yml` runs
  `pg_dump --clean --if-exists | gzip` into `./backups/` every `BACKUP_INTERVAL_SECONDS`
  (default 24h) and prunes files older than `BACKUP_RETENTION_DAYS` (default 14).
- **Ad-hoc / host cron:** `scripts/backup.sh` — same dump format, dump-size sanity check,
  retention pruning, and optional S3 upload when `BACKUP_S3_BUCKET` is set and the aws CLI
  is installed (it warns and skips otherwise — no silent fake).
- **Managed alternative:** on managed Postgres, enable automated backups + PITR (WAL),
  retain ≥ 14 days. PITR is what achieves the ≤ 5 min RPO objective; the logical-dump
  path above has an effective RPO equal to the backup interval (default ~24h). Tighten
  `BACKUP_INTERVAL_SECONDS` or move to PITR to close that gap.

### Redis
- `appendonly yes`, `appendfsync everysec`, `maxmemory-policy noeviction` (set in compose).
- Redis holds queues/cache/sessions — recoverable state, not source of truth. Losing it drops
  in-flight jobs (idempotency + `failed_jobs` limit blast radius) and sessions (users re-auth).

### Object storage (S3)
- Bucket versioning + cross-region replication for certificates/exports/media.

## Restore procedure
1. Compose stack: `./scripts/restore.sh --force backups/db-<timestamp>.sql.gz`
   (refuses to run without `--force` plus a typed confirmation of the target DB name;
   prints table/migration counts as post-restore verification). On managed Postgres,
   use PITR to the target timestamp instead.
2. Provision Redis (empty is fine).
3. Deploy the matching app image tag — CI pushes `ghcr.io/<org>/<repo>/api:sha-<commit>`
   to GHCR, so any previous build is pullable; `php artisan migrate:status` to confirm
   schema parity.
4. Warm caches; verify readiness; replay `failed_jobs` if appropriate.
5. Validate a smoke path: login → catalog → enrol → certificate verify.

## DR drills
- **Automated drill:** `./scripts/verify-backup.sh` restores the latest backup into a
  throwaway database inside the postgres container, verifies table count > 0, and drops
  it. It never touches the live database — safe to schedule via host cron (e.g. weekly).
- **Full drill:** quarterly restore into an isolated environment; record actual RPO/RTO
  and fix gaps.

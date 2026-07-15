# HElbaron — Rollback Guide

## Principle
Images are immutable and tagged by version. Rollback = redeploy the previous tag. Database
migrations follow **expand/contract** so the previous app version stays compatible with the
current schema.

## App rollback (no schema change)
CI pushes every main/tag build to GHCR as `ghcr.io/<org>/<repo>/api:sha-<commit>`, so the
previous image is always one pull away:
```bash
./scripts/rollback.sh ghcr.io/<org>/<repo>/api:sha-<previous-commit>   # registry (preferred)
./scripts/rollback.sh helbaron-api:<previous-tag>                      # local image fallback
```
The script rolls api/nginx/horizon/scheduler, clears+rewarms caches, and verifies
`GET /api/v1/health/ready` = 200. Also check error rate is back to normal.

## If a migration must be reverted
- Only if the migration is contractive/destructive and the new code is fully removed.
- `php artisan migrate:rollback --step=1 --force` (restore from backup first if data-affecting).
- Prefer a forward-fix migration over `migrate:reset` in production.

## Data rollback
Use PITR / snapshot restore (see `DISASTER_RECOVERY_GUIDE.md`). Never `migrate:fresh` in prod.

## Checklist
- [ ] Previous image tag identified and available
- [ ] Migrations reviewed (safe to keep? revert?)
- [ ] Rolled web replicas; readiness green
- [ ] Horizon/scheduler restarted on the old image
- [ ] Incident/postmortem updated

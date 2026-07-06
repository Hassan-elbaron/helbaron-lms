# HElbaron — Rollback Guide

## Principle
Images are immutable and tagged by version. Rollback = redeploy the previous tag. Database
migrations follow **expand/contract** so the previous app version stays compatible with the
current schema.

## App rollback (no schema change)
```bash
HELBARON_IMAGE=helbaron-api:<previous> docker compose -f docker-compose.prod.yml up -d api nginx horizon scheduler
php artisan optimize:clear && php artisan optimize
```
Verify `GET /api/v1/health/ready` = 200 and error rate normal.

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

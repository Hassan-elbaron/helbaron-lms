#!/usr/bin/env bash
# HElbaron — rollback to a previous immutable image tag.
# CI pushes every main/tag build to GHCR as ghcr.io/<org>/<repo>/api:sha-<commit> (plus :latest),
# so any previous commit's image is one pull away.
# Usage:
#   ./scripts/rollback.sh ghcr.io/<org>/<repo>/api:sha-<previous-commit>   # registry (preferred)
#   ./scripts/rollback.sh helbaron-api:<previous-tag>                      # local image
set -euo pipefail

PREV="${1:?usage: rollback.sh <previous-image-ref>  (e.g. ghcr.io/<org>/<repo>/api:sha-<commit>)}"
COMPOSE="docker compose -f docker-compose.prod.yml"
export HELBARON_IMAGE="$PREV"

echo "==> HElbaron rollback to: $PREV"

# Registry refs (contain '/') are pulled; bare local tags must already exist on the host.
case "$PREV" in
  */*) echo "==> Pulling $PREV"; docker pull "$PREV" ;;
esac

echo "!! Migrations are expand/contract. If the last deploy added a *contractive* migration,"
echo "   restore the database from backup FIRST (see docs/ops/DISASTER_RECOVERY_GUIDE.md)."

$COMPOSE up -d --no-deps api nginx horizon scheduler
$COMPOSE run --rm api php artisan optimize:clear
$COMPOSE run --rm api php artisan config:cache route:cache event:cache

echo "==> Verify readiness"
curl -fsS "http://localhost:8080/api/v1/health/ready" && echo "  OK" || {
  echo "!! Rollback readiness failed" >&2; exit 1; }

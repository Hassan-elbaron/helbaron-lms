#!/usr/bin/env bash
# HElbaron — rollback to a previous immutable image tag.
# Usage: ./scripts/rollback.sh helbaron-api:<previous-tag>
set -euo pipefail

PREV="${1:?usage: rollback.sh <previous-image-tag>}"
COMPOSE="docker compose -f docker-compose.prod.yml"
export HELBARON_IMAGE="$PREV"

echo "==> HElbaron rollback to: $PREV"
echo "!! Migrations are expand/contract. If the last deploy added a *contractive* migration,"
echo "   restore the database from backup FIRST (see docs/ops/DISASTER_RECOVERY_GUIDE.md)."

$COMPOSE up -d --no-deps api nginx horizon scheduler
$COMPOSE run --rm api php artisan optimize:clear
$COMPOSE run --rm api php artisan config:cache route:cache event:cache

echo "==> Verify readiness"
curl -fsS "http://localhost:8080/api/v1/health/ready" && echo "  OK" || {
  echo "!! Rollback readiness failed" >&2; exit 1; }

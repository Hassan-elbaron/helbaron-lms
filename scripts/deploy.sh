#!/usr/bin/env bash
# HElbaron — zero-downtime production deploy.
# Usage: HELBARON_IMAGE=helbaron-api:<tag> ./scripts/deploy.sh
set -euo pipefail

COMPOSE="docker compose -f docker-compose.prod.yml"
IMAGE="${HELBARON_IMAGE:-helbaron-api:1.0.0-rc.1}"
export HELBARON_IMAGE="$IMAGE"

echo "==> HElbaron deploy: $IMAGE"

echo "==> Build image"
$COMPOSE build api

echo "==> Run migrations (once, forward-only)"
$COMPOSE run --rm api php artisan migrate --force

echo "==> Warm caches"
$COMPOSE run --rm api php artisan config:cache
$COMPOSE run --rm api php artisan route:cache
$COMPOSE run --rm api php artisan event:cache

echo "==> Roll web + workers"
$COMPOSE up -d --no-deps --build api nginx
$COMPOSE up -d --no-deps horizon scheduler

echo "==> Readiness gate"
for i in $(seq 1 30); do
  if $COMPOSE exec -T nginx wget -qO- http://api:9000 >/dev/null 2>&1 || \
     curl -fsS "http://localhost:8080/api/v1/health/ready" >/dev/null 2>&1; then
    echo "==> Ready"; exit 0
  fi
  echo "   waiting for readiness ($i/30)"; sleep 2
done
echo "!! Readiness check did not pass — investigate before serving traffic" >&2
exit 1

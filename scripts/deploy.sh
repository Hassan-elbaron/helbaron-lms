#!/usr/bin/env bash
# HElbaron — zero-downtime production deploy.
#
# Usage:
#   ./scripts/deploy.sh <image-tag-or-ref>   # registry deploy: pulls the image pushed by CI
#                                            #   bare tag (e.g. sha-abc123, latest) resolves against
#                                            #   $DEPLOY_REGISTRY_IMAGE (e.g. ghcr.io/<org>/<repo>/api)
#                                            #   full refs (containing '/') are used verbatim
#   ./scripts/deploy.sh                      # fallback: on-host build (HELBARON_IMAGE env or default)
set -euo pipefail

cd "$(dirname "$0")/.."

COMPOSE="docker compose -f docker-compose.prod.yml"
IMAGE_TAG="${1:-}"
REGISTRY_MODE=0

if [ -n "$IMAGE_TAG" ]; then
  REGISTRY_MODE=1
  case "$IMAGE_TAG" in
    */*) IMAGE="$IMAGE_TAG" ;;
    *)   IMAGE="${DEPLOY_REGISTRY_IMAGE:?bare tag given — set DEPLOY_REGISTRY_IMAGE (e.g. ghcr.io/<org>/<repo>/api) or pass a full image ref}:${IMAGE_TAG}" ;;
  esac
  export HELBARON_IMAGE="$IMAGE"
  echo "==> HElbaron deploy (registry image): $IMAGE"
  echo "==> Pull image"
  docker pull "$IMAGE"
else
  IMAGE="${HELBARON_IMAGE:-helbaron-api:1.0.0-rc.1}"
  export HELBARON_IMAGE="$IMAGE"
  echo "==> HElbaron deploy (on-host build): $IMAGE"
  echo "==> Build image"
  $COMPOSE build api
fi

echo "==> Run migrations (once, forward-only)"
$COMPOSE run --rm api php artisan migrate --force

echo "==> Warm caches"
$COMPOSE run --rm api php artisan config:cache
$COMPOSE run --rm api php artisan route:cache
$COMPOSE run --rm api php artisan event:cache

echo "==> Roll api + web + workers"
if [ "$REGISTRY_MODE" -eq 1 ]; then
  $COMPOSE up -d --no-deps api nginx
else
  $COMPOSE up -d --no-deps --build api nginx
fi
$COMPOSE up -d web
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

#!/usr/bin/env bash
set -euo pipefail

# HElbaron local setup (scaffold). Run from the repo root.
echo "==> Starting local services (PostgreSQL + Redis)"
docker compose up -d

echo "==> API: install dependencies and generate key"
pushd apps/api >/dev/null
[ -f .env ] || cp .env.example .env
composer install
php artisan key:generate
popd >/dev/null

echo "==> Web: install dependencies"
pushd apps/web >/dev/null
[ -f .env.local ] || cp .env.example .env.local
npm install
popd >/dev/null

echo "==> Done. Next: run the API (php artisan serve) and web (npm run dev)."

#!/usr/bin/env bash
# HElbaron — automated backup restore drill (safe: never touches the live database).
#
# Restores the LATEST ./backups/db-*.sql.gz into a throwaway database created inside
# the compose postgres container, verifies table count > 0, then drops it.
# Exit 0 = drill passed. Suitable for cron / scheduled DR drills
# (see docs/ops/DISASTER_RECOVERY_GUIDE.md).
set -euo pipefail

cd "$(dirname "$0")/.."

COMPOSE="docker compose -f docker-compose.prod.yml"

if [ -f apps/api/.env.production ]; then
  set -a
  # shellcheck disable=SC1091
  . apps/api/.env.production
  set +a
fi
DB_USERNAME="${DB_USERNAME:-helbaron}"
BACKUP_DIR="${BACKUP_DIR:-backups}"

LATEST=$(ls -1t "$BACKUP_DIR"/db-*.sql.gz 2>/dev/null | head -n1 || true)
if [ -z "$LATEST" ]; then
  echo "!! No backups found in ./$BACKUP_DIR — run scripts/backup.sh first" >&2
  exit 1
fi

TMPDB="restore_drill_$(date +%s)"
echo "==> Drill: restoring $LATEST into temp database $TMPDB"

cleanup() {
  $COMPOSE exec -T postgres psql -U "$DB_USERNAME" -d postgres \
    -c "DROP DATABASE IF EXISTS $TMPDB;" >/dev/null 2>&1 || true
}
trap cleanup EXIT

$COMPOSE exec -T postgres psql -U "$DB_USERNAME" -d postgres -v ON_ERROR_STOP=1 \
  -c "CREATE DATABASE $TMPDB;" >/dev/null

# Dumps use --clean --if-exists, so DROPs are no-ops in the fresh temp DB.
gunzip -c "$LATEST" | $COMPOSE exec -T postgres psql -v ON_ERROR_STOP=1 \
  -U "$DB_USERNAME" -d "$TMPDB" >/dev/null

TABLES=$($COMPOSE exec -T postgres psql -U "$DB_USERNAME" -d "$TMPDB" -Atc \
  "SELECT count(*) FROM information_schema.tables WHERE table_schema='public';")
echo "==> Restored public tables: $TABLES"

if [ "${TABLES:-0}" -gt 0 ]; then
  echo "==> DRILL PASSED ($LATEST is restorable)"
else
  echo "!! DRILL FAILED: 0 tables restored from $LATEST" >&2
  exit 1
fi

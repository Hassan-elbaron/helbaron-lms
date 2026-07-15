#!/usr/bin/env bash
# HElbaron — restore a logical backup into the prod compose Postgres. DESTRUCTIVE.
#
# Usage: ./scripts/restore.sh --force backups/db-YYYYmmdd-HHMMSS.sql.gz
#
# Safety: refuses to run without --force AND an interactive typed confirmation of
# the target database name.
#
# RTO/RPO assumptions:
#   - RPO: dumps come from scripts/backup.sh or the compose `db-backup` service
#     (default interval 24h => worst-case data loss ~24h). Tighten
#     BACKUP_INTERVAL_SECONDS, or use managed Postgres PITR, to approach the
#     documented <=5 min RPO target in DISASTER_RECOVERY_GUIDE.md.
#   - RTO: gunzip | psql of a logical dump is typically minutes at this DB size;
#     total budget <=60 min including redeploy and verification.
#   - Dumps are taken with --clean --if-exists, so restoring over an existing
#     schema drops and recreates objects (that is why this is destructive).
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
DB_DATABASE="${DB_DATABASE:-helbaron}"

FORCE=0
FILE=""
for arg in "$@"; do
  case "$arg" in
    --force) FORCE=1 ;;
    -*) echo "!! Unknown option: $arg" >&2; exit 2 ;;
    *) FILE="$arg" ;;
  esac
done

[ -n "$FILE" ] || { echo "usage: restore.sh --force <backup-file.sql.gz>" >&2; exit 2; }
[ -f "$FILE" ] || { echo "!! No such backup file: $FILE" >&2; exit 2; }
if [ "$FORCE" -ne 1 ]; then
  echo "!! Refusing to restore without --force. This OVERWRITES database '$DB_DATABASE'." >&2
  echo "   usage: restore.sh --force <backup-file.sql.gz>" >&2
  exit 2
fi

echo "!! About to restore '$FILE' into database '$DB_DATABASE' on the prod compose stack."
echo "!! ALL CURRENT DATA IN '$DB_DATABASE' WILL BE REPLACED."
printf 'Type the target database name (%s) to confirm: ' "$DB_DATABASE"
read -r CONFIRM
if [ "$CONFIRM" != "$DB_DATABASE" ]; then
  echo "!! Confirmation mismatch — aborting (nothing was changed)" >&2
  exit 2
fi

echo "==> Restoring $FILE -> $DB_DATABASE"
gunzip -c "$FILE" | $COMPOSE exec -T postgres psql -v ON_ERROR_STOP=1 -U "$DB_USERNAME" -d "$DB_DATABASE" >/dev/null

echo "==> Verification"
TABLES=$($COMPOSE exec -T postgres psql -U "$DB_USERNAME" -d "$DB_DATABASE" -Atc \
  "SELECT count(*) FROM information_schema.tables WHERE table_schema='public';")
MIGRATIONS=$($COMPOSE exec -T postgres psql -U "$DB_USERNAME" -d "$DB_DATABASE" -Atc \
  "SELECT count(*) FROM migrations;")
echo "   public tables:      $TABLES"
echo "   migrations applied: $MIGRATIONS"

if [ "${TABLES:-0}" -gt 0 ] && [ "${MIGRATIONS:-0}" -gt 0 ]; then
  echo "==> Restore verified. Next: redeploy the matching app image tag and check /api/v1/health/ready."
else
  echo "!! Restore verification FAILED (tables=$TABLES migrations=$MIGRATIONS)" >&2
  exit 1
fi

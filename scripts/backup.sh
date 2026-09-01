#!/usr/bin/env bash
# HElbaron — logical Postgres backup via the prod compose stack.
#
# Writes gzip'd SQL dumps to ./backups/db-<timestamp>.sql.gz, prunes old ones,
# and optionally uploads to S3.
#
# Env (read from apps/api/.env.production if present, else current environment):
#   DB_USERNAME             (default: helbaron)
#   DB_DATABASE             (default: helbaron)
#   BACKUP_RETENTION_DAYS   (default: 14)
#   BACKUP_DIR              (default: /var/backups/academy-lms — see below)
#   BACKUP_S3_BUCKET        (optional: upload target; requires aws CLI)
#
# BACKUP_DIR defaults OUTSIDE the deployment checkout on purpose. It used to default to ./backups,
# which is inside the directory Dokploy replaces on every deploy: the dumps were written to the one
# place guaranteed to be destroyed by the next release. The scheduled backups in
# docker-compose.prod.yml write to $BACKUP_TARGET (a named Docker volume by default) for the same
# reason; this script is the ad-hoc path and needs the same guarantee.
#
# Note: the docker-compose.prod.yml `db-backup` service performs the same dump on a
# schedule; this script is for ad-hoc/manual backups and host cron.
set -euo pipefail

cd "$(dirname "$0")/.."

COMPOSE="docker compose -f docker-compose.prod.yml"

# Load production env if available (never committed; see docs/ops/SECRETS.md).
if [ -f apps/api/.env.production ]; then
  set -a
  # shellcheck disable=SC1091
  . apps/api/.env.production
  set +a
fi

DB_USERNAME="${DB_USERNAME:-helbaron}"
DB_DATABASE="${DB_DATABASE:-helbaron}"
BACKUP_RETENTION_DAYS="${BACKUP_RETENTION_DAYS:-14}"
BACKUP_DIR="${BACKUP_DIR:-/var/backups/academy-lms}"

if ! mkdir -p "$BACKUP_DIR" 2>/dev/null || [ ! -w "$BACKUP_DIR" ]; then
  echo "!! Cannot write to BACKUP_DIR=$BACKUP_DIR" >&2
  echo "   Create it once with the right ownership, e.g." >&2
  echo "     sudo install -d -o \"\$(id -un)\" -g \"\$(id -gn)\" -m 0750 \"$BACKUP_DIR\"" >&2
  echo "   or set BACKUP_DIR to a writable path OUTSIDE this checkout." >&2
  exit 1
fi

OUT="$BACKUP_DIR/db-$(date +%Y%m%d-%H%M%S).sql.gz"

echo "==> pg_dump $DB_DATABASE (user: $DB_USERNAME) -> $OUT"
# --clean --if-exists makes the dump restorable in place via scripts/restore.sh.
if ! $COMPOSE exec -T postgres pg_dump --clean --if-exists -U "$DB_USERNAME" "$DB_DATABASE" | gzip > "$OUT"; then
  echo "!! Backup failed (pg_dump or gzip returned non-zero)" >&2
  rm -f "$OUT"
  exit 1
fi

# Guard against silently-empty dumps.
SIZE=$(wc -c < "$OUT")
if [ "$SIZE" -lt 512 ]; then
  echo "!! Backup suspiciously small ($SIZE bytes) — treating as failure" >&2
  # Remove it: a truncated dump left on disk looks like a backup to the next person who needs one,
  # and to the retention sweep that counts files rather than reading them.
  rm -f "$OUT"
  exit 1
fi
echo "==> Backup written: $OUT ($SIZE bytes)"

echo "==> Pruning backups older than $BACKUP_RETENTION_DAYS days"
find "$BACKUP_DIR" -name 'db-*.sql.gz' -mtime +"$BACKUP_RETENTION_DAYS" -print -delete

if [ -n "${BACKUP_S3_BUCKET:-}" ]; then
  if command -v aws >/dev/null 2>&1; then
    echo "==> Uploading to s3://$BACKUP_S3_BUCKET/"
    aws s3 cp "$OUT" "s3://$BACKUP_S3_BUCKET/$(basename "$OUT")"
  else
    echo "!! BACKUP_S3_BUCKET is set but the aws CLI is not installed — SKIPPING S3 upload." >&2
    echo "   Install awscli (and credentials) on this host to enable off-site copies." >&2
  fi
fi

echo "==> Done"

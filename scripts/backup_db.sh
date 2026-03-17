#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
ENV_FILE="$ROOT_DIR/.env"
BACKUP_DIR="$ROOT_DIR/storage/backups"

if [[ ! -f "$ENV_FILE" ]]; then
  echo "No se encontro .env en $ROOT_DIR"
  exit 1
fi

mkdir -p "$BACKUP_DIR"

set -a
# shellcheck disable=SC1090
source "$ENV_FILE"
set +a

STAMP="$(date +%Y%m%d_%H%M%S)"
OUT_FILE="$BACKUP_DIR/${DB_DATABASE}_${STAMP}.sql"

mysqldump \
  --host="${DB_HOST}" \
  --port="${DB_PORT}" \
  --user="${DB_USERNAME}" \
  --password="${DB_PASSWORD}" \
  --single-transaction \
  --routines \
  --events \
  "${DB_DATABASE}" > "$OUT_FILE"

echo "Backup creado: $OUT_FILE"

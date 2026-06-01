#!/bin/bash
if [ -z "$1" ]; then
  echo "Usage: ./scripts/restore.sh scripts/backups/backup_XXXXXX.sql"
  exit 1
fi

docker compose exec -T database psql -U app app < $1
echo "Restauration effectuée depuis : $1"
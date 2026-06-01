#!/bin/bash
TIMESTAMP=$(date +%Y%m%d_%H%M%S)
BACKUP_FILE="scripts/backups/backup_${TIMESTAMP}.sql"

mkdir -p scripts/backups

docker compose exec -T database pg_dump -U app app > $BACKUP_FILE

echo "Sauvegarde créée : $BACKUP_FILE"
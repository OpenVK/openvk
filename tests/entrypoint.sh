#!/bin/bash
set -e

DB_HOST="${DB_HOST:-mariadb-primary}"
DB_USER="${DB_USER:-openvk}"
DB_PASSWORD="${DB_PASSWORD:-openvk}"
DB_NAME="${DB_NAME:-db}"

cd /opt/chandler/extensions/available/openvk

# Wait for MariaDB
echo "Waiting for MariaDB..."
until mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASSWORD" "$DB_NAME" --ssl=0 --default-character-set=utf8mb4 -e "SELECT 1" &>/dev/null; do
    sleep 2
done
echo "MariaDB ready."

# Run schema migrations (creates all tables + admin user)
./openvkctl upgrade --no-interaction --quick

# Import deterministic test seed data
echo "Importing seed data..."
mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASSWORD" "$DB_NAME" --ssl=0 --default-character-set=utf8mb4 -f < tests/seed-data.sql || true
echo "Seed data imported."

# Start Apache
exec apache2-foreground

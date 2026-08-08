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

# Import deterministic test seed data (ignore duplicates on container recreate)
echo "Importing seed data..."
if mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASSWORD" "$DB_NAME" --ssl=0 --default-character-set=utf8mb4 < tests/seed-data.sql; then
    echo "Seed data imported."
else
    echo "Seed data import skipped (database already seeded)."
fi

# Seed drops UUID triggers first and recreates them at the end. If import
# fails mid-way (e.g. duplicate keys on recreate), login breaks without them.
echo "Ensuring Chandler UUID triggers exist..."
mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASSWORD" "$DB_NAME" --ssl=0 --default-character-set=utf8mb4 <<'SQL'
DROP TRIGGER IF EXISTS bfiu_users;
DROP TRIGGER IF EXISTS bfiu_groups;
DROP TRIGGER IF EXISTS bfiu_tokens;
CREATE TRIGGER bfiu_users  BEFORE INSERT ON ChandlerUsers  FOR EACH ROW SET new.id = uuid();
CREATE TRIGGER bfiu_groups BEFORE INSERT ON ChandlerGroups FOR EACH ROW SET new.id = uuid();
CREATE TRIGGER bfiu_tokens BEFORE INSERT ON ChandlerTokens FOR EACH ROW SET new.token = uuid();
SQL

# Start Apache
exec apache2-foreground

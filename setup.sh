#!/bin/bash
set -e

echo "=== TransactiWar Setup ==="
echo "Waiting for MySQL to be ready..."

until mysqladmin ping -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" --skip-ssl --silent 2>/dev/null; do
    echo "  MySQL not ready, retrying in 2s..."
    sleep 2
done

echo "MySQL is ready."

# Create test accounts
echo "Creating test accounts..."
/create_accounts.sh

echo "Setup complete. Starting Apache..."
exec apache2-foreground

#!/bin/bash
set -euo pipefail

ENV_FILE="${1:-.env}"

if [[ -f "$ENV_FILE" ]]; then
    echo "$ENV_FILE already exists. Leaving it unchanged."
    exit 0
fi

random_hex() {
    openssl rand -hex "$1"
}

DB_PASS="$(random_hex 18)"
MYSQL_ROOT_PASSWORD="$(random_hex 18)"
APP_SECRET="$(random_hex 32)"

cat > "$ENV_FILE" <<EOF
DB_HOST=db
DB_NAME=transactiwar
DB_USER=twuser
DB_PASS=$DB_PASS
MYSQL_ROOT_PASSWORD=$MYSQL_ROOT_PASSWORD
APP_SECRET=$APP_SECRET
TRUST_PROXY_HEADERS=0
EOF

chmod 600 "$ENV_FILE" 2>/dev/null || true

echo "Created $ENV_FILE with fresh secrets."
echo "Review it before deploying to shared infrastructure."
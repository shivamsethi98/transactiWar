#!/bin/bash
set -e

CERT_DIR="/etc/apache2/certs"
CERT_FILE="$CERT_DIR/server.crt"
KEY_FILE="$CERT_DIR/server.key"
APACHE_CONF="/etc/apache2/sites-available/000-default.conf"
APP_BASE_URL="${APP_BASE_URL:-https://localhost}"

configure_canonical_origin() {
    APP_BASE_URL="${APP_BASE_URL%/}"

    case "$APP_BASE_URL" in
        http://*|https://*) ;;
        *)
            echo "APP_BASE_URL must start with http:// or https://" >&2
            exit 1
            ;;
    esac

    sed -i "s|__APP_BASE_URL__|$APP_BASE_URL|g" "$APACHE_CONF"
}

ensure_tls_certificates() {
    if [[ -s "$CERT_FILE" && -s "$KEY_FILE" ]]; then
        return
    fi

    echo "TLS certificate files not found. Generating self-signed development certificate..."

    if [[ ! -w "$CERT_DIR" ]]; then
        CERT_DIR="/tmp/certs"
        CERT_FILE="$CERT_DIR/server.crt"
        KEY_FILE="$CERT_DIR/server.key"
        mkdir -p "$CERT_DIR"

        sed -i "s|^\s*SSLCertificateFile\s\+.*$|    SSLCertificateFile $CERT_FILE|" "$APACHE_CONF"
        sed -i "s|^\s*SSLCertificateKeyFile\s\+.*$|    SSLCertificateKeyFile $KEY_FILE|" "$APACHE_CONF"
    else
        mkdir -p "$CERT_DIR"
    fi

    openssl req -x509 -nodes -newkey rsa:2048 \
        -keyout "$KEY_FILE" \
        -out "$CERT_FILE" \
        -days 365 \
        -subj "/CN=localhost" >/dev/null 2>&1
}

echo "=== TransactiWar Setup ==="
configure_canonical_origin
ensure_tls_certificates
echo "Waiting for MySQL to be ready..."

until mysqladmin ping -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" --skip-ssl --silent 2>/dev/null; do
    echo "  MySQL not ready, retrying in 2s..."
    sleep 2
done

echo "MySQL is ready."

# Create test accounts
echo "Creating test accounts..."
/create_accounts.sh

apache2ctl -t

echo "Setup complete. Starting Apache..."
exec apache2-foreground

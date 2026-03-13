# TLS Enablement Guide (TransactiWar)

This document summarizes all changes made to enable HTTPS/TLS in this project and how to run it.

## What Was Changed

### 1. Apache TLS Virtual Hosts
File changed: `config/apache.conf`

- Added HTTP virtual host on port 80 that redirects all requests to HTTPS.
- Added HTTPS virtual host on port 443.
- Configured certificate and key paths:
  - `SSLCertificateFile /etc/apache2/certs/server.crt`
  - `SSLCertificateKeyFile /etc/apache2/certs/server.key`
- Added HSTS header:
  - `Strict-Transport-Security: max-age=31536000; includeSubDomains`

### 2. Apache SSL Module + HTTPS Port Exposure
File changed: `Dockerfile`

- Enabled SSL module:
  - `a2enmod rewrite headers ssl`
- Exposed HTTPS port in addition to HTTP:
  - `EXPOSE 80 443`

### 3. Docker Compose TLS Port + Certificate Mount
File changed: `docker-compose.yml`

- Added HTTPS port mapping:
  - `8443:443`
- Added certificate volume mount (read-only):
  - `./certs:/etc/apache2/certs:ro`

### 4. Session HTTPS Detection Hardening
File changed: `app/core/session.php`

- Replaced direct HTTPS-only check with `is_https_request()` helper.
- `session.cookie_secure=1` is now set when request is HTTPS by:
  - direct HTTPS,
  - port 443,
  - or trusted reverse-proxy header when `TRUST_PROXY_HEADERS=1`.

### 5. Documentation Updates
File changed: `README.md`

- Added local certificate generation commands.
- Added secure app URL:
  - `https://localhost:8443`
- Added HTTPS port conflict troubleshooting and TLS notes.

### 6. Keep Certs Out of Git
Files changed:
- `.gitignore`
- `certs/.gitkeep`

- Ignore all certificate files under `certs/`.
- Keep the folder itself tracked via `.gitkeep`.

## One-Time Local Setup

Run from project root:

```bash
mkdir -p certs
openssl req -x509 -nodes -newkey rsa:2048 \
  -keyout certs/server.key \
  -out certs/server.crt \
  -days 365 \
  -subj "/CN=localhost"
```

## Build and Run

```bash
docker compose up --build -d
docker compose ps
```

Open:

- HTTPS: `https://localhost:8443`
- HTTP (redirects): `http://localhost:8080`

## Verification Checklist

- `docker compose config` passes.
- HTTP requests redirect to HTTPS.
- HTTPS endpoint loads successfully.
- Login and session flows work under HTTPS.
- Session cookie is marked `Secure` in HTTPS responses.

## Notes for Production

- Replace self-signed certs with CA-issued certificates (for example, Let's Encrypt).
- If app is behind reverse proxy/ingress, set environment variable:
  - `TRUST_PROXY_HEADERS=1`
- Ensure proxy forwards `X-Forwarded-Proto: https`.
- Keep HSTS enabled only after HTTPS is stable for your domain.

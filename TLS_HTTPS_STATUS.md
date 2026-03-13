# TLS/HTTPS Status Report

## Current Branch Status (copilot/check-tls-https-status)

TLS/HTTPS is **not enabled** at the infrastructure layer on this branch. The application is HTTPS-aware (it can detect HTTPS and set secure headers/cookies when HTTPS is present), but the container stack only serves HTTP.

**Evidence (current branch):**
- **Dockerfile** only enables `rewrite` and `headers` modules, exposes **port 80**, and does **not** enable `mod_ssl` or copy any certificates.
- **docker-compose.yml** maps only **8080:80** and does not provide any TLS certificate volume or HTTPS port.
- **config/apache.conf** defines a single `VirtualHost *:80` and contains no `SSLEngine`/`SSLCertificateFile` directives.

## Diff vs `main`

`main` also does **not** enable TLS/HTTPS at the infrastructure level. The differences are security hardening and configuration improvements, not TLS enablement.

### Relevant differences
- **config/apache.conf**
  - Expanded CSP policy and added **conditional HSTS** header (only sent when HTTPS is detected).
- **docker-compose.yml**
  - Environment values moved to `.env` with required secrets (`DB_PASS`, `APP_SECRET`).
  - Added `TRUST_PROXY_HEADERS` support for HTTPS detection behind a reverse proxy.
  - Reduced MySQL healthcheck retries (no TLS impact).
- **README.md**
  - Added `generate_env.sh` usage and notes about trusted proxy headers.

## Conclusion

Both `main` and this branch are **HTTP-only** by default. To enable HTTPS, the stack would need an HTTPS `VirtualHost`, SSL certificates, and an HTTPS port mapping in Docker Compose.

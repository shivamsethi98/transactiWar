# TransactiWar Security Issue Report

## Issue Title
Hardcoded secrets in repository configuration and documentation

## Summary
Sensitive values are currently hardcoded in project files.
If this repository is shared publicly or with unintended users, these values can be abused to access infrastructure or weaken application security.

## Evidence (Hardcoded Locations)

1. Database password exposed in compose environment:
- docker-compose.yml (DB_PASS)
- docker-compose.yml (MYSQL_PASSWORD)

2. Root database password exposed:
- docker-compose.yml (MYSQL_ROOT_PASSWORD)

3. Application secret exposed:
- docker-compose.yml (APP_SECRET)

4. Test credentials shown in plaintext:
- README.md (Test Accounts table)

## Why This Is a Security Problem

- Hardcoded credentials can be leaked via git history, screenshots, backups, or accidental sharing.
- Reused secrets may allow unauthorized DB or app access in non-local environments.
- APP secret exposure can weaken session integrity and security assumptions.
- Publishing plaintext test credentials creates bad practice and possible misuse if reused elsewhere.

## Impact

- Unauthorized database access
- Data tampering or data loss
- Potential session/security control bypass if APP secret is reused
- Compliance and audit concerns (secret management failure)

## Solution Guidance

### 1. Move secrets out of source code
Use environment variables from an untracked file (for example .env).
Keep only variable references in compose, not literal values.

Example pattern:
- DB_PASS=${DB_PASS}
- MYSQL_ROOT_PASSWORD=${MYSQL_ROOT_PASSWORD}
- MYSQL_PASSWORD=${MYSQL_PASSWORD}
- APP_SECRET=${APP_SECRET}

### 2. Add secret files to ignore
Ensure .env and related secret files are listed in .gitignore.

### 3. Rotate exposed secrets immediately
Change all exposed values:
- MySQL root password
- MySQL app user password
- APP secret
Assume previously committed values are compromised.

### 4. Improve healthcheck authentication
Use authenticated healthcheck credentials so readiness checks are accurate and logs are clean.

### 5. Control test credentials in docs
Avoid publishing real or reusable passwords.
Mark credentials as local-demo only and rotate regularly.

### 6. Enable TLS (HTTPS) for transport security

Current issue in this system:
- The app is served over HTTP only (`docker-compose.yml` maps `8080:80`).
- `config/apache.conf` has only `<VirtualHost *:80>` and no TLS listener.
- Without TLS, login credentials, session cookies, and transfer data can be intercepted or modified on untrusted networks (MITM risk).

Why TLS is important for this system:
- Encrypts traffic in transit (protects username/password, profile data, transfer details).
- Prevents session hijacking via network sniffing.
- Reduces man-in-the-middle tampering risk for requests/responses.
- Activates stronger cookie protection behavior (`Secure` cookie flag when HTTPS is used).
- Improves production readiness and compliance posture.

Problems solved by adding TLS:
- MITM eavesdropping on credentials and session tokens.
- In-transit modification of sensitive actions (for example, transfer workflows).
- Browser mixed trust/insecure transport warnings for authenticated pages.

Where changes are needed to enable TLS:
1. `config/apache.conf`
- Add HTTPS virtual host (`<VirtualHost *:443>`) with:
	- `SSLEngine on`
	- `SSLCertificateFile` and `SSLCertificateKeyFile`
- Keep or add an HTTP virtual host (`*:80`) that redirects all traffic to HTTPS.
- Add HSTS header after HTTPS is confirmed working.

2. `Dockerfile`
- Enable Apache SSL module (`a2enmod ssl`).
- Ensure TLS site config is copied and enabled.
- Expose port `443` in addition to `80`.

3. `docker-compose.yml`
- Publish TLS port (for example `8443:443` or `443:443`).
- Mount certificate files into the container (for example `./certs:/etc/apache2/certs:ro`).
- Optionally keep `8080:80` only for redirect to HTTPS.

4. `README.md`
- Update access URL from `http://localhost:8080` to HTTPS endpoint.
- Document certificate setup (self-signed for local, CA-issued/Let's Encrypt for deployment).

5. `app/core/session.php` (already TLS-aware)
- Current logic already sets `session.cookie_secure=1` when HTTPS is detected.
- No functional redesign needed; enabling HTTPS allows this control to take effect consistently.

## Verification Checklist

- [ ] No secret literals remain in tracked files
- [ ] docker compose config resolves values from environment variables
- [ ] Containers start successfully with new secrets
- [ ] App login, registration, and transfer features still work
- [ ] Old leaked credentials no longer work
- [ ] Repo history reviewed for previously committed secrets

## Priority
High (secret exposure and credential management issue)

## Status
Open

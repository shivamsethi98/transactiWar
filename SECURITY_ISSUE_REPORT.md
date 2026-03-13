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

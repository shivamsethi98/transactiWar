# TransactiWar — Battle for Security, Compete for Supremacy

A secure web application for user registration, profile management, and money transfers.
Built for **CS6903 Network Security — Phase 1** (IITH).

## Prerequisites

- Docker Engine 20.10+
- Docker Compose v2+

Verify installation:

```bash
docker --version
docker compose version
```

## Quick Start

```bash
# Clone and enter the project directory
cd transactiWar

# Generate deployment secrets (one-time)
./generate_env.sh

# Build and start the containers
docker compose up --build -d

# Verify both containers are running
docker compose ps
```

Access the app at **https://localhost** (HTTPS) [https://localhost:443]

> **Note:** TLS certificates are generated automatically on first startup if none are found.
> Your browser will show a security warning for the self-signed cert — this is expected for local development.
> To use your own certificates, place `server.crt` and `server.key` in the `certs/` directory before starting.

To stop the application:

```bash
docker compose down       # Stop containers (preserves data)
docker compose down -v    # Stop and remove all data (fresh start)
```

## Environment Configuration

Secrets are stored in a `.env` file (excluded from git). The `generate_env.sh` script creates one with random values:

| Variable | Description | Default |
|---|---|---|
| `DB_HOST` | MySQL hostname | `db` |
| `DB_NAME` | Database name | `transactiwar` |
| `DB_USER` | Database username | `twuser` |
| `DB_PASS` | Database password | *(random, required)* |
| `MYSQL_ROOT_PASSWORD` | MySQL root password | *(random, required)* |
| `APP_SECRET` | HMAC key for session fingerprinting | *(random, required)* |
| `TRUST_PROXY_HEADERS` | Trust X-Forwarded-Proto headers (set `1` behind a reverse proxy) | `0` |

A `.env.example` template is provided for reference.

## Test Accounts

Five pre-created accounts are available for evaluation:

| Username        | Password            | Full Name       | Starting Balance |
|-----------------|---------------------|-----------------|-----------------|
| alice_sharma    | Alice@Secure#2026   | Alice Sharma    | Rs. 100.00      |
| bob_kumar       | Bob#Strong@Pass9    | Bob Kumar       | Rs. 100.00      |
| charlie_reddy   | Charlie#War@2026    | Charlie Reddy   | Rs. 100.00      |
| diana_patel     | Diana@Transact#7    | Diana Patel     | Rs. 100.00      |
| eve_gupta       | Eve#Guard@2026      | Eve Gupta       | Rs. 100.00      |

New accounts registered through the app also start with Rs. 100.00.

## Features

- **User Registration** — unique username/email, strong password policy (8-72 chars, mixed case, digit, symbol)
- **Login/Logout** — rate-limited authentication with session management
- **Profile Management** — edit email, full name, biography; upload profile photo (JPG/PNG/GIF, max 2MB)
- **View Profiles** — browse other users' public profiles (email hidden for privacy)
- **User Search** — search by username or user ID
- **Money Transfer** — transfer money with optional comments; prevents negative balances and self-transfers
- **Transaction History** — paginated list of all sent/received transactions with timestamps
- **About Page** — team member information, accessible to both guests and logged-in users
- **Activity Logging** — logs page, username, timestamp, and client IP for every request

## Tech Stack

- **Frontend**: HTML, CSS, JavaScript, Bootstrap 5.3
- **Backend**: PHP 8.2 (no frameworks)
- **Database**: MySQL 8.0 (InnoDB)
- **Deployment**: Docker + Docker Compose

## Security Implementation

All security measures are custom-built (no external security frameworks used).

### Transport Security (TLS/HTTPS)
- HTTP → HTTPS 301 redirect enforced at Apache level
- Self-signed TLS certificates auto-generated on first startup
- HSTS header (`max-age=31536000; includeSubDomains`) sent conditionally over HTTPS
- `is_https()` helper supports standard HTTPS detection and trusted proxy headers

### SQL Injection Prevention
- PDO with native prepared statements (`EMULATE_PREPARES=false`)
- All user input passes through parameterized queries
- LIKE wildcards (`%`, `_`) escaped in search queries

### XSS Prevention
- `htmlspecialchars(ENT_QUOTES | ENT_HTML5, 'UTF-8')` on all dynamic output via `e()` helper
- JSON encoding with hex flags via `ejs()` for JS contexts
- Content-Security-Policy header restricts script/style sources

### CSRF Protection
- Synchronizer token pattern with `random_bytes(32)`
- Timing-safe validation via `hash_equals()`
- Token regeneration on successful validation (single-use)
- All POST forms include CSRF token; validated in the router

### Session Security
- `use_strict_mode`, `use_only_cookies`, `cookie_httponly`, `cookie_samesite=Strict`
- Dynamic `cookie_secure` flag when served over HTTPS
- Session fingerprinting via HMAC-SHA256 (User-Agent + Accept-Language)
- `session_regenerate_id(true)` on login (prevents fixation)
- Idle timeout: 30 minutes; Absolute timeout: 4 hours
- APP_SECRET required via environment variable (no fallback)

### File Upload Security
- Extension allowlist: jpg, jpeg, png, gif
- MIME type validation via `finfo` (magic bytes)
- Image dimension validation (1-4000px)
- GD library re-creation (strips EXIF, embedded code, polyglot payloads)
- Random filename generation (`random_bytes(16)`)
- Stored outside webroot (`/uploads/avatars/`), served through PHP handler

### Money Transfer Security
- `SELECT ... FOR UPDATE` row-level locking inside InnoDB transactions
- Consistent lock ordering (lower ID first) to prevent deadlocks
- Balance check inside transaction after acquiring locks
- Database CHECK constraint: `balance >= 0`, `amount > 0`
- Rate limit: 30 transfers per user per minute

### Rate Limiting (DB-backed, fail-closed)
- Login: 5 attempts per IP / 15 min, 10 per username / 15 min
- Registration: 3 per IP / hour
- Transfer: 30 per user / minute
- Search: 60 per user / minute
- Fails closed on DB error (prevents bypass)

### HTTP Security Headers
Security headers set at both Apache and PHP levels (defense in depth):

| Header | Value |
|---|---|
| `Content-Security-Policy` | `default-src 'self'; base-uri 'self'; form-action 'self'; frame-ancestors 'none'; object-src 'none'; script-src 'self' https://cdn.jsdelivr.net; style-src 'self' 'unsafe-inline' ...` |
| `X-Frame-Options` | `DENY` |
| `X-Content-Type-Options` | `nosniff` |
| `Referrer-Policy` | `strict-origin-when-cross-origin` |
| `Permissions-Policy` | `camera=(), microphone=(), geolocation=()` |
| `Strict-Transport-Security` | `max-age=31536000; includeSubDomains` (HTTPS only) |
| `Cache-Control` | `no-store, no-cache, must-revalidate` |

- HTTP method restriction: only GET and POST allowed (405 on others)
- Open redirect prevention: `redirect()` validates relative paths only

### PHP Hardening (`config/php.ini`)
- `expose_php = Off`, `display_errors = Off`
- Dangerous functions disabled: `exec`, `shell_exec`, `system`, `proc_open`, etc.
- `allow_url_fopen = Off`, `allow_url_include = Off`
- `open_basedir` restriction: `/var/www/html/:/tmp/:/uploads/`
- Upload limits: 2MB file, 3MB total POST
- Password length enforced: 8-72 characters (bcrypt truncation protection)

### Access Control
- `.htaccess` blocks direct access to `core/`, `templates/`, `pages/` directories
- PHP execution blocked in `assets/` via RewriteRule
- Hidden files blocked via Apache `FilesMatch`
- `.env` inaccessible via HTTP (403)
- Single entry point router (`index.php`) enforces auth guards on all routes

## Project Structure

```
transactiWar/
├── app/                        # Apache DocumentRoot
│   ├── index.php               # Single entry point / router
│   ├── .htaccess               # URL rewriting + directory access control
│   ├── core/                   # Security modules
│   │   ├── db.php              #   PDO connection singleton
│   │   ├── session.php         #   Session management + fingerprinting
│   │   ├── csrf.php            #   CSRF token generation/validation
│   │   ├── auth.php            #   Authentication guards
│   │   ├── security.php        #   Input validation + output encoding + is_https()
│   │   ├── rate_limiter.php    #   Rate limiting (fail-closed)
│   │   ├── logger.php          #   Activity logging
│   │   ├── upload.php          #   Secure avatar upload
│   │   └── helpers.php         #   Flash messages, redirect, pagination
│   ├── pages/                  # Feature pages
│   │   ├── login.php           #   Login with rate limiting
│   │   ├── register.php        #   Registration with validation
│   │   ├── dashboard.php       #   Balance, stats, recent transactions
│   │   ├── profile.php         #   Edit profile + avatar upload
│   │   ├── profile_view.php    #   View other users' profiles
│   │   ├── transfer.php        #   Money transfer with row locking
│   │   ├── search.php          #   User search (by ID or username)
│   │   ├── history.php         #   Transaction history (paginated)
│   │   ├── about.php           #   Team members and project info
│   │   ├── avatar.php          #   Secure avatar image serving
│   │   └── logout.php          #   Session destruction (POST-only)
│   ├── templates/              # Shared HTML templates
│   │   ├── header.php          #   Head + navbar (with About link)
│   │   ├── footer.php          #   Footer + scripts
│   │   └── alerts.php          #   Flash message display
│   └── assets/                 # Static files (CSS, JS, images)
├── sql/
│   ├── schema.sql              # Database schema (5 tables)
│   └── seed.sql                # Seed data placeholder
├── config/
│   ├── php.ini                 # Hardened PHP configuration
│   └── apache.conf             # VirtualHost + TLS + security headers
├── certs/                      # TLS certificates (auto-generated or user-provided)
├── Dockerfile                  # PHP 8.2 + Apache image
├── docker-compose.yml          # Multi-container orchestration
├── .env.example                # Environment variable template
├── generate_env.sh             # Auto-generates .env with random secrets
├── setup.sh                    # Container startup (TLS + DB wait + accounts)
├── create_accounts.sh          # Test account creation
└── uploads/avatars/            # Avatar storage (outside webroot)
```

## Database Schema

| Table            | Purpose                              |
|-----------------|--------------------------------------|
| `users`          | User accounts, balances, profiles    |
| `transactions`   | Money transfer records               |
| `activity_log`   | Page visit audit trail               |
| `login_attempts` | Brute-force detection log            |
| `rate_limits`    | Generic rate limiting records        |

## Troubleshooting

**Containers won't start:**
```bash
docker compose down -v    # Remove old volumes
docker compose up --build # Rebuild from scratch
```

**Port 80 or 443 already in use:**
```bash
# Check what is using the port
sudo lsof -i :80
# Change port in docker-compose.yml, e.g. "8080:80" and "8443:443"
# Then update config/apache.conf redirect rule to match the HTTPS port
```

**MySQL not ready errors:**
The setup script automatically retries. If it persists, increase `retries` in the healthcheck config in `docker-compose.yml`.

**Rate limit lockout during testing:**
Wait 15 minutes, or restart containers with `docker compose down -v && docker compose up --build -d`.

## TLS Notes

- TLS certificates are auto-generated inside the container on first startup if none are provided
- To provide your own certs, place `server.crt` and `server.key` in the `certs/` directory
- Browsers will show a warning for self-signed certs; this is expected for local setup
- For production, replace with CA-issued certificates (e.g., Let's Encrypt)

## Team

Built by students of IIT Hyderabad for CS6903 Network Security:

- Digvijaysing Rajput (CS24MTECH14020)
- Vinay Kadari (CS24MTECH14008)
- Saswata Mishra (CS24MTECH12001)
- Rajesh Krishna (CS24MTECH11012)
- Shivam Sethi (CS24MTECH12021)

## References

- OWASP Top 10: https://owasp.org/www-project-top-ten/
- OWASP Cheat Sheet Series: https://cheatsheetseries.owasp.org/
- PHP Manual — Password Hashing: https://www.php.net/manual/en/function.password-hash.php
- PHP Manual — PDO Prepared Statements: https://www.php.net/manual/en/pdo.prepared-statements.php
- PHP Manual — Session Security: https://www.php.net/manual/en/session.security.php
- MySQL InnoDB Locking Reads: https://dev.mysql.com/doc/refman/8.0/en/innodb-locking-reads.html
- Bootstrap 5 Documentation: https://getbootstrap.com/docs/5.3/
- Content Security Policy (MDN): https://developer.mozilla.org/en-US/docs/Web/HTTP/CSP

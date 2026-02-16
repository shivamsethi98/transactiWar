
## Quick Start

```bash
docker-compose up --build
```

Access the app at **http://localhost:8080**

## Test Accounts

| Username   | Password     |
|------------|-------------|
| testuser1  | TestPass1!  |
| testuser2  | TestPass2!  |
| testuser3  | TestPass3!  |
| testuser4  | TestPass4!  |
| testuser5  | TestPass5!  |

All test accounts start with Rs. 100.00 balance.

## Tech Stack

- **Frontend**: HTML, CSS, JavaScript, Bootstrap 5.3
- **Backend**: PHP 8.2 + MySQL 8.0
- **Deployment**: Docker + Docker Compose

## Security Measures

- Prepared statements (PDO, `EMULATE_PREPARES=false`) — SQL injection prevention
- Output encoding via `htmlspecialchars()` on all dynamic content — XSS prevention
- CSRF tokens (synchronizer pattern with `hash_equals()`) on all forms
- Session: strict mode, HttpOnly, SameSite=Strict, fingerprint, regeneration on login
- File uploads: extension allowlist, MIME check, GD re-creation, stored outside webroot
- Money transfers: `SELECT ... FOR UPDATE` with consistent lock ordering — race condition prevention
- Rate limiting: login (per IP + per username), registration, transfers, search
- Security headers: CSP, X-Frame-Options, X-Content-Type-Options, Referrer-Policy
- Hardened `php.ini`: dangerous functions disabled, `display_errors=Off`, `open_basedir`

## Project Structure

```
app/                    # Apache DocumentRoot
├── index.php           # Single entry point / router
├── core/               # Security modules (db, session, csrf, auth, etc.)
├── pages/              # Feature pages (login, register, dashboard, etc.)
├── templates/          # Shared header, footer, alerts
└── assets/             # Static CSS, JS, images
sql/                    # Database schema
config/                 # PHP and Apache configuration
uploads/                # Avatar storage (outside webroot)
```

## References

- OWASP Top 10: https://owasp.org/www-project-top-ten/
- PHP Manual — Password Hashing: https://www.php.net/manual/en/function.password-hash.php
- PHP Manual — PDO Prepared Statements: https://www.php.net/manual/en/pdo.prepared-statements.php
- Bootstrap 5 Documentation: https://getbootstrap.com/docs/5.3/
- MySQL InnoDB Locking: https://dev.mysql.com/doc/refman/8.0/en/innodb-locking-reads.html

# TransactiWar (Native PHP Secure Money Transfer App)

## Stack
- Backend: Native PHP 8.x + PDO
- Database: MySQL 8.x
- Frontend: HTML5 + CSS3 + Bootstrap 5 + minimal JS
- Runtime: Docker + Docker Compose

## Run with Docker
1. `cd /var/www/html` inside project root (or local equivalent).
2. `docker compose up --build`
3. Open `http://localhost:8080`

## DB Initialization
- Schema auto-loads from `sql/schema.sql` through MySQL init mount.
- New users are auto-credited with `Rs. 100` (`10000` paise) in `register.php`.

## Security Controls Implemented
- PDO prepared statements for all SQL queries.
- Output escaping via `htmlspecialchars()` wrapper `e()`.
- CSRF token generation/validation in `src/utils/security.php`.
- Password hashing with Argon2id (fallback Bcrypt).
- Session hardening:
  - HttpOnly cookie
  - Secure cookie flag (configurable by `SESSION_COOKIE_SECURE`)
  - SameSite=Strict
  - Session ID regeneration
  - Idle timeout
  - User-Agent/IP fingerprint checks
- Upload hardening:
  - MIME verification using `finfo`
  - Secondary image validation via `getimagesize`
  - Random hashed filenames
  - Uploads stored outside document root
- Transaction integrity:
  - MySQL transaction + `FOR UPDATE` row locks
  - Overdraft prevention
- Manual middleware in `src/auth/security_guard.php`.
- Manual action logging in `logs` table:
  - webpage accessed
  - username
  - timestamp
  - client IP

## Key Paths
- Public web root: `public/`
- Middleware: `src/auth/security_guard.php`
- Security utilities: `src/utils/security.php`
- Transfer logic: `public/transfer.php`
- Upload logic: `public/upload.php`
- DB schema: `sql/schema.sql`

## command to access MYsql database
docker compose exec db mysql -u transactiwar_user -pasdfghjkl
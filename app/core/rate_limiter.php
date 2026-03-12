<?php
/**
 * DB-backed rate limiting.
 * Returns true if action is ALLOWED, false if rate limit exceeded.
 */

function check_rate_limit(string $identifier, string $action, int $max_attempts, int $window_seconds): bool
{
    try {
        $pdo = get_db();

        // Count recent attempts
        $stmt = $pdo->prepare(
            'SELECT COUNT(*) FROM rate_limits
             WHERE identifier = ? AND action = ? AND attempted_at > DATE_SUB(NOW(), INTERVAL ? SECOND)'
        );
        $stmt->execute([$identifier, $action, $window_seconds]);
        $count = (int) $stmt->fetchColumn();

        if ($count >= $max_attempts) {
            return false;
        }

        // Record this attempt
        $stmt = $pdo->prepare(
            'INSERT INTO rate_limits (identifier, action) VALUES (?, ?)'
        );
        $stmt->execute([$identifier, $action]);

        return true;
    } catch (PDOException $ex) {
        error_log('Rate limiter error: ' . $ex->getMessage());
        return false; // Fail closed — block on DB error to prevent bypass
    }
}

function check_login_rate_limit(string $ip, string $username): bool
{
    // Max 10 attempts per IP in 15 minutes
    if (!check_rate_limit('ip:' . $ip, 'login', 10, 900)) {
        return false;
    }

    // Max 5 failed attempts for the same username from the same IP in 15 minutes.
    // Avoids a global per-username lockout that another user could weaponize.
    try {
        $pdo = get_db();
        $stmt = $pdo->prepare(
            'SELECT COUNT(*) FROM login_attempts
             WHERE ip_address = ? AND username = ? AND success = 0
             AND attempted_at > DATE_SUB(NOW(), INTERVAL 15 MINUTE)'
        );
        $stmt->execute([$ip, $username]);

        return (int) $stmt->fetchColumn() < 5;
    } catch (PDOException $ex) {
        error_log('Login rate limiter error: ' . $ex->getMessage());
        return false; // Fail closed
    }
}

function record_login_attempt(string $ip, string $username, bool $success): void
{
    try {
        $pdo  = get_db();
        $stmt = $pdo->prepare(
            'INSERT INTO login_attempts (ip_address, username, success) VALUES (?, ?, ?)'
        );
        $stmt->execute([$ip, $username, $success ? 1 : 0]);
    } catch (PDOException $ex) {
        error_log('Login attempt log failed: ' . $ex->getMessage());
    }
}

function cleanup_old_rate_limits(): void
{
    try {
        $pdo = get_db();
        $pdo->exec("DELETE FROM rate_limits WHERE attempted_at < DATE_SUB(NOW(), INTERVAL 1 HOUR)");
        $pdo->exec("DELETE FROM login_attempts WHERE attempted_at < DATE_SUB(NOW(), INTERVAL 1 DAY)");
    } catch (PDOException $ex) {
        error_log('Rate limit cleanup failed: ' . $ex->getMessage());
    }
}

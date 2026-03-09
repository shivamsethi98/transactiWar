<?php
/**
 * Secure session management.
 * Handles: strict mode, fingerprinting, idle/absolute timeouts.
 */

function init_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.cookie_httponly', '1');
    ini_set('session.cookie_samesite', 'Strict');
    ini_set('session.cookie_path', '/');
    ini_set('session.gc_maxlifetime', '1800');

    // Set Secure flag if served over HTTPS
    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
        ini_set('session.cookie_secure', '1');
    }

    session_start();

    // Idle timeout: 30 minutes
    if (isset($_SESSION['last_activity'])) {
        if (time() - $_SESSION['last_activity'] > 1800) {
            destroy_session();
            header('Location: /login?msg=timeout');
            exit;
        }
    }
    $_SESSION['last_activity'] = time();

    // Absolute timeout: 4 hours
    if (isset($_SESSION['created_at'])) {
        if (time() - $_SESSION['created_at'] > 14400) {
            destroy_session();
            header('Location: /login?msg=timeout');
            exit;
        }
    }

    // Session fingerprint validation (detect hijacking)
    if (isset($_SESSION['user_id'], $_SESSION['fingerprint'])) {
        $current_fp = generate_fingerprint();
        if (!hash_equals($_SESSION['fingerprint'], $current_fp)) {
            destroy_session();
            header('Location: /login?msg=suspicious');
            exit;
        }
    }
}

function generate_fingerprint(): string
{
    $secret = getenv('APP_SECRET');
    if (!$secret) {
        error_log('CRITICAL: APP_SECRET environment variable is not set');
        http_response_code(500);
        exit;
    }
    $data = ($_SERVER['HTTP_USER_AGENT'] ?? 'unknown')
          . '|' . ($_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '');
    return hash_hmac('sha256', $data, $secret);
}

function destroy_session(): void
{
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $p['path'],
            $p['domain'],
            $p['secure'],
            $p['httponly']
        );
    }

    session_destroy();
}

function login_user(array $user): void
{
    session_regenerate_id(true); // Prevent session fixation
    $_SESSION['user_id']       = $user['id'];
    $_SESSION['username']      = $user['username'];
    $_SESSION['fingerprint']   = generate_fingerprint();
    $_SESSION['created_at']    = time();
    $_SESSION['last_activity'] = time();
}

function is_logged_in(): bool
{
    return isset($_SESSION['user_id']);
}

function current_user_id(): ?int
{
    return $_SESSION['user_id'] ?? null;
}

function current_username(): ?string
{
    return $_SESSION['username'] ?? null;
}

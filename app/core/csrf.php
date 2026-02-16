<?php
/**
 * CSRF protection using synchronizer token pattern.
 * Tokens are per-session and single-use.
 */

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . csrf_token() . '">';
}

function csrf_validate(): bool
{
    $token = $_POST['csrf_token'] ?? '';

    if (empty($token) || empty($_SESSION['csrf_token'])) {
        return false;
    }

    // Timing-safe comparison
    $valid = hash_equals($_SESSION['csrf_token'], $token);

    // Regenerate token after validation (one-time use)
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

    return $valid;
}

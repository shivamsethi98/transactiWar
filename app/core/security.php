<?php
/**
 * Input validation and output encoding helpers.
 * e() is THE function for XSS prevention — use on ALL dynamic output.
 */

function e(string $str): string
{
    return htmlspecialchars($str, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

function ejs($data): string
{
    return json_encode($data, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
}

function validate_username(string $val): bool
{
    return (bool) preg_match('/^[a-zA-Z0-9_]{3,50}$/', $val);
}

function validate_email(string $val): bool
{
    return (bool) filter_var($val, FILTER_VALIDATE_EMAIL);
}

function validate_int($val): int|false
{
    return filter_var($val, FILTER_VALIDATE_INT);
}

function validate_amount(string $val): float|false
{
    if (!preg_match('/^\d+(\.\d{1,2})?$/', $val)) {
        return false;
    }

    $amount = (float) $val;

    if ($amount <= 0 || $amount > 999999.99) {
        return false;
    }

    return $amount;
}

function validate_password(string $password): array
{
    $errors = [];

    if (strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters.';
    }
    if (!preg_match('/[A-Z]/', $password)) {
        $errors[] = 'Password must contain an uppercase letter.';
    }
    if (!preg_match('/[a-z]/', $password)) {
        $errors[] = 'Password must contain a lowercase letter.';
    }
    if (!preg_match('/[0-9]/', $password)) {
        $errors[] = 'Password must contain a digit.';
    }
    if (!preg_match('/[^a-zA-Z0-9]/', $password)) {
        $errors[] = 'Password must contain a special character.';
    }

    return $errors;
}

function sanitize_string(string $val, int $max_length = 255): string
{
    return mb_substr(trim($val), 0, $max_length, 'UTF-8');
}

function get_client_ip(): string
{
    // Only trust REMOTE_ADDR; X-Forwarded-For is attacker-controlled
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

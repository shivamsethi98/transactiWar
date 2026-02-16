<?php
/**
 * Authentication guard functions.
 * Used by the router to protect pages.
 */

function require_login(): void
{
    if (!is_logged_in()) {
        set_flash('error', 'Please log in to continue.');
        header('Location: /login');
        exit;
    }
}

function require_guest(): void
{
    if (is_logged_in()) {
        header('Location: /dashboard');
        exit;
    }
}

function get_logged_in_user(): ?array
{
    if (!is_logged_in()) {
        return null;
    }

    $pdo  = get_db();
    $stmt = $pdo->prepare('SELECT id, username, email, balance, full_name, biography, avatar_path, created_at FROM users WHERE id = ?');
    $stmt->execute([current_user_id()]);

    return $stmt->fetch() ?: null;
}

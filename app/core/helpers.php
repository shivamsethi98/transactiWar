<?php
/**
 * Utility functions: flash messages, redirects, pagination.
 */

function set_flash(string $type, string $message): void
{
    $_SESSION['flash'][] = [
        'type'    => $type,
        'message' => $message,
    ];
}

function get_flash(): array
{
    $messages = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $messages;
}

function redirect(string $url): void
{
    // Only allow relative paths — prevent open redirect and header injection
    if (!str_starts_with($url, '/') || str_contains($url, '//') || str_contains($url, "\n") || str_contains($url, "\r")) {
        $url = '/dashboard';
    }
    header('Location: ' . $url);
    exit;
}

function format_money(string|float $amount): string
{
    return number_format((float) $amount, 2);
}

function time_ago(string $datetime): string
{
    $now  = new DateTime();
    $past = new DateTime($datetime);
    $diff = $now->diff($past);

    if ($diff->y > 0) return $diff->y . 'y ago';
    if ($diff->m > 0) return $diff->m . 'mo ago';
    if ($diff->d > 0) return $diff->d . 'd ago';
    if ($diff->h > 0) return $diff->h . 'h ago';
    if ($diff->i > 0) return $diff->i . 'm ago';

    return 'Just now';
}

function paginate(int $total, int $per_page, int $current_page): array
{
    $total_pages = max(1, (int) ceil($total / $per_page));
    $current_page = max(1, min($current_page, $total_pages));
    $offset = ($current_page - 1) * $per_page;

    return [
        'total'        => $total,
        'per_page'     => $per_page,
        'current_page' => $current_page,
        'total_pages'  => $total_pages,
        'offset'       => $offset,
    ];
}

<?php
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    exit;
}

$current = $_SESSION['ui_theme'] ?? 'light';
$_SESSION['ui_theme'] = ($current === 'dark') ? 'light' : 'dark';

$redirect_to = sanitize_string($_POST['redirect_to'] ?? '/dashboard', 200);
if ($redirect_to === '' || !str_starts_with($redirect_to, '/')) {
    $redirect_to = '/dashboard';
}

redirect($redirect_to);

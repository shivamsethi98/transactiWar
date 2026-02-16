<?php
/**
 * Serves uploaded avatar images securely.
 * Files are stored outside webroot and served through this script.
 */

$filename = $_GET['f'] ?? '';

// Validate filename format: 32 hex chars + extension
if (!preg_match('/^[a-f0-9]{32}\.(jpg|png|gif)$/', $filename)) {
    http_response_code(404);
    exit;
}

$path = '/uploads/avatars/' . $filename;

if (!file_exists($path)) {
    http_response_code(404);
    exit;
}

$ext = pathinfo($filename, PATHINFO_EXTENSION);
$content_types = [
    'jpg' => 'image/jpeg',
    'png' => 'image/png',
    'gif' => 'image/gif',
];

header('Content-Type: ' . $content_types[$ext]);
header('Content-Disposition: inline');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: public, max-age=86400');
header('Content-Length: ' . filesize($path));

readfile($path);
exit;

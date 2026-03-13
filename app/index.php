<?php
/**
 * TransactiWar — Single entry point / router.
 * Every request flows through the security chain here.
 */

// Boot core modules
require_once __DIR__ . '/core/db.php';
require_once __DIR__ . '/core/security.php';
require_once __DIR__ . '/core/session.php';
require_once __DIR__ . '/core/csrf.php';
require_once __DIR__ . '/core/auth.php';
require_once __DIR__ . '/core/logger.php';
require_once __DIR__ . '/core/rate_limiter.php';
require_once __DIR__ . '/core/upload.php';
require_once __DIR__ . '/core/helpers.php';

// Security headers (defense in depth — also set via Apache)
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Pragma: no-cache');

if (is_https()) {
    header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
}

// Only allow GET and POST methods
if (!in_array($_SERVER['REQUEST_METHOD'], ['GET', 'POST'], true)) {
    http_response_code(405);
    header('Allow: GET, POST');
    exit;
}

// Initialize session
init_session();

// Parse the route
$request_uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$route = trim($request_uri, '/');
if ($route === '') {
    $route = is_logged_in() ? 'dashboard' : 'login';
}

// Log activity
log_activity($route);

// Periodic cleanup (1% chance per request)
if (mt_rand(1, 100) === 1) {
    cleanup_old_rate_limits();
}

// Route definitions
$public_routes    = ['login', 'register'];
$protected_routes = ['dashboard', 'profile', 'profile_view', 'search', 'transfer', 'history', 'logout', 'avatar', 'theme_toggle'];
$open_routes      = ['about']; // Accessible to both guests and logged-in users
$all_routes       = array_merge($public_routes, $protected_routes, $open_routes);

// Auth guard
if (in_array($route, $protected_routes, true)) {
    require_login();
} elseif (in_array($route, $public_routes, true)) {
    require_guest();
}

// CSRF validation on all POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_validate()) {
        http_response_code(403);
        $error_title   = 'Forbidden';
        $error_message = 'Invalid or expired form token. Please go back and try again.';
        include __DIR__ . '/pages/error.php';
        exit;
    }
}

// Dispatch to page
if (in_array($route, $all_routes, true)) {
    $page_file = __DIR__ . '/pages/' . $route . '.php';
    if (file_exists($page_file)) {
        include $page_file;
    } else {
        http_response_code(404);
        include __DIR__ . '/pages/404.php';
    }
} else {
    http_response_code(404);
    include __DIR__ . '/pages/404.php';
}

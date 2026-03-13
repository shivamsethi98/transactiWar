<?php
$active_theme = $_SESSION['ui_theme'] ?? 'light';
if (!in_array($active_theme, ['light', 'dark'], true)) {
    $active_theme = 'light';
}
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="<?= e($active_theme) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($page_title ?? 'TransactiWar') ?></title>

    <!-- Bootstrap 5.3 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"
          integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">

    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

    <!-- Google Fonts - Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">

    <!-- Custom CSS -->
    <link href="/assets/css/style.css?v=2" rel="stylesheet">
</head>
<body data-theme="<?= e($active_theme) ?>">

<?php if (is_logged_in()): ?>
<!-- Navbar for logged-in users -->
<nav class="navbar navbar-expand-lg tw-navbar sticky-top">
    <div class="container">
        <a class="navbar-brand fw-bold" href="/dashboard">
            <i class="bi bi-shield-lock-fill me-1"></i>TransactiWar
        </a>

        <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navMain">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navMain">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link <?= ($route ?? '') === 'dashboard' ? 'active' : '' ?>" href="/dashboard">
                        <i class="bi bi-grid-1x2 me-1"></i>Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= ($route ?? '') === 'transfer' ? 'active' : '' ?>" href="/transfer">
                        <i class="bi bi-send me-1"></i>Transfer
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= ($route ?? '') === 'history' ? 'active' : '' ?>" href="/history">
                        <i class="bi bi-clock-history me-1"></i>History
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= ($route ?? '') === 'search' ? 'active' : '' ?>" href="/search">
                        <i class="bi bi-search me-1"></i>Search
                    </a>
                </li>
            </ul>

            <ul class="navbar-nav">
                <li class="nav-item me-1">
                    <form method="POST" action="/theme_toggle" class="d-inline">
                        <?= csrf_field() ?>
                        <input type="hidden" name="redirect_to" value="<?= e($request_uri ?? '/') ?>">
                        <button type="submit" class="nav-link btn btn-link text-decoration-none tw-theme-toggle" title="Toggle theme">
                            <?php if ($active_theme === 'dark'): ?>
                                <i class="bi bi-sun me-1"></i>Light
                            <?php else: ?>
                                <i class="bi bi-moon-stars me-1"></i>Dark
                            <?php endif; ?>
                        </button>
                    </form>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= ($route ?? '') === 'about' ? 'active' : '' ?>" href="/about">
                        <i class="bi bi-info-circle me-1"></i>About
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= ($route ?? '') === 'profile' ? 'active' : '' ?>" href="/profile">
                        <i class="bi bi-person-circle me-1"></i><?= e(current_username() ?? '') ?>
                    </a>
                </li>
                <li class="nav-item">
                    <form method="POST" action="/logout" class="d-inline">
                        <?= csrf_field() ?>
                        <button type="submit" class="nav-link btn btn-link text-decoration-none">
                            <i class="bi bi-box-arrow-right me-1"></i>Logout
                        </button>
                    </form>
                </li>
            </ul>
        </div>
    </div>
</nav>
<?php endif; ?>

<main class="<?= is_logged_in() ? 'tw-main' : 'tw-main-guest' ?>">
    <div class="container">
        <?php include __DIR__ . '/alerts.php'; ?>

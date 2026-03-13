<?php
$page_title = 'Log In — TransactiWar';

$error = '';
$login_success = false;
$login_redirect_delay_ms = 1000;

// Show timeout/suspicious messages from query params
$msg = $_GET['msg'] ?? '';
if ($msg === 'timeout') {
    set_flash('warning', 'Your session has expired. Please log in again.');
} elseif ($msg === 'suspicious') {
    set_flash('warning', 'Session anomaly detected. Please log in again.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize_string($_POST['username'] ?? '', 50);
    $password = $_POST['password'] ?? '';
    $ip       = get_client_ip();

    if (!check_login_rate_limit($ip, $username)) {
        $error = 'Too many login attempts. Please wait 15 minutes.';
    } else {
        // Reject oversized passwords early to prevent bcrypt DoS
        if (strlen($password) > 72) {
            record_login_attempt($ip, $username, false);
            $error = 'Invalid username or password.';
        } else {
            $pdo  = get_db();
            $stmt = $pdo->prepare('SELECT id, username, password FROM users WHERE username = ?');
            $stmt->execute([$username]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                // Success
                record_login_attempt($ip, $username, true);
                login_user($user);
                redirect('/dashboard');
            } else {
                // Failure — generic message prevents username enumeration
                record_login_attempt($ip, $username, false);
                $error = 'Invalid username or password.';
            }
        }
    }
}

if ($login_success) {
    header('Refresh: 1; url=/dashboard');
}

include __DIR__ . '/../templates/header.php';
?>

<div class="tw-auth-card mx-auto">
    <div class="tw-auth-brand">
        <h1><i class="bi bi-shield-lock-fill"></i> TransactiWar</h1>
        <p>Log in to your account</p>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger py-2" style="font-size: 0.85rem;">
            <?= e($error) ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="/login" data-validate>
        <?= csrf_field() ?>

        <div class="mb-3">
            <label for="username" class="form-label">Username</label>
            <input type="text" class="form-control" id="username" name="username"
                   required maxlength="50" placeholder="Enter your username" autofocus>
        </div>

        <div class="mb-4">
            <label for="password" class="form-label">Password</label>
            <input type="password" class="form-control" id="password" name="password"
                   required placeholder="Enter your password">
        </div>

        <button type="submit" class="btn btn-primary w-100 mb-3">
            <i class="bi bi-box-arrow-in-right me-1"></i>Log In
        </button>

        <p class="text-center text-muted mb-0" style="font-size: 0.85rem;">
            Don't have an account? <a href="/register" class="text-decoration-none">Register</a>
        </p>
    </form>

    <?php if ($login_success): ?>
        <div class="tw-login-buffer" id="login-buffer"
             data-redirect="/dashboard"
             data-delay-ms="<?= (int) $login_redirect_delay_ms ?>">
            <div class="spinner-border text-primary" role="status" aria-hidden="true"></div>
            <p class="mb-0 mt-3">Verifying secure session...</p>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../templates/footer.php'; ?>

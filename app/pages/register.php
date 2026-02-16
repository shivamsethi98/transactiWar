<?php
$page_title = 'Register — TransactiWar';

$errors   = [];
$old      = ['username' => '', 'email' => '', 'full_name' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username  = sanitize_string($_POST['username'] ?? '', 50);
    $email     = sanitize_string($_POST['email'] ?? '', 255);
    $full_name = sanitize_string($_POST['full_name'] ?? '', 100);
    $password  = $_POST['password'] ?? '';
    $confirm   = $_POST['confirm_password'] ?? '';

    $old = ['username' => $username, 'email' => $email, 'full_name' => $full_name];

    // Rate limit: 3 registrations per IP per hour
    if (!check_rate_limit('ip:' . get_client_ip(), 'register', 3, 3600)) {
        $errors[] = 'Too many registration attempts. Please try again later.';
    }

    // Validate username
    if (!validate_username($username)) {
        $errors[] = 'Username must be 3-50 characters (letters, numbers, underscores only).';
    }

    // Validate email
    if (!validate_email($email)) {
        $errors[] = 'Please enter a valid email address.';
    }

    // Validate password
    $pwd_errors = validate_password($password);
    if (!empty($pwd_errors)) {
        $errors = array_merge($errors, $pwd_errors);
    }

    // Confirm password
    if ($password !== $confirm) {
        $errors[] = 'Passwords do not match.';
    }

    // Check uniqueness (only if no other errors to avoid unnecessary queries)
    if (empty($errors)) {
        $pdo = get_db();

        $stmt = $pdo->prepare('SELECT id FROM users WHERE LOWER(username) = LOWER(?) OR LOWER(email) = LOWER(?)');
        $stmt->execute([$username, $email]);

        if ($stmt->fetch()) {
            $errors[] = 'Username or email already taken.';
        }
    }

    // Create user
    if (empty($errors)) {
        $pdo  = get_db();
        $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

        $stmt = $pdo->prepare(
            'INSERT INTO users (username, email, password, full_name, balance) VALUES (?, ?, ?, ?, 100.00)'
        );

        try {
            $stmt->execute([$username, $email, $hash, $full_name ?: null]);
            set_flash('success', 'Account created! You have been credited Rs. 100. Please log in.');
            redirect('/login');
        } catch (PDOException $ex) {
            error_log('Registration error: ' . $ex->getMessage());
            $errors[] = 'Registration failed. Please try again.';
        }
    }
}

include __DIR__ . '/../templates/header.php';
?>

<div class="tw-auth-card mx-auto">
    <div class="tw-auth-brand">
        <h1><i class="bi bi-shield-lock-fill"></i> TransactiWar</h1>
        <p>Create your account</p>
    </div>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger py-2">
            <ul class="mb-0 ps-3" style="font-size: 0.85rem;">
                <?php foreach ($errors as $err): ?>
                    <li><?= e($err) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="POST" action="/register" data-validate>
        <?= csrf_field() ?>

        <div class="mb-3">
            <label for="username" class="form-label">Username</label>
            <input type="text" class="form-control" id="username" name="username"
                   value="<?= e($old['username']) ?>" required minlength="3" maxlength="50"
                   pattern="[a-zA-Z0-9_]{3,50}" placeholder="john_doe">
        </div>

        <div class="mb-3">
            <label for="email" class="form-label">Email</label>
            <input type="email" class="form-control" id="email" name="email"
                   value="<?= e($old['email']) ?>" required maxlength="255" placeholder="you@example.com">
        </div>

        <div class="mb-3">
            <label for="full_name" class="form-label">Full Name <span class="text-muted">(optional)</span></label>
            <input type="text" class="form-control" id="full_name" name="full_name"
                   value="<?= e($old['full_name']) ?>" maxlength="100" placeholder="John Doe">
        </div>

        <div class="mb-3">
            <label for="password-input" class="form-label">Password</label>
            <input type="password" class="form-control" id="password-input" name="password"
                   required minlength="8" placeholder="Min 8 chars, mixed case + digit + symbol">
            <div class="progress mt-2" style="height: 4px;">
                <div id="password-strength" class="progress-bar" style="width: 0%;"></div>
            </div>
        </div>

        <div class="mb-4">
            <label for="confirm_password" class="form-label">Confirm Password</label>
            <input type="password" class="form-control" id="confirm_password" name="confirm_password"
                   required minlength="8" placeholder="Repeat your password">
        </div>

        <button type="submit" class="btn btn-primary w-100 mb-3">
            <i class="bi bi-person-plus me-1"></i>Create Account
        </button>

        <p class="text-center text-muted mb-0" style="font-size: 0.85rem;">
            Already have an account? <a href="/login" class="text-decoration-none">Log in</a>
        </p>
    </form>
</div>

<?php include __DIR__ . '/../templates/footer.php'; ?>

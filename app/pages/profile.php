<?php
$page_title = 'My Profile — TransactiWar';

$user   = get_logged_in_user();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'update_profile';

    if ($action === 'update_profile') {
        $email     = sanitize_string($_POST['email'] ?? '', 255);
        $full_name = sanitize_string($_POST['full_name'] ?? '', 100);
        $biography = sanitize_string($_POST['biography'] ?? '', 5000);

        // Validate email
        if (!validate_email($email)) {
            $errors[] = 'Please enter a valid email address.';
        }

        // Check email uniqueness (if changed)
        if (empty($errors) && strtolower($email) !== strtolower($user['email'])) {
            $pdo  = get_db();
            $stmt = $pdo->prepare('SELECT id FROM users WHERE LOWER(email) = LOWER(?) AND id != ?');
            $stmt->execute([$email, current_user_id()]);
            if ($stmt->fetch()) {
                $errors[] = 'Email already in use.';
            }
        }

        if (empty($errors)) {
            $pdo  = get_db();
            $stmt = $pdo->prepare('UPDATE users SET email = ?, full_name = ?, biography = ? WHERE id = ?');
            $stmt->execute([$email, $full_name ?: null, $biography ?: null, current_user_id()]);

            set_flash('success', 'Profile updated.');
            redirect('/profile');
        }

    } elseif ($action === 'update_avatar') {
        if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] !== UPLOAD_ERR_NO_FILE) {
            $result = handle_avatar_upload($_FILES['avatar'], current_user_id());
            if ($result['ok']) {
                set_flash('success', 'Profile photo updated.');
            } else {
                set_flash('error', $result['error'] ?? 'Upload failed. Please try again.');
            }
        } else {
            set_flash('error', 'No file selected.');
        }
        redirect('/profile');
    }

    // Reload user data after update
    $user = get_logged_in_user();
}

$avatar_url = $user['avatar_path']
    ? '/avatar?f=' . urlencode($user['avatar_path'])
    : '/assets/img/default-avatar.png';

include __DIR__ . '/../templates/header.php';
?>

<div class="row g-4">
    <!-- Profile card -->
    <div class="col-lg-4">
        <div class="tw-card text-center">
            <img src="<?= e($avatar_url) ?>" alt="Avatar" class="tw-avatar-lg mb-3" id="avatar-preview">

            <h5 class="mb-1"><?= e($user['username']) ?></h5>
            <?php if ($user['full_name']): ?>
                <p class="text-muted mb-2" style="font-size: 0.875rem;"><?= e($user['full_name']) ?></p>
            <?php endif; ?>

            <div class="tw-balance-label">Balance</div>
            <div class="tw-balance mb-3">Rs. <?= format_money($user['balance']) ?></div>

            <p class="text-muted mb-0" style="font-size: 0.75rem;">
                Joined <?= e(date('M j, Y', strtotime($user['created_at']))) ?>
            </p>
        </div>

        <!-- Avatar upload -->
        <div class="tw-card">
            <div class="tw-card-header">Profile Photo</div>
            <form method="POST" action="/profile" enctype="multipart/form-data">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="update_avatar">
                <input type="file" class="form-control mb-2" id="avatar-input" name="avatar"
                       accept="image/jpeg,image/png,image/gif">
                <small class="text-muted d-block mb-3">JPG, PNG, or GIF. Max 2MB and 4000x4000 pixels.</small>
                <button type="submit" class="btn btn-outline-primary btn-sm w-100">
                    <i class="bi bi-upload me-1"></i>Upload Photo
                </button>
            </form>
        </div>
    </div>

    <!-- Edit form -->
    <div class="col-lg-8">
        <div class="tw-card">
            <div class="tw-card-header">Edit Profile</div>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger py-2">
                    <ul class="mb-0 ps-3" style="font-size: 0.85rem;">
                        <?php foreach ($errors as $err): ?>
                            <li><?= e($err) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="POST" action="/profile" data-validate>
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="update_profile">

                <div class="mb-3">
                    <label class="form-label">Username</label>
                    <input type="text" class="form-control" value="<?= e($user['username']) ?>" disabled>
                    <small class="text-muted">Username cannot be changed.</small>
                </div>

                <div class="mb-3">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" class="form-control" id="email" name="email"
                           value="<?= e($user['email']) ?>" required maxlength="255">
                </div>

                <div class="mb-3">
                    <label for="full_name" class="form-label">Full Name</label>
                    <input type="text" class="form-control" id="full_name" name="full_name"
                           value="<?= e($user['full_name'] ?? '') ?>" maxlength="100">
                </div>

                <div class="mb-4">
                    <label for="biography" class="form-label">Biography</label>
                    <textarea class="form-control" id="biography" name="biography"
                              rows="5" maxlength="5000" placeholder="Tell us about yourself..."><?= e($user['biography'] ?? '') ?></textarea>
                    <small class="text-muted">Max 5000 characters.</small>
                </div>

                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-lg me-1"></i>Save Changes
                </button>
            </form>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../templates/footer.php'; ?>

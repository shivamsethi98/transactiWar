<?php
$view_id = validate_int($_GET['id'] ?? '');

if (!$view_id) {
    set_flash('error', 'Invalid user ID.');
    redirect('/search');
}

// If viewing own profile, redirect to edit page
if ($view_id === current_user_id()) {
    redirect('/profile');
}

$pdo  = get_db();
$stmt = $pdo->prepare('SELECT id, username, email, full_name, biography, avatar_path, created_at FROM users WHERE id = ?');
$stmt->execute([$view_id]);
$user = $stmt->fetch();

if (!$user) {
    set_flash('error', 'User not found.');
    redirect('/search');
}

$page_title = e($user['username']) . ' — TransactiWar';

$avatar_url = $user['avatar_path']
    ? '/avatar?f=' . urlencode($user['avatar_path'])
    : '/assets/img/default-avatar.png';

include __DIR__ . '/../templates/header.php';
?>

<div class="row justify-content-center">
    <div class="col-lg-6">
        <div class="tw-card text-center">
            <img src="<?= e($avatar_url) ?>" alt="Avatar" class="tw-avatar-lg mb-3">

            <h4 class="mb-1"><?= e($user['username']) ?></h4>
            <p class="text-muted mb-0" style="font-size: 0.875rem;">
                User #<?= e((string) $user['id']) ?>
            </p>

            <?php if ($user['full_name']): ?>
                <p class="mt-2 mb-0"><?= e($user['full_name']) ?></p>
            <?php endif; ?>

            <p class="text-muted mt-2 mb-0" style="font-size: 0.75rem;">
                Joined <?= e(date('M j, Y', strtotime($user['created_at']))) ?>
            </p>
        </div>

        <?php if ($user['biography']): ?>
            <div class="tw-card">
                <div class="tw-card-header">Biography</div>
                <div class="tw-bio"><?= e($user['biography']) ?></div>
            </div>
        <?php endif; ?>

        <div class="d-flex gap-2">
            <a href="/transfer?to=<?= (int) $user['id'] ?>" class="btn btn-primary flex-fill">
                <i class="bi bi-send me-1"></i>Send Money
            </a>
            <a href="/search" class="btn btn-outline-secondary flex-fill">
                <i class="bi bi-arrow-left me-1"></i>Back
            </a>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../templates/footer.php'; ?>

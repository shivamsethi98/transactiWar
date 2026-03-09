<?php
$page_title = 'Search Users — TransactiWar';

$query   = sanitize_string($_GET['q'] ?? '', 100);
$results = [];

if ($query !== '') {
    // Rate limit: 60 searches per user per minute
    if (!check_rate_limit('user:' . current_user_id(), 'search', 60, 60)) {
        set_flash('error', 'Too many searches. Please slow down.');
    } else {
        $pdo = get_db();

        // If numeric, search by ID; otherwise by username
        $id_val = validate_int($query);
        if ($id_val !== false && $id_val > 0) {
            $stmt = $pdo->prepare(
                'SELECT id, username, full_name, avatar_path FROM users WHERE id = ?'
            );
            $stmt->execute([$id_val]);
        } else {
            $stmt = $pdo->prepare(
                'SELECT id, username, full_name, avatar_path FROM users
                 WHERE username LIKE ? ORDER BY username LIMIT 20'
            );
            // Escape LIKE wildcards to prevent user enumeration
            $safe_query = str_replace(['%', '_'], ['\\%', '\\_'], $query);
            $stmt->execute(['%' . $safe_query . '%']);
        }

        $results = $stmt->fetchAll();
    }
}

include __DIR__ . '/../templates/header.php';
?>

<div class="row justify-content-center">
    <div class="col-lg-6">
        <div class="tw-card">
            <div class="tw-card-header">
                <i class="bi bi-search me-1"></i>Search Users
            </div>

            <form method="GET" action="/search" class="mb-3">
                <div class="input-group">
                    <input type="text" class="form-control" name="q"
                           value="<?= e($query) ?>" placeholder="Username or User ID"
                           maxlength="100" autofocus>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-search"></i>
                    </button>
                </div>
            </form>

            <?php if ($query !== '' && empty($results)): ?>
                <div class="tw-empty">
                    <i class="bi bi-person-x"></i>
                    <p>No users found for "<?= e($query) ?>".</p>
                </div>
            <?php elseif (!empty($results)): ?>
                <div>
                    <?php foreach ($results as $user): ?>
                        <?php
                        $avatar_url = $user['avatar_path']
                            ? '/avatar?f=' . urlencode($user['avatar_path'])
                            : '/assets/img/default-avatar.png';
                        ?>
                        <a href="/profile_view?id=<?= (int) $user['id'] ?>" class="tw-user-item">
                            <img src="<?= e($avatar_url) ?>" alt="" class="tw-avatar">
                            <div>
                                <div class="fw-semibold"><?= e($user['username']) ?></div>
                                <small class="text-muted">
                                    #<?= (int) $user['id'] ?>
                                    <?php if ($user['full_name']): ?>
                                        &middot; <?= e($user['full_name']) ?>
                                    <?php endif; ?>
                                </small>
                            </div>
                            <i class="bi bi-chevron-right ms-auto text-muted"></i>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php elseif ($query === ''): ?>
                <p class="text-muted text-center mb-0" style="font-size: 0.875rem;">
                    Search by username or user ID to find other users.
                </p>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../templates/footer.php'; ?>

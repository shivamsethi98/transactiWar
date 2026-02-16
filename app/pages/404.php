<?php $page_title = 'Not Found — TransactiWar'; ?>
<?php include __DIR__ . '/../templates/header.php'; ?>

<div class="text-center py-5">
    <div class="tw-empty">
        <i class="bi bi-exclamation-triangle"></i>
        <h2>404 — Page Not Found</h2>
        <p>The page you're looking for doesn't exist.</p>
        <a href="<?= is_logged_in() ? '/dashboard' : '/login' ?>" class="btn btn-primary mt-3">
            <i class="bi bi-house me-1"></i>Go Home
        </a>
    </div>
</div>

<?php include __DIR__ . '/../templates/footer.php'; ?>

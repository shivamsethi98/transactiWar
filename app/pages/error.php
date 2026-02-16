<?php $page_title = 'Error — TransactiWar'; ?>
<?php include __DIR__ . '/../templates/header.php'; ?>

<div class="text-center py-5">
    <div class="tw-empty">
        <i class="bi bi-shield-exclamation"></i>
        <h2><?= e($error_title ?? 'Error') ?></h2>
        <p><?= e($error_message ?? 'Something went wrong. Please try again.') ?></p>
        <a href="<?= is_logged_in() ? '/dashboard' : '/login' ?>" class="btn btn-primary mt-3">
            <i class="bi bi-house me-1"></i>Go Home
        </a>
    </div>
</div>

<?php include __DIR__ . '/../templates/footer.php'; ?>

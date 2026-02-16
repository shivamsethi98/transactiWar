<?php
$flash_messages = get_flash();
foreach ($flash_messages as $msg):
    $type_class = match ($msg['type']) {
        'success' => 'alert-success',
        'error'   => 'alert-danger',
        'warning' => 'alert-warning',
        default   => 'alert-info',
    };
?>
<div class="alert <?= $type_class ?> alert-dismissible fade show tw-alert" role="alert">
    <?= e($msg['message']) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
<?php endforeach; ?>

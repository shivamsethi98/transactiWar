<?php
$page_title = 'Dashboard — TransactiWar';

$uid  = current_user_id();
$user = get_logged_in_user();
$pdo  = get_db();

// Recent transactions (last 5)
$stmt = $pdo->prepare(
    'SELECT t.id, t.sender_id, t.receiver_id, t.amount, t.comment, t.created_at,
            s.username AS sender_name, r.username AS receiver_name
     FROM transactions t
     JOIN users s ON t.sender_id = s.id
     JOIN users r ON t.receiver_id = r.id
     WHERE t.sender_id = ? OR t.receiver_id = ?
     ORDER BY t.created_at DESC
     LIMIT 5'
);
$stmt->execute([$uid, $uid]);
$recent = $stmt->fetchAll();

// Stats
$stmt = $pdo->prepare('SELECT COUNT(*) FROM transactions WHERE sender_id = ? OR receiver_id = ?');
$stmt->execute([$uid, $uid]);
$total_tx = (int) $stmt->fetchColumn();

$stmt = $pdo->prepare('SELECT COALESCE(SUM(amount), 0) FROM transactions WHERE sender_id = ?');
$stmt->execute([$uid]);
$total_sent = (float) $stmt->fetchColumn();

$stmt = $pdo->prepare('SELECT COALESCE(SUM(amount), 0) FROM transactions WHERE receiver_id = ?');
$stmt->execute([$uid]);
$total_received = (float) $stmt->fetchColumn();

include __DIR__ . '/../templates/header.php';
?>

<!-- Balance & Quick Actions -->
<div class="row g-4 mb-4">
    <div class="col-md-6">
        <div class="tw-card">
            <div class="tw-balance-label">Your Balance</div>
            <div class="tw-balance">Rs. <?= format_money($user['balance']) ?></div>
            <p class="text-muted mt-1 mb-0" style="font-size: 0.8rem;">
                Welcome back, <?= e($user['username']) ?>
            </p>
        </div>
    </div>

    <div class="col-md-6">
        <div class="tw-card">
            <div class="tw-card-header">Quick Actions</div>
            <div class="tw-quick-actions">
                <a href="/transfer" class="tw-quick-action">
                    <i class="bi bi-send"></i>
                    Send Money
                </a>
                <a href="/search" class="tw-quick-action">
                    <i class="bi bi-search"></i>
                    Find Users
                </a>
                <a href="/history" class="tw-quick-action">
                    <i class="bi bi-clock-history"></i>
                    History
                </a>
                <a href="/profile" class="tw-quick-action">
                    <i class="bi bi-person"></i>
                    Profile
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Stats -->
<div class="row g-4 mb-4">
    <div class="col-4">
        <div class="tw-card tw-stat">
            <div class="tw-stat-value"><?= $total_tx ?></div>
            <div class="tw-stat-label">Transactions</div>
        </div>
    </div>
    <div class="col-4">
        <div class="tw-card tw-stat">
            <div class="tw-stat-value tw-amount-negative">Rs. <?= format_money($total_sent) ?></div>
            <div class="tw-stat-label">Total Sent</div>
        </div>
    </div>
    <div class="col-4">
        <div class="tw-card tw-stat">
            <div class="tw-stat-value tw-amount-positive">Rs. <?= format_money($total_received) ?></div>
            <div class="tw-stat-label">Total Received</div>
        </div>
    </div>
</div>

<!-- Recent Transactions -->
<div class="tw-card">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div class="tw-card-header mb-0">Recent Transactions</div>
        <?php if ($total_tx > 5): ?>
            <a href="/history" class="text-decoration-none" style="font-size: 0.8rem;">View all &rarr;</a>
        <?php endif; ?>
    </div>

    <?php if (empty($recent)): ?>
        <div class="tw-empty">
            <i class="bi bi-clock-history"></i>
            <p>No transactions yet. <a href="/transfer" class="text-decoration-none">Make your first transfer!</a></p>
        </div>
    <?php else: ?>
        <div class="tw-table-wrap">
            <table class="tw-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>User</th>
                        <th class="text-end">Amount</th>
                        <th>Comment</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recent as $tx): ?>
                        <?php
                        $is_sender = ($tx['sender_id'] == $uid);
                        $other     = $is_sender ? $tx['receiver_name'] : $tx['sender_name'];
                        $other_id  = $is_sender ? $tx['receiver_id'] : $tx['sender_id'];
                        ?>
                        <tr>
                            <td style="white-space: nowrap; font-size: 0.85rem;">
                                <?= e(date('M j', strtotime($tx['created_at']))) ?>
                                <small class="text-muted"><?= e(date('g:i A', strtotime($tx['created_at']))) ?></small>
                            </td>
                            <td>
                                <a href="/profile_view?id=<?= (int) $other_id ?>" class="text-decoration-none">
                                    <?= e($other) ?>
                                </a>
                            </td>
                            <td class="text-end">
                                <?php if ($is_sender): ?>
                                    <span class="tw-amount-negative">-Rs. <?= format_money($tx['amount']) ?></span>
                                <?php else: ?>
                                    <span class="tw-amount-positive">+Rs. <?= format_money($tx['amount']) ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($tx['comment']): ?>
                                    <span class="tw-comment" title="<?= e($tx['comment']) ?>"><?= e($tx['comment']) ?></span>
                                <?php else: ?>
                                    <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../templates/footer.php'; ?>

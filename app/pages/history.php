<?php
$page_title = 'Transaction History — TransactiWar';

$uid = current_user_id();
$pdo = get_db();

// Count total transactions for pagination
$stmt = $pdo->prepare('SELECT COUNT(*) FROM transactions WHERE sender_id = ? OR receiver_id = ?');
$stmt->execute([$uid, $uid]);
$total = (int) $stmt->fetchColumn();

$page = max(1, (int) ($_GET['page'] ?? 1));
$pg   = paginate($total, 20, $page);

// Fetch transactions with sender/receiver usernames
$stmt = $pdo->prepare(
    'SELECT t.id, t.sender_id, t.receiver_id, t.amount, t.comment, t.created_at,
            s.username AS sender_name, r.username AS receiver_name
     FROM transactions t
     JOIN users s ON t.sender_id = s.id
     JOIN users r ON t.receiver_id = r.id
     WHERE t.sender_id = ? OR t.receiver_id = ?
     ORDER BY t.created_at DESC
     LIMIT ? OFFSET ?'
);
$stmt->execute([$uid, $uid, $pg['per_page'], $pg['offset']]);
$transactions = $stmt->fetchAll();

include __DIR__ . '/../templates/header.php';
?>

<div class="tw-card">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div class="tw-card-header mb-0">Transaction History</div>
        <span class="text-muted" style="font-size: 0.8rem;"><?= $total ?> total</span>
    </div>

    <?php if (empty($transactions)): ?>
        <div class="tw-empty">
            <i class="bi bi-clock-history"></i>
            <p>No transactions yet.</p>
            <a href="/transfer" class="btn btn-primary btn-sm">Make a Transfer</a>
        </div>
    <?php else: ?>
        <div class="tw-table-wrap">
            <table class="tw-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Type</th>
                        <th>User</th>
                        <th class="text-end">Amount</th>
                        <th>Comment</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($transactions as $tx): ?>
                        <?php
                        $is_sender = ($tx['sender_id'] == $uid);
                        $other     = $is_sender ? $tx['receiver_name'] : $tx['sender_name'];
                        $other_id  = $is_sender ? $tx['receiver_id'] : $tx['sender_id'];
                        ?>
                        <tr>
                            <td style="white-space: nowrap;">
                                <?= e(date('M j, Y', strtotime($tx['created_at']))) ?>
                                <br><small class="text-muted"><?= e(date('g:i A', strtotime($tx['created_at']))) ?></small>
                            </td>
                            <td>
                                <?php if ($is_sender): ?>
                                    <span class="tw-badge tw-badge-sent">Sent</span>
                                <?php else: ?>
                                    <span class="tw-badge tw-badge-received">Received</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="/profile_view?id=<?= (int) $other_id ?>" class="text-decoration-none">
                                    <?= e($other) ?>
                                </a>
                                <br><small class="text-muted">#<?= (int) $other_id ?></small>
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
                                    <span class="tw-comment" title="<?= e($tx['comment']) ?>">
                                        <?= e($tx['comment']) ?>
                                    </span>
                                <?php else: ?>
                                    <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <?php if ($pg['total_pages'] > 1): ?>
            <nav class="mt-3">
                <ul class="pagination tw-pagination justify-content-center mb-0">
                    <?php if ($pg['current_page'] > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="/history?page=<?= $pg['current_page'] - 1 ?>">
                                <i class="bi bi-chevron-left"></i>
                            </a>
                        </li>
                    <?php endif; ?>

                    <?php for ($i = max(1, $pg['current_page'] - 2); $i <= min($pg['total_pages'], $pg['current_page'] + 2); $i++): ?>
                        <li class="page-item <?= $i === $pg['current_page'] ? 'active' : '' ?>">
                            <a class="page-link" href="/history?page=<?= $i ?>"><?= $i ?></a>
                        </li>
                    <?php endfor; ?>

                    <?php if ($pg['current_page'] < $pg['total_pages']): ?>
                        <li class="page-item">
                            <a class="page-link" href="/history?page=<?= $pg['current_page'] + 1 ?>">
                                <i class="bi bi-chevron-right"></i>
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </nav>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../templates/footer.php'; ?>

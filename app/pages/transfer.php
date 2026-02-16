<?php
$page_title = 'Transfer Money — TransactiWar';

$errors = [];
$old    = [
    'receiver_id' => $_GET['to'] ?? '',
    'amount'      => '',
    'comment'     => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sender_id   = current_user_id(); // ALWAYS from session
    $receiver_id = validate_int($_POST['receiver_id'] ?? '');
    $amount_raw  = trim($_POST['amount'] ?? '');
    $comment     = sanitize_string($_POST['comment'] ?? '', 500);

    $old = [
        'receiver_id' => $_POST['receiver_id'] ?? '',
        'amount'      => $amount_raw,
        'comment'     => $comment,
    ];

    // Rate limit: 30 transfers per user per minute
    if (!check_rate_limit('user:' . $sender_id, 'transfer', 30, 60)) {
        $errors[] = 'Too many transfers. Please wait a moment.';
    }

    // Validate receiver
    if (!$receiver_id || $receiver_id <= 0) {
        $errors[] = 'Please enter a valid receiver ID.';
    }

    if ($receiver_id === $sender_id) {
        $errors[] = 'Cannot transfer to yourself.';
    }

    // Validate amount
    $amount = validate_amount($amount_raw);
    if ($amount === false) {
        $errors[] = 'Enter a valid amount (positive, up to 2 decimal places).';
    }

    if (!empty($errors)) {
        goto render;
    }

    // Execute transfer with row-level locking
    $pdo = get_db();

    try {
        $pdo->beginTransaction();

        // Lock rows in consistent order (lower ID first) to prevent deadlocks
        $first_id  = min($sender_id, $receiver_id);
        $second_id = max($sender_id, $receiver_id);

        $stmt = $pdo->prepare('SELECT id, balance FROM users WHERE id = ? FOR UPDATE');
        $stmt->execute([$first_id]);
        $first = $stmt->fetch();

        $stmt = $pdo->prepare('SELECT id, balance FROM users WHERE id = ? FOR UPDATE');
        $stmt->execute([$second_id]);
        $second = $stmt->fetch();

        // Verify receiver exists
        if (!$first || !$second) {
            $pdo->rollBack();
            $errors[] = 'Receiver not found.';
            goto render;
        }

        // Determine who is sender/receiver from locked rows
        $sender_balance = ($first['id'] == $sender_id)
            ? (float) $first['balance']
            : (float) $second['balance'];

        // Check balance INSIDE the transaction AFTER acquiring locks
        if ($sender_balance < $amount) {
            $pdo->rollBack();
            $errors[] = 'Insufficient balance. You have Rs. ' . format_money($sender_balance) . '.';
            goto render;
        }

        // Debit sender (atomic)
        $stmt = $pdo->prepare('UPDATE users SET balance = balance - ? WHERE id = ?');
        $stmt->execute([$amount, $sender_id]);

        // Credit receiver (atomic)
        $stmt = $pdo->prepare('UPDATE users SET balance = balance + ? WHERE id = ?');
        $stmt->execute([$amount, $receiver_id]);

        // Record transaction
        $stmt = $pdo->prepare(
            'INSERT INTO transactions (sender_id, receiver_id, amount, comment) VALUES (?, ?, ?, ?)'
        );
        $stmt->execute([$sender_id, $receiver_id, $amount, $comment ?: null]);

        $pdo->commit();

        // PRG: redirect after success
        set_flash('success', 'Transferred Rs. ' . format_money($amount) . ' successfully.');
        redirect('/history');

    } catch (PDOException $ex) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log('Transfer error: ' . $ex->getMessage());
        $errors[] = 'Transfer failed. Please try again.';
    }
}

render:

include __DIR__ . '/../templates/header.php';
?>

<div class="row justify-content-center">
    <div class="col-lg-5">
        <div class="tw-card">
            <div class="tw-card-header">
                <i class="bi bi-send me-1"></i>Send Money
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

            <?php
            // Show current balance
            $current_user = get_logged_in_user();
            ?>
            <div class="text-center mb-3 p-3" style="background: var(--tw-bg); border-radius: var(--tw-radius-sm);">
                <div class="tw-balance-label">Your Balance</div>
                <div class="tw-balance" style="font-size: 1.5rem;">Rs. <?= format_money($current_user['balance']) ?></div>
            </div>

            <form method="POST" action="/transfer" id="transfer-form" data-validate>
                <?= csrf_field() ?>

                <div class="mb-3">
                    <label for="receiver_id" class="form-label">Receiver User ID</label>
                    <input type="number" class="form-control" id="receiver_id" name="receiver_id"
                           value="<?= e((string) $old['receiver_id']) ?>" required min="1"
                           placeholder="Enter user ID">
                    <small class="text-muted">Find users via <a href="/search">Search</a>.</small>
                </div>

                <div class="mb-3">
                    <label for="amount" class="form-label">Amount (Rs.)</label>
                    <input type="text" class="form-control" id="amount" name="amount"
                           value="<?= e($old['amount']) ?>" required
                           pattern="^\d+(\.\d{1,2})?$" placeholder="0.00"
                           inputmode="decimal">
                </div>

                <div class="mb-4">
                    <label for="comment" class="form-label">Comment <span class="text-muted">(optional)</span></label>
                    <textarea class="form-control" id="comment" name="comment"
                              rows="2" maxlength="500" placeholder="Add a note..."><?= e($old['comment']) ?></textarea>
                </div>

                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-send me-1"></i>Transfer
                </button>
            </form>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../templates/footer.php'; ?>

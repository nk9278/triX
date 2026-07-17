<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/components.php';

require_role(1);
$pdo = getDbConnection();

$error = '';
$success = '';

// Handle forms
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = "Invalid security token.";
    } else {
        $action = $_POST['action'] ?? '';

        if ($action === 'generate') {
            $amount = (float)($_POST['amount'] ?? 0);
            if ($amount <= 0) {
                $error = "Amount must be greater than zero.";
            } else {
                try {
                    $pdo->beginTransaction();

                    // Add to wallet
                    $stmt = $pdo->prepare("UPDATE wallets SET balance = balance + :amount1, total_received = total_received + :amount2 WHERE user_id = :user_id");
                    $stmt->execute(['amount1' => $amount, 'amount2' => $amount, 'user_id' => $_SESSION['user_id']]);

                    // Log transaction
                    $tx_id = generate_transaction_id();
                    $stmt = $pdo->prepare("SELECT balance FROM wallets WHERE user_id = :user_id");
                    $stmt->execute(['user_id' => $_SESSION['user_id']]);
                    $new_balance = $stmt->fetchColumn();

                    $stmt = $pdo->prepare("
                        INSERT INTO wallet_transactions (transaction_id, from_user, to_user, amount, opening_balance, closing_balance, remark)
                        VALUES (:tx_id, :from_user, :to, :amount, :open, :close, 'Coin Generation')
                    ");
                    $stmt->bindValue(':tx_id', $tx_id);
                    $stmt->bindValue(':from_user', null, PDO::PARAM_NULL);
                    $stmt->bindValue(':to', $_SESSION['user_id']);
                    $stmt->bindValue(':amount', $amount);
                    $stmt->bindValue(':open', $new_balance - $amount);
                    $stmt->bindValue(':close', $new_balance);
                    $stmt->execute();

                    log_activity($pdo, $_SESSION['user_id'], 'Coins Generated', "Generated $amount coins");
                    $pdo->commit();
                    $success = "Successfully generated " . number_format($amount, 2) . " coins.";
                } catch (Exception $e) {
                    $pdo->rollBack();
                    $error = "Failed to generate coins: " . $e->getMessage();
                }
            }
        } elseif ($action === 'transfer') {
            $to_user_id = (int)($_POST['to_user_id'] ?? 0);
            $amount = (float)($_POST['amount'] ?? 0);
            $remark = sanitize_input($_POST['remark'] ?? '');

            // Verify recipient is direct child and active
            $stmt = $pdo->prepare("SELECT id, status FROM users WHERE id = :id AND parent_id = :parent_id");
            $stmt->execute(['id' => $to_user_id, 'parent_id' => $_SESSION['user_id']]);
            $recipient = $stmt->fetch();

            if (!$recipient) {
                $error = "Invalid recipient.";
            } elseif ($recipient['status'] !== 'active') {
                $error = "Recipient account is not active.";
            } else {
                $result = transfer_coins($pdo, $_SESSION['user_id'], $to_user_id, $amount, $remark);
                if ($result['success']) {
                    $success = $result['message'];
                } else {
                    $error = $result['message'];
                }
            }
        }
    }
}

// Fetch current wallet
$wallet = get_wallet($pdo, $_SESSION['user_id']);

// Fetch children for transfer dropdown
$stmt = $pdo->prepare("SELECT id, name, username FROM users WHERE parent_id = :parent_id AND status = 'active' ORDER BY name ASC");
$stmt->execute(['parent_id' => $_SESSION['user_id']]);
$children = $stmt->fetchAll();

// Fetch transaction history
$search = $_GET['search'] ?? '';
$period = $_GET['period'] ?? 'all';

$query = "SELECT t.*, u_from.username as from_username, u_to.username as to_username
          FROM wallet_transactions t
          LEFT JOIN users u_from ON t.from_user = u_from.id
          LEFT JOIN users u_to ON t.to_user = u_to.id
          WHERE 1=1";
$params = [];

if (!empty($search)) {
    $query .= " AND (t.transaction_id LIKE :search OR u_from.username LIKE :search OR u_to.username LIKE :search)";
    $params['search'] = "%$search%";
}

if ($period === 'today') {
    $query .= " AND DATE(t.created_at) = CURDATE()";
} elseif ($period === 'yesterday') {
    $query .= " AND DATE(t.created_at) = SUBDATE(CURDATE(), 1)";
} elseif ($period === '7days') {
    $query .= " AND DATE(t.created_at) >= SUBDATE(CURDATE(), 7)";
} elseif ($period === '30days') {
    $query .= " AND DATE(t.created_at) >= SUBDATE(CURDATE(), 30)";
}

$query .= " ORDER BY t.id DESC LIMIT 50";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$transactions = $stmt->fetchAll();

$show_header = true;
$show_bottom_nav = false;
$csrf_token = generate_csrf_token();
require_once __DIR__ . '/../includes/header.php';
?>

<div class="mb-4">
    <h2 class="fw-bold">Wallet</h2>
    <p class="text-secondary">Manage your virtual coins</p>
</div>

<?php if (!empty($error)): ?>
    <div class="alert alert-danger py-2"><?php echo e($error); ?></div>
<?php endif; ?>

<?php if (!empty($success)): ?>
    <div class="alert alert-success py-2"><?php echo e($success); ?></div>
<?php endif; ?>

<?php render_wallet_balance_card($wallet); ?>

<!-- Coin Generation (Super Admin Only) -->
<h6 class="text-secondary mb-3">Generate Coins</h6>
<div class="card p-3 mb-4 border-info border-start border-4 border-0">
    <form method="POST">
        <input type="hidden" name="csrf_token" value="<?php echo e($csrf_token); ?>">
        <input type="hidden" name="action" value="generate">

        <div class="mb-3">
            <label class="form-label text-secondary small">Amount to Generate</label>
            <input type="number" step="0.01" min="1" class="form-control" name="amount" required placeholder="0.00">
        </div>
        <button type="submit" class="btn btn-info w-100 text-white fw-bold">Generate Coins</button>
    </form>
</div>

<!-- Transfer Coins -->
<h6 class="text-secondary mb-3">Transfer Coins</h6>
<div class="card p-3 mb-4">
    <form method="POST">
        <input type="hidden" name="csrf_token" value="<?php echo e($csrf_token); ?>">
        <input type="hidden" name="action" value="transfer">

        <div class="mb-3">
            <label class="form-label text-secondary small">Select Admin</label>
            <select class="form-select" name="to_user_id" required>
                <option value="">-- Choose Admin --</option>
                <?php foreach ($children as $child): ?>
                    <option value="<?php echo $child['id']; ?>"><?php echo e($child['name']); ?> (@<?php echo e($child['username']); ?>)</option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="mb-3">
            <label class="form-label text-secondary small">Amount</label>
            <input type="number" step="0.01" min="1" class="form-control" name="amount" required placeholder="0.00">
        </div>

        <div class="mb-3">
            <label class="form-label text-secondary small">Remark (Optional)</label>
            <input type="text" class="form-control" name="remark" placeholder="Enter remark">
        </div>

        <button type="submit" class="btn btn-primary w-100">Transfer Coins</button>
    </form>
</div>

<!-- Transaction History -->
<h6 class="text-secondary mb-3">Transaction History</h6>
<?php render_transaction_history_filters(); ?>

<?php if (empty($transactions)): ?>
    <div class="card p-4 text-center mb-4">
        <p class="text-secondary mb-0">No transactions found.</p>
    </div>
<?php else: ?>
    <?php foreach ($transactions as $tx): ?>
        <?php render_transaction_card($tx, $_SESSION['user_id']); ?>
    <?php endforeach; ?>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

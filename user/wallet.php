<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/components.php';

require_role(6);
$pdo = getDbConnection();

$error = '';
$success = '';

// Fetch current wallet
$wallet = get_wallet($pdo, $_SESSION['user_id']);

// Fetch transaction history
$search = $_GET['search'] ?? '';
$period = $_GET['period'] ?? 'all';

$query = "SELECT t.*, u_from.username as from_username, u_to.username as to_username
          FROM wallet_transactions t
          LEFT JOIN users u_from ON t.from_user = u_from.id
          LEFT JOIN users u_to ON t.to_user = u_to.id
          WHERE (t.from_user = :user_id1 OR t.to_user = :user_id2)";
$params = ['user_id1' => $_SESSION['user_id'], 'user_id2' => $_SESSION['user_id']];

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
$show_bottom_nav = true;
require_once __DIR__ . '/../includes/header.php';
?>

<div class="mb-4">
    <h2 class="fw-bold">Wallet</h2>
    <p class="text-secondary">View your virtual coins</p>
</div>

<?php render_wallet_balance_card($wallet); ?>

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

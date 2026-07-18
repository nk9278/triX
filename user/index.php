<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/components.php';
require_role(6); // User Role

$pdo = getDbConnection();

// Get Wallet Balance
$stmt = $pdo->prepare("SELECT balance FROM wallets WHERE user_id = :user_id");
$stmt->execute(['user_id' => $_SESSION['user_id']]);
$wallet = $stmt->fetch();
$balance = $wallet ? $wallet['balance'] : 0.00;

// Get Active Bets Count
$stmt = $pdo->prepare("SELECT COUNT(*) FROM bets WHERE user_id = :user_id AND status = 'pending'");
$stmt->execute(['user_id' => $_SESSION['user_id']]);
$active_bets = $stmt->fetchColumn();

// Get Recent Activity
$actStmt = $pdo->prepare("SELECT action, description, created_at FROM activity_logs WHERE user_id = :user_id ORDER BY id DESC LIMIT 5");
$actStmt->execute(['user_id' => $_SESSION['user_id']]);
$activities = $actStmt->fetchAll();

$show_header = true;
$show_bottom_nav = true;
require_once __DIR__ . '/../includes/header.php';
?>

<div class="dashboard-content">
    <?php render_dashboard_card($_SESSION['name'], 'User', $_SESSION['username']); ?>

    <!-- User Stats -->
    <div class="row g-3 mb-4">
        <div class="col-6">
            <div class="card p-3 text-center h-100 border-success border-start border-4 border-0">
                <div class="fs-4 fw-bold text-success">🪙 <?php echo number_format($balance, 2); ?></div>
                <div class="small text-secondary">Wallet Balance</div>
            </div>
        </div>
        <div class="col-6">
            <div class="card p-3 text-center h-100 border-primary border-start border-4 border-0">
                <div class="fs-4 fw-bold text-primary"><?php echo $active_bets; ?></div>
                <div class="small text-secondary">Active Bets</div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <h6 class="text-secondary mb-3">Quick Actions</h6>
    <div class="row g-3 mb-4">
        <div class="col-6">
            <a href="games.php" class="text-decoration-none">
                <div class="card p-3 text-center h-100 bg-primary text-white border-0">
                    <i class="bi bi-controller fs-2 mb-2"></i>
                    <div class="fw-bold">Play Games</div>
                </div>
            </a>
        </div>
        <div class="col-6">
            <a href="bets.php" class="text-decoration-none">
                <div class="card p-3 text-center h-100 bg-info text-white border-0">
                    <i class="bi bi-ticket-detailed fs-2 mb-2"></i>
                    <div class="fw-bold">My Bets</div>
                </div>
            </a>
        </div>
        <div class="col-6">
            <a href="bet_history.php" class="text-decoration-none">
                <div class="card p-3 text-center h-100 bg-secondary text-white border-0">
                    <i class="bi bi-clock-history fs-2 mb-2"></i>
                    <div class="fw-bold">Bet History</div>
                </div>
            </a>
        </div>
        <div class="col-6">
            <a href="wallet.php" class="text-decoration-none">
                <div class="card p-3 text-center h-100 bg-warning text-dark border-0">
                    <i class="bi bi-wallet2 fs-2 mb-2"></i>
                    <div class="fw-bold">Wallet</div>
                </div>
            </a>
        </div>
    </div>

    <!-- Recent Activity -->
    <h6 class="text-secondary mb-3">Recent Activity</h6>
    <?php if (empty($activities)): ?>
        <div class="card p-4 text-center mb-5">
            <p class="text-secondary mb-0">No recent activity.</p>
        </div>
    <?php else: ?>
        <div class="card mb-5">
            <div class="list-group list-group-flush bg-transparent">
                <?php foreach ($activities as $act): ?>
                <div class="list-group-item bg-transparent border-secondary text-white py-3">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="fw-bold"><?php echo e($act['action']); ?></span>
                        <small class="text-secondary"><?php echo date('M d H:i', strtotime($act['created_at'])); ?></small>
                    </div>
                    <div class="small text-secondary"><?php echo e($act['description']); ?></div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

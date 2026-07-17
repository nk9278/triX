<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/components.php';

// Super Admin is Role 1
require_role(6);

// Get Dashboard Stats
$pdo = getDbConnection();
$stats = [
    'total' => 0,
    'active' => 0,
    'inactive' => 0,
    'today' => 0
];

$stmt = $pdo->prepare("SELECT status, created_at FROM users WHERE parent_id = :parent_id");
$stmt->execute(['parent_id' => $_SESSION['user_id']]);
$users = $stmt->fetchAll();

$today = date('Y-m-d');
foreach ($users as $u) {
    $stats['total']++;
    if ($u['status'] === 'active') {
        $stats['active']++;
    } else {
        $stats['inactive']++;
    }

    if (strpos($u['created_at'], $today) === 0) {
        $stats['today']++;
    }
}

// Get Recent Activity
$actStmt = $pdo->prepare("SELECT action, description, created_at FROM activity_logs WHERE user_id IN (SELECT id FROM users WHERE parent_id = :parent_id1) OR user_id = :parent_id2 ORDER BY id DESC LIMIT 10");
$actStmt->execute(['parent_id1' => $_SESSION['user_id'], 'parent_id2' => $_SESSION['user_id']]);
$activities = $actStmt->fetchAll();

$show_header = true;
$show_bottom_nav = true;
require_once __DIR__ . '/../includes/header.php';
?>

<div class="dashboard-content">
    <?php render_dashboard_card($_SESSION['name'], 'User', $_SESSION['username']); ?>

    <!-- Stats Grid -->
    <h6 class="text-secondary mb-3">Overview</h6>
    <div class="row g-3 mb-4">
        <div class="col-6">
            <div class="card p-3 text-center h-100 border-primary border-start border-4 border-0">
                <div class="fs-3 fw-bold text-white"><?php echo $stats['total']; ?></div>
                <div class="small text-secondary">Total Members</div>
            </div>
        </div>
        <div class="col-6">
            <div class="card p-3 text-center h-100 border-success border-start border-4 border-0">
                <div class="fs-3 fw-bold text-success"><?php echo $stats['active']; ?></div>
                <div class="small text-secondary">Active</div>
            </div>
        </div>
        <div class="col-6">
            <div class="card p-3 text-center h-100 border-danger border-start border-4 border-0">
                <div class="fs-3 fw-bold text-danger"><?php echo $stats['inactive']; ?></div>
                <div class="small text-secondary">Inactive</div>
            </div>
        </div>
        <div class="col-6">
            <div class="card p-3 text-center h-100 border-info border-start border-4 border-0">
                <div class="fs-3 fw-bold text-info"><?php echo $stats['today']; ?></div>
                <div class="small text-secondary">New Today</div>
            </div>
        </div>
    </div>

    <!-- Recent Activity -->
    <h6 class="text-secondary mb-3">Recent Activity</h6>
    <?php if (empty($activities)): ?>
        <div class="card p-4 text-center mb-4">
            <p class="text-secondary mb-0">No recent activity.</p>
        </div>
    <?php else: ?>
        <div class="card mb-4">
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

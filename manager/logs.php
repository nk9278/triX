<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/components.php';

require_role(3);

$pdo = getDbConnection();
$stmt = $pdo->prepare("SELECT ip_address, device, login_time, logout_time FROM login_logs WHERE user_id = :user_id ORDER BY id DESC LIMIT 50");
$stmt->execute(['user_id' => $_SESSION['user_id']]);
$logs = $stmt->fetchAll();

$show_header = true;
$show_bottom_nav = false;
require_once __DIR__ . '/../includes/header.php';
?>

<div class="mb-4">
    <h2 class="fw-bold">Login Logs</h2>
    <p class="text-secondary">Your recent login history</p>
</div>

<?php if (empty($logs)): ?>
    <div class="card p-4 text-center">
        <p class="text-secondary mb-0">No login logs found.</p>
    </div>
<?php else: ?>
    <?php foreach ($logs as $log): ?>
        <div class="card p-3 mb-3">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="text-white fw-bold"><i class="bi bi-box-arrow-in-right text-success me-1"></i> Login</span>
                <span class="text-secondary small"><?php echo date('M d, Y H:i', strtotime($log['login_time'])); ?></span>
            </div>

            <div class="text-secondary small mb-1">
                <i class="bi bi-globe me-1"></i> <?php echo e($log['ip_address']); ?>
            </div>

            <div class="text-secondary small text-truncate mb-2" title="<?php echo e($log['device']); ?>">
                <i class="bi bi-display me-1"></i> <?php echo e($log['device']); ?>
            </div>

            <?php if ($log['logout_time']): ?>
            <div class="border-top border-secondary pt-2 mt-2">
                <span class="text-secondary small"><i class="bi bi-box-arrow-left text-danger me-1"></i> Logout: <?php echo date('M d, Y H:i', strtotime($log['logout_time'])); ?></span>
            </div>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

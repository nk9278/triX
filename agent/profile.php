<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/components.php';

require_role(5);

$pdo = getDbConnection();
$stmt = $pdo->prepare("SELECT name, username, created_at FROM users WHERE id = :id");
$stmt->execute(['id' => $_SESSION['user_id']]);
$user = $stmt->fetch();

$show_header = true;
$show_bottom_nav = false;
require_once __DIR__ . '/../includes/header.php';
?>

<div class="mb-4">
    <h2 class="fw-bold">My Profile</h2>
    <p class="text-secondary">Your account details</p>
</div>

<div class="card p-4 text-center mb-4">
    <div class="mb-3">
        <i class="bi bi-person-circle text-primary" style="font-size: 4rem;"></i>
    </div>
    <h3 class="text-white fw-bold mb-1"><?php echo e($user['name']); ?></h3>
    <p class="text-secondary mb-3">@<?php echo e($user['username']); ?></p>
    <span class="badge bg-primary px-3 py-2 rounded-pill fs-6">Agent</span>
</div>

<div class="card p-3">
    <div class="d-flex justify-content-between align-items-center py-2 border-bottom border-secondary">
        <span class="text-secondary">Name</span>
        <span class="text-white fw-bold"><?php echo e($user['name']); ?></span>
    </div>
    <div class="d-flex justify-content-between align-items-center py-2 border-bottom border-secondary">
        <span class="text-secondary">Username</span>
        <span class="text-white fw-bold"><?php echo e($user['username']); ?></span>
    </div>
    <div class="d-flex justify-content-between align-items-center py-2 border-bottom border-secondary">
        <span class="text-secondary">Role</span>
        <span class="text-white fw-bold">Agent</span>
    </div>
    <div class="d-flex justify-content-between align-items-center py-2">
        <span class="text-secondary">Member Since</span>
        <span class="text-white fw-bold"><?php echo date('M d, Y', strtotime($user['created_at'])); ?></span>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

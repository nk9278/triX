<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/components.php';

require_role(3);

$error = '';
$success_msg = '';
$new_pwd = '';
$pdo = getDbConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = "Invalid security token.";
    } else {
        $user_id = (int)($_POST['user_id'] ?? 0);
        $action = $_POST['action'] ?? '';

        // Verify user belongs to this parent
        $stmt = $pdo->prepare("SELECT id, status, username FROM users WHERE id = :id AND parent_id = :parent_id");
        $stmt->execute(['id' => $user_id, 'parent_id' => $_SESSION['user_id']]);
        $target_user = $stmt->fetch();

        if ($target_user) {
            if ($action === 'toggle_status') {
                $new_status = $target_user['status'] === 'active' ? 'inactive' : 'active';
                $update = $pdo->prepare("UPDATE users SET status = :status WHERE id = :id");
                $update->execute(['status' => $new_status, 'id' => $user_id]);
                $success_msg = "User status updated to " . ucfirst($new_status) . ".";
            } elseif ($action === 'reset_password') {
                $new_pwd = generate_password();
                $hashed_password = password_hash($new_pwd, PASSWORD_DEFAULT);
                $update = $pdo->prepare("UPDATE users SET password = :password WHERE id = :id");
                $update->execute(['password' => $hashed_password, 'id' => $user_id]);
                $success_msg = "Password reset for " . $target_user['username'];
            }
        } else {
            $error = "User not found or unauthorized.";
        }
    }
}

// Manager sees Admins (Role 2)
$child_role_id = 4;
$child_role_name = 'Super Agents';

$stmt = $pdo->prepare("SELECT id, name, username, status, created_at FROM users WHERE parent_id = :parent_id AND role_id = :role_id ORDER BY id DESC");
$stmt->execute(['parent_id' => $_SESSION['user_id'], 'role_id' => $child_role_id]);
$users = $stmt->fetchAll();

$show_header = true;
$show_bottom_nav = false;
// Generate token for actions
generate_csrf_token();
require_once __DIR__ . '/../includes/header.php';
?>

<div class="mb-4">
    <h2 class="fw-bold"><?php echo e($child_role_name); ?></h2>
    <p class="text-secondary">Manage your <?php echo strtolower(e($child_role_name)); ?></p>
</div>

<?php if (!empty($error)): ?>
    <div class="alert alert-danger py-2"><?php echo e($error); ?></div>
<?php endif; ?>

<?php if (!empty($success_msg)): ?>
    <div class="alert alert-success py-2"><?php echo e($success_msg); ?></div>
<?php endif; ?>

<?php if (!empty($new_pwd)): ?>
    <div class="card p-3 border-warning mb-4">
        <h5 class="text-warning mb-3"><i class="bi bi-key"></i> New Password Generated</h5>
        <h3 class="fw-bold text-white text-center mb-3 letter-spacing-2"><?php echo e($new_pwd); ?></h3>
        <button class="btn btn-warning w-100 copy-btn" data-copy="<?php echo e($new_pwd); ?>">
            <i class="bi bi-copy"></i> Copy Password
        </button>
    </div>
<?php endif; ?>

<?php if (empty($users)): ?>
    <div class="card p-4 text-center">
        <p class="text-secondary mb-0">No users found.</p>
    </div>
<?php else: ?>
    <?php foreach ($users as $user): ?>
        <?php render_user_card($user, true); ?>
    <?php endforeach; ?>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

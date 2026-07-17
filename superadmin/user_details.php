<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/components.php';

require_role(1);

$pdo = getDbConnection();
$user_id = (int)($_GET['id'] ?? 0);
$error = '';
$success = '';

// Verify this user belongs to the current parent
$stmt = $pdo->prepare("
    SELECT u.*, p.name as parent_name,
           (SELECT login_time FROM login_logs WHERE user_id = u.id ORDER BY id DESC LIMIT 1) as last_login
    FROM users u
    LEFT JOIN users p ON u.parent_id = p.id
    WHERE u.id = :id AND u.parent_id = :parent_id
");
$stmt->execute(['id' => $user_id, 'parent_id' => $_SESSION['user_id']]);
$user = $stmt->fetch();

if (!$user) {
    header("Location: users.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = "Invalid security token.";
    } else {
        $name = sanitize_input($_POST['name'] ?? '');
        if (empty($name)) {
            $error = "Name cannot be empty.";
        } else {
            $update = $pdo->prepare("UPDATE users SET name = :name WHERE id = :id");
            $update->execute(['name' => $name, 'id' => $user_id]);
            log_activity($pdo, $_SESSION['user_id'], 'Edit User', 'Updated name for ' . $user['username']);
            $success = "User details updated successfully.";
            $user['name'] = $name; // Update local variable for display
        }
    }
}

$show_header = true;
$show_bottom_nav = false;
$csrf_token = generate_csrf_token();
require_once __DIR__ . '/../includes/header.php';
?>

<div class="mb-4 d-flex justify-content-between align-items-center">
    <div>
        <h2 class="fw-bold">User Details</h2>
        <p class="text-secondary">Viewing <?php echo e($user['username']); ?></p>
    </div>
    <a href="users.php" class="btn btn-outline-light btn-sm"><i class="bi bi-arrow-left"></i> Back</a>
</div>

<?php if (!empty($error)): ?>
    <div class="alert alert-danger py-2"><?php echo e($error); ?></div>
<?php endif; ?>

<?php if (!empty($success)): ?>
    <div class="alert alert-success py-2"><?php echo e($success); ?></div>
<?php endif; ?>

<!-- User Info Card -->
<div class="card p-3 mb-4">
    <div class="d-flex justify-content-between align-items-start mb-3">
        <div>
            <h5 class="mb-1 text-white fw-bold"><?php echo e($user['name']); ?></h5>
            <div class="text-secondary">@<?php echo e($user['username']); ?></div>
        </div>
        <span class="badge <?php echo $user['status'] === 'active' ? 'bg-success' : 'bg-danger'; ?> rounded-pill">
            <?php echo ucfirst(e($user['status'])); ?>
        </span>
    </div>

    <div class="border-top border-secondary pt-3 mt-2">
        <div class="row g-3">
            <div class="col-6">
                <small class="text-secondary d-block">Role</small>
                <span class="text-white fw-bold"><?php echo get_role_name($user['role_id']); ?></span>
            </div>
            <div class="col-6">
                <small class="text-secondary d-block">Parent Account</small>
                <span class="text-white fw-bold"><?php echo e($user['parent_name'] ?? 'System'); ?></span>
            </div>
            <div class="col-6">
                <small class="text-secondary d-block">Created Date</small>
                <span class="text-white fw-bold"><?php echo date('M d, Y', strtotime($user['created_at'])); ?></span>
            </div>
            <div class="col-6">
                <small class="text-secondary d-block">Last Login</small>
                <span class="text-white fw-bold"><?php echo $user['last_login'] ? date('M d, Y', strtotime($user['last_login'])) : 'Never'; ?></span>
            </div>
        </div>
    </div>
</div>

<!-- Edit Form -->
<h6 class="text-secondary mb-3">Edit Details</h6>
<div class="card p-3 mb-4">
    <form method="POST">
        <input type="hidden" name="csrf_token" value="<?php echo e($csrf_token); ?>">

        <div class="mb-3">
            <label for="name" class="form-label text-secondary small">Name</label>
            <input type="text" class="form-control" id="name" name="name" value="<?php echo e($user['name']); ?>" required>
        </div>

        <div class="mb-3">
            <label class="form-label text-secondary small">Username (Cannot be changed)</label>
            <input type="text" class="form-control" value="<?php echo e($user['username']); ?>" disabled readonly>
        </div>

        <div class="mb-4">
            <label class="form-label text-secondary small">Role (Cannot be changed)</label>
            <input type="text" class="form-control" value="<?php echo get_role_name($user['role_id']); ?>" disabled readonly>
        </div>

        <button type="submit" class="btn btn-primary w-100">Save Changes</button>
    </form>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

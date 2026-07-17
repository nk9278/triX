<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/components.php';

require_role(1);

$pdo = getDbConnection();
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = "Invalid security token.";
    } else {
        $name = sanitize_input($_POST['name'] ?? '');
        if (empty($name)) {
            $error = "Name cannot be empty.";
        } else {
            $update = $pdo->prepare("UPDATE users SET name = :name WHERE id = :id");
            $update->execute(['name' => $name, 'id' => $_SESSION['user_id']]);
            $_SESSION['name'] = $name; // Update session
            log_activity($pdo, $_SESSION['user_id'], 'Profile Update', 'Updated own profile name');
            $success = "Profile updated successfully.";
        }
    }
}

$stmt = $pdo->prepare("SELECT name, username, created_at FROM users WHERE id = :id");
$stmt->execute(['id' => $_SESSION['user_id']]);
$user = $stmt->fetch();

$show_header = true;
$show_bottom_nav = false;
$csrf_token = generate_csrf_token();
require_once __DIR__ . '/../includes/header.php';
?>

<div class="mb-4">
    <h2 class="fw-bold">My Profile</h2>
    <p class="text-secondary">Your account details</p>
</div>

<?php if (!empty($error)): ?>
    <div class="alert alert-danger py-2"><?php echo e($error); ?></div>
<?php endif; ?>

<?php if (!empty($success)): ?>
    <div class="alert alert-success py-2"><?php echo e($success); ?></div>
<?php endif; ?>

<div class="card p-4 text-center mb-4">
    <div class="mb-3">
        <i class="bi bi-person-circle text-primary" style="font-size: 4rem;"></i>
    </div>
    <h3 class="text-white fw-bold mb-1"><?php echo e($user['name']); ?></h3>
    <p class="text-secondary mb-3">@<?php echo e($user['username']); ?></p>
    <span class="badge bg-primary px-3 py-2 rounded-pill fs-6">Super Admin</span>
</div>

<div class="card p-3 mb-4">
    <div class="d-flex justify-content-between align-items-center py-2 border-bottom border-secondary">
        <span class="text-secondary">Username</span>
        <span class="text-white fw-bold"><?php echo e($user['username']); ?></span>
    </div>
    <div class="d-flex justify-content-between align-items-center py-2 border-bottom border-secondary">
        <span class="text-secondary">Role</span>
        <span class="text-white fw-bold">Super Admin</span>
    </div>
    <div class="d-flex justify-content-between align-items-center py-2">
        <span class="text-secondary">Member Since</span>
        <span class="text-white fw-bold"><?php echo date('M d, Y', strtotime($user['created_at'])); ?></span>
    </div>
</div>

<!-- Edit Profile -->
<h6 class="text-secondary mb-3">Edit Profile</h6>
<div class="card p-3 mb-4">
    <form method="POST">
        <input type="hidden" name="csrf_token" value="<?php echo e($csrf_token); ?>">

        <div class="mb-3">
            <label for="name" class="form-label text-secondary small">Name</label>
            <input type="text" class="form-control" id="name" name="name" value="<?php echo e($user['name']); ?>" required>
        </div>

        <button type="submit" class="btn btn-primary w-100">Update Profile</button>
    </form>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

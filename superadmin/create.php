<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/components.php';

require_role(1);

$error = '';
$success = false;
$new_username = '';
$new_password = '';
$new_name = '';

// Super Admin creates Admin (Role 2)
$child_role_id = 2;
$child_role_name = 'Admin';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = "Invalid security token.";
    } else {
        $name = sanitize_input($_POST['name'] ?? '');
        if (empty($name)) {
            $error = "Name is required.";
        } else {
            $pdo = getDbConnection();
            $new_username = generate_username($pdo);
            $new_password = generate_password();
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $new_name = $name;

            $stmt = $pdo->prepare("INSERT INTO users (parent_id, role_id, name, username, password) VALUES (:parent_id, :role_id, :name, :username, :password)");
            if ($stmt->execute([
                'parent_id' => $_SESSION['user_id'],
                'role_id' => $child_role_id,
                'name' => $name,
                'username' => $new_username,
                'password' => $hashed_password
            ])) {
                $success = true;
                log_activity($pdo, $_SESSION['user_id'], 'Account Created', 'Created new ' . $child_role_name . ' account: ' . $new_username);
            } else {
                $error = "Failed to create account.";
            }
        }
    }
}

$csrf_token = generate_csrf_token();
$show_header = true;
$show_bottom_nav = false;
require_once __DIR__ . '/../includes/header.php';
?>

<div class="mb-4">
    <h2 class="fw-bold">Create <?php echo e($child_role_name); ?></h2>
    <p class="text-secondary">Add a new <?php echo e($child_role_name); ?> to the system.</p>
</div>

<?php if ($success): ?>
    <div class="card p-4 text-center border-success mb-4">
        <i class="bi bi-check-circle-fill text-success" style="font-size: 3rem;"></i>
        <h4 class="mt-3 text-white">Account Created Successfully</h4>
        <div class="mt-4 bg-dark-subtle p-3 rounded text-start">
            <p class="mb-1 text-secondary">Name: <span class="text-white fw-bold"><?php echo e($new_name); ?></span></p>
            <p class="mb-1 text-secondary">Username: <span class="text-white fw-bold"><?php echo e($new_username); ?></span></p>
            <p class="mb-0 text-secondary">Password: <span class="text-white fw-bold"><?php echo e($new_password); ?></span></p>
        </div>
        <div class="mt-4 d-grid gap-2">
            <button class="btn btn-primary copy-btn" data-copy="Username: <?php echo e($new_username); ?>&#10;Password: <?php echo e($new_password); ?>">
                <i class="bi bi-copy"></i> Copy Credentials
            </button>
            <a href="create.php" class="btn btn-outline-light">Close</a>
        </div>
    </div>
<?php else: ?>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger"><?php echo e($error); ?></div>
    <?php endif; ?>

    <div class="card p-4">
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo e($csrf_token); ?>">
            <div class="mb-4">
                <label for="name" class="form-label">Name</label>
                <input type="text" class="form-control form-control-lg" id="name" name="name" required placeholder="Enter full name">
            </div>
            <div class="d-grid">
                <button type="submit" class="btn btn-primary btn-lg">Create <?php echo e($child_role_name); ?></button>
            </div>
        </form>
    </div>

<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

<?php
require_once __DIR__ . '/includes/auth.php';

// Redirect if already logged in
if (is_logged_in()) {
    redirect_based_on_role();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = "Invalid security token. Please try again.";
    } else {
        $username = sanitize_input($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($username) || empty($password)) {
            $error = "Please enter username and password.";
        } else {
            $result = login_user($username, $password);
            if ($result['success']) {
                redirect_based_on_role();
            } else {
                $error = $result['message'];
            }
        }
    }
}

// Generate CSRF token for the form
$csrf_token = generate_csrf_token();
$show_header = false;
$show_bottom_nav = false;

require_once __DIR__ . '/includes/header.php';
?>

<div class="d-flex flex-column justify-content-center min-vh-100 p-3">
    <div class="text-center mb-5">
        <h1 class="display-4 fw-bold text-white mb-2">TriX</h1>
        <p class="text-secondary">Gaming Platform</p>
    </div>

    <div class="card p-4">
        <h4 class="card-title text-center mb-4">Login</h4>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger py-2" role="alert">
                <?php echo e($error); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['error']) && $_GET['error'] === 'timeout'): ?>
            <div class="alert alert-warning py-2" role="alert">
                Session expired. Please login again.
            </div>
        <?php endif; ?>

        <form method="POST" action="login.php">
            <input type="hidden" name="csrf_token" value="<?php echo e($csrf_token); ?>">

            <div class="mb-3">
                <label for="username" class="form-label">Username</label>
                <input type="text" class="form-control form-control-lg" id="username" name="username" required autocomplete="username">
            </div>

            <div class="mb-4">
                <label for="password" class="form-label">Password</label>
                <input type="password" class="form-control form-control-lg" id="password" name="password" required autocomplete="current-password">
            </div>

            <div class="d-grid">
                <button type="submit" class="btn btn-primary btn-lg">Login</button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

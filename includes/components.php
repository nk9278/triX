<?php
/**
 * Shared UI Components
 */

function render_dashboard_card($name, $role_name, $username) {
    ?>
    <div class="mb-4">
        <h2 class="fw-bold">Welcome, <?php echo e($name); ?></h2>
        <p class="text-secondary">Dashboard Overview</p>
    </div>

    <!-- Main Dashboard Card -->
    <div class="card p-4 mb-4">
        <div class="d-flex align-items-center justify-content-between mb-3">
            <h5 class="m-0 text-white">Role</h5>
            <i class="bi bi-person-badge text-primary fs-4"></i>
        </div>
        <h2 class="display-6 fw-bold m-0 text-white"><?php echo e($role_name); ?></h2>
        <div class="mt-2 text-secondary">
            <i class="bi bi-at"></i> <?php echo e($username); ?>
        </div>
        <div class="mt-2 text-secondary small">
            <i class="bi bi-calendar-check"></i> <?php echo date('Y-m-d'); ?>
        </div>
    </div>
    <?php
}

function render_user_card($user, $can_reset_password = false) {
    ?>
    <div class="card p-3 mb-3">
        <div class="d-flex justify-content-between align-items-start mb-2">
            <div>
                <h6 class="mb-0 text-white fw-bold"><?php echo e($user['name']); ?></h6>
                <div class="text-secondary small">@<?php echo e($user['username']); ?> | <?php echo get_role_name($user['role_id'] ?? 0); ?></div>
            </div>
            <span class="badge <?php echo $user['status'] === 'active' ? 'bg-success' : 'bg-danger'; ?> rounded-pill">
                <?php echo ucfirst(e($user['status'])); ?>
            </span>
        </div>

        <div class="text-secondary small mb-1">
            <i class="bi bi-clock"></i> Created: <?php echo date('M d, Y', strtotime($user['created_at'])); ?>
        </div>
        <div class="text-secondary small mb-3">
            <i class="bi bi-box-arrow-in-right"></i> Last Login: <?php echo isset($user['last_login']) && $user['last_login'] ? date('M d, Y H:i', strtotime($user['last_login'])) : 'Never'; ?>
        </div>

        <div class="d-flex gap-2 mt-2 pt-2 border-top border-secondary">
            <form method="POST" class="m-0 p-0 flex-grow-1">
                <input type="hidden" name="csrf_token" value="<?php echo e($_SESSION['csrf_token'] ?? ''); ?>">
                <input type="hidden" name="user_id" value="<?php echo (int)$user['id']; ?>">
                <input type="hidden" name="action" value="toggle_status">
                <button type="submit" class="btn btn-sm btn-outline-light w-100">
                    <?php echo $user['status'] === 'active' ? 'Deactivate' : 'Activate'; ?>
                </button>
            </form>

            <?php if($can_reset_password): ?>
            <form method="POST" class="m-0 p-0 flex-grow-1">
                <input type="hidden" name="csrf_token" value="<?php echo e($_SESSION['csrf_token'] ?? ''); ?>">
                <input type="hidden" name="user_id" value="<?php echo (int)$user['id']; ?>">
                <input type="hidden" name="action" value="reset_password">
                <button type="submit" class="btn btn-sm btn-outline-warning w-100" onclick="return confirm('Are you sure you want to reset password?');">
                    Reset PWD
                </button>
            </form>
            <?php endif; ?>

            <a href="user_details.php?id=<?php echo (int)$user['id']; ?>" class="btn btn-sm btn-primary flex-grow-1">
                Details
            </a>
        </div>
    </div>
    <?php
}

function render_search_and_filter() {
    $search = $_GET['search'] ?? '';
    $status = $_GET['status'] ?? 'all';
    ?>
    <div class="card p-3 mb-4">
        <form method="GET" action="users.php" class="row g-2">
            <div class="col-12">
                <input type="text" name="search" class="form-control" placeholder="Search Name or Username" value="<?php echo e($search); ?>">
            </div>
            <div class="col-8">
                <select name="status" class="form-control">
                    <option value="all" <?php echo $status === 'all' ? 'selected' : ''; ?>>All Status</option>
                    <option value="active" <?php echo $status === 'active' ? 'selected' : ''; ?>>Active Only</option>
                    <option value="inactive" <?php echo $status === 'inactive' ? 'selected' : ''; ?>>Inactive Only</option>
                </select>
            </div>
            <div class="col-4">
                <button type="submit" class="btn btn-primary w-100">Filter</button>
            </div>
        </form>
    </div>
    <?php
}

function get_role_name($role_id) {
    switch ($role_id) {
        case 1: return 'Super Admin';
        case 2: return 'Admin';
        case 3: return 'Manager';
        case 4: return 'Super Agent';
        case 5: return 'Agent';
        case 6: return 'User';
        default: return 'Unknown';
    }
}

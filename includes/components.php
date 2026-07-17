<?php
/**
 * Shared UI Components
 */

function render_dashboard_card($name, $role_name, $username, $wallet = null) {
    ?>
    <div class="mb-4">
        <h2 class="fw-bold">Welcome, <?php echo e($name); ?></h2>
        <p class="text-secondary">Dashboard Overview</p>
    </div>

    <?php if ($wallet): ?>
    <!-- Wallet Card -->
    <div class="card p-4 mb-4 bg-primary text-white border-0">
        <div class="d-flex align-items-center justify-content-between mb-3">
            <h6 class="m-0 text-white-50">Available Balance</h6>
            <i class="bi bi-wallet2 fs-4"></i>
        </div>
        <h2 class="display-6 fw-bold m-0"><?php echo number_format($wallet['balance'], 2); ?> <span class="fs-6 fw-normal text-white-50">Coins</span></h2>
    </div>
    <?php endif; ?>

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

function render_wallet_balance_card($wallet) {
    ?>
    <div class="card p-4 mb-4 border-primary border-start border-4 border-0">
        <h6 class="text-secondary mb-3">Available Balance</h6>
        <div class="d-flex align-items-center mb-4">
            <i class="bi bi-wallet2 text-primary me-3" style="font-size: 2.5rem;"></i>
            <h1 class="display-5 fw-bold text-white mb-0"><?php echo number_format($wallet['balance'], 2); ?></h1>
        </div>
        <div class="row g-2">
            <div class="col-6">
                <div class="bg-dark-subtle p-2 rounded text-center">
                    <small class="text-secondary d-block">Received</small>
                    <span class="text-success fw-bold"><?php echo number_format($wallet['total_received'], 2); ?></span>
                </div>
            </div>
            <div class="col-6">
                <div class="bg-dark-subtle p-2 rounded text-center">
                    <small class="text-secondary d-block">Sent</small>
                    <span class="text-danger fw-bold"><?php echo number_format($wallet['total_sent'], 2); ?></span>
                </div>
            </div>
        </div>
    </div>
    <?php
}

function render_transaction_history_filters() {
    $search = $_GET['search'] ?? '';
    $period = $_GET['period'] ?? 'all';
    ?>
    <div class="card p-3 mb-4">
        <form method="GET" class="row g-2">
            <div class="col-12">
                <input type="text" name="search" class="form-control" placeholder="Search Tx ID or Username" value="<?php echo e($search); ?>">
            </div>
            <div class="col-8">
                <select name="period" class="form-control">
                    <option value="all" <?php echo $period === 'all' ? 'selected' : ''; ?>>All Time</option>
                    <option value="today" <?php echo $period === 'today' ? 'selected' : ''; ?>>Today</option>
                    <option value="yesterday" <?php echo $period === 'yesterday' ? 'selected' : ''; ?>>Yesterday</option>
                    <option value="7days" <?php echo $period === '7days' ? 'selected' : ''; ?>>Last 7 Days</option>
                    <option value="30days" <?php echo $period === '30days' ? 'selected' : ''; ?>>Last 30 Days</option>
                </select>
            </div>
            <div class="col-4">
                <button type="submit" class="btn btn-primary w-100">Filter</button>
            </div>
        </form>
    </div>
    <?php
}

function render_transaction_card($tx, $current_user_id) {
    $is_sender = ($tx['from_user'] == $current_user_id);
    $is_generation = ($tx['from_user'] === null);

    $icon = $is_sender ? 'bi-arrow-up-right-circle text-danger' : 'bi-arrow-down-left-circle text-success';
    $amount_class = $is_sender ? 'text-danger' : 'text-success';
    $amount_prefix = $is_sender ? '-' : '+';

    if ($is_generation) {
        $icon = 'bi-plus-circle text-primary';
        $amount_class = 'text-primary';
        $amount_prefix = '+';
    }

    $other_party = $is_sender ? $tx['to_username'] : ($is_generation ? 'System' : $tx['from_username']);
    ?>
    <div class="card p-3 mb-3">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <div class="d-flex align-items-center gap-2">
                <i class="bi <?php echo $icon; ?> fs-4"></i>
                <div>
                    <div class="text-white fw-bold"><?php echo $is_sender ? 'Sent to' : 'Received from'; ?></div>
                    <div class="text-secondary small">@<?php echo e($other_party); ?></div>
                </div>
            </div>
            <div class="text-end">
                <div class="fw-bold fs-5 <?php echo $amount_class; ?>">
                    <?php echo $amount_prefix . number_format($tx['amount'], 2); ?>
                </div>
                <div class="text-secondary small"><?php echo e($tx['transaction_id']); ?></div>
            </div>
        </div>
        <div class="d-flex justify-content-between align-items-end mt-2 pt-2 border-top border-secondary">
            <div class="text-secondary small">
                <?php echo date('M d, Y H:i', strtotime($tx['created_at'])); ?>
            </div>
            <?php if (!empty($tx['remark'])): ?>
                <div class="text-secondary small fst-italic">"<?php echo e($tx['remark']); ?>"</div>
            <?php endif; ?>
        </div>
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

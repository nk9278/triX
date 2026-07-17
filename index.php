<?php
require_once __DIR__ . '/includes/auth.php';

// Require user to be logged in
require_login();

$show_header = true;
$show_bottom_nav = true;

require_once __DIR__ . '/includes/header.php';
?>

<div class="dashboard-content">
    <div class="mb-4">
        <h2 class="fw-bold">Welcome, <?php echo e($_SESSION['name']); ?></h2>
        <p class="text-secondary">Dashboard Overview</p>
    </div>

    <!-- Main Dashboard Card -->
    <div class="card p-4 mb-4">
        <div class="d-flex align-items-center justify-content-between mb-3">
            <h5 class="m-0 text-white">Wallet Balance</h5>
            <i class="bi bi-wallet2 text-primary fs-4"></i>
        </div>
        <h2 class="display-6 fw-bold m-0 text-white">0.00 <span class="fs-6 text-secondary fw-normal">Coins</span></h2>
    </div>

    <!-- Quick Actions -->
    <h6 class="text-secondary mb-3">Quick Actions</h6>
    <div class="row g-3 mb-4">
        <div class="col-6">
            <div class="card p-3 text-center h-100">
                <i class="bi bi-person-plus text-primary fs-2 mb-2"></i>
                <div class="small fw-bold">Add User</div>
            </div>
        </div>
        <div class="col-6">
            <div class="card p-3 text-center h-100">
                <i class="bi bi-cash-stack text-success fs-2 mb-2"></i>
                <div class="small fw-bold">Transfer</div>
            </div>
        </div>
        <div class="col-6">
            <div class="card p-3 text-center h-100">
                <i class="bi bi-graph-up text-warning fs-2 mb-2"></i>
                <div class="small fw-bold">Reports</div>
            </div>
        </div>
        <div class="col-6">
            <div class="card p-3 text-center h-100">
                <i class="bi bi-gear text-secondary fs-2 mb-2"></i>
                <div class="small fw-bold">Settings</div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

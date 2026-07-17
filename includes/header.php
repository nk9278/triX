<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>TriX</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="/assets/css/style.css" rel="stylesheet">
</head>
<body>

<div class="app-container">

    <?php if(isset($show_header) && $show_header): ?>
    <!-- Fixed Mobile Header -->
    <header class="mobile-header fixed-top">
        <div class="d-flex justify-content-between align-items-center w-100 px-3">
            <button type="button" class="btn btn-link text-white p-0 fs-3" id="menu-toggle">
                <i class="bi bi-list"></i>
            </button>
            <div class="brand-logo fw-bold fs-4">TriX</div>
            <div class="d-flex align-items-center gap-3">
                <a href="notifications.php" class="text-white"><i class="bi bi-bell fs-5"></i></a>
                <a href="profile.php" class="text-white"><i class="bi bi-person-circle fs-5"></i></a>
            </div>
        </div>
    </header>

    <!-- Full Screen Mobile Menu Overlay -->
    <div id="mobile-menu-overlay" class="menu-overlay">
        <div class="menu-content">
            <div class="d-flex justify-content-between align-items-center p-3 border-bottom border-secondary">
                <div class="fw-bold fs-4 text-white">TriX</div>
                <button type="button" class="btn btn-link text-white p-0 fs-3" id="menu-close">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>
            <div class="menu-items p-3">
                <div class="mb-4 text-center">
                    <div class="fs-5 text-white mb-1"><?php echo htmlspecialchars($_SESSION['name'] ?? 'User'); ?></div>
                    <div class="text-muted small">@<?php echo htmlspecialchars($_SESSION['username'] ?? ''); ?></div>
                </div>
                <ul class="nav flex-column">
                    <li class="nav-item mb-2">
                        <a class="nav-link text-white rounded p-3 bg-dark-subtle" href="/index.php"><i class="bi bi-house-door me-2"></i> Dashboard</a>
                    </li>
                    <li class="nav-item mb-2">
                        <a class="nav-link text-white rounded p-3 bg-dark-subtle" href="profile.php"><i class="bi bi-person me-2"></i> Profile</a>
                    </li>
                    <?php if (isset($_SESSION['role_id']) && $_SESSION['role_id'] < 6): ?>
                    <li class="nav-item mb-2">
                        <a class="nav-link text-white rounded p-3 bg-dark-subtle" href="users.php"><i class="bi bi-people me-2"></i> Users</a>
                    </li>
                    <li class="nav-item mb-2">
                        <a class="nav-link text-white rounded p-3 bg-dark-subtle" href="create.php"><i class="bi bi-person-plus me-2"></i> Create</a>
                    </li>
                    <?php endif; ?>
                    <li class="nav-item mb-2">
                        <a class="nav-link text-white rounded p-3 bg-dark-subtle" href="logs.php"><i class="bi bi-clock-history me-2"></i> Login Logs</a>
                    </li>
                    <?php if (isset($_SESSION['role_id']) && $_SESSION['role_id'] < 6): ?>
                    <li class="nav-item mb-2">
                        <a class="nav-link text-white rounded p-3 bg-dark-subtle" href="settings.php"><i class="bi bi-gear me-2"></i> Settings</a>
                    </li>
                    <?php endif; ?>
                    <li class="nav-item mt-5">
                        <a class="nav-link text-danger rounded p-3 bg-dark-subtle" href="/logout.php"><i class="bi bi-box-arrow-right me-2"></i> Logout</a>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Add padding to prevent content from hiding under the fixed header -->
    <div class="content-wrapper pt-5 mt-3 pb-5 mb-5 px-3">
    <?php else: ?>
    <!-- Content wrapper without header -->
    <div class="content-wrapper p-3">
    <?php endif; ?>

<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/components.php';

// Super Admin is Role 1
require_role(1);

$show_header = true;
$show_bottom_nav = false;

require_once __DIR__ . '/../includes/header.php';
?>

<div class="dashboard-content">
    <?php render_dashboard_card($_SESSION['name'], 'Super Admin', $_SESSION['username']); ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

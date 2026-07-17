<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/components.php';

// Super Agent is Role 1
require_role(4);

$show_header = true;
$show_bottom_nav = false;

require_once __DIR__ . '/../includes/header.php';
?>

<div class="dashboard-content">
    <?php render_dashboard_card($_SESSION['name'], 'Super Agent', $_SESSION['username']); ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

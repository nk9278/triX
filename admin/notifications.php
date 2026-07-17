<?php
require_once __DIR__ . '/../includes/auth.php';

require_login(); // Any role can access notifications

$show_header = true;
$show_bottom_nav = ($_SESSION['role_id'] == 6);
require_once __DIR__ . '/../includes/header.php';
?>

<div class="mb-4">
    <h2 class="fw-bold">Notifications</h2>
    <p class="text-secondary">Your recent alerts</p>
</div>

<div class="card p-5 text-center">
    <i class="bi bi-bell-slash text-secondary mb-3" style="font-size: 3rem;"></i>
    <p class="text-secondary mb-0">No Notifications Available</p>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

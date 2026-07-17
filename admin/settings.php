<?php
require_once __DIR__ . '/../includes/auth.php';

require_role(2);

$show_header = true;
$show_bottom_nav = false;
require_once __DIR__ . '/../includes/header.php';
?>

<div class="mb-4">
    <h2 class="fw-bold">Settings</h2>
    <p class="text-secondary">System configuration</p>
</div>

<div class="card p-3 mb-4">
    <form>
        <div class="mb-3">
            <label class="form-label text-secondary small">Site Name</label>
            <input type="text" class="form-control" value="TriX" disabled>
        </div>
        <div class="mb-3">
            <label class="form-label text-secondary small">Logo URL</label>
            <input type="text" class="form-control" placeholder="Logo path..." disabled>
        </div>
        <div class="mb-4">
            <label class="form-label text-secondary small">Timezone</label>
            <input type="text" class="form-control" value="UTC" disabled>
        </div>
        <button type="button" class="btn btn-primary w-100" disabled>Save Settings</button>
        <small class="text-secondary d-block mt-2 text-center">Settings configuration is disabled in this phase.</small>
    </form>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

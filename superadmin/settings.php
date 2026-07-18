<?php
require_once '../includes/auth.php';
require_role(1);
require_once '../includes/header.php';
require_once '../config/database.php';

$pdo = getDbConnection();

// Fetch current settings
$stmt = $pdo->query("SELECT * FROM settings LIMIT 1");
$settings = $stmt->fetch();

if (!$settings) {
    // Insert defaults if missing
    $pdo->query("INSERT INTO settings (site_name) VALUES ('TriX')");
    $stmt = $pdo->query("SELECT * FROM settings LIMIT 1");
    $settings = $stmt->fetch();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        $error = "Invalid CSRF token.";
    } else {
        $site_name = trim($_POST['site_name'] ?? 'TriX');
        $timezone = trim($_POST['timezone'] ?? 'UTC');
        $coin_name = trim($_POST['coin_name'] ?? 'Coins');
        $min_bet = (float)($_POST['min_bet'] ?? 10.00);
        $max_bet = (float)($_POST['max_bet'] ?? 10000.00);
        $default_bet = (float)($_POST['default_bet'] ?? 100.00);
        $default_multiplier = (float)($_POST['default_multiplier'] ?? 2.00);
        $default_wallet_balance = (float)($_POST['default_wallet_balance'] ?? 0.00);
        $maintenance_mode = isset($_POST['maintenance_mode']) ? 1 : 0;

        $stmt = $pdo->prepare("
            UPDATE settings
            SET site_name = ?, timezone = ?, coin_name = ?, min_bet = ?, max_bet = ?,
                default_bet = ?, default_multiplier = ?, default_wallet_balance = ?, maintenance_mode = ?
        ");

        if ($stmt->execute([
            $site_name, $timezone, $coin_name, $min_bet, $max_bet,
            $default_bet, $default_multiplier, $default_wallet_balance, $maintenance_mode
        ])) {
            log_activity($pdo, $_SESSION['user_id'], 'Settings Updated', "Updated general system settings");
            $success = "Settings updated successfully.";

            // Refresh settings
            $stmt = $pdo->query("SELECT * FROM settings LIMIT 1");
            $settings = $stmt->fetch();
        } else {
            $error = "Failed to update settings.";
        }
    }
}
?>

<div class="container-fluid mt-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4>General Settings</h4>
        <a href="api_settings.php" class="btn btn-info btn-sm">API Settings</a>
    </div>

    <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    <?php if (isset($success)): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>

    <div class="card mt-3 mb-5">
        <div class="card-body">
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">

                <h6 class="text-secondary mb-3">General</h6>
                <div class="mb-3">
                    <label class="form-label">Website Name</label>
                    <input type="text" class="form-control" name="site_name" value="<?php echo htmlspecialchars($settings['site_name']); ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Timezone</label>
                    <input type="text" class="form-control" name="timezone" value="<?php echo htmlspecialchars($settings['timezone']); ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Coin Name</label>
                    <input type="text" class="form-control" name="coin_name" value="<?php echo htmlspecialchars($settings['coin_name']); ?>" required>
                </div>

                <h6 class="text-secondary mt-4 mb-3">Game & Betting</h6>
                <div class="row">
                    <div class="col-6 mb-3">
                        <label class="form-label">Minimum Bet</label>
                        <input type="number" step="0.01" class="form-control" name="min_bet" value="<?php echo htmlspecialchars($settings['min_bet']); ?>" required>
                    </div>
                    <div class="col-6 mb-3">
                        <label class="form-label">Maximum Bet</label>
                        <input type="number" step="0.01" class="form-control" name="max_bet" value="<?php echo htmlspecialchars($settings['max_bet']); ?>" required>
                    </div>
                    <div class="col-6 mb-3">
                        <label class="form-label">Default Bet Amount</label>
                        <input type="number" step="0.01" class="form-control" name="default_bet" value="<?php echo htmlspecialchars($settings['default_bet']); ?>" required>
                    </div>
                    <div class="col-6 mb-3">
                        <label class="form-label">Default Payout Multiplier</label>
                        <input type="number" step="0.01" class="form-control" name="default_multiplier" value="<?php echo htmlspecialchars($settings['default_multiplier']); ?>" required>
                    </div>
                </div>

                <h6 class="text-secondary mt-4 mb-3">User</h6>
                <div class="mb-3">
                    <label class="form-label">Default Wallet Balance</label>
                    <input type="number" step="0.01" class="form-control" name="default_wallet_balance" value="<?php echo htmlspecialchars($settings['default_wallet_balance']); ?>" required>
                </div>

                <div class="form-check form-switch mt-4 mb-4">
                    <input class="form-check-input" type="checkbox" role="switch" id="maintenanceMode" name="maintenance_mode" <?php echo $settings['maintenance_mode'] ? 'checked' : ''; ?>>
                    <label class="form-check-label text-danger fw-bold" for="maintenanceMode">Maintenance Mode</label>
                </div>

                <button type="submit" class="btn btn-primary w-100">Save Settings</button>
            </form>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>

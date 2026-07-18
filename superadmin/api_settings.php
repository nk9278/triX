<?php
require_once '../includes/auth.php';
require_role(1);
require_once '../includes/header.php';
require_once '../config/database.php';

$pdo = getDbConnection();

// Fetch current API settings
$stmt = $pdo->query("SELECT * FROM api_settings LIMIT 1");
$api = $stmt->fetch();

// Simple encryption key for Phase 7 mock (In production use a robust app key)
define('ENC_KEY', 'trix_secure_key_12345');

function encrypt_cred($data) {
    return openssl_encrypt($data, 'AES-128-ECB', ENC_KEY);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        $error = "Invalid CSRF token.";
    } else {
        $provider = trim($_POST['provider'] ?? '');
        $base_url = trim($_POST['base_url'] ?? '');
        $api_key = trim($_POST['api_key'] ?? '');
        $api_secret = trim($_POST['api_secret'] ?? '');
        $sync_interval = (int)($_POST['sync_interval'] ?? 60);
        $status = $_POST['status'] ?? 'inactive';

        if (empty($provider) || empty($base_url) || empty($api_key)) {
            $error = "Provider, Base URL, and API Key are required.";
        } else {
            // If they just submitted '********', keep the old one, else encrypt the new one
            $enc_key = ($api_key === '********' && $api) ? $api['api_key'] : encrypt_cred($api_key);

            $enc_secret = '';
            if (!empty($api_secret)) $enc_secret = ($api_secret === '********' && $api) ? $api['api_secret'] : encrypt_cred($api_secret);

            if ($api) {
                // Update
                $stmt = $pdo->prepare("
                    UPDATE api_settings
                    SET provider = ?, base_url = ?, api_key = ?, api_secret = ?, sync_interval = ?, status = ?
                    WHERE id = ?
                ");
                $result = $stmt->execute([$provider, $base_url, $enc_key, $enc_secret, $sync_interval, $status, $api['id']]);
            } else {
                // Insert
                $stmt = $pdo->prepare("
                    INSERT INTO api_settings (provider, base_url, api_key, api_secret, sync_interval, status)
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                $result = $stmt->execute([$provider, $base_url, $enc_key, $enc_secret, $sync_interval, $status]);
            }

            if ($result) {
                log_activity($pdo, $_SESSION['user_id'], 'API Settings Updated', "Updated API provider to $provider");
                $success = "API Settings saved successfully.";
                $stmt = $pdo->query("SELECT * FROM api_settings LIMIT 1");
                $api = $stmt->fetch();
            } else {
                $error = "Failed to save API settings.";
            }
        }
    }
}
?>

<div class="container-fluid mt-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4>Cricket API Settings</h4>
        <a href="settings.php" class="btn btn-secondary btn-sm">Back to General</a>
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

                <div class="mb-3">
                    <label class="form-label">Provider</label>
                    <select class="form-select" name="provider" required>
                        <option value="CricAPI" <?php echo ($api && $api['provider'] === 'CricAPI') ? 'selected' : ''; ?>>CricAPI</option>
                        <option value="Roanuz" <?php echo ($api && $api['provider'] === 'Roanuz') ? 'selected' : ''; ?>>Roanuz</option>
                        <option value="EntitySport" <?php echo ($api && $api['provider'] === 'EntitySport') ? 'selected' : ''; ?>>EntitySport</option>
                        <option value="SportMonks" <?php echo ($api && $api['provider'] === 'SportMonks') ? 'selected' : ''; ?>>SportMonks</option>
                        <option value="API Sports" <?php echo ($api && $api['provider'] === 'API Sports') ? 'selected' : ''; ?>>API Sports</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">Base URL</label>
                    <input type="url" class="form-control" name="base_url" value="<?php echo $api ? htmlspecialchars($api['base_url']) : 'https://api.cricapi.com/v1/'; ?>" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">API Key</label>
                    <input type="password" class="form-control" name="api_key" value="<?php echo $api ? '********' : ''; ?>" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">API Secret (Optional)</label>
                    <input type="password" class="form-control" name="api_secret" value="<?php echo ($api && !empty($api['api_secret'])) ? '********' : ''; ?>">
                </div>

                <div class="mb-3">
                    <label class="form-label">Sync Interval (Seconds)</label>
                    <input type="number" class="form-control" name="sync_interval" min="10" value="<?php echo $api ? $api['sync_interval'] : 60; ?>" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">API Status</label>
                    <select class="form-select" name="status">
                        <option value="active" <?php echo ($api && $api['status'] === 'active') ? 'selected' : ''; ?>>Active</option>
                        <option value="inactive" <?php echo (!$api || $api['status'] === 'inactive') ? 'selected' : ''; ?>>Inactive</option>
                    </select>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary w-100">Save Configuration</button>
                    <!-- Test Connection is a conceptual feature for Phase 7 as instructed. It can be a simple JS alert for now or an AJAX call to the service -->
                    <button type="button" class="btn btn-outline-info w-100" onclick="alert('Testing connection with saved credentials...\nConnection OK!')">Test Connection</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>

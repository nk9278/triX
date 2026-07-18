<?php
require_once '../includes/auth.php';
require_role(1);
require_once '../includes/header.php';
require_once '../config/database.php';
require_once '../includes/api_service.php';

$pdo = getDbConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        $error = "Invalid CSRF token.";
    } else {
        $type = $_POST['type'] ?? 'upcoming';
        $game_id = (int)$_POST['game_id'];

        if (empty($game_id)) {
            $error = "Please select a target Game category.";
        } else {
            $result = fetchMatchesFromAPI($pdo, $type);

            if (!$result['success']) {
                $error = $result['message'];
            } else {
                $imported = 0;
                $config = getApiConfig($pdo);
                $provider = $config['provider'];

                foreach ($result['data'] as $m) {
                    // Check for duplicates
                    $stmt = $pdo->prepare("SELECT id FROM matches WHERE provider = ? AND provider_match_id = ?");
                    $stmt->execute([$provider, $m['provider_match_id']]);
                    if (!$stmt->fetch()) {
                        // Insert
                        $stmt = $pdo->prepare("
                            INSERT INTO matches (game_id, title, start_time, status, provider, provider_match_id, venue, toss_winner, last_sync)
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
                        ");
                        $stmt->execute([
                            $game_id, $m['title'], $m['start_time'], $m['status'],
                            $provider, $m['provider_match_id'], $m['venue'], $m['toss_winner']
                        ]);
                        $imported++;
                    }
                }

                log_activity($pdo, $_SESSION['user_id'], 'Match Import', "Imported $imported $type matches via API.");
                $success = "Successfully imported $imported matches.";
            }
        }
    }
}

$stmt = $pdo->query("SELECT id, name FROM games WHERE status = 'active'");
$games = $stmt->fetchAll();
?>

<div class="container-fluid mt-4">
    <h4>Import Matches via API</h4>

    <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    <?php if (isset($success)): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>

    <div class="card mt-3">
        <div class="card-body">
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">

                <div class="mb-3">
                    <label class="form-label">Match Status to Import</label>
                    <select class="form-select" name="type" required>
                        <option value="upcoming">Upcoming Matches</option>
                        <option value="live">Live Matches</option>
                        <option value="completed">Completed Matches</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">Assign to Game Category</label>
                    <select class="form-select" name="game_id" required>
                        <option value="">-- Select Game --</option>
                        <?php foreach ($games as $game): ?>
                            <option value="<?php echo $game['id']; ?>"><?php echo htmlspecialchars($game['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <button type="submit" class="btn btn-primary">Fetch & Import Matches</button>
            </form>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>

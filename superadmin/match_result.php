<?php
require_once '../includes/auth.php';
require_role(1);
require_once '../includes/header.php';
require_once '../config/database.php';

$pdo = getDbConnection();

// Fetch completed but unsetlled matches
$stmt = $pdo->query("
    SELECT m.*, g.name as game_name
    FROM matches m
    JOIN games g ON m.game_id = g.id
    WHERE m.status = 'completed' AND m.is_settled = 0
    ORDER BY m.start_time ASC
");
$matches = $stmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        $error = "Invalid CSRF token.";
    } else {
        $match_id = (int)$_POST['match_id'];
        $winning_team = trim($_POST['winning_team'] ?? '');
        $remarks = trim($_POST['remarks'] ?? '');

        if (empty($match_id) || empty($winning_team)) {
            $error = "Match and Winning Team are required.";
        } else {
            // Verify match is completed and not settled
            $checkStmt = $pdo->prepare("SELECT status, is_settled, title FROM matches WHERE id = ?");
            $checkStmt->execute([$match_id]);
            $match_data = $checkStmt->fetch();

            if (!$match_data || $match_data['status'] !== 'completed' || $match_data['is_settled'] == 1) {
                $error = "Invalid match selection.";
            } else {
                $_SESSION['settle_preview'] = [
                    'match_id' => $match_id,
                    'winning_team' => $winning_team,
                    'remarks' => $remarks,
                    'match_title' => $match_data['title']
                ];
                header("Location: settle_preview.php");
                die();
            }
        }
    }
}
?>

<div class="container-fluid mt-4">
    <h4>Match Result Management</h4>

    <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($_GET['success']); ?></div>
    <?php endif; ?>

    <div class="card mt-3">
        <div class="card-body">
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">

                <div class="mb-3">
                    <label class="form-label">Select Completed Match</label>
                    <select class="form-select" name="match_id" required>
                        <option value="">-- Select Match --</option>
                        <?php foreach ($matches as $match): ?>
                            <option value="<?php echo $match['id']; ?>">
                                <?php echo htmlspecialchars($match['title']); ?> (<?php echo htmlspecialchars($match['game_name']); ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">Winning Option / Team</label>
                    <input type="text" class="form-control" name="winning_team" required placeholder="e.g. India, Australia, Tie, Cancelled">
                    <small class="text-muted">Enter 'Cancelled' to refund all bets for this match.</small>
                </div>

                <div class="mb-3">
                    <label class="form-label">Remarks (Optional)</label>
                    <textarea class="form-control" name="remarks" rows="2"></textarea>
                </div>

                <button type="submit" class="btn btn-primary">Preview Settlement</button>
            </form>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>

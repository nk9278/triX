<?php
require_once '../includes/auth.php';
require_role(1);
require_once '../includes/header.php';
require_once '../config/database.php';

$pdo = getDbConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        $error = "Invalid request.";
    } else {
        $game_id = (int)$_POST['game_id'];
        $title = trim($_POST['title'] ?? '');
        $start_time = $_POST['start_time'] ?? '';
        $status = $_POST['status'] ?? 'upcoming';

        if (empty($title) || empty($start_time) || empty($game_id)) {
            $error = "All fields are required.";
        } else {
            $stmt = $pdo->prepare("INSERT INTO matches (game_id, title, start_time, status) VALUES (?, ?, ?, ?)");
            if ($stmt->execute([$game_id, $title, $start_time, $status])) {
                $match_id = $pdo->lastInsertId();
                log_activity($pdo, $_SESSION['user_id'], 'Match Created', "Created Match: $title (ID: $match_id)");
                header("Location: matches.php?success=Match added successfully");
                die();
            } else {
                $error = "Failed to add match.";
            }
        }
    }
}

$stmt = $pdo->query("SELECT id, name FROM games WHERE status = 'active'");
$games = $stmt->fetchAll();
?>

<div class="container-fluid mt-4">
    <h4>Add Match</h4>

    <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <div class="card mt-3">
        <div class="card-body">
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">

                <div class="mb-3">
                    <label class="form-label">Game</label>
                    <select class="form-select" name="game_id" required>
                        <option value="">Select Game</option>
                        <?php foreach ($games as $game): ?>
                            <option value="<?php echo $game['id']; ?>"><?php echo htmlspecialchars($game['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">Match Title</label>
                    <input type="text" class="form-control" name="title" required placeholder="e.g. India vs Pakistan">
                </div>

                <div class="mb-3">
                    <label class="form-label">Start Time</label>
                    <input type="datetime-local" class="form-control" name="start_time" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Status</label>
                    <select class="form-select" name="status">
                        <option value="upcoming">Upcoming</option>
                        <option value="live">Live</option>
                        <option value="completed">Completed</option>
                    </select>
                </div>

                <button type="submit" class="btn btn-primary">Add Match</button>
                <a href="matches.php" class="btn btn-secondary">Cancel</a>
            </form>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>

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
        $name = trim($_POST['name'] ?? '');
        $status = $_POST['status'] ?? 'active';

        if (empty($name)) {
            $error = "Game name is required.";
        } else {
            $stmt = $pdo->prepare("INSERT INTO games (name, status) VALUES (?, ?)");
            if ($stmt->execute([$name, $status])) {
                $game_id = $pdo->lastInsertId();
                log_activity($pdo, $_SESSION['user_id'], 'Game Created', "Created Game: $name (ID: $game_id)");
                header("Location: games.php");
                die();
            } else {
                $error = "Failed to add game.";
            }
        }
    }
}
?>

<div class="container-fluid mt-4">
    <h4>Add Game</h4>

    <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <div class="card mt-3">
        <div class="card-body">
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">

                <div class="mb-3">
                    <label class="form-label">Game Name</label>
                    <input type="text" class="form-control" name="name" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Status</label>
                    <select class="form-select" name="status">
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>

                <button type="submit" class="btn btn-primary">Add Game</button>
                <a href="games.php" class="btn btn-secondary">Cancel</a>
            </form>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>

<?php
require_once '../includes/auth.php';
require_role(1);
require_once '../includes/header.php';
require_once '../config/database.php';

$pdo = getDbConnection();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$stmt = $pdo->prepare("SELECT * FROM games WHERE id = ?");
$stmt->execute([$id]);
$game = $stmt->fetch();

if (!$game) {
    echo "<div class='container mt-4'><div class='alert alert-danger'>Game not found.</div></div>";
    require_once '../includes/footer.php';
    die();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        $error = "Invalid request.";
    } else {
        $name = trim($_POST['name'] ?? '');
        $status = $_POST['status'] ?? 'active';

        if (empty($name)) {
            $error = "Game name is required.";
        } else {
            $stmt = $pdo->prepare("UPDATE games SET name = ?, status = ? WHERE id = ?");
            if ($stmt->execute([$name, $status, $id])) {
                log_activity($pdo, $_SESSION['user_id'], 'Game Updated', "Updated Game: $name (ID: $id)");
                header("Location: games.php");
                die();
            } else {
                $error = "Failed to update game.";
            }
        }
    }
}
?>

<div class="container-fluid mt-4">
    <h4>Edit Game</h4>

    <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <div class="card mt-3">
        <div class="card-body">
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">

                <div class="mb-3">
                    <label class="form-label">Game Name</label>
                    <input type="text" class="form-control" name="name" value="<?php echo htmlspecialchars($game['name']); ?>" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Status</label>
                    <select class="form-select" name="status">
                        <option value="active" <?php echo $game['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                        <option value="inactive" <?php echo $game['status'] === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                    </select>
                </div>

                <button type="submit" class="btn btn-primary">Update Game</button>
                <a href="games.php" class="btn btn-secondary">Cancel</a>
            </form>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>

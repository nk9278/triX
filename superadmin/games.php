<?php
require_once '../includes/auth.php';
require_role(1);
require_once '../includes/header.php';
require_once '../config/database.php';

$pdo = getDbConnection();

// Handle deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        $error = "Invalid request.";
    } else {
        $game_id = (int)$_POST['game_id'];
        $stmt = $pdo->prepare("UPDATE games SET status = 'inactive' WHERE id = ?");
        $stmt->execute([$game_id]);

        log_activity($pdo, $_SESSION['user_id'], 'Game Deactivated', "Deactivated Game ID: $game_id");
        $success = "Game deactivated successfully.";
    }
}

$stmt = $pdo->query("SELECT * FROM games ORDER BY created_at DESC");
$games = $stmt->fetchAll();
?>

<div class="container-fluid mt-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4>Game Management</h4>
        <a href="game_add.php" class="btn btn-primary btn-sm">Add Game</a>
    </div>

    <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    <?php if (isset($success)): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Status</th>
                            <th>Created At</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($games as $game): ?>
                            <tr>
                                <td><?php echo $game['id']; ?></td>
                                <td><?php echo htmlspecialchars($game['name']); ?></td>
                                <td>
                                    <?php if ($game['status'] === 'active'): ?>
                                        <span class="badge bg-success">Active</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">Inactive</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo $game['created_at']; ?></td>
                                <td>
                                    <a href="game_edit.php?id=<?php echo $game['id']; ?>" class="btn btn-sm btn-warning">Edit</a>
                                    <form method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to deactivate this game?');">
                                        <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="game_id" value="<?php echo $game['id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($games)): ?>
                            <tr>
                                <td colspan="5" class="text-center">No games found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>

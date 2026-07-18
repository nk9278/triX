<?php
require_once '../includes/auth.php';
require_role(1);
require_once '../config/database.php';
require_once '../includes/header.php';

$pdo = getDbConnection();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$stmt = $pdo->prepare("SELECT * FROM matches WHERE id = ?");
$stmt->execute([$id]);
$match = $stmt->fetch();

if (!$match) {
    echo "<div class='container mt-4'><div class='alert alert-danger'>Match not found.</div></div>";
    require_once '../includes/footer.php';
    die();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        $error = "Invalid CSRF token.";
    } else {
        $status = $_POST['status'] ?? '';
        if (in_array($status, ['upcoming', 'live', 'completed'])) {
            $stmt = $pdo->prepare("UPDATE matches SET status = ? WHERE id = ?");
            if ($stmt->execute([$status, $id])) {
                log_activity($pdo, $_SESSION['user_id'], 'Match Status Updated', "Updated status of match {$match['title']} (ID: $id) to $status");
                header("Location: matches.php?success=Match status updated to " . ucfirst($status));
                die();
            } else {
                $error = "Failed to update match status.";
            }
        } else {
            $error = "Invalid status.";
        }
    }
}
?>

<div class="container-fluid mt-4">
    <h4>Change Match Status</h4>

    <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <div class="card mt-3">
        <div class="card-body">
            <h5 class="card-title"><?php echo htmlspecialchars($match['title']); ?></h5>
            <p class="card-text">Current Status: <strong><?php echo ucfirst(htmlspecialchars($match['status'])); ?></strong></p>

            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">

                <div class="mb-3">
                    <label class="form-label">New Status</label>
                    <select class="form-select" name="status">
                        <option value="upcoming" <?php echo $match['status'] === 'upcoming' ? 'selected' : ''; ?>>Upcoming</option>
                        <option value="live" <?php echo $match['status'] === 'live' ? 'selected' : ''; ?>>Live</option>
                        <option value="completed" <?php echo $match['status'] === 'completed' ? 'selected' : ''; ?>>Completed</option>
                    </select>
                </div>

                <button type="submit" class="btn btn-primary">Update Status</button>
                <a href="matches.php" class="btn btn-secondary">Cancel</a>
            </form>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>

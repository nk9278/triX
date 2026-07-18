<?php
require_once '../includes/auth.php';
require_role(6);
require_once '../includes/header.php';
require_once '../config/database.php';

$pdo = getDbConnection();
$stmt = $pdo->query("SELECT * FROM games WHERE status = 'active' ORDER BY name ASC");
$games = $stmt->fetchAll();
?>

<div class="container-fluid mt-4">
    <h4>Available Games</h4>
    <div class="row">
        <?php foreach ($games as $game): ?>
            <div class="col-12 col-md-6 mb-3">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title"><?php echo htmlspecialchars($game['name']); ?></h5>
                        <a href="play.php?game_id=<?php echo $game['id']; ?>" class="btn btn-primary w-100">Play Now</a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
        <?php if (empty($games)): ?>
            <div class="col-12">
                <div class="alert alert-info">No games currently available.</div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>

<?php
require_once '../includes/auth.php';
require_role(1);
require_once '../includes/header.php';
require_once '../config/database.php';

$pdo = getDbConnection();

$stmt = $pdo->query("SELECT m.*, g.name as game_name FROM matches m JOIN games g ON m.game_id = g.id ORDER BY m.start_time DESC");
$matches = $stmt->fetchAll();
?>

<div class="container-fluid mt-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4>Match Management</h4>
        <a href="match_add.php" class="btn btn-primary btn-sm">Add Match</a>
    </div>

    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($_GET['success']); ?></div>
    <?php endif; ?>
    <?php if (isset($_GET['error'])): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($_GET['error']); ?></div>
    <?php endif; ?>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Game</th>
                            <th>Title</th>
                            <th>Start Time</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($matches as $match): ?>
                            <tr>
                                <td><?php echo $match['id']; ?></td>
                                <td><?php echo htmlspecialchars($match['game_name']); ?></td>
                                <td><?php echo htmlspecialchars($match['title']); ?></td>
                                <td><?php echo $match['start_time']; ?></td>
                                <td>
                                    <?php if ($match['status'] === 'upcoming'): ?>
                                        <span class="badge bg-secondary">Upcoming</span>
                                    <?php elseif ($match['status'] === 'live'): ?>
                                        <span class="badge bg-success">Live</span>
                                    <?php else: ?>
                                        <span class="badge bg-primary">Completed</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="match_edit.php?id=<?php echo $match['id']; ?>" class="btn btn-sm btn-warning">Edit</a>
                                    <a href="match_status.php?id=<?php echo $match['id']; ?>" class="btn btn-sm btn-info">Status</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($matches)): ?>
                            <tr>
                                <td colspan="6" class="text-center">No matches found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>

<?php
require_once '../includes/auth.php';
require_role(6);
require_once '../includes/header.php';
require_once '../config/database.php';

$pdo = getDbConnection();
$user_id = $_SESSION['user_id'];

$stmt = $pdo->prepare("
    SELECT b.*, m.title as match_title
    FROM bets b
    JOIN matches m ON b.match_id = m.id
    WHERE b.user_id = ?
    ORDER BY b.created_at DESC
");
$stmt->execute([$user_id]);
$bets = $stmt->fetchAll();
?>

<div class="container-fluid mt-4">
    <h4>Bet History</h4>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Match</th>
                            <th>Selection</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($bets as $bet): ?>
                            <tr>
                                <td><?php echo $bet['id']; ?></td>
                                <td><?php echo htmlspecialchars($bet['match_title']); ?></td>
                                <td><?php echo htmlspecialchars($bet['selection']); ?></td>
                                <td><?php echo number_format($bet['amount'], 2); ?></td>
                                <td>
                                    <?php if ($bet['status'] === 'pending'): ?>
                                        <span class="badge bg-warning text-dark">Pending</span>
                                    <?php elseif ($bet['status'] === 'won'): ?>
                                        <span class="badge bg-success">Won</span>
                                    <?php elseif ($bet['status'] === 'lost'): ?>
                                        <span class="badge bg-danger">Lost</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Refunded</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo $bet['created_at']; ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($bets)): ?>
                            <tr>
                                <td colspan="6" class="text-center">No bet history found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>

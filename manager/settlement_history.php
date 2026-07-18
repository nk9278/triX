<?php
require_once '../includes/auth.php';
require_role(3); // Manager
require_once '../includes/header.php';
require_once '../config/database.php';

$pdo = getDbConnection();
$user_id = $_SESSION['user_id'];

// Get all children in hierarchy (Managers, Super Agents, Agents, Users)
// Recursive CTE is best for true hierarchy, but given strict 5-level structure, we can chain
$stmt = $pdo->prepare("
    WITH RECURSIVE UserHierarchy AS (
        SELECT id FROM users WHERE parent_id = ?
        UNION ALL
        SELECT u.id FROM users u INNER JOIN UserHierarchy uh ON u.parent_id = uh.id
    )
    SELECT sd.*, s.settlement_id, m.title as match_title, u.username, b.selection
    FROM settlement_details sd
    JOIN settlements s ON sd.settlement_id = s.settlement_id
    JOIN matches m ON s.match_id = m.id
    JOIN users u ON sd.user_id = u.id
    JOIN bets b ON sd.bet_id = b.id
    WHERE sd.user_id IN (SELECT id FROM UserHierarchy)
    ORDER BY sd.processed_at DESC
");
$stmt->execute([$user_id]);
$settlements = $stmt->fetchAll();
?>

<div class="container-fluid mt-4">
    <h4>Settlement History</h4>

    <div class="row">
        <?php foreach ($settlements as $s): ?>
            <div class="col-12 col-md-6 mb-3">
                <div class="card">
                    <div class="card-header d-flex justify-content-between">
                        <span><small class="text-muted">Settlement ID:</small> <br> <?php echo htmlspecialchars($s['settlement_id']); ?></span>
                        <span class="text-end"><small class="text-muted">Date:</small> <br> <?php echo date('M d, Y H:i', strtotime($s['processed_at'])); ?></span>
                    </div>
                    <div class="card-body">
                        <h6 class="card-title"><?php echo htmlspecialchars($s['match_title']); ?></h6>
                        <p class="mb-1"><strong>User:</strong> <?php echo htmlspecialchars($s['username']); ?></p>
                        <p class="mb-1"><strong>Selection:</strong> <?php echo htmlspecialchars($s['selection']); ?></p>
                        <p class="mb-1"><strong>Bet Amount:</strong> <?php echo number_format($s['bet_amount'], 2); ?></p>

                        <div class="mt-3 d-flex justify-content-between align-items-center">
                            <div>
                                <?php if ($s['result'] === 'won'): ?>
                                    <span class="badge bg-success fs-6">Won</span>
                                <?php elseif ($s['result'] === 'lost'): ?>
                                    <span class="badge bg-danger fs-6">Lost</span>
                                <?php elseif ($s['result'] === 'refunded' || $s['result'] === 'cancelled'): ?>
                                    <span class="badge bg-secondary fs-6">Refunded</span>
                                <?php endif; ?>
                            </div>
                            <div class="fw-bold fs-5 <?php echo $s['payout_amount'] > 0 ? 'text-success' : 'text-danger'; ?>">
                                Payout: <?php echo number_format($s['payout_amount'], 2); ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>

        <?php if (empty($settlements)): ?>
            <div class="col-12">
                <div class="alert alert-info">No settlement history found for your hierarchy.</div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>

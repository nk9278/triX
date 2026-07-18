<?php
require_once '../includes/auth.php';
require_role(6);
require_once '../includes/header.php';
require_once '../config/database.php';

$pdo = getDbConnection();
$user_id = $_SESSION['user_id'];

// Phase 6 requires viewing balance after settlement, which we can get from wallet_transactions
$stmt = $pdo->prepare("
    SELECT b.*, m.title as match_title,
           (SELECT closing_balance FROM wallet_transactions wt WHERE wt.bet_id = b.id AND wt.transaction_type IN ('Bet Win', 'Bet Refund') ORDER BY id DESC LIMIT 1) as balance_after
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

    <div class="row">
        <?php foreach ($bets as $bet): ?>
            <div class="col-12 col-md-6 mb-3">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <small class="text-muted"><?php echo date('M d, Y H:i', strtotime($bet['created_at'])); ?></small>
                        <?php if ($bet['status'] === 'pending'): ?>
                            <span class="badge bg-warning text-dark">Pending</span>
                        <?php elseif ($bet['status'] === 'won'): ?>
                            <span class="badge bg-success">Won</span>
                        <?php elseif ($bet['status'] === 'lost'): ?>
                            <span class="badge bg-danger">Lost</span>
                        <?php else: ?>
                            <span class="badge bg-secondary">Refunded</span>
                        <?php endif; ?>
                    </div>
                    <div class="card-body">
                        <h6 class="card-title"><?php echo htmlspecialchars($bet['match_title']); ?></h6>
                        <div class="d-flex justify-content-between mb-2">
                            <span><strong>Selection:</strong> <?php echo htmlspecialchars($bet['selection']); ?></span>
                            <span><strong>Bet:</strong> <?php echo number_format($bet['amount'], 2); ?></span>
                        </div>

                        <?php if ($bet['status'] !== 'pending'): ?>
                            <hr>
                            <div class="d-flex justify-content-between text-muted small">
                                <span>Settled: <?php echo $bet['settled_at'] ? date('M d, H:i', strtotime($bet['settled_at'])) : '-'; ?></span>
                                <?php if ($bet['status'] === 'won' || $bet['status'] === 'refunded'): ?>
                                    <span class="fw-bold <?php echo $bet['status'] === 'won' ? 'text-success' : 'text-secondary'; ?>">
                                        +<?php echo number_format($bet['winning_amount'], 2); ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                            <?php if ($bet['balance_after']): ?>
                                <div class="text-end small mt-1">
                                    Bal. after settlement: <?php echo number_format($bet['balance_after'], 2); ?>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>

        <?php if (empty($bets)): ?>
            <div class="col-12">
                <div class="alert alert-info">No bet history found.</div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>

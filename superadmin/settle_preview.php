<?php
require_once '../includes/auth.php';
require_role(1);
require_once '../includes/header.php';
require_once '../config/database.php';

if (!isset($_SESSION['settle_preview'])) {
    header("Location: match_result.php");
    die();
}

$preview = $_SESSION['settle_preview'];
$match_id = $preview['match_id'];
$winning_team = $preview['winning_team'];
$is_cancelled = (strtolower($winning_team) === 'cancelled');

$pdo = getDbConnection();

// Fetch pending bets for this match
$stmt = $pdo->prepare("
    SELECT b.*, u.username
    FROM bets b
    JOIN users u ON b.user_id = u.id
    WHERE b.match_id = ? AND b.status = 'pending'
");
$stmt->execute([$match_id]);
$bets = $stmt->fetchAll();

$total_bets = count($bets);
$total_bet_amount = 0;
$total_payout = 0;
$total_refund = 0;

$preview_details = [];
$multiplier = 2.00; // Hardcoded default for Phase 6 as per spec

foreach ($bets as $bet) {
    $total_bet_amount += $bet['amount'];
    $result = 'lost';
    $payout = 0;

    if ($is_cancelled) {
        $result = 'cancelled'; // or 'refunded'
        $payout = $bet['amount'];
        $total_refund += $payout;
    } else {
        if (strtolower($bet['selection']) === strtolower($winning_team)) {
            $result = 'won';
            $payout = $bet['amount'] * $multiplier;
            $total_payout += $payout;
        }
    }

    $preview_details[] = [
        'bet_id' => $bet['id'],
        'username' => $bet['username'],
        'selection' => $bet['selection'],
        'bet_amount' => $bet['amount'],
        'result' => $result,
        'payout' => $payout
    ];
}

$_SESSION['settle_preview']['calculations'] = [
    'total_bets' => $total_bets,
    'total_bet_amount' => $total_bet_amount,
    'total_payout' => $total_payout,
    'total_refund' => $total_refund,
    'details' => $preview_details
];

?>

<div class="container-fluid mt-4">
    <h4>Settlement Preview</h4>

    <div class="card mb-4 border-info">
        <div class="card-body">
            <h5 class="card-title"><?php echo htmlspecialchars($preview['match_title']); ?></h5>
            <p class="mb-1"><strong>Winning Option:</strong> <?php echo htmlspecialchars($winning_team); ?></p>
            <hr>
            <div class="row text-center">
                <div class="col-4">
                    <h6>Total Bets</h6>
                    <div class="fs-4"><?php echo $total_bets; ?></div>
                </div>
                <div class="col-4">
                    <h6>Total Payout</h6>
                    <div class="fs-4 text-success"><?php echo number_format($total_payout, 2); ?></div>
                </div>
                <div class="col-4">
                    <h6>Total Refund</h6>
                    <div class="fs-4 text-warning"><?php echo number_format($total_refund, 2); ?></div>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped mb-0">
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>Selection</th>
                            <th>Bet</th>
                            <th>Result</th>
                            <th>Payout</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($preview_details as $d): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($d['username']); ?></td>
                                <td><?php echo htmlspecialchars($d['selection']); ?></td>
                                <td><?php echo number_format($d['bet_amount'], 2); ?></td>
                                <td>
                                    <?php if ($d['result'] === 'won'): ?>
                                        <span class="badge bg-success">Won</span>
                                    <?php elseif ($d['result'] === 'lost'): ?>
                                        <span class="badge bg-danger">Lost</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Refund</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo number_format($d['payout'], 2); ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($preview_details)): ?>
                            <tr>
                                <td colspan="5" class="text-center p-3">No pending bets to settle for this match.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <form method="POST" action="settle_confirm.php" class="d-inline">
        <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
        <button type="submit" class="btn btn-success w-100 mb-2">Confirm Settlement</button>
    </form>
    <a href="match_result.php" class="btn btn-secondary w-100">Cancel / Go Back</a>

</div>

<?php require_once '../includes/footer.php'; ?>

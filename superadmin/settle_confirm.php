<?php
require_once '../includes/auth.php';
require_role(1);
require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['settle_preview'])) {
    header("Location: match_result.php");
    die();
}

if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
    die("Invalid CSRF token.");
}

$preview = $_SESSION['settle_preview'];
$match_id = $preview['match_id'];
$winning_team = $preview['winning_team'];
$calculations = $preview['calculations'];
$details = $calculations['details'];

$pdo = getDbConnection();

try {
    $pdo->beginTransaction();

    // Lock match to prevent race condition
    $stmt = $pdo->prepare("SELECT is_settled, status FROM matches WHERE id = ? FOR UPDATE");
    $stmt->execute([$match_id]);
    $match = $stmt->fetch();

    if (!$match || $match['is_settled'] == 1 || $match['status'] !== 'completed') {
        throw new Exception("Match is not valid for settlement or already settled.");
    }

    $settlement_id = 'SET-' . date('YmdHis') . '-' . $match_id;

    // Insert into settlements
    $stmt = $pdo->prepare("
        INSERT INTO settlements (settlement_id, match_id, processed_by, total_bets, total_payout, total_refund)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $settlement_id,
        $match_id,
        $_SESSION['user_id'],
        $calculations['total_bets'],
        $calculations['total_payout'],
        $calculations['total_refund']
    ]);

    foreach ($details as $d) {
        $bet_id = $d['bet_id'];
        $result_status = $d['result']; // 'won', 'lost', 'cancelled'
        $payout = $d['payout'];

        // Fetch bet to find user_id and lock it
        $stmt = $pdo->prepare("SELECT user_id, amount, selection FROM bets WHERE id = ? AND status = 'pending' FOR UPDATE");
        $stmt->execute([$bet_id]);
        $bet = $stmt->fetch();

        if ($bet) {
            $user_id = $bet['user_id'];
            $bet_amount = $bet['amount'];

            // Map 'cancelled' to 'refunded' for bets table status
            $bet_db_status = ($result_status === 'cancelled') ? 'refunded' : $result_status;

            // Update bet
            $stmt = $pdo->prepare("UPDATE bets SET status = ?, winning_amount = ?, settled_at = NOW(), settled_by = ? WHERE id = ?");
            $stmt->execute([$bet_db_status, $payout, $_SESSION['user_id'], $bet_id]);

            // Insert settlement detail
            $stmt = $pdo->prepare("
                INSERT INTO settlement_details (settlement_id, bet_id, user_id, result, bet_amount, payout_amount)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$settlement_id, $bet_id, $user_id, $result_status, $bet_amount, $payout]);

            // Wallet updates if there is a payout or refund
            if ($payout > 0) {
                // Lock wallet
                $stmt = $pdo->prepare("SELECT balance FROM wallets WHERE user_id = ? FOR UPDATE");
                $stmt->execute([$user_id]);
                $wallet = $stmt->fetch();

                if ($wallet) {
                    $open_bal = $wallet['balance'];
                    $close_bal = $open_bal + $payout;

                    // Update balance and total_received
                    $stmt = $pdo->prepare("UPDATE wallets SET balance = ?, total_received = total_received + ? WHERE user_id = ?");
                    $stmt->execute([$close_bal, $payout, $user_id]);

                    $tx_type = ($result_status === 'won') ? 'Bet Win' : 'Bet Refund';
                    $tx_id = 'TX-' . time() . '-' . rand(1000,9999);

                    // Note: to_user is the user getting the coins. from_user is NULL (system)
                    $stmt = $pdo->prepare("
                        INSERT INTO wallet_transactions
                        (transaction_id, from_user, to_user, amount, opening_balance, closing_balance, transaction_type, match_id, bet_id, remark)
                        VALUES (?, NULL, ?, ?, ?, ?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([
                        $tx_id, $user_id, $payout, $open_bal, $close_bal, $tx_type, $match_id, $bet_id, "Settlement for Bet $bet_id"
                    ]);
                }
            }
        }
    }

    // Lock match and update
    $stmt = $pdo->prepare("UPDATE matches SET is_settled = 1, winning_team = ? WHERE id = ?");
    $stmt->execute([$winning_team, $match_id]);

    log_activity($pdo, $_SESSION['user_id'], 'Match Settled', "Settled match $match_id. Payout: {$calculations['total_payout']}");

    $pdo->commit();
    unset($_SESSION['settle_preview']);

    header("Location: match_result.php?success=Settlement completed successfully.");
    die();

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    die("Settlement Failed: " . $e->getMessage());
}

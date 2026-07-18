<?php
require_once '../includes/auth.php';
require_once '../config/database.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    die();
}

if ($_SESSION['role_id'] != 6) {
    echo json_encode(['success' => false, 'message' => 'Only users can place bets.']);
    die();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    die();
}

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    $input = $_POST;
}

if (!isset($input['csrf_token']) || !verify_csrf_token($input['csrf_token'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid CSRF token.']);
    die();
}

$user_id = $_SESSION['user_id'];
$match_id = (int)($input['match_id'] ?? 0);
$amount = (float)($input['amount'] ?? 0);
$selection = trim($input['selection'] ?? '');

if ($match_id <= 0 || $amount <= 0 || empty($selection)) {
    echo json_encode(['success' => false, 'message' => 'Invalid input data. Amount must be > 0.']);
    die();
}

$pdo = getDbConnection();

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("SELECT status, title FROM matches WHERE id = ? FOR UPDATE");
    $stmt->execute([$match_id]);
    $match = $stmt->fetch();

    if (!$match) {
        throw new Exception("Match not found.");
    }

    if ($match['status'] === 'completed') {
        throw new Exception("Cannot bet on a completed match.");
    }

    $stmt = $pdo->prepare("SELECT balance FROM wallets WHERE user_id = ? FOR UPDATE");
    $stmt->execute([$user_id]);
    $wallet = $stmt->fetch();

    if (!$wallet) {
        throw new Exception("Wallet not found.");
    }

    if ($wallet['balance'] < $amount) {
        throw new Exception("Insufficient balance.");
    }

    $stmt = $pdo->prepare("UPDATE wallets SET balance = balance - ?, total_sent = total_sent + ? WHERE user_id = ?");
    $stmt->execute([$amount, $amount, $user_id]);

    $stmt = $pdo->prepare("INSERT INTO bets (user_id, match_id, amount, selection, status) VALUES (?, ?, ?, ?, 'pending')");
    $stmt->execute([$user_id, $match_id, $amount, $selection]);
    $bet_id = $pdo->lastInsertId();

    $tx_id = 'BET' . time() . rand(1000, 9999);
    $open_bal = (float)$wallet['balance'];
    $close_bal = $open_bal - $amount;

    $stmt = $pdo->prepare("INSERT INTO wallet_transactions (transaction_id, from_user, to_user, amount, opening_balance, closing_balance, remark) VALUES (:tx_id, :from, :to, :amount, :open, :close, :remark)");
    $stmt->execute([
        'tx_id' => $tx_id,
        'from' => $user_id,
        'to' => null,
        'amount' => $amount,
        'open' => $open_bal,
        'close' => $close_bal,
        'remark' => "Bet Placement (Match $match_id)"
    ]);

    log_activity($pdo, $user_id, 'Bet Placed', "Placed bet of $amount on match '{$match['title']}' for '$selection'");

    $pdo->commit();
    echo json_encode(['success' => true, 'message' => 'Bet placed successfully.', 'new_balance' => $close_bal]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

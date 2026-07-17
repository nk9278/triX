<?php
/**
 * Global Utility and Security Functions
 */

// Generate CSRF token if not exists
function generate_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Verify CSRF token
function verify_csrf_token($token) {
    if (isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token)) {
        return true;
    }
    return false;
}

// Sanitize string input
function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

// Escape output (shorthand for htmlspecialchars)
function e($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

// Get the user's IP address safely
function get_ip_address() {
    $ip = '';
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    }
    // Simple validation
    if (!filter_var($ip, FILTER_VALIDATE_IP)) {
        $ip = 'Unknown';
    }
    return $ip;
}

// Get basic device info
function get_device_info() {
    return isset($_SERVER['HTTP_USER_AGENT']) ? substr($_SERVER['HTTP_USER_AGENT'], 0, 255) : 'Unknown';
}

/**
 * Generate a unique username.
 * Format: T + 4 random digits.
 * Check database to ensure uniqueness.
 */
function generate_username($pdo) {
    $is_unique = false;
    $username = '';

    while (!$is_unique) {
        $digits = str_pad(mt_rand(0, 9999), 4, '0', STR_PAD_LEFT);
        $username = 'T' . $digits;

        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = :username");
        $stmt->execute(['username' => $username]);
        if (!$stmt->fetch()) {
            $is_unique = true;
        }
    }

    return $username;
}

/**
 * Generate a random 4-digit numeric password.
 */
function generate_password() {
    return str_pad(mt_rand(0, 9999), 4, '0', STR_PAD_LEFT);
}

/**
 * Log user activity.
 */
function log_activity($pdo, $user_id, $action, $description = '') {
    $stmt = $pdo->prepare("INSERT INTO activity_logs (user_id, action, description) VALUES (:user_id, :action, :description)");
    $stmt->execute([
        'user_id' => $user_id,
        'action' => $action,
        'description' => $description
    ]);
}

/**
 * Get user wallet details
 */
function get_wallet($pdo, $user_id) {
    $stmt = $pdo->prepare("SELECT * FROM wallets WHERE user_id = :user_id");
    $stmt->execute(['user_id' => $user_id]);
    return $stmt->fetch();
}

/**
 * Generate unique transaction ID
 */
function generate_transaction_id() {
    return 'TRX' . time() . mt_rand(1000, 9999);
}

/**
 * Execute coin transfer
 */
function transfer_coins($pdo, $from_user_id, $to_user_id, $amount, $remark = '') {
    if ($amount <= 0) return ['success' => false, 'message' => 'Amount must be greater than zero.'];

    try {
        $pdo->beginTransaction();

        // Lock sender wallet
        $stmt = $pdo->prepare("SELECT balance FROM wallets WHERE user_id = :user_id FOR UPDATE");
        $stmt->execute(['user_id' => $from_user_id]);
        $sender_wallet = $stmt->fetch();

        if (!$sender_wallet || $sender_wallet['balance'] < $amount) {
            $pdo->rollBack();
            return ['success' => false, 'message' => 'Insufficient balance.'];
        }

        // Lock receiver wallet
        $stmt = $pdo->prepare("SELECT balance FROM wallets WHERE user_id = :user_id FOR UPDATE");
        $stmt->execute(['user_id' => $to_user_id]);
        $receiver_wallet = $stmt->fetch();

        if (!$receiver_wallet) {
            $pdo->rollBack();
            return ['success' => false, 'message' => 'Receiver wallet not found.'];
        }

        // Deduct from sender
        $stmt = $pdo->prepare("UPDATE wallets SET balance = balance - :amount1, total_sent = total_sent + :amount2 WHERE user_id = :user_id");
        $stmt->execute(['amount1' => $amount, 'amount2' => $amount, 'user_id' => $from_user_id]);

        // Add to receiver
        $stmt = $pdo->prepare("UPDATE wallets SET balance = balance + :amount1, total_received = total_received + :amount2 WHERE user_id = :user_id");
        $stmt->execute(['amount1' => $amount, 'amount2' => $amount, 'user_id' => $to_user_id]);

        // Record transaction
        $tx_id = generate_transaction_id();
        $stmt = $pdo->prepare("
            INSERT INTO wallet_transactions (transaction_id, from_user, to_user, amount, opening_balance, closing_balance, remark)
            VALUES (:tx_id, :from, :to, :amount, :open, :close, :remark)
        ");
        $stmt->execute([
            'tx_id' => $tx_id,
            'from' => $from_user_id,
            'to' => $to_user_id,
            'amount' => $amount,
            'open' => $receiver_wallet['balance'],
            'close' => $receiver_wallet['balance'] + $amount,
            'remark' => $remark
        ]);

        log_activity($pdo, $from_user_id, 'Coins Sent', "Sent $amount coins to user ID: $to_user_id");
        log_activity($pdo, $to_user_id, 'Coins Received', "Received $amount coins from user ID: $from_user_id");

        $pdo->commit();
        return ['success' => true, 'message' => 'Transfer successful.'];
    } catch (Exception $e) {
        $pdo->rollBack();
        return ['success' => false, 'message' => 'Transfer failed due to system error.'];
    }
}

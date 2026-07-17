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

<?php
/**
 * Authentication and Session Management
 */

// Start secure session if not already started
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_samesite', 'Lax');
    // If using HTTPS, set secure flag
    if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
        ini_set('session.cookie_secure', 1);
    }

    session_start();
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';

// Check if user is logged in
function is_logged_in() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

// Require login to access a page
function require_login() {
    if (!is_logged_in()) {
        header("Location: login.php");
        exit;
    }

    // Check session timeout
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > SESSION_LIFETIME)) {
        logout();
        header("Location: login.php?error=timeout");
        exit;
    }

    $_SESSION['last_activity'] = time();
}

// Attempt to login
function login_user($username, $password) {
    $pdo = getDbConnection();

    $stmt = $pdo->prepare("SELECT id, username, password, role_id, name, status FROM users WHERE username = :username LIMIT 1");
    $stmt->execute(['username' => $username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        if ($user['status'] !== 'active') {
            return ['success' => false, 'message' => 'Account is inactive.'];
        }

        // Prevent session fixation
        session_regenerate_id(true);

        // Set session variables
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['role_id'] = $user['role_id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['name'] = $user['name'];
        $_SESSION['last_activity'] = time();

        // Log the login
        $ip = get_ip_address();
        $device = get_device_info();

        $logStmt = $pdo->prepare("INSERT INTO login_logs (user_id, ip_address, device) VALUES (:user_id, :ip, :device)");
        $logStmt->execute([
            'user_id' => $user['id'],
            'ip' => $ip,
            'device' => $device
        ]);

        $_SESSION['login_log_id'] = $pdo->lastInsertId();

        return ['success' => true];
    }

    return ['success' => false, 'message' => 'Invalid username or password.'];
}

// Logout user
function logout() {
    if (is_logged_in() && isset($_SESSION['login_log_id'])) {
        $pdo = getDbConnection();
        $stmt = $pdo->prepare("UPDATE login_logs SET logout_time = CURRENT_TIMESTAMP WHERE id = :id");
        $stmt->execute(['id' => $_SESSION['login_log_id']]);
    }

    // Unset all session variables
    $_SESSION = [];

    // Destroy the session cookie
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }

    // Destroy the session
    session_destroy();
}

// Check role
function has_role($role_id) {
    if (!is_logged_in()) return false;
    return $_SESSION['role_id'] == $role_id;
}

// Get the base directory for a given role ID
function get_role_directory($role_id) {
    switch ($role_id) {
        case 1: return '/superadmin/';
        case 2: return '/admin/';
        case 3: return '/manager/';
        case 4: return '/superagent/';
        case 5: return '/agent/';
        case 6: return '/user/';
        default: return '/login.php';
    }
}

// Redirect user to their respective dashboard based on role
function redirect_based_on_role() {
    if (is_logged_in()) {
        $dir = get_role_directory($_SESSION['role_id']);
        header("Location: " . BASE_URL . ltrim($dir, '/'));
        exit;
    }
}

// Require a specific role to access a page
function require_role($allowed_role_id) {
    require_login();

    if (!has_role($allowed_role_id)) {
        // Unauthorized access, redirect to their own dashboard
        redirect_based_on_role();
    }
}

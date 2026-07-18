<?php
require_once '../includes/auth.php';
require_once '../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    die();
}

$pdo = getDbConnection();
$stmt = $pdo->query("SELECT id, name FROM games WHERE status = 'active' ORDER BY name ASC");
$games = $stmt->fetchAll();

echo json_encode(['success' => true, 'games' => $games]);

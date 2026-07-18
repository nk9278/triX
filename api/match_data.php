<?php
require_once '../includes/auth.php';
require_once '../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    die();
}

$game_id = isset($_GET['game_id']) ? (int)$_GET['game_id'] : 0;

$pdo = getDbConnection();
$stmt = $pdo->prepare("SELECT id, title, start_time, status, is_settled FROM matches WHERE game_id = ? AND status IN ('upcoming', 'live') AND is_settled = 0 ORDER BY start_time ASC");
$stmt->execute([$game_id]);
$matches = $stmt->fetchAll();

foreach ($matches as &$match) {
    $teams = explode(' vs ', $match['title']);
    if (count($teams) == 2) {
        $match['team_a'] = trim($teams[0]);
        $match['team_b'] = trim($teams[1]);
    } else {
        $match['team_a'] = "Team A";
        $match['team_b'] = "Team B";
    }

    $match['odds_a'] = rand(11, 29) / 10;
    $match['odds_b'] = rand(11, 29) / 10;
    $match['odds_tie'] = rand(50, 150) / 10;
}

echo json_encode(['success' => true, 'matches' => $matches]);

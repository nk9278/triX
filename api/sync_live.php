<?php
require_once '../includes/auth.php';
require_once '../config/database.php';
require_once '../includes/api_service.php';

header('Content-Type: application/json');

// This file can be hit by AJAX polling or a cron job.
// We'll restrict to authenticated or secure internal calls.
// For Phase 7 demo, we'll allow AJAX calls from logged-in users to update display,
// and potentially process status changes.

$pdo = getDbConnection();
$config = getApiConfig($pdo);

if (!$config) {
    echo json_encode(['success' => false, 'message' => 'API inactive']);
    die();
}

$match_id = isset($_GET['match_id']) ? (int)$_GET['match_id'] : 0;

if ($match_id > 0) {
    // Fetch current state
    $stmt = $pdo->prepare("SELECT provider_match_id, status FROM matches WHERE id = ?");
    $stmt->execute([$match_id]);
    $match = $stmt->fetch();

    if ($match && $match['provider_match_id']) {
        // Mock live update
        // We simulate that randomly an upcoming match goes live, or live goes completed.
        $new_status = $match['status'];

        $rand = rand(1, 10);
        if ($match['status'] === 'upcoming' && $rand > 8) {
            $new_status = 'live';
        } elseif ($match['status'] === 'live' && $rand > 8) {
            $new_status = 'completed';
        }

        if ($new_status !== $match['status']) {
            $update = $pdo->prepare("UPDATE matches SET status = ?, last_sync = NOW() WHERE id = ?");
            $update->execute([$new_status, $match_id]);
        }

        // Mock score data
        $score = [
            'runs' => rand(50, 200),
            'wickets' => rand(0, 10),
            'overs' => rand(5, 20) . '.' . rand(0, 5),
            'run_rate' => number_format(rand(40, 100)/10, 2),
            'status_text' => ucfirst($new_status)
        ];

        echo json_encode(['success' => true, 'status' => $new_status, 'score' => $score]);
        die();
    }
}

echo json_encode(['success' => false]);

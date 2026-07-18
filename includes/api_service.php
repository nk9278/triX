<?php
/**
 * Cricket API Service Layer
 * Supports generic integration structure for Phase 7
 */

function getApiConfig($pdo) {
    $stmt = $pdo->query("SELECT * FROM api_settings WHERE status = 'active' LIMIT 1");
    return $stmt->fetch();
}

/**
 * Fetch matches from external API
 * Mocks the provider integration (e.g. CricAPI, SportMonks)
 */
function fetchMatchesFromAPI($pdo, $type = 'upcoming') {
    $config = getApiConfig($pdo);
    if (!$config) {
        return ['success' => false, 'message' => 'API is not configured or inactive.'];
    }

    // In a real integration, we'd use curl or file_get_contents to fetch from $config['base_url']
    // Since Phase 7 spec requires a functional mock layer that *can* connect if credentials exist:

    // Fake data to simulate API response
    $matches = [];
    $time = time();
    if ($type === 'upcoming') {
        $matches[] = [
            'provider_match_id' => 'EXT-UPCOMING',
            'title' => 'India vs South Africa',
            'start_time' => date('Y-m-d H:i:s', $time + 86400),
            'status' => 'upcoming',
            'venue' => 'MCG',
            'toss_winner' => ''
        ];
    } elseif ($type === 'live') {
        $matches[] = [
            'provider_match_id' => 'EXT-LIVE',
            'title' => 'England vs New Zealand',
            'start_time' => date('Y-m-d H:i:s', $time - 3600),
            'status' => 'live',
            'venue' => 'Lord\'s',
            'toss_winner' => 'England'
        ];
    } elseif ($type === 'completed') {
        $matches[] = [
            'provider_match_id' => 'EXT-COMPLETED',
            'title' => 'Pakistan vs Sri Lanka',
            'start_time' => date('Y-m-d H:i:s', $time - 86400),
            'status' => 'completed',
            'venue' => 'Gaddafi Stadium',
            'toss_winner' => 'Pakistan'
        ];
    }

    return ['success' => true, 'data' => $matches];
}

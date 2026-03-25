<?php
// api/trigger_alert.php

// 1. Setup Headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); 
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// 2. Connect to Database 
require '../db_connect.php';

// 3. Parse Incoming JSON
$data = json_decode(file_get_contents("php://input"));

if (!$data || !isset($data->user_id) || !isset($data->lat) || !isset($data->lng)) {
    echo json_encode(['success' => false, 'error' => 'Missing required GPS data or user ID']);
    exit();
}

$user_id = (int) $data->user_id;
$lat = (float) $data->lat;
$lng = (float) $data->lng;

// --- 4. THE DYNAMIC RISK ENGINE ---
$risk_score = 20; // Base Score

// Rule A: Time Factor (West Africa Time)
date_default_timezone_set('Africa/Lagos');
$current_hour = (int) date('H'); 
if ($current_hour >= 22 || $current_hour <= 5) {
    $risk_score += 40;
}

// Rule B: Dynamic Location Factor (Haversine Formula)
function calculateDistance($lat1, $lon1, $lat2, $lon2) {
    $earth_radius = 6371; 
    $dLat = deg2rad($lat2 - $lat1);
    $dLon = deg2rad($lon2 - $lon1);
    $a = sin($dLat/2) * sin($dLat/2) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon/2) * sin($dLon/2);
    $c = 2 * asin(sqrt($a));
    return $earth_radius * $c;
}

try {
    // Fetch user's safe zones
    $stmt = $pdo->prepare("SELECT latitude, longitude, radius_km FROM safe_zones WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $safe_zones = $stmt->fetchAll();

    $is_in_safe_zone = false;
    if (count($safe_zones) > 0) {
        foreach ($safe_zones as $zone) {
            $distance = calculateDistance($lat, $lng, $zone['latitude'], $zone['longitude']);
            if ($distance <= $zone['radius_km']) {
                $is_in_safe_zone = true;
                break;
            }
        }
    } else {
        // If no safe zones defined, maybe we don't penalize? 
        // PRD says: "If incoming coordinates are outside ALL of their personal safe zone radii, add 40"
        // If they have 0 safe zones, they are technically outside all of them.
        $is_in_safe_zone = false;
    }

    if (!$is_in_safe_zone) {
        $risk_score += 40;
    }

    $risk_score = min(100, $risk_score);

    // --- 5. ATOMIC INSERT INTO ALERTS & ALERT_LOCATIONS ---
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("INSERT INTO alerts (user_id, risk_score, status) VALUES (?, ?, 'active')");
    $stmt->execute([$user_id, $risk_score]);
    $alert_id = $pdo->lastInsertId();

    $stmt = $pdo->prepare("INSERT INTO alert_locations (alert_id, latitude, longitude) VALUES (?, ?, ?)");
    $stmt->execute([$alert_id, $lat, $lng]);

    $pdo->commit();

    echo json_encode([
        'success' => true, 
        'alert_id' => $alert_id, 
        'risk_score' => $risk_score,
        'message' => 'Silent SOS Triggered Successfully'
    ]);

} catch (PDOException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
<?php
// api/trigger_alert.php

// 1. Setup Headers to accept JSON and allow cross-origin requests
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); 
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests for CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// 2. Connect to Database 
require '../db_connect.php';

// 3. Parse Incoming JSON Payload
$data = json_decode(file_get_contents("php://input"));

if (!isset($data->user_id) || !isset($data->lat) || !isset($data->lng)) {
    echo json_encode(['success' => false, 'error' => 'Missing required GPS data or user ID']);
    exit();
}

$user_id = (int) $data->user_id;
$lat = (float) $data->lat;
$lng = (float) $data->lng;

// --- 4. THE AI RISK ENGINE ---
$risk_score = 20; // Default Base Score

// Rule A: Time Factor (West Africa Time)
date_default_timezone_set('Africa/Lagos');
$current_hour = (int) date('H'); 

// If it's between 10 PM (22) and 5 AM (05)
if ($current_hour >= 22 || $current_hour <= 5) {
    $risk_score += 40;
}

// Rule B: Location Factor (Haversine Formula)
// Hardcoded "Safe Zone" (e.g., Ikeja, Lagos)
$safe_lat = 6.6018;
$safe_lng = 3.3515;

function calculateDistance($lat1, $lon1, $lat2, $lon2) {
    $earth_radius = 6371; 
    $dLat = deg2rad($lat2 - $lat1);
    $dLon = deg2rad($lon2 - $lon1);
    $a = sin($dLat/2) * sin($dLat/2) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon/2) * sin($dLon/2);
    $c = 2 * asin(sqrt($a));
    return $earth_radius * $c;
}

$distance = calculateDistance($lat, $lng, $safe_lat, $safe_lng);

// If user is more than 5km away from Safe Zone
if ($distance > 5) {
    $risk_score += 40;
}

$risk_score = min(100, $risk_score);

// --- 5. INSERT ALERT INTO DATABASE ---
try {
    $stmt = $pdo->prepare("INSERT INTO alerts (user_id, latitude, longitude, risk_score, status) VALUES (:user_id, :lat, :lng, :risk_score, 'active')");
    $stmt->bindParam(':user_id', $user_id);
    $stmt->bindParam(':lat', $lat);
    $stmt->bindParam(':lng', $lng);
    $stmt->bindParam(':risk_score', $risk_score);
    
    if ($stmt->execute()) {
        $alert_id = $pdo->lastInsertId();
        echo json_encode([
            'success' => true, 
            'alert_id' => $alert_id, 
            'risk_score' => $risk_score,
            'message' => 'Silent SOS Triggered Successfully'
        ]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to save alert to database']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
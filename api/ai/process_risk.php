<?php
// api/ai/process_risk.php
require_once __DIR__ . '/../../db_connect.php';

function calculateRisk($pdo, $user_id, $current_lat, $current_lng) {
    $score = 20; // Base score
    $factors = [];

    // 1. Temporal Analysis (Lagos Night Weighting)
    date_default_timezone_set('Africa/Lagos');
    $hour = (int)date('H');
    if ($hour >= 22 || $hour <= 5) {
        $score += 30;
        $factors[] = "Late Night Window";
    }

    // 2. Geofence Intelligence
    $stmt = $pdo->prepare("SELECT latitude, longitude, radius_km FROM safe_zones WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $zones = $stmt->fetchAll();

    $in_safe_zone = false;
    foreach ($zones as $zone) {
        $dist = haversine($current_lat, $current_lng, $zone['latitude'], $zone['longitude']);
        if ($dist <= $zone['radius_km']) {
            $in_safe_zone = true;
            break;
        }
    }

    if (!$in_safe_zone && count($zones) > 0) {
        $score += 30;
        $factors[] = "Outside Safe Zone";
    }

    // 3. Device Status Analysis
    $stmt = $pdo->prepare("SELECT battery_level, is_connected FROM devices WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $device = $stmt->fetch();

    if ($device) {
        if ($device['battery_level'] < 20) {
            $score += 15;
            $factors[] = "Low Device Battery";
        }
        if (!$device['is_connected']) {
            $score += 20;
            $factors[] = "Device Disconnected";
        }
    }

    $final_score = min(100, $score);
    
    // Log the risk assessment
    $stmt = $pdo->prepare("INSERT INTO risk_logs (user_id, score, primary_factor) VALUES (?, ?, ?)");
    $stmt->execute([$user_id, $final_score, implode(', ', $factors)]);

    return [
        'score' => $final_score,
        'factors' => $factors
    ];
}

function haversine($lat1, $lon1, $lat2, $lon2) {
    $earth_radius = 6371;
    $dLat = deg2rad($lat2 - $lat1);
    $dLon = deg2rad($lon2 - $lon1);
    $a = sin($dLat/2) * sin($dLat/2) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon/2) * sin($dLon/2);
    return $earth_radius * 2 * asin(sqrt($a));
}
?>
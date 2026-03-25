<?php
// api/trigger_alert.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); 
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/../db_connect.php';
require_once __DIR__ . '/ai/process_risk.php';

$data = json_decode(file_get_contents("php://input"));

if (!$data || !isset($data->user_id) || !isset($data->lat) || !isset($data->lng)) {
    echo json_encode(['success' => false, 'error' => 'Missing data']);
    exit();
}

$user_id = (int)$data->user_id;
$lat = (float)$data->lat;
$lng = (float)$data->lng;

try {
    // 1. Run Dynamic Risk Intelligence
    $risk = calculateRisk($pdo, $user_id, $lat, $lng);
    $risk_score = $risk['score'];

    // 2. Atomic Save to Alerts & Locations
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
        'message' => 'Silent SOS Active. Trusted contacts notified.'
    ]);

} catch (PDOException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
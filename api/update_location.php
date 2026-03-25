<?php
// api/update_location.php

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); 
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require '../db_connect.php';

$data = json_decode(file_get_contents("php://input"));

if (!$data || !isset($data->alert_id) || !isset($data->lat) || !isset($data->lng)) {
    echo json_encode(['success' => false, 'error' => 'Missing alert_id or GPS data']);
    exit();
}

$alert_id = (int) $data->alert_id;
$lat = (float) $data->lat;
$lng = (float) $data->lng;

try {
    // 1. Verify alert is still active
    $stmt = $pdo->prepare("SELECT status FROM alerts WHERE id = ?");
    $stmt->execute([$alert_id]);
    $alert = $stmt->fetch();

    if (!$alert) {
        echo json_encode(['success' => false, 'error' => 'Alert not found']);
        exit();
    }

    if ($alert['status'] !== 'active') {
        echo json_encode(['success' => false, 'error' => 'Alert is no longer active']);
        exit();
    }

    // 2. Insert new location breadcrumb
    $stmt = $pdo->prepare("INSERT INTO alert_locations (alert_id, latitude, longitude) VALUES (?, ?, ?)");
    if ($stmt->execute([$alert_id, $lat, $lng])) {
        echo json_encode(['success' => true, 'message' => 'Location updated']);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to update location']);
    }

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
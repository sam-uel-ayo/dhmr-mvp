<?php
// api/user/add_zone.php
require_once __DIR__ . '/../middleware/auth_check.php';
require_once __DIR__ . '/../../db_connect.php';

header('Content-Type: application/json');

$data = json_decode(file_get_contents("php://input"));

if (!$data || !isset($data->name) || !isset($data->lat) || !isset($data->lng) || !isset($data->radius)) {
    echo json_encode(['success' => false, 'error' => 'Missing zone info']);
    exit();
}

try {
    $stmt = $pdo->prepare("INSERT INTO safe_zones (user_id, zone_name, latitude, longitude, radius_km) VALUES (?, ?, ?, ?, ?)");
    if ($stmt->execute([$_SESSION['user_id'], $data->name, $data->lat, $data->lng, $data->radius])) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to add safe zone']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
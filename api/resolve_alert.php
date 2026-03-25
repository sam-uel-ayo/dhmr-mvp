<?php
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

if (!isset($data->alert_id)) {
    echo json_encode(['success' => false, 'error' => 'Missing alert ID']);
    exit();
}

try {
    $stmt = $pdo->prepare("UPDATE alerts SET status = 'resolved' WHERE id = :id");
    $stmt->bindParam(':id', $data->alert_id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false]);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
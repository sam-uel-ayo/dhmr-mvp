<?php
// api/admin/resolve_alert.php
require_once __DIR__ . '/../middleware/admin_check.php';
require_once __DIR__ . '/../../db_connect.php';

header('Content-Type: application/json');

$data = json_decode(file_get_contents("php://input"));

if (!$data || !isset($data->alert_id)) {
    echo json_encode(['success' => false, 'error' => 'Missing alert_id']);
    exit();
}

try {
    $stmt = $pdo->prepare("UPDATE alerts SET status = 'resolved' WHERE id = ?");
    if ($stmt->execute([$data->alert_id])) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to resolve alert']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
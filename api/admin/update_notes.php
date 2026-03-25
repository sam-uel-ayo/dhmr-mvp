<?php
// api/admin/update_notes.php
require_once __DIR__ . '/../middleware/admin_check.php';
require_once __DIR__ . '/../../db_connect.php';

header('Content-Type: application/json');

$data = json_decode(file_get_contents("php://input"));

if (!$data || !isset($data->alert_id) || !isset($data->notes)) {
    echo json_encode(['success' => false, 'error' => 'Missing data']);
    exit();
}

try {
    $pdo->beginTransaction();

    // 1. Update Incident Report Notes
    $stmt = $pdo->prepare("UPDATE incident_reports SET responder_notes = ? WHERE alert_id = ?");
    $stmt->execute([$data->notes, $data->alert_id]);

    // 2. Log to Timeline
    $stmt = $pdo->prepare("INSERT INTO incident_timeline (alert_id, event_type, event_details) VALUES (?, ?, ?)");
    $stmt->execute([$data->alert_id, 'Responder Update', "New notes added: " . substr($data->notes, 0, 50) . "..."]);

    $pdo->commit();
    echo json_encode(['success' => true]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
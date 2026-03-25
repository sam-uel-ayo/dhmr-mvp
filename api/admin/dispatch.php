<?php
// api/admin/dispatch.php
require_once __DIR__ . '/../middleware/admin_check.php';
require_once __DIR__ . '/../../db_connect.php';

header('Content-Type: application/json');

$data = json_decode(file_get_contents("php://input"));

if (!$data || !isset($data->alert_id) || !isset($data->authority_id)) {
    echo json_encode(['success' => false, 'error' => 'Missing data']);
    exit();
}

try {
    $pdo->beginTransaction();

    // 1. Get Agency Name
    $stmt = $pdo->prepare("SELECT agency_name FROM authorities WHERE id = ?");
    $stmt->execute([$data->authority_id]);
    $agency = $stmt->fetch();

    if (!$agency) {
        throw new Exception("Agency not found");
    }

    // 2. Update Incident Report
    $stmt = $pdo->prepare("UPDATE incident_reports SET authority_id = ?, dispatch_status = 'dispatched' WHERE alert_id = ?");
    $stmt->execute([$data->authority_id, $data->alert_id]);

    // 3. Log to Timeline
    $stmt = $pdo->prepare("INSERT INTO incident_timeline (alert_id, event_type, event_details) VALUES (?, ?, ?)");
    $stmt->execute([$data->alert_id, 'Authority Dispatched', "{$agency['agency_name']} has been officially notified and dispatched to the user's location."]);

    $pdo->commit();
    echo json_encode(['success' => true]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
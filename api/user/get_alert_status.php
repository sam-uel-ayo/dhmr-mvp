<?php
// api/user/get_alert_status.php
require_once __DIR__ . '/../middleware/auth_check.php';
require_once __DIR__ . '/../../db_connect.php';

header('Content-Type: application/json');

$user_id = $_SESSION['user_id'];

try {
    // 1. Fetch active alerts for this user only
    $stmt = $pdo->prepare("
        SELECT alerts.*, ir.case_id, ir.dispatch_status, ir.responder_notes, a.agency_name, a.agency_type
        FROM alerts 
        LEFT JOIN incident_reports ir ON alerts.id = ir.alert_id
        LEFT JOIN authorities a ON ir.authority_id = a.id
        WHERE alerts.user_id = ? AND alerts.status = 'active'
        ORDER BY alerts.created_at DESC LIMIT 1
    ");
    $stmt->execute([$user_id]);
    $active_alert = $stmt->fetch();

    if ($active_alert) {
        echo json_encode([
            'success' => true,
            'hasActiveAlert' => true,
            'data' => $active_alert
        ]);
    } else {
        // Check for the *latest* resolved alert too, just in case we need to show resolution
        $stmt = $pdo->prepare("
            SELECT alerts.status 
            FROM alerts 
            WHERE user_id = ? 
            ORDER BY created_at DESC LIMIT 1
        ");
        $stmt->execute([$user_id]);
        $latest = $stmt->fetch();
        
        echo json_encode([
            'success' => true,
            'hasActiveAlert' => false,
            'status' => $latest ? $latest['status'] : 'none'
        ]);
    }

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
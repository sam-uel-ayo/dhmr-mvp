<?php
// api/admin/get_active_alerts.php
require_once __DIR__ . '/../middleware/admin_check.php';
require_once __DIR__ . '/../../db_connect.php';

header('Content-Type: application/json');

try {
    // 1. Fetch active alerts with user info and case info
    $stmt = $pdo->prepare("
        SELECT alerts.*, users.name, ir.case_id, ir.dispatch_status, ir.responder_notes, ir.authority_id
        FROM alerts 
        JOIN users ON alerts.user_id = users.id 
        LEFT JOIN incident_reports ir ON alerts.id = ir.alert_id
        WHERE alerts.status = 'active' 
        ORDER BY alerts.created_at DESC
    ");
    $stmt->execute();
    $alerts = $stmt->fetchAll();

    foreach ($alerts as &$alert) {
        // 2. Fetch trusted contacts
        $stmt_contacts = $pdo->prepare("SELECT contact_name, contact_phone FROM trusted_contacts WHERE user_id = ?");
        $stmt_contacts->execute([$alert['user_id']]);
        $alert['contacts'] = $stmt_contacts->fetchAll();

        // 3. Fetch location history
        $stmt_locs = $pdo->prepare("SELECT latitude, longitude, recorded_at FROM alert_locations WHERE alert_id = ? ORDER BY recorded_at ASC");
        $stmt_locs->execute([$alert['id']]);
        $alert['location_history'] = $stmt_locs->fetchAll();

        // 4. Fetch incident timeline (V4)
        $stmt_timeline = $pdo->prepare("SELECT event_type, event_details, created_at FROM incident_timeline WHERE alert_id = ? ORDER BY created_at DESC");
        $stmt_timeline->execute([$alert['id']]);
        $alert['timeline'] = $stmt_timeline->fetchAll();
    }

    // 5. Fetch Authorities Registry (V4)
    $stmt_auth = $pdo->prepare("SELECT * FROM authorities");
    $stmt_auth->execute();
    $authorities = $stmt_auth->fetchAll();

    echo json_encode([
        'success' => true, 
        'data' => $alerts,
        'authorities' => $authorities
    ]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
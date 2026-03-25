<?php
// api/admin/get_active_alerts.php
require_once __DIR__ . '/../middleware/admin_check.php';
require_once __DIR__ . '/../../db_connect.php';

header('Content-Type: application/json');

try {
    $stmt = $pdo->prepare("SELECT alerts.*, users.name FROM alerts JOIN users ON alerts.user_id = users.id WHERE alerts.status = 'active' ORDER BY alerts.created_at DESC");
    $stmt->execute();
    $alerts = $stmt->fetchAll();

    foreach ($alerts as &$alert) {
        $stmt_contacts = $pdo->prepare("SELECT contact_name, contact_phone FROM trusted_contacts WHERE user_id = ?");
        $stmt_contacts->execute([$alert['user_id']]);
        $alert['contacts'] = $stmt_contacts->fetchAll();

        $stmt_locs = $pdo->prepare("SELECT latitude, longitude, recorded_at FROM alert_locations WHERE alert_id = ? ORDER BY recorded_at ASC");
        $stmt_locs->execute([$alert['id']]);
        $alert['location_history'] = $stmt_locs->fetchAll();
    }

    echo json_encode(['success' => true, 'data' => $alerts]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
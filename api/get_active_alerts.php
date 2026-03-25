<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); // Allow your local dashboard to fetch this

require '../db_connect.php';

try {
    // Join the users table so we get the user's name and contact phone
    $stmt = $pdo->prepare("
        SELECT alerts.*, users.name, users.trusted_contact_phone 
        FROM alerts 
        JOIN users ON alerts.user_id = users.id 
        WHERE alerts.status = 'active' 
        ORDER BY alerts.created_at DESC
    ");
    $stmt->execute();
    $alerts = $stmt->fetchAll();
    
    echo json_encode(['success' => true, 'data' => $alerts]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
<?php
// api/user/add_contact.php
require_once __DIR__ . '/../middleware/auth_check.php';
require_once __DIR__ . '/../../db_connect.php';

header('Content-Type: application/json');

$data = json_decode(file_get_contents("php://input"));

if (!$data || !isset($data->name) || !isset($data->phone)) {
    echo json_encode(['success' => false, 'error' => 'Missing contact info']);
    exit();
}

try {
    $stmt = $pdo->prepare("INSERT INTO trusted_contacts (user_id, contact_name, contact_phone) VALUES (?, ?, ?)");
    if ($stmt->execute([$_SESSION['user_id'], $data->name, $data->phone])) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to add contact']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
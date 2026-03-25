<?php
// api/middleware/admin_check.php
require_once __DIR__ . '/auth_check.php';

if ($_SESSION['role'] !== 'admin') {
    header('Content-Type: application/json');
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Forbidden. Admin access required.']);
    exit();
}
?>
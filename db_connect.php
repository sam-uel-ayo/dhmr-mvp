<?php
// db_connect.php

// Bulletproof function to grab environment variables across different PHP server configs
function getEnvVar($key, $default) {
    $val = getenv($key);
    if (!$val) $val = $_SERVER[$key] ?? null;
    if (!$val) $val = $_ENV[$key] ?? null;
    return $val ?: $default;
}

$host = getEnvVar('MYSQLHOST', 'localhost');
$port = getEnvVar('MYSQLPORT', '3306');
$user = getEnvVar('MYSQLUSER', 'root');
$pass = getEnvVar('MYSQLPASSWORD', '');
$dbname = getEnvVar('MYSQL_DATABASE', 'dhmr_db'); // Matches your Railway variable exactly

try {
    $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4";
    $pdo = new PDO($dsn, $user, $pass);
    
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // TEMPORARY DEBUG MODE: Expose the real error and the host it's trying to connect to
    header('Content-Type: application/json');
    die(json_encode([
        'success' => false, 
        'error' => 'Database connection failed.',
        'real_error_message' => $e->getMessage(),
        'debug_host_attempted' => $host
    ]));
}
?>
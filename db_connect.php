<?php
// db_connect.php

// Railway injects these specific environment variables automatically when you attach a MySQL database.
// The fallback values after the "?:" are for your local testing environment.
$host = getenv('MYSQLHOST') ?: 'localhost';
$port = getenv('MYSQLPORT') ?: '3306';
$user = getenv('MYSQLUSER') ?: 'root';
$pass = getenv('MYSQLPASSWORD') ?: ''; // Put your local MySQL password here if you have one
$dbname = getenv('MYSQLDATABASE') ?: 'dhmr_db';

try {
    $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4";
    $pdo = new PDO($dsn, $user, $pass);
    
    // Set PDO to throw exceptions on errors and return associative arrays
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Return a clean JSON error if it fails, rather than dumping stack traces
    header('Content-Type: application/json');
    die(json_encode(['success' => false, 'error' => 'Database connection failed.']));
}
?>
<?php
// init_v2.php
require 'db_connect.php';

$sql = "
SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS alert_locations;
DROP TABLE IF EXISTS alerts;
DROP TABLE IF EXISTS safe_zones;
DROP TABLE IF EXISTS trusted_contacts;
DROP TABLE IF EXISTS users;
SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    name VARCHAR(255) NOT NULL,
    role ENUM('user', 'admin') DEFAULT 'user'
);

CREATE TABLE trusted_contacts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    contact_name VARCHAR(255) NOT NULL,
    contact_phone VARCHAR(50) NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE safe_zones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    zone_name VARCHAR(255) NOT NULL,
    latitude DECIMAL(10, 8) NOT NULL,
    longitude DECIMAL(11, 8) NOT NULL,
    radius_km DECIMAL(5, 2) NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE alerts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    risk_score INT NOT NULL,
    status ENUM('active', 'resolved') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE alert_locations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    alert_id INT NOT NULL,
    latitude DECIMAL(10, 8) NOT NULL,
    longitude DECIMAL(11, 8) NOT NULL,
    recorded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (alert_id) REFERENCES alerts(id) ON DELETE CASCADE
);
";

try {
    $pdo->exec($sql);
    echo "<h1>Phase 1: Database Initialized (v2)</h1>";
    echo "<p>All tables created successfully with foreign key constraints.</p>";

    // Insert Initial Users
    $adminPassword = password_hash('admin123', PASSWORD_DEFAULT);
    $userPassword = password_hash('user123', PASSWORD_DEFAULT);

    $stmt = $pdo->prepare("INSERT INTO users (email, password_hash, name, role) VALUES (?, ?, ?, ?)");
    
    $stmt->execute(['admin@dhmr.com', $adminPassword, 'System Admin', 'admin']);
    $stmt->execute(['user@dhmr.com', $userPassword, 'Demo User', 'user']);

    echo "<p>Admin and Standard users created successfully.</p>";
    echo "<ul>
            <li><b>Admin:</b> admin@dhmr.com / admin123</li>
            <li><b>User:</b> user@dhmr.com / user123</li>
          </ul>";

} catch (PDOException $e) {
    echo "<h1>Initialization Error</h1>";
    echo "<p>" . $e->getMessage() . "</p>";
}
?>
<?php
// init_v3.php
require 'db_connect.php';

$sql = "
SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS risk_logs;
DROP TABLE IF EXISTS devices;
SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE devices (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    device_type ENUM('necklace', 'bangle', 'keyholder') NOT NULL,
    battery_level INT DEFAULT 100,
    is_connected BOOLEAN DEFAULT TRUE,
    last_sync TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE risk_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    score INT NOT NULL,
    primary_factor VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Associate the demo user with a device
INSERT IGNORE INTO devices (user_id, device_type, battery_level, is_connected)
VALUES (2, 'necklace', 85, TRUE);
";

try {
    $pdo->exec($sql);
    echo "<h1>Phase 3: Database Upgraded (v3)</h1>";
    echo "<p>Tables 'devices' and 'risk_logs' created successfully.</p>";
} catch (PDOException $e) {
    echo "<h1>Upgrade Error</h1>";
    echo "<p>" . $e->getMessage() . "</p>";
}
?>
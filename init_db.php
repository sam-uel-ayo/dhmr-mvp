<?php
// init_db.php
require 'db_connect.php';

$sql = "
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    trusted_contact_phone VARCHAR(50) NOT NULL
);

CREATE TABLE IF NOT EXISTS alerts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    latitude DECIMAL(10, 8) NOT NULL,
    longitude DECIMAL(11, 8) NOT NULL,
    risk_score INT NOT NULL,
    status VARCHAR(20) DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Insert a dummy user so our MVP simulator has a valid user_id to send
INSERT IGNORE INTO users (id, name, trusted_contact_phone) 
VALUES (1, 'Demo User', '+2348000000000');
";

try {
    $pdo->exec($sql);
    echo "<h1>Database Initialized</h1>";
    echo "<p>Tables 'users' and 'alerts' created successfully. Dummy user added.</p>";
} catch (PDOException $e) {
    echo "<h1>Initialization Error</h1>";
    echo "<p>" . $e->getMessage() . "</p>";
}
?>
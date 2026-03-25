<?php
// init_v4.php
require 'db_connect.php';

$sql = "
SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS incident_timeline;
DROP TABLE IF EXISTS incident_reports;
DROP TABLE IF EXISTS authorities;
SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE authorities (
    id INT AUTO_INCREMENT PRIMARY KEY,
    agency_name VARCHAR(255) NOT NULL,
    agency_type ENUM('police', 'medical', 'security') NOT NULL,
    contact_details TEXT
);

CREATE TABLE incident_reports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    case_id VARCHAR(50) UNIQUE NOT NULL,
    alert_id INT NOT NULL,
    authority_id INT DEFAULT NULL,
    dispatch_status ENUM('pending', 'dispatched', 'arrived') DEFAULT 'pending',
    responder_notes TEXT,
    police_case_number VARCHAR(50) DEFAULT NULL,
    FOREIGN KEY (alert_id) REFERENCES alerts(id) ON DELETE CASCADE,
    FOREIGN KEY (authority_id) REFERENCES authorities(id) ON DELETE SET NULL
);

CREATE TABLE incident_timeline (
    id INT AUTO_INCREMENT PRIMARY KEY,
    alert_id INT NOT NULL,
    event_type VARCHAR(255) NOT NULL,
    event_details TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (alert_id) REFERENCES alerts(id) ON DELETE CASCADE
);

-- Seed Authorities
INSERT IGNORE INTO authorities (agency_name, agency_type, contact_details)
VALUES 
('Lagos State Police Command HQ', 'police', 'Ikeja, Lagos | +234 803 301 1107'),
('Emergency Medical Response Unit', 'medical', 'Gbagada General Hospital | +234 802 312 4567'),
('Red Line Private Security', 'security', 'Victoria Island | +234 809 999 0001');
";

try {
    $pdo->exec($sql);
    echo "<h1>Phase 4: Database Upgraded (v4)</h1>";
    echo "<p>Incident Management tables created and authorities seeded successfully.</p>";
} catch (PDOException $e) {
    echo "<h1>Upgrade Error</h1>";
    echo "<p>" . $e->getMessage() . "</p>";
}
?>
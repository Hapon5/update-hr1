<?php
include 'Database/Connections.php';

$tables = [
    'interviews' => "CREATE TABLE IF NOT EXISTS interviews (
        id INT AUTO_INCREMENT PRIMARY KEY,
        candidate_id INT NULL,
        candidate_name VARCHAR(255) NOT NULL,
        email VARCHAR(255) NOT NULL,
        position VARCHAR(255) NOT NULL,
        interviewer VARCHAR(255) NOT NULL,
        start_time DATETIME NOT NULL,
        end_time DATETIME NOT NULL,
        location VARCHAR(255),
        interview_type ENUM('Onsite', 'Online'),
        meeting_link TEXT,
        status ENUM('scheduled', 'completed', 'cancelled', 'no_show', 'rejected', 'hired') DEFAULT 'scheduled',
        notes TEXT,
        score INT,
        feedback TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",
    'safety_incidents' => "CREATE TABLE IF NOT EXISTS safety_incidents (
        id INT AUTO_INCREMENT PRIMARY KEY,
        employee_name VARCHAR(255) NOT NULL,
        incident_details TEXT NOT NULL,
        incident_type VARCHAR(100),
        severity ENUM('Low', 'Medium', 'High', 'Critical'),
        location VARCHAR(255),
        incident_date DATETIME,
        status ENUM('Open', 'Closed') DEFAULT 'Open',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",
    'employees' => "CREATE TABLE IF NOT EXISTS employees (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        email VARCHAR(255),
        position VARCHAR(255),
        department VARCHAR(255),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )"
];

foreach ($tables as $name => $sql) {
    try {
        $conn->exec($sql);
        echo "Table $name checked/created.\n";
    } catch (Exception $e) {
        echo "Error creating $name: " . $e->getMessage() . "\n";
    }
}
?>

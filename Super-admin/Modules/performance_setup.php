<?php
// Database Connection
include(__DIR__ . '/../../Database/Connections.php');

// 1. Create Tables if they don't exist
$sqlEmployees = "CREATE TABLE IF NOT EXISTS `employees` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `employee_id_code` VARCHAR(50) UNIQUE,
    `full_name` VARCHAR(255),
    `position` VARCHAR(255),
    `department` VARCHAR(255),
    `image_path` VARCHAR(255),
    `status` ENUM('Active', 'Inactive') DEFAULT 'Active',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";
$conn->exec($sqlEmployees);

$sqlReviews = "CREATE TABLE IF NOT EXISTS `performance_reviews` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `employee_id` INT,
    `review_date` DATE,
    `review_type` ENUM('Monthly', 'Quarterly', 'Annual') DEFAULT 'Monthly',
    `kpi_score` DECIMAL(5,2),
    `attendance_score` DECIMAL(5,2),
    `supervisor_quality_rating` INT,
    `productivity_score` DECIMAL(5,2),
    `promotion_recommended` BOOLEAN DEFAULT 0,
    `comments` TEXT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`employee_id`) REFERENCES `employees`(`id`) ON DELETE CASCADE
)";
$conn->exec($sqlReviews);

// 2. Insert Dummy Employees (if empty)
$check = $conn->query("SELECT count(*) FROM employees")->fetchColumn();
if ($check == 0) {
    $employees = [
        ['001', 'Andy Ferrer', 'IT Support', 'IT', 'profile/ferrer.jpeg'],
        ['002', 'Siegfried Mar Viloria', 'Team Leader', 'Operations', 'profile/Viloria.jpeg'],
        ['003', 'John Lloyd Morales', 'System Analyst', 'IT', 'profile/morales.jpeg'],
        ['004', 'Andrea Ilagan', 'Technical Support', 'IT', 'profile/ilagan.jpeg'],
        ['005', 'Charlotte Achivida', 'Cyber Security', 'Security', 'profile/achivida.jpeg']
    ];

    $stmt = $conn->prepare("INSERT INTO employees (employee_id_code, full_name, position, department, image_path) VALUES (?, ?, ?, ?, ?)");
    foreach ($employees as $emp) {
        $stmt->execute($emp);
    }
}
?>
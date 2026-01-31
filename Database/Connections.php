<?php
$host = 'localhost';
$dbname = 'hr1_hr1crane';       // your database name
$username = 'hr1_crane';     // your MySQL username
$password = '123';     // your MySQL password

try {
    // Create PDO instance
    $conn = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);

    // Set PDO error mode to Exception
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Optional: fetch assoc by default
    $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    // You can log the error instead of echoing in production
    die("Database connection failed: " . $e->getMessage());
}
?>
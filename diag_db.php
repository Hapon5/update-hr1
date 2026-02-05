<?php
require_once 'Database/Connections.php';

try {
    echo "Connected successfully.<br>\n";
    
    // Check tables
    $stmt = $conn->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "Tables: " . implode(", ", $tables) . "<br>\n";

    // Check employees table structure
    if (in_array('employees', $tables)) {
        echo "Table 'employees' exists.<br>\n";
        $stmt = $conn->query("DESCRIBE employees");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "Columns in employees:<br>\n";
        foreach ($columns as $col) {
            echo $col['Field'] . " (" . $col['Type'] . ")<br>\n";
        }
        
        // Check if user exists
        $email = 'employee@gmail.com'; // Use a test email or just check count
        $stmt = $conn->query("SELECT count(*) as count FROM employees");
        $row = $stmt->fetch();
        echo "Total employees: " . $row['count'] . "<br>\n";
        
    } else {
        echo "Table 'employees' DOES NOT EXIST.<br>\n";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>

<?php
include 'Database/Connections.php';

function describeTable($conn, $tableName) {
    echo "--- $tableName ---\n";
    try {
        $stmt = $conn->query("DESCRIBE $tableName");
        while ($row = $stmt->fetch()) {
            print_r($row);
        }
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage() . "\n";
    }
    echo "\n";
}

describeTable($conn, 'safety_incidents');
describeTable($conn, 'interviews');

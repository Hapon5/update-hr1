<?php
require_once __DIR__ . '/../Database/Connections.php';

try {
    // 1. Update Staff@gmail.com to Account_type 2
    $stmt1 = $conn->prepare("UPDATE logintbl SET Account_type = 2 WHERE Email = 'Staff@gmail.com'");
    $stmt1->execute();
    echo "Updated 'Staff@gmail.com' to Account Type 2 (Staff). Rows affected: " . $stmt1->rowCount() . "\n";

    // 2. Update employee@gmail.com to Account_type 3
    $stmt2 = $conn->prepare("UPDATE logintbl SET Account_type = 3 WHERE Email = 'employee@gmail.com'");
    $stmt2->execute();
    echo "Updated 'employee@gmail.com' to Account Type 3 (Employee). Rows affected: " . $stmt2->rowCount() . "\n";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>

<?php
include 'Database/Connections.php';
try {
    $stmt = $conn->query("DESCRIBE candidates");
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($result, JSON_PRETTY_PRINT);
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>

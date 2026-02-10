<?php
include 'Database/Connections.php';

try {
    // Check if id is auto_increment for job_postings
    $conn->exec("ALTER TABLE job_postings MODIFY COLUMN id INT AUTO_INCREMENT PRIMARY KEY");
    echo "Table job_postings updated: id is now AUTO_INCREMENT.\n";
} catch (Exception $e) {
    echo "Error updating job_postings: " . $e->getMessage() . "\n";
}

try {
    // Also check applications table just in case
    $conn->exec("ALTER TABLE applications MODIFY COLUMN id INT AUTO_INCREMENT PRIMARY KEY");
    echo "Table applications updated: id is now AUTO_INCREMENT.\n";
} catch (Exception $e) {
    echo "Error updating applications: " . $e->getMessage() . "\n";
}
?>

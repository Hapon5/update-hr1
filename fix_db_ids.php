<?php
include 'Database/Connections.php';

function fixAutoIncrement($conn, $table) {
    echo "Processing table: $table...\n";
    try {
        // First, check if the table exists
        $stmt = $conn->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() == 0) {
            echo " - Table '$table' does not exist. Skipping.\n";
            return;
        }

        // Try to add AUTO_INCREMENT. 
        // We use MODIFY which preserves the value but adds the attribute.
        // We do NOT include 'PRIMARY KEY' in the definition to avoid "Multiple primary key" errors if it already is one.
        // If it isn't a primary key, we might need to add it, but usually 'id' is created as PK.
        $sql = "ALTER TABLE $table MODIFY COLUMN id INT AUTO_INCREMENT";
        $conn->exec($sql);
        echo " - SUCCESS: Added AUTO_INCREMENT to '$table'.\n";
        
    } catch (PDOException $e) {
        echo " - ERROR: " . $e->getMessage() . "\n";
        
        // If the error suggests it needs to be a key first (unlikely for MODIFY but possible if strictly validated)
        // or if we really need to set it as PRIMARY KEY because it wasn't.
        if (strpos($e->getMessage(), 'nsufficient keys') !== false) {
             try {
                $conn->exec("ALTER TABLE $table MODIFY COLUMN id INT AUTO_INCREMENT PRIMARY KEY");
                echo " - RETRY SUCCESS: Added AUTO_INCREMENT PRIMARY KEY to '$table'.\n";
             } catch (PDOException $e2) {
                 echo " - RETRY ERROR: " . $e2->getMessage() . "\n";
             }
        }
    }
}

// Fix the tables involved in job_posting.php
fixAutoIncrement($conn, 'job_postings');
fixAutoIncrement($conn, 'applications');
fixAutoIncrement($conn, 'candidates');

echo "\nDone. Please try your action again.\n";
?>

<?php
// Consolidated Debug Script
// Un-comment lines or sections to run specific debug tasks

require 'Database/Connections.php';

echo "<h1>Debug Console</h1>";
echo "<p>Use query param ?task=... to run a specific task.</p>";
echo "<ul>
    <li><a href='?task=tables'>Show All Tables</a></li>
    <li><a href='?task=cols_employees'>Show Employee Columns</a></li>
    <li><a href='?task=cols_safety'>Show Safety Columns</a></li>
    <li><a href='?task=cols_recog'>Show Recognition Columns</a></li>
    <li><a href='?task=files_resumes'>List Resume Files</a></li>
    <li><a href='?task=check_candidates'>Check Candidate Resumes</a></li>
</ul>";

$task = $_GET['task'] ?? '';

echo "<hr>";

if ($task === 'tables') {
    echo "<h3>All Tables:</h3>";
    try {
        $tables = $conn->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
        echo "<pre>" . print_r($tables, true) . "</pre>";
    } catch (Exception $e) {
        echo $e->getMessage();
    }
}

if ($task === 'cols_employees') {
    echo "<h3>Columns in 'employees':</h3>";
    try {
        $cols = $conn->query("SHOW COLUMNS FROM employees")->fetchAll(PDO::FETCH_ASSOC);
        echo "<pre>";
        foreach ($cols as $c)
            echo $c['Field'] . "\n";
        echo "</pre>";
    } catch (Exception $e) {
        echo $e->getMessage();
    }
}

if ($task === 'cols_safety') {
    echo "<h3>Columns in 'safety_incidents':</h3>";
    try {
        $cols = $conn->query("SHOW COLUMNS FROM safety_incidents")->fetchAll(PDO::FETCH_ASSOC);
        echo "<pre>";
        foreach ($cols as $c)
            echo $c['Field'] . "\n";
        echo "</pre>";
    } catch (Exception $e) {
        echo $e->getMessage();
    }
}

if ($task === 'cols_recog') {
    echo "<h3>Columns in 'recognitions':</h3>";
    try {
        $cols = $conn->query("SHOW COLUMNS FROM recognitions")->fetchAll(PDO::FETCH_ASSOC);
        echo "<pre>";
        foreach ($cols as $c)
            echo $c['Field'] . "\n";
        echo "</pre>";
    } catch (Exception $e) {
        echo $e->getMessage();
    }
}

if ($task === 'files_resumes') {
    echo "<h3>Files in uploads/resumes:</h3>";
    $dirs = [
        'uploads/resumes/*',
        'Main/uploads/resumes/*'
    ];
    echo "<pre>";
    foreach ($dirs as $pattern) {
        $files = glob(__DIR__ . '/' . $pattern);
        if ($files) {
            foreach ($files as $f)
                echo basename($f) . " (in " . dirname($f) . ")\n";
        }
    }
    echo "</pre>";
}

if ($task === 'check_candidates') {
    echo "<h3>Checking Specific Candidates:</h3>";
    $names = ['Viloria', 'Morales', 'Ferrer', 'Ilagan', 'Achivida'];
    echo "<pre>";
    foreach ($names as $n) {
        try {
            $stmt = $conn->prepare("SELECT full_name, resume_path FROM candidates WHERE full_name LIKE ?");
            $stmt->execute(['%' . $n . '%']);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if ($results) {
                foreach ($results as $row) {
                    echo "Found: " . $row['full_name'] . " -> " . $row['resume_path'] . "\n";
                }
            } else {
                echo "Not found: $n\n";
            }
        } catch (Exception $e) {
            echo "Error: " . $e->getMessage() . "\n";
        }
    }
    echo "</pre>";
}
?>
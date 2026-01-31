<?php
// API Endpoint for New Hired Employees
// Path: integration/new_hired.php

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET');

// Include Database Connection
$pathsToTry = [
    __DIR__ . '/../Database/Connections.php',
    __DIR__ . '/../../Database/Connections.php'
];

$conn = null;
foreach ($pathsToTry as $path) {
    if (file_exists($path)) {
        require_once $path;
        break;
    }
}

if (!$conn) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Database connection failed']);
    exit;
}

// Ensure employees table exists (Auto-migration)
try {
    $conn->exec("CREATE TABLE IF NOT EXISTS employees (
        id INT AUTO_INCREMENT PRIMARY KEY,
        first_name VARCHAR(100) NOT NULL,
        last_name VARCHAR(100) NOT NULL,
        email VARCHAR(150),
        phone VARCHAR(20),
        position VARCHAR(100),
        department VARCHAR(100),
        date_hired DATE,
        salary DECIMAL(10, 2),
        status ENUM('Active', 'Inactive', 'Resigned', 'Terminated') DEFAULT 'Active',
        base64_image LONGTEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
} catch (PDOException $e) {
    // Continue even if error (table might exist)
}

try {
    $method = $_SERVER['REQUEST_METHOD'];

    if ($method === 'POST') {
        // --- POST: Add New Hired Employee ---
        $input = json_decode(file_get_contents('php://input'), true);

        // Required fields
        $first_name = $input['first_name'] ?? null;
        $last_name = $input['last_name'] ?? null;
        $position = $input['position'] ?? null;
        $email = $input['email'] ?? null;

        // Validation
        if (!$first_name || !$last_name || !$position || !$email) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Missing required fields: first_name, last_name, position, email']);
            exit;
        }

        // Optional fields
        $phone = $input['phone'] ?? '';
        $department = $input['department'] ?? 'General';
        $date_hired = $input['date_hired'] ?? date('Y-m-d');
        $salary = $input['salary'] ?? 0.00;
        $status = $input['status'] ?? 'Active';
        $base64_image = $input['base64_image'] ?? null;

        $stmt = $conn->prepare("INSERT INTO employees (first_name, last_name, email, phone, position, department, date_hired, salary, status, base64_image) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
        if ($stmt->execute([$first_name, $last_name, $email, $phone, $position, $department, $date_hired, $salary, $status, $base64_image])) {
            $newId = $conn->lastInsertId();
            http_response_code(201);
            echo json_encode(['status' => 'success', 'message' => 'Employee hired successfully', 'id' => $newId]);
        } else {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => 'Failed to add employee']);
        }

    } elseif ($method === 'GET') {
        // --- GET: Fetch Employees ---
        if (isset($_GET['id'])) {
            $stmt = $conn->prepare("SELECT * FROM employees WHERE id = ?");
            $stmt->execute([$_GET['id']]);
            $emp = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($emp) {
                echo json_encode(['status' => 'success', 'data' => $emp]);
            } else {
                http_response_code(404);
                echo json_encode(['status' => 'error', 'message' => 'Employee not found']);
            }
        } else {
            $stmt = $conn->prepare("SELECT * FROM employees ORDER BY date_hired DESC");
            $stmt->execute();
            $emps = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['status' => 'success', 'count' => count($emps), 'data' => $emps]);
        }

    } else {
        http_response_code(405);
        echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>

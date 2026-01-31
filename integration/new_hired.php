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

    // Ensure user_notifications table exists
    $conn->exec("CREATE TABLE IF NOT EXISTS user_notifications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        type VARCHAR(50),
        data LONGTEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");

} catch (PDOException $e) {
    // Continue even if error (table might exist)
}

try {
    $method = $_SERVER['REQUEST_METHOD'];

    if ($method === 'POST') {
        // --- POST: Add New Hired Employee & Request ---
        
        // 1. Detect Input Type (JSON vs Form Data)
        $input = [];
        if (strpos($_SERVER['CONTENT_TYPE'] ?? '', 'application/json') !== false) {
            $input = json_decode(file_get_contents('php://input'), true) ?? [];
        } else {
            $input = $_POST; // For multipart/form-data
        }

        // 2. Extract Fields (Mapping Laravel snippet to our DB)
        $first_name = $input['first_name'] ?? ($input['name'] ?? null);
        $last_name = $input['last_name'] ?? ($input['lastname'] ?? null);
        $email = $input['email'] ?? null;
        $position = $input['position'] ?? null;
        $phone = $input['phone'] ?? '';
        $account_type = $input['account_type'] ?? '3'; // Default to Employee

        if (!$first_name || !$last_name || !$email) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Missing required fields: first_name (name), last_name (lastname), email']);
            exit;
        }

        // 3. Handle Photo Upload
        $photoPath = $input['base64_image'] ?? null;
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = __DIR__ . '/../uploads/hr_photos/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
            
            $fileName = time() . '_' . basename($_FILES['photo']['name']);
            $targetPath = $uploadDir . $fileName;
            
            if (move_uploaded_file($_FILES['photo']['tmp_name'], $targetPath)) {
                $photoPath = 'uploads/hr_photos/' . $fileName;
            }
        }

        // 4. Log to user_notifications (JSON Data)
        $notificationData = array_merge($input, ['photo' => $photoPath]);
        $stmtNotify = $conn->prepare("INSERT INTO user_notifications (type, data) VALUES ('hr_request', ?)");
        $stmtNotify->execute([json_encode($notificationData)]);

        // 5. Insert into employees table
        $stmtEmp = $conn->prepare("INSERT INTO employees (first_name, last_name, email, phone, position, base64_image, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
        
        if ($stmtEmp->execute([$first_name, $last_name, $email, $phone, $position, $photoPath, 'Active'])) {
            $newId = $conn->lastInsertId();
            http_response_code(201);
            echo json_encode([
                'status' => 'success', 
                'message' => 'HR request and Employee added successfully', 
                'employee_id' => $newId,
                'photo_path' => $photoPath
            ]);
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

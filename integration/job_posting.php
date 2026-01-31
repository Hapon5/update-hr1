<?php
// API Endpoint for Job Postings
// Path: integration/job_posting.php

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

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

try {
    // Check Request Method
    $method = $_SERVER['REQUEST_METHOD'];

    if ($method === 'GET') {
        // --- GET: Fetch Job Postings ---
        if (isset($_GET['id'])) {
            $stmt = $conn->prepare("SELECT * FROM job_postings WHERE id = ?");
            $stmt->execute([$_GET['id']]);
            $job = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($job) {
                echo json_encode(['status' => 'success', 'data' => $job]);
            } else {
                http_response_code(404);
                echo json_encode(['status' => 'error', 'message' => 'Job not found']);
            }
        } else {
            // Fetch all ACTIVE job postings
            $stmt = $conn->prepare("SELECT * FROM job_postings WHERE status = 'active' ORDER BY created_at DESC");
            $stmt->execute();
            $jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode(['status' => 'success', 'count' => count($jobs), 'data' => $jobs]);
        }

    } elseif ($method === 'POST') {
        // --- POST: Create New Job Posting ---
        
        // Get JSON input
        $input = json_decode(file_get_contents('php://input'), true);

        // Required fields
        $title = $input['title'] ?? null;
        $position = $input['position'] ?? null;
        
        // Optional/Default fields
        $location = $input['location'] ?? 'Remote';
        $requirements = $input['requirements'] ?? '';
        $contact = $input['contact'] ?? '';
        $platform = $input['platform'] ?? 'API'; // Mark as from API
        $status = $input['status'] ?? 'active';
        $date_posted = date('Y-m-d'); // Current date

        if (!$title || !$position) {
            http_response_code(400); // Bad Request
            echo json_encode(['status' => 'error', 'message' => 'Missing required fields: title, position']);
            exit;
        }

        $stmt = $conn->prepare("INSERT INTO job_postings (title, position, location, requirements, contact, platform, date_posted, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        
        if ($stmt->execute([$title, $position, $location, $requirements, $contact, $platform, $date_posted, $status])) {
            $newId = $conn->lastInsertId();
            http_response_code(201); // Created
            echo json_encode(['status' => 'success', 'message' => 'Job posting created successfully', 'id' => $newId]);
        } else {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => 'Failed to create job posting']);
        }

    } else {
        http_response_code(405); // Method Not Allowed
        echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>

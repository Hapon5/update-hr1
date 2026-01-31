<?php
// API Endpoint for Candidate Submissions
// Path: integration/candidates.php

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

try {
    $method = $_SERVER['REQUEST_METHOD'];

    if ($method === 'POST') {
        // --- POST: Submit New Candidate/Applicant ---
        $input = json_decode(file_get_contents('php://input'), true);

        // Required fields
        $full_name = $input['full_name'] ?? null;
        $email = $input['email'] ?? null;
        $job_id = $input['job_id'] ?? null; // The ID of the job they are applying for

        if (!$full_name || !$email || !$job_id) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Missing required fields: full_name, email, job_id']);
            exit;
        }

        // Optional fields
        $phone = $input['phone'] ?? '';
        $resume_link = $input['resume_link'] ?? '';
        $message = $input['message'] ?? '';
        $date_applied = date('Y-m-d H:i:s');

        // Logic: Insert into 'candidates' table (Assuming this table exists based on previous file research)
        // We'll use the existing columns found in candidate_sourcing_&_tracking.php
        $stmt = $conn->prepare("INSERT INTO candidates (CandidateName, Email, Phone, JobID, Status, Date_Applied) VALUES (?, ?, ?, ?, ?, ?)");
        
        if ($stmt->execute([$full_name, $email, $phone, $job_id, 'Pending', $date_applied])) {
            $newId = $conn->lastInsertId();
            http_response_code(201);
            echo json_encode(['status' => 'success', 'message' => 'Application submitted successfully', 'candidate_id' => $newId]);
        } else {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => 'Failed to process application']);
        }

    } elseif ($method === 'GET') {
        // --- GET: Fetch Candidates ---
        $stmt = $conn->prepare("SELECT * FROM candidates ORDER BY ID DESC LIMIT 50");
        $stmt->execute();
        $candidates = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['status' => 'success', 'count' => count($candidates), 'data' => $candidates]);

    } else {
        http_response_code(405);
        echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>

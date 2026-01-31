<?php
// API Endpoint for Performance Reviews
// Path: integration/performance.php

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

    if ($method === 'GET') {
        // --- GET: Fetch Performance Reviews ---
        if (isset($_GET['employee_id'])) {
            $stmt = $conn->prepare("SELECT * FROM performance_reviews WHERE employee_id = ? ORDER BY review_date DESC");
            $stmt->execute([$_GET['employee_id']]);
            $reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['status' => 'success', 'count' => count($reviews), 'data' => $reviews]);
        } elseif (isset($_GET['id'])) {
            $stmt = $conn->prepare("SELECT * FROM performance_reviews WHERE id = ?");
            $stmt->execute([$_GET['id']]);
            $review = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($review) {
                echo json_encode(['status' => 'success', 'data' => $review]);
            } else {
                http_response_code(404);
                echo json_encode(['status' => 'error', 'message' => 'Review not found']);
            }
        } else {
            $stmt = $conn->prepare("SELECT pr.*, e.name as employee_name FROM performance_reviews pr LEFT JOIN employees e ON pr.employee_id = e.id ORDER BY pr.review_date DESC LIMIT 50");
            $stmt->execute();
            $reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['status' => 'success', 'count' => count($reviews), 'data' => $reviews]);
        }

    } elseif ($method === 'POST') {
        // --- POST: Create New Performance Review ---
        $input = json_decode(file_get_contents('php://input'), true);

        // Required fields
        $employee_id = $input['employee_id'] ?? null;
        $review_date = $input['review_date'] ?? date('Y-m-d');
        $review_type = $input['review_type'] ?? 'Monthly';
        
        // Score fields
        $kpi_score = $input['kpi_score'] ?? 0;
        $attendance_score = $input['attendance_score'] ?? 0;
        $supervisor_quality_rating = $input['supervisor_quality_rating'] ?? 0;
        $productivity_score = $input['productivity_score'] ?? 0;
        
        // Optional fields
        $promotion_recommended = $input['promotion_recommended'] ?? 0;
        $comments = $input['comments'] ?? '';

        if (!$employee_id) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Missing required field: employee_id']);
            exit;
        }

        $stmt = $conn->prepare("INSERT INTO performance_reviews 
            (employee_id, review_date, review_type, kpi_score, attendance_score, supervisor_quality_rating, productivity_score, promotion_recommended, comments) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
        if ($stmt->execute([
            $employee_id, 
            $review_date, 
            $review_type, 
            $kpi_score, 
            $attendance_score, 
            $supervisor_quality_rating, 
            $productivity_score, 
            $promotion_recommended, 
            $comments
        ])) {
            $newId = $conn->lastInsertId();
            http_response_code(201);
            echo json_encode(['status' => 'success', 'message' => 'Performance review submitted successfully', 'id' => $newId]);
        } else {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => 'Failed to submit performance review']);
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

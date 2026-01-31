<?php
/**
 * Performance API Receiver
 * Endpoint: performance.php
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Allow POST only
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'status' => 'error',
        'message' => 'POST method required'
    ]);
    exit;
}

// DB connection - Adjusted for HR1 project structure
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

// Read raw JSON input
$input = json_decode(file_get_contents('php://input'), true);

// Validate JSON
if (!$input) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid JSON payload'
    ]);
    exit;
}

// Required fields
$required = [
    'employee_id',
    'review_date',
    'review_type',
    'kpi_score',
    'attendance_score',
    'supervisor_quality_rating',
    'productivity_score',
    'promotion_recommended'
];

foreach ($required as $field) {
    if (!isset($input[$field])) {
        http_response_code(422);
        echo json_encode([
            'status' => 'error',
            'message' => "Missing field: $field"
        ]);
        exit;
    }
}

try {
    // Insert into DB
    $sql = "
    INSERT INTO performance_reviews
    (
        employee_id,
        review_date,
        review_type,
        kpi_score,
        attendance_score,
        supervisor_quality_rating,
        productivity_score,
        promotion_recommended,
        comments
    )
    VALUES
    (
        :employee_id,
        :review_date,
        :review_type,
        :kpi_score,
        :attendance_score,
        :supervisor_quality_rating,
        :productivity_score,
        :promotion_recommended,
        :comments
    )
    ";

    $stmt = $conn->prepare($sql);

    $stmt->execute([
        ':employee_id'                 => $input['employee_id'],
        ':review_date'                 => $input['review_date'],
        ':review_type'                 => $input['review_type'],
        ':kpi_score'                   => $input['kpi_score'],
        ':attendance_score'            => $input['attendance_score'],
        ':supervisor_quality_rating'   => $input['supervisor_quality_rating'],
        ':productivity_score'          => $input['productivity_score'],
        ':promotion_recommended'       => $input['promotion_recommended'],
        ':comments'                    => $input['comments'] ?? null
    ]);

    // Success response
    echo json_encode([
        'status' => 'success',
        'message' => 'Performance review saved successfully',
        'employee_id' => $input['employee_id'],
        'inserted_id' => $conn->lastInsertId()
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}

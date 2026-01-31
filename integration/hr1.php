<?php
// Core HR1 Receiver API
// Path: integration/hr1.php

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
        $input = json_decode(file_get_contents('php://input'), true);
        
        // Simple dispatcher based on 'type'
        $type = $input['type'] ?? 'general';
        
        switch ($type) {
            case 'ping':
                echo json_encode(['status' => 'success', 'message' => 'HR1 is online', 'timestamp' => date('Y-m-d H:i:s')]);
                break;
                
            case 'log':
                // Optional: Log external messages to a file or DB
                echo json_encode(['status' => 'success', 'message' => 'Log received']);
                break;
                
            default:
                echo json_encode(['status' => 'success', 'message' => 'Message received by HR1', 'received_at' => date('Y-m-d H:i:s')]);
        }
        
    } elseif ($method === 'GET') {
        echo json_encode(['status' => 'success', 'message' => 'HR1 API (Recruitment) is operational']);
    } else {
        http_response_code(405);
        echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>

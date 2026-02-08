<?php
session_start();
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['Email'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Get the raw POST data
$jsonData = file_get_contents('php://input');
$data = json_decode($jsonData, true);

if (!$data) {
    echo json_encode(['success' => false, 'message' => 'Invalid data provided']);
    exit;
}

// HR3 API Configuration
$apiUrl = 'https://hr3.cranecali-ms.com/api/hr/employees/sync';
$apiKey = 'HR_EMPLOYEE_SYNC_API_KEY_PLACEHOLDER'; // User should replace this with actual key

// Prepare the payload for HR3
$profile_image = null;
if (!empty($data['image_path'])) {
    $fullPath = '../Main/' . ltrim($data['image_path'], './');
    if (file_exists($fullPath)) {
        $type = pathinfo($fullPath, PATHINFO_EXTENSION);
        $imgData = file_get_contents($fullPath);
        $profile_image = 'data:image/' . $type . ';base64,' . base64_encode($imgData);
    }
}

$payload = [
    'source_system' => 'HR1',
    'sent_at' => date('Y-m-d\TH:i:s\Z'),
    'employees' => [
        [
            'external_id' => 'HR1-EMP-' . ($data['id'] ?? uniqid()),
            'employee_id' => $data['employee_id'] ?? ('EMP-' . str_pad($data['id'] ?? rand(1,9999), 4, '0', STR_PAD_LEFT)),
            'email' => $data['email'] ?? '',
            'department' => $data['department'] ?? 'General',
            'position' => $data['position'] ?? 'Staff',
            'manager_name' => $data['manager_name'] ?? 'N/A',
            'hire_date' => $data['hire_date'] ?? date('Y-m-d'),
            'employment_type' => $data['employment_type'] ?? 'Regular',
            'work_location' => $data['work_location'] ?? 'Main Office',
            'emergency_contact_name' => $data['emergency_contact_name'] ?? 'N/A',
            'emergency_contact_phone' => $data['emergency_contact_phone'] ?? 'N/A',
            'address' => $data['address'] ?? 'N/A',
            'status' => 'Active',
            'profile_image' => $profile_image
        ]
    ]
];

// Initialize cURL
$ch = curl_init($apiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'X-API-KEY: ' . $apiKey
]);

// Execute cURL
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

if ($curlError) {
    echo json_encode([
        'success' => false, 
        'message' => 'Connection error: ' . $curlError
    ]);
    exit;
}

// The API returns a response
if ($httpCode >= 200 && $httpCode < 300) {
    echo json_encode([
        'success' => true, 
        'message' => 'Successfully synced to HR3!',
        'api_response' => json_decode($response, true)
    ]);
} else {
    echo json_encode([
        'success' => false, 
        'message' => 'HR3 API returned error code ' . $httpCode,
        'details' => json_decode($response, true) ?: $response
    ]);
}

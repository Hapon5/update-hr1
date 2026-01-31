<?php
// API Sender for Performance Reviews
// Path: integration/send_performance.php

// 1. Define the API Endpoint URL
$apiUrl = 'http://localhost/hr1/integration/performance.php';

// 2. Prepare the Data Payload (JSON)
// Note: employee_id should exist in your 'employees' table.
// In development, '1' refers to the first dummy employee (Andy Ferrer).
$data = [
    'employee_id' => 1,
    'review_date' => date('Y-m-d'),
    'review_type' => 'Monthly',
    'kpi_score' => 92.5,
    'attendance_score' => 98.0,
    'supervisor_quality_rating' => 5,
    'productivity_score' => 95.5,
    'promotion_recommended' => 1,
    'comments' => 'Integrated performance review sent via API. Outstanding technical contributions.'
];

// 3. Initialize cURL
$ch = curl_init($apiUrl);

// 4. Set cURL Options
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); 
curl_setopt($ch, CURLOPT_POST, true);           
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data)); 
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Content-Length: ' . strlen(json_encode($data))
]);

// 5. Execute Request
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

// 6. Check for Errors
if (curl_errno($ch)) {
    echo 'cURL Error: ' . curl_error($ch);
} else {
    echo "<h3>Performance API Request Sent</h3>";
    echo "<strong>URL:</strong> $apiUrl<br>";
    echo "<strong>HTTP Status:</strong> $httpCode<br>";
    echo "<strong>Response:</strong><pre>" . htmlspecialchars($response) . "</pre>";
}

// 7. Close cURL Session
curl_close($ch);
?>

<?php
// API Sender for HR1 Core
// Path: integration/send_hr1.php

// 1. Define the API Endpoint URL
$apiUrl = 'http://localhost/hr1/integration/hr1.php';

// 2. Prepare the Data Payload (JSON)
$data = [
    'type' => 'ping',
    'source' => 'External System',
    'message' => 'Hello from HR2/3/4'
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
    echo "<h3>HR1 Core API Request Sent</h3>";
    echo "<strong>URL:</strong> $apiUrl<br>";
    echo "<strong>HTTP Status:</strong> $httpCode<br>";
    echo "<strong>Response:</strong><pre>" . htmlspecialchars($response) . "</pre>";
}

// 7. Close cURL Session
curl_close($ch);
?>

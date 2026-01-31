<?php
// API Sender for New Hired Employees
// Path: integration/send_new_hired.php

// 1. Define the API Endpoint URL
// Adjust 'localhost/hr1' if your local setup is different
$apiUrl = 'http://localhost/hr1/integration/new_hired.php';

// 2. Prepare the Data Payload (JSON)
$data = [
    'first_name' => 'Jane',
    'last_name' => 'Doe',
    'email' => 'jane.doe@example.com',
    'position' => 'Marketing Specialist',
    'phone' => '09123456789',
    'department' => 'Marketing',
    'date_hired' => date('Y-m-d'),
    'salary' => 25000.00,
    'status' => 'Active',
    // Optional: Add a base64 image string here if needed
    // 'base64_image' => 'data:image/png;base64,...'
];

// 3. Initialize cURL
$ch = curl_init($apiUrl);

// 4. Set cURL Options
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // Return response as string
curl_setopt($ch, CURLOPT_POST, true);           // Use POST method
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data)); // Send JSON data
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
    echo "<h3>API Request Sent</h3>";
    echo "<strong>URL:</strong> $apiUrl<br>";
    echo "<strong>HTTP Status:</strong> $httpCode<br>";
    echo "<strong>Response:</strong><pre>" . htmlspecialchars($response) . "</pre>";
}

// 7. Close cURL Session
curl_close($ch);
?>

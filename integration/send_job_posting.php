<?php
// API Sender for Job Postings
// Path: integration/send_job_posting.php

// 1. Define the API Endpoint URL
$apiUrl = 'http://localhost/hr1/integration/job_posting.php';

// 2. Prepare the Data Payload (JSON)
$data = [
    'title' => 'Senior Developer',
    'position' => 'Full Stack Engineer',
    'location' => 'Makati City',
    'requirements' => '5+ years experience, PHP, JS, SQL',
    'contact' => 'hr@company.com',
    'platform' => 'LinkedIn Integration', // Optional
    'status' => 'active'
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
    echo "<h3>Job Posting API Request Sent</h3>";
    echo "<strong>URL:</strong> $apiUrl<br>";
    echo "<strong>HTTP Status:</strong> $httpCode<br>";
    echo "<strong>Response:</strong><pre>" . htmlspecialchars($response) . "</pre>";
}

// 7. Close cURL Session
curl_close($ch);
?>

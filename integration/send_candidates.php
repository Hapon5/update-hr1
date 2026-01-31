<?php
// API Sender for Candidate Submissions
// Path: integration/send_candidates.php

// 1. Define the API Endpoint URL
$apiUrl = 'http://localhost/hr1/integration/candidates.php';

// 2. Prepare the Data Payload (JSON)
$data = [
    'full_name' => 'John Applicant',
    'email' => 'john.app@example.com',
    'phone' => '09876543210',
    'job_id' => 1, // Make sure this job ID exists in your job_postings table
    'resume_link' => 'http://example.com/resume.pdf',
    'message' => 'I am very interested in this position.'
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
    echo "<h3>Candidate API Request Sent</h3>";
    echo "<strong>URL:</strong> $apiUrl<br>";
    echo "<strong>HTTP Status:</strong> $httpCode<br>";
    echo "<strong>Response:</strong><pre>" . htmlspecialchars($response) . "</pre>";
}

// 7. Close cURL Session
curl_close($ch);
?>

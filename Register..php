<?php
// Register..php - Backend logic for user registration with Verification

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Ensure $conn is available
if (!isset($conn)) {
    $pathsToTry = [
        __DIR__ . '/Connections.php',
        __DIR__ . '/../Connections.php',
        __DIR__ . '/Database/Connections.php',
    ];
    foreach ($pathsToTry as $path) {
        if (file_exists($path)) {
            require_once $path;
            break;
        }
    }
}

// Include PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

$registerError = "";
$registerSuccess = "";

// Function to send verification email
function sendVerificationEmail($email, $name, $code)
{
    $mail = new PHPMailer(true);
    try {
        // SMTP Settings
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'linbilcelestre31@gmail.com';
        $mail->Password = 'bivb opss calj bfsd'; // App Password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        $mail->setFrom('linbilcelestre31@gmail.com', 'HR1-CRANE Verification');
        $mail->addAddress($email, $name);

        $mail->isHTML(true);
        $mail->Subject = 'Your Verification Code';
        $mail->Body = "
            <div style='font-family: Arial, sans-serif; padding: 20px; background-color: #f4f4f4;'>
                <div style='background-color: white; padding: 20px; border-radius: 8px; max-width: 500px; margin: auto;'>
                    <h2 style='color: #333; text-align: center;'>Verify Your Account</h2>
                    <p>Hi $name,</p>
                    <p>Thank you for registering. Please use the following code to complete your verification:</p>
                    <div style='font-size: 24px; font-weight: bold; text-align: center; color: #4F46E5; padding: 10px; border: 1px dashed #4F46E5; margin: 20px 0;'>
                        $code
                    </div>
                    <p>If you did not request this, please ignore this email.</p>
                </div>
            </div>
        ";

        $mail->send();
        return true;
    } catch (Exception $e) {
        // Log error or handle it
        return false;
    }
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['form_type']) && $_POST['form_type'] === 'register') {

    // 1. Sanitize and Validate Input
    $name = trim($_POST['name'] ?? '');
    $email = strtolower(trim($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';
    $role = isset($_POST['role']) && in_array($_POST['role'], ['0', '1']) ? $_POST['role'] : '0';

    if (empty($name) || empty($email) || empty($password)) {
        $registerError = "All fields are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $registerError = "Invalid email format.";
    } else {
        try {
            // 2. Check if email already exists
            $checkStmt = $conn->prepare("SELECT LoginID FROM logintbl WHERE Email = :email");
            $checkStmt->execute(['email' => $email]);

            if ($checkStmt->rowCount() > 0) {
                $registerError = "Email is already registered.";
            } else {
                // 3. Generate Verification Code
                $verificationCode = rand(100000, 999999);

                // 4. Store Data in Session (Temporary)
                $_SESSION['registration_data'] = [
                    'name' => $name,
                    'email' => $email,
                    'password' => $password, // Will hash later
                    'role' => $role,
                    'code' => $verificationCode
                ];

                // 5. Send Email
                if (sendVerificationEmail($email, $name, $verificationCode)) {
                    // 6. Redirect to Verification Page
                    header("Location: Verification.php");
                    exit;
                } else {
                    $registerError = "Failed to send verification email. Please try again.";
                }
            }
        } catch (Exception $e) {
            $registerError = "Registration error: " . $e->getMessage();
        }
    }
}
?>
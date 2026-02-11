<?php
session_start();

// === Reliable Connections.php Include ===
$pathsToTry = [
    __DIR__ . '/Connections.php',
    __DIR__ . '/../Connections.php',
    __DIR__ . '/Database/Connections.php',
];

$connectionsIncluded = false;
foreach ($pathsToTry as $path) {
    if (file_exists($path)) {
        require_once $path;
        $connectionsIncluded = true;
        break;
    }
}

if (!$connectionsIncluded || !isset($conn)) {
    die("Critical Error: Unable to load database connection.");
}

// Redirect if no pending action
if (!isset($_SESSION['pending_otp_user']) && !isset($_SESSION['registration_data'])) {
    header("Location: login.php");
    exit;
}

include 'Register..php'; // For sendVerificationEmail function

$error = "";
$success = "";
$isRegistration = isset($_SESSION['registration_data']);
$pendingUser = $isRegistration ? $_SESSION['registration_data'] : $_SESSION['pending_otp_user'];
$email = $isRegistration ? $pendingUser['email'] : $pendingUser['Email'];
$otpToMatch = $isRegistration ? $pendingUser['code'] : $pendingUser['otp'];

// Handle OTP Verification
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['form_type']) && $_POST['form_type'] === 'verify_otp') {
    $inputOtp = trim($_POST['otp'] ?? '');
    
    if (empty($inputOtp)) {
        $error = "Please enter the OTP code.";
    } elseif ($inputOtp != $otpToMatch) {
        $error = "Invalid OTP code. Please try again.";
    } elseif (!$isRegistration && time() > $pendingUser['otp_expiry']) {
        $error = "OTP code has expired. Please resend a new one.";
    } else {
        // Verification Successful
        if ($isRegistration) {
            // Registration Mode: Insert to DB
            $hashedPassword = password_hash($pendingUser['password'], PASSWORD_DEFAULT);
            $accountType = $pendingUser['role'];
            $name = $pendingUser['name'];

            try {
                $conn->beginTransaction();

                // Insert into logintbl
                $stmt = $conn->prepare("INSERT INTO logintbl (Email, Password, Account_type) VALUES (:email, :password, :accountType)");
                $stmt->execute([
                    'email' => $email,
                    'password' => $hashedPassword,
                    'accountType' => $accountType
                ]);

                // Insert into candidates
                $stmtCandidate = $conn->prepare("INSERT INTO candidates (full_name, email, status, source, job_title, position, contact_number, experience_years, age, address) VALUES (:name, :email, 'new', 'Online Registration', :jobTitle, :position, 'N/A', 0, 0, 'N/A')");
                $jobTitle = ($accountType == '0' || $accountType == '1') ? 'Administrator' : 'Employee';
                $position = ($accountType == '0' || $accountType == '1') ? 'System Controller' : 'Staff';
                $stmtCandidate->execute([
                    'name' => $name,
                    'email' => $email,
                    'jobTitle' => $jobTitle,
                    'position' => $position
                ]);

                $conn->commit();

                // Set login session immediately after registration
                session_regenerate_id(true);
                $_SESSION['Email'] = $email;
                $_SESSION['Account_type'] = $accountType;
                $_SESSION['GlobalName'] = $name;

                // Clear temporary session
                unset($_SESSION['registration_data']);
                unset($_SESSION['otp_send_failed']); // Clear fallback flag

            } catch (Exception $e) {
                if ($conn->inTransaction()) $conn->rollBack();
                $error = "System Error: " . $e->getMessage();
                return; // Stop execution
            }
        } else {
            // Login Mode: Just set sessions
            session_regenerate_id(true);
            $_SESSION['Email'] = $pendingUser['Email'];
            $_SESSION['Account_type'] = $pendingUser['Account_type'];

            // Fetch Global Name
            $stmtName = $conn->prepare("SELECT full_name FROM candidates WHERE email = :email LIMIT 1");
            $stmtName->execute(['email' => $pendingUser['Email']]);
            $nameRow = $stmtName->fetch(PDO::FETCH_ASSOC);
            $_SESSION['GlobalName'] = $nameRow ? $nameRow['full_name'] : "System User";

            // Clear temporary session
            unset($_SESSION['pending_otp_user']);
            unset($_SESSION['otp_send_failed']); // Clear fallback flag
        }

        // Common Routing based on account type
        $accountType = $_SESSION['Account_type'];
        if ($accountType == 1) {
            header('Location: Main/Dashboard.php');
        } elseif ($accountType == 0) {
            header('Location: Super-admin/Dashboard.php');
        } elseif ($accountType == 2) {
            header('Location: Staff/Dashboard.php');
        } elseif ($accountType == 3) {
            header('Location: Employee/Dashboard.php');
        } else {
            header('Location: landing.php');
        }
        exit;
    }
}

// Handle OTP Resend
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['form_type']) && $_POST['form_type'] === 'resend_otp') {
    $currentTime = time();
    
    // MODIFIED: Removed 60-second wait. User can resend immediately.
    if (false) { // Condition disabled
    } else {
        // MODIFIED: Fixed OTP for admin account
        $newOtp = ($email === 'admin@gmail.com') ? "123456" : rand(100000, 999999);
        
        if ($isRegistration) {
            $_SESSION['registration_data']['code'] = $newOtp;
            $_SESSION['registration_data']['last_sent'] = time();
        } else {
            $_SESSION['pending_otp_user']['otp'] = $newOtp;
            $_SESSION['pending_otp_user']['otp_expiry'] = time() + (5 * 60);
            $_SESSION['pending_otp_user']['last_sent'] = time();
        }

        // Fetch Name
        if ($isRegistration) {
            $userName = $pendingUser['name'];
        } else {
            $stmtName = $conn->prepare("SELECT full_name FROM candidates WHERE email = :email LIMIT 1");
            $stmtName->execute(['email' => $email]);
            $nameRow = $stmtName->fetch(PDO::FETCH_ASSOC);
            $userName = $nameRow ? $nameRow['full_name'] : "User";
        }

        // MODIFIED: Bypass actual email sending for admin@gmail.com
        if ($email === 'admin@gmail.com' || sendVerificationEmail($email, $userName, $newOtp)) {
            $success = "A new OTP code has been sent to your email.";
            // Update local variable for Match check without refresh
            $otpToMatch = $newOtp; 
        } else {
            $error = "Failed to send OTP. Error: " . ($_SESSION['last_mail_error'] ?? 'Check connection/SMTP settings.');
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Login | HR1</title>
    <link rel="icon" type="image/x-icon" href="Image/logo.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { font-family: 'Poppins', sans-serif; }
        .bg-crane {
            background-image: url('Image/crane.jpg');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
        }
    </style>
</head>
<body class="bg-gray-100 flex justify-center items-center h-screen bg-crane">
    <div class="bg-white p-8 rounded-2xl shadow-2xl max-w-md w-full text-center relative overflow-hidden animate-fade-in shadow-[0_20px_50px_rgba(0,0,0,0.3)] border border-white/20 backdrop-blur-sm bg-white/95">
        <div class="absolute top-0 left-0 w-full h-2 bg-gradient-to-r from-black via-gray-800 to-black"></div>

        <div class="mb-8">
            <div class="w-20 h-20 bg-black rounded-full flex items-center justify-center mx-auto mb-4 text-white text-3xl shadow-lg shadow-black/20">
                <i class="fas fa-shield-alt"></i>
            </div>
            <h2 class="text-3xl font-bold text-gray-900 tracking-tight">Security Code</h2>
            <p class="text-sm text-gray-500 mt-3 font-medium">
                We've sent a 6-digit code to <br>
                <span class="text-black font-semibold"><?php echo htmlspecialchars($email); ?></span>
            </p>
        </div>

        <?php if ($error): ?>
            <div class="bg-red-50 text-red-600 text-sm p-4 rounded-xl mb-6 border border-red-100 flex items-center gap-3">
                <i class="fas fa-exclamation-circle text-lg"></i>
                <span class="text-left font-medium"><?php echo htmlspecialchars($error); ?></span>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="bg-green-50 text-green-600 text-sm p-4 rounded-xl mb-6 border border-green-100 flex items-center gap-3">
                <i class="fas fa-check-circle text-lg"></i>
                <span class="text-left font-medium"><?php echo htmlspecialchars($success); ?></span>
            </div>
        <?php endif; ?>

        <form method="POST" class="space-y-6">
            <input type="hidden" name="form_type" value="verify_otp">
            <div>
                <label class="block text-xs font-bold text-gray-400 uppercase tracking-widest mb-3">Enter Verification Code</label>
                <input type="text" name="otp" maxlength="6" 
                    class="w-full text-center text-4xl tracking-[0.4em] font-black py-4 bg-gray-50 border-2 border-gray-100 rounded-xl focus:ring-2 focus:ring-black focus:border-black outline-none transition-all placeholder-gray-200" 
                    placeholder="000000" required autofocus>
            </div>

            <button type="submit" 
                class="w-full py-4 bg-black text-white font-bold rounded-xl hover:bg-gray-900 transition-all active:scale-[0.98] shadow-xl shadow-black/10 text-lg">
                Verify & Sign In
            </button>
        </form>

        <div class="mt-8 pt-6 border-t border-gray-100">
            <form method="POST" id="resendForm">
                <input type="hidden" name="form_type" value="resend_otp">
                <p class="text-sm text-gray-500 font-medium">
                    Didn't receive the code? <br>
                    <button type="submit" id="resendBtn" 
                        class="text-black font-bold hover:underline mt-2 transition-all">
                        Resend Code
                    </button>
                </p>
            </form>
            <a href="login.php" class="inline-block mt-4 text-xs text-gray-400 hover:text-black transition-colors">
                <i class="fas fa-arrow-left mr-1"></i> Back to Login
            </a>
        </div>
    </div>

    <script>
        // MODIFIED: Timer removed.
    </script>
</body>
</html>

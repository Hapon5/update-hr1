<?php
session_start();
include 'Database/Connections.php';

// Check if registration data exists
if (!isset($_SESSION['registration_data'])) {
    header("Location: login.php");
    exit;
}

$email = $_SESSION['registration_data']['email'];
$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $inputCode = "";
    if (isset($_POST['otp'])) {
        $inputCode = trim($_POST['otp']);
    } elseif (isset($_POST['code']) && is_array($_POST['code'])) {
        $inputCode = implode('', $_POST['code']);
    }

    if ($inputCode == $_SESSION['registration_data']['code']) {
        // Verification Successful - Insert to DB
        $regData = $_SESSION['registration_data'];
        $hashedPassword = password_hash($regData['password'], PASSWORD_DEFAULT);
        $accountType = $regData['role'];
        $name = $regData['name'];

        try {
            $conn->beginTransaction();

            // Insert into logintbl
            $stmt = $conn->prepare("INSERT INTO logintbl (Email, Password, Account_type) VALUES (:email, :password, :accountType)");
            $stmt->execute([
                'email' => $email,
                'password' => $hashedPassword,
                'accountType' => $accountType
            ]);

            // Insert into candidates (if applicable - usually for all users for profile info)
            // Even admins might need an employee record, but here we stick to request.
            // If role 0, definitely candidate.

            // Request to REMOVE connection to candidates table:
            // Insert into candidates table to store user name
            $stmtCandidate = $conn->prepare("INSERT INTO candidates (full_name, email, status, source) VALUES (:name, :email, 'new', 'Online Registration')");
            $stmtCandidate->execute([
                'name' => $name,
                'email' => $email
            ]);

            $conn->commit();

            // Clear Session
            unset($_SESSION['registration_data']);
            $_SESSION['register_success'] = "Verification successful! You can now login.";

            header("Location: login.php");
            exit;

        } catch (Exception $e) {
            $conn->rollBack();
            $error = "System Error: " . $e->getMessage();
        }
    } else {
        $error = "Invalid verification code. Please try again.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Account | HR1-CRANE</title>
    <link rel="icon" type="image/x-icon" href="Image/logo.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
        }
    </style>
</head>

<body class="bg-gray-100 flex justify-center items-center h-screen"
    style="background-image: url('Image/crane.jpg'); background-size: cover; background-position: center;">
    <div class="bg-white p-8 rounded-2xl shadow-2xl max-w-md w-full text-center relative overflow-hidden">
        <div class="absolute top-0 left-0 w-full h-2 bg-gradient-to-r from-blue-500 to-indigo-600"></div>

        <div class="mb-6">
            <div
                class="w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-4 text-blue-600 text-2xl">
                <i class="fas fa-shield-alt"></i>
            </div>
            <h2 class="text-2xl font-bold text-gray-800">Verification Required</h2>
            <p class="text-sm text-gray-500 mt-2">We've sent a code to <span class="font-semibold text-gray-700">
                    <?php echo htmlspecialchars($email); ?>
                </span></p>
        </div>

        <?php if ($error): ?>
            <div class="bg-red-50 text-red-600 text-sm p-3 rounded-lg mb-4 border border-red-100">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="space-y-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Enter Verification Code</label>
                <input type="text" name="otp" maxlength="6"
                    class="w-full text-center text-3xl tracking-[0.5em] font-bold py-3 bg-gray-50 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition-all uppercase placeholder-gray-300"
                    placeholder="000000" required autofocus>
            </div>

            <button type="submit"
                class="w-full py-3 bg-black text-white font-bold rounded-lg hover:bg-gray-800 transition-transform active:scale-95 shadow-lg">
                Verify Account
            </button>
        </form>

        <div class="mt-6 text-sm">
            <p class="text-gray-500">Didn't receive the email? <a href="login.php"
                    class="text-indigo-600 font-medium hover:underline">Try Again / Register</a></p>
        </div>
    </div>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>
</body>

</html>
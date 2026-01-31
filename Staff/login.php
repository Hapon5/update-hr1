<?php
session_start();
include('../Database/Connections.php');

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    if (empty($email) || empty($password)) {
        $error = "Please enter both email and password.";
    } else {
        // Fetch user by email only query
        $stmt = $conn->prepare("SELECT * FROM logintbl WHERE Email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        // Check password (hash or plain text legacy)
        $dbPassword = $user['Password'];
        $passwordMatches = false;
        if (strlen($dbPassword) > 20 && str_starts_with($dbPassword, '$')) {
            $passwordMatches = password_verify($password, $dbPassword);
        } else {
            $passwordMatches = ($dbPassword === $password);
        }

        // --- AUTO-FIX for Staff@gmail.com (If currently Admin/Type 1) ---
        if ($user && strtolower($email) === 'staff@gmail.com' && $user['Account_type'] != 2 && $passwordMatches) {
            $update = $conn->prepare("UPDATE logintbl SET Account_type = 2 WHERE LoginID = ?");
            $update->execute([$user['LoginID']]);
            $user['Account_type'] = 2; // Refetch virtually
        }
        // ----------------------------------------------------------------

        if ($user && $passwordMatches) {
            if ($user['Account_type'] == 2) {
                $_SESSION['Email'] = $user['Email'];
                $_SESSION['Account_type'] = 2; // Staff
                header("Location: Dashboard.php");
                exit();
            } else {
                $error = "Invalid credentials. This account is not a Staff account (Type " . $user['Account_type'] . ").";
            }
        } else {
            $error = "Invalid credentials or unauthorized access.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Login</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>body { font-family: 'Poppins', sans-serif; }</style>
</head>
<body class="bg-gray-100 flex items-center justify-center h-screen" style="background-image: url('../Image/building.jpg'); background-size: cover; background-position: center;">
    <div class="bg-white p-8 rounded-lg shadow-xl w-full max-w-md relative overflow-hidden">
        <div class="absolute top-0 left-0 w-full h-2 bg-indigo-600"></div>
        
        <div class="text-center mb-8">
            <h2 class="text-3xl font-bold text-gray-800">Staff Portal</h2>
            <p class="text-gray-500 text-sm mt-1">HR Staff & Management Access</p>
        </div>

        <?php if ($error): ?>
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 text-sm" role="alert">
                <p><?php echo htmlspecialchars($error); ?></p>
            </div>
        <?php endif; ?>

        <form method="POST" class="space-y-6">
            <div>
                <label class="block text-gray-700 text-sm font-bold mb-2">Email Address</label>
                <input type="email" name="email" class="w-full px-4 py-3 rounded-lg bg-gray-50 border border-gray-300 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-all" required autofocus placeholder="staff@company.com">
            </div>
            <div>
                <label class="block text-gray-700 text-sm font-bold mb-2">Password</label>
                <input type="password" name="password" class="w-full px-4 py-3 rounded-lg bg-gray-50 border border-gray-300 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-all" required placeholder="••••••••">
            </div>
            <button type="submit" class="w-full bg-indigo-600 text-white font-bold py-3 rounded-lg hover:bg-indigo-700 transition-colors shadow-lg">
                Sign In
            </button>
        </form>
        <div class="mt-6 text-center text-sm">
            <a href="../login.php" class="text-gray-500 hover:text-gray-800">Back to Main Login</a>
        </div>
    </div>
</body>
</html>

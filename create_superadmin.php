<?php
session_start();

// Database connection
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

$message = "";
$messageType = "";

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name = trim($_POST['name'] ?? '');
    $email = strtolower(trim($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';
    
    if (empty($name) || empty($email) || empty($password)) {
        $message = "All fields are required.";
        $messageType = "error";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Invalid email format.";
        $messageType = "error";
    } else {
        try {
            // Check if email already exists
            $checkStmt = $conn->prepare("SELECT LoginID FROM logintbl WHERE Email = :email");
            $checkStmt->execute(['email' => $email]);
            
            if ($checkStmt->rowCount() > 0) {
                $message = "Email is already registered.";
                $messageType = "error";
            } else {
                // Hash password
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                
                // Begin transaction
                $conn->beginTransaction();
                
                // Insert into logintbl with Account_type = 0 (Super Admin)
                $stmt = $conn->prepare("INSERT INTO logintbl (Email, Password, Account_type) VALUES (:email, :password, 0)");
                $stmt->execute([
                    'email' => $email,
                    'password' => $hashedPassword
                ]);
                
                // Insert into candidates table (for profile)
                $stmtCandidate = $conn->prepare("INSERT INTO candidates (full_name, email, job_title, position, experience_years, age, contact_number, address, status, source) VALUES (:name, :email, :job_title, :position, :experience_years, :age, :contact_number, :address, 'new', 'Super Admin Setup')");
                $stmtCandidate->execute([
                    'name' => $name,
                    'email' => $email,
                    'job_title' => 'Super Admin',
                    'position' => 'Super Admin',
                    'experience_years' => 0,
                    'age' => 0,
                    'contact_number' => 'N/A',
                    'address' => 'N/A'
                ]);
                
                $conn->commit();
                
                $message = "Super Admin account created successfully! You can now login with your credentials.";
                $messageType = "success";
                
                // Clear form
                $name = $email = $password = "";
            }
        } catch (Exception $e) {
            $conn->rollBack();
            $message = "Error creating account: " . $e->getMessage();
            $messageType = "error";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Super Admin - HR1</title>
    <link rel="icon" type="image/x-icon" href="Image/logo.png">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
    </style>
</head>
<body>
    <div class="w-full max-w-md px-6">
        <!-- Logo -->
        <div class="text-center mb-8">
            <div class="inline-block bg-white rounded-full p-4 shadow-2xl mb-4">
                <img src="Image/logo.png" alt="HR1 Logo" class="w-16 h-16">
            </div>
            <h1 class="text-4xl font-bold text-white mb-2">HR1 System</h1>
            <p class="text-white/80 text-sm">Super Admin Account Setup</p>
        </div>

        <!-- Form Card -->
        <div class="bg-white rounded-2xl shadow-2xl p-8">
            <?php if (!empty($message)): ?>
                <div class="mb-6 p-4 rounded-xl <?php echo $messageType === 'success' ? 'bg-green-50 border border-green-200 text-green-700' : 'bg-red-50 border border-red-200 text-red-700'; ?>">
                    <div class="flex items-center gap-3">
                        <i class="fas <?php echo $messageType === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?> text-lg"></i>
                        <span class="text-sm font-medium"><?php echo htmlspecialchars($message); ?></span>
                    </div>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <!-- Full Name -->
                <div class="mb-5">
                    <label class="block text-sm font-bold text-gray-700 mb-2">
                        <i class="fas fa-user mr-2 text-indigo-500"></i>Full Name
                    </label>
                    <input type="text" name="name" value="<?php echo htmlspecialchars($name ?? ''); ?>" required
                        class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition-all"
                        placeholder="Enter your full name">
                </div>

                <!-- Email -->
                <div class="mb-5">
                    <label class="block text-sm font-bold text-gray-700 mb-2">
                        <i class="fas fa-envelope mr-2 text-indigo-500"></i>Email Address
                    </label>
                    <input type="email" name="email" value="<?php echo htmlspecialchars($email ?? ''); ?>" required
                        class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition-all"
                        placeholder="Enter your email">
                </div>

                <!-- Password -->
                <div class="mb-6">
                    <label class="block text-sm font-bold text-gray-700 mb-2">
                        <i class="fas fa-lock mr-2 text-indigo-500"></i>Password
                    </label>
                    <div class="relative">
                        <input type="password" name="password" id="password" required
                            class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition-all pr-12"
                            placeholder="Enter your password">
                        <button type="button" onclick="togglePassword()" 
                            class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 transition-colors">
                            <i class="fas fa-eye" id="toggleIcon"></i>
                        </button>
                    </div>
                </div>

                <!-- Account Type Info -->
                <div class="mb-6 p-4 bg-indigo-50 border border-indigo-200 rounded-xl">
                    <div class="flex items-start gap-3">
                        <i class="fas fa-crown text-indigo-600 text-lg mt-0.5"></i>
                        <div>
                            <p class="text-sm font-bold text-indigo-900 mb-1">Super Admin Account</p>
                            <p class="text-xs text-indigo-700">This account will have full system access with all administrative privileges.</p>
                        </div>
                    </div>
                </div>

                <!-- Submit Button -->
                <button type="submit" 
                    class="w-full bg-gradient-to-r from-indigo-600 to-purple-600 text-white font-bold py-3 rounded-xl hover:from-indigo-700 hover:to-purple-700 transition-all shadow-lg hover:shadow-xl transform hover:-translate-y-0.5">
                    <i class="fas fa-user-plus mr-2"></i>Create Super Admin Account
                </button>

                <!-- Back to Login -->
                <div class="mt-6 text-center">
                    <a href="login.php" class="text-sm text-indigo-600 hover:text-indigo-700 font-medium transition-colors">
                        <i class="fas fa-arrow-left mr-2"></i>Back to Login
                    </a>
                </div>
            </form>
        </div>

        <!-- Security Notice -->
        <div class="mt-6 text-center">
            <p class="text-white/70 text-xs">
                <i class="fas fa-shield-alt mr-1"></i>
                This page should be removed after initial setup for security purposes.
            </p>
        </div>
    </div>

    <script>
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const toggleIcon = document.getElementById('toggleIcon');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.classList.remove('fa-eye');
                toggleIcon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                toggleIcon.classList.remove('fa-eye-slash');
                toggleIcon.classList.add('fa-eye');
            }
        }

        // Auto-hide success message after 5 seconds
        <?php if ($messageType === 'success'): ?>
        setTimeout(() => {
            const successMsg = document.querySelector('.bg-green-50');
            if (successMsg) {
                successMsg.style.transition = 'opacity 0.5s';
                successMsg.style.opacity = '0';
                setTimeout(() => successMsg.remove(), 500);
            }
        }, 5000);
        <?php endif; ?>
    </script>
</body>
</html>

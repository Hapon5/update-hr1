<?php
session_start();

// === Reliable Connections.php Include ===
// Tries multiple common locations
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

// INAYOS: Pinalitan ang $Connections ng $conn
if (!$connectionsIncluded || !isset($conn)) {
    die("Critical Error: Unable to load database connection.");
}

// === Include Registration Backend ===
include 'Register..php';

// === PHP 7 Compatibility: str_starts_with ===
if (!function_exists('str_starts_with')) {
    function str_starts_with($haystack, $needle)
    {
        return $needle !== '' && substr($haystack, 0, strlen($needle)) === $needle;
    }
}

// === Initialize Variables ===
$Email = $Password = "";
$EmailErr = $passwordErr = "";
$loginError = "";
$registerSuccess = "";
if (isset($_SESSION['register_success'])) {
    $registerSuccess = $_SESSION['register_success'];
    unset($_SESSION['register_success']);
}

// === Handle Form Submission ===
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['form_type']) && $_POST['form_type'] === 'login') {
    // Validate Email
    if (empty($_POST["Email"])) {
        $EmailErr = "Email is required";
    } else {
        $Email = strtolower(trim($_POST["Email"]));
        if (!filter_var($Email, FILTER_VALIDATE_EMAIL)) {
            $EmailErr = "Please enter a valid email address";
        }
    }

    // Validate Password
    if (empty($_POST["Password"])) {
        $passwordErr = "Password is required";
    } else {
        $Password = trim($_POST["Password"]);
    }

    // If valid, try logging in
    if (empty($EmailErr) && empty($passwordErr)) {
        try {
            // INAYOS: Pinalitan ang $Connections ng $conn
            $stmt = $conn->prepare("
                SELECT Email, Password, Account_type 
                FROM logintbl 
                WHERE Email = :email 
                LIMIT 1
            ");
            $stmt->execute(['email' => $Email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user) {
                $dbPassword = $user['Password'];
                $accountType = $user['Account_type'];
                $passwordMatches = false;

                if (!empty($dbPassword)) {
                    // Check hashed password or fallback to plaintext match (legacy)
                    if (strlen($dbPassword) > 20 && str_starts_with($dbPassword, '$')) {
                        $passwordMatches = password_verify($Password, $dbPassword);
                    } else {
                        $passwordMatches = hash_equals($dbPassword, $Password);
                    }
                }

                if ($passwordMatches) {
                    // Login successful
                    session_regenerate_id(true);
                    $_SESSION['Email'] = $user['Email'];
                    $_SESSION['Account_type'] = $accountType;

                    // Fetch Global Name (User's Name)
                    // Check candidates table first (as Registration flow saves here)
                    $stmtName = $conn->prepare("SELECT full_name FROM candidates WHERE email = :email LIMIT 1");
                    $stmtName->execute(['email' => $user['Email']]);
                    $nameRow = $stmtName->fetch(PDO::FETCH_ASSOC);

                    if ($nameRow) {
                        $_SESSION['GlobalName'] = $nameRow['full_name'];
                    } else {
                        // Fallback or check other tables if needed
                        $_SESSION['GlobalName'] = "System User";
                    }

                    // Route based on account type
                    // Route based on account type
                    if ($accountType == 1) {
                        // HR Admin
                        header('Location: Main/Dashboard.php');
                    } elseif ($accountType == 0) {
                        // Applicant / User -> Redirecting to Super-admin as requested
                        header('Location: Super-admin/Dashboard.php');
                    } elseif ($accountType == 2) {
                        header('Location: Super-admin/Dashboard.php');
                    } else {
                        // Default Fallback
                        header('Location: landing.php');
                    }
                    exit;
                } else {
                    $passwordErr = "Incorrect password";
                }
            } else {
                $EmailErr = "Email is not registered";
            }
        } catch (Exception $e) {
            $loginError = "Login failed. Please try again.";
        }
    }
}

// === Handle Verification Submission (Integrated) ===
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['form_type']) && $_POST['form_type'] === 'verify') {
    if (isset($_SESSION['registration_data'])) {
        $inputCode = trim($_POST['otp'] ?? '');

        if ($inputCode == $_SESSION['registration_data']['code']) {
            // Verification Successful
            $regData = $_SESSION['registration_data'];
            $hashedPassword = password_hash($regData['password'], PASSWORD_DEFAULT);
            $accountType = $regData['role'];
            $name = $regData['name'];
            $email = $regData['email'];

            try {
                $conn->beginTransaction();

                // Insert into logintbl
                $stmt = $conn->prepare("INSERT INTO logintbl (Email, Password, Account_type) VALUES (:email, :password, :accountType)");
                $stmt->execute([
                    'email' => $email,
                    'password' => $hashedPassword,
                    'accountType' => $accountType
                ]);

                // Insert into candidates (linked by email)
                $stmtCandidate = $conn->prepare("INSERT INTO candidates (full_name, email, status, source) VALUES (:name, :email, 'new', 'Online Registration')");
                $stmtCandidate->execute([
                    'name' => $name,
                    'email' => $email
                ]);

                $conn->commit();

                // Clear Session & Success Message
                unset($_SESSION['registration_data']);
                $_SESSION['register_success'] = "Verification successful! You can now login.";
                header("Location: login.php");
                exit;

            } catch (Exception $e) {
                $conn->rollBack();
                $loginError = "System Error: " . $e->getMessage();
            }
            $showVerifyModal = true; // Keep modal open
        }
    } else {
        $loginError = "No pending registration found.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HR1 - Login</title>
    <link rel="icon" type="image/x-icon" href="Image/logo.png">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Poppins', 'ui-sans-serif', 'system-ui']
                    },
                    colors: {
                        brand: {
                            500: '#0000',
                            600: '#0000'
                        }
                    }
                }
            }
        }
    </script>
    <style>
        @keyframes fade-in {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .animate-fade-in {
            animation: fade-in 0.6s ease-out;
        }

        input:focus {
            outline: none;
        }
    </style>
</head>

<body class="bg-gray-100 flex justify-center items-center flex-col h-screen font-sans"
    style="background-image: url('Image/crane.jpg'); background-size: cover; background-position: center; background-repeat: no-repeat;">

    <div class="container relative bg-white rounded-2xl shadow-2xl overflow-hidden w-full max-w-6xl min-h-[600px]"
        id="container">
        <!-- Register Container (Sign Up) -->
        <div
            class="form-container sign-up-container absolute top-0 h-full transition-all duration-600 ease-in-out left-0 w-1/2 opacity-0 z-1">
            <form method="POST" action=""
                class="bg-white flex items-center justify-center flex-col h-full px-12 text-center">
                <input type="hidden" name="form_type" value="register">
                <h1 class="font-bold text-3xl mb-4">Create Account</h1>
                <?php if (!empty($registerError)): ?>
                    <div class="mb-4 text-red-500 text-sm">
                        <?php echo htmlspecialchars($registerError); ?>
                    </div>
                <?php endif; ?>
                <div class="social-container text-lg mb-4">
                    <a href="#"
                        class="inline-flex justify-center items-center w-10 h-10 rounded-full border border-gray-300 mx-1"><i
                            class="fab fa-facebook-f"></i></a>
                    <a href="#"
                        class="inline-flex justify-center items-center w-10 h-10 rounded-full border border-gray-300 mx-1"><i
                            class="fab fa-google-plus-g"></i></a>
                    <a href="#"
                        class="inline-flex justify-center items-center w-10 h-10 rounded-full border border-gray-300 mx-1"><i
                            class="fab fa-linkedin-in"></i></a>
                </div>
                <span class="text-sm text-gray-400 mb-4">or use your email for registration</span>

                <div class="w-full space-y-4 text-left">
                    <div>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-user text-gray-400"></i>
                            </div>
                            <input type="text" name="name" placeholder="Name"
                                class="w-full pl-10 pr-4 py-3 bg-gray-100 border-none rounded-lg focus:ring-2 focus:ring-brand-500 outline-none"
                                required />
                        </div>
                    </div>
                    <div>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-envelope text-gray-400"></i>
                            </div>
                            <input type="email" name="email" placeholder="Email"
                                class="w-full pl-10 pr-4 py-3 bg-gray-100 border-none rounded-lg focus:ring-2 focus:ring-brand-500 outline-none"
                                required />
                        </div>
                    </div>
                    <div>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-lock text-gray-400"></i>
                            </div>
                            <input type="password" name="password" id="regPassword" placeholder="Password"
                                class="w-full pl-10 pr-10 py-3 bg-gray-100 border-none rounded-lg focus:ring-2 focus:ring-brand-500 outline-none"
                                required />
                            <div class="absolute inset-y-0 right-0 pr-3 flex items-center cursor-pointer text-gray-400 hover:text-gray-600"
                                onclick="togglePassword('regPassword', 'regEyeIcon')">
                                <i class="fas fa-eye" id="regEyeIcon"></i>
                            </div>
                        </div>
                    </div>
                    <div>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-user-tag text-gray-400"></i>
                            </div>
                            <select name="role"
                                class="w-full pl-10 pr-4 py-3 bg-gray-100 border-none rounded-lg focus:ring-2 focus:ring-brand-500 outline-none appearance-none text-gray-500">
                                <option value="0">Applicant / User</option>
                                <option value="1">HR Admin</option>
                            </select>
                            <div class="absolute inset-y-0 right-0 flex items-center px-2 pointer-events-none">
                                <i class="fas fa-chevron-down text-gray-400"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <button
                    class="mt-6 bg-black text-white font-bold py-3 px-12 rounded-full tracking-wider uppercase transform transition-transform duration-80 active:scale-95 focus:outline-none">Sign
                    Up</button>
            </form>
        </div>

        <!-- Login Container (Sign In) -->
        <div
            class="form-container sign-in-container absolute top-0 h-full transition-all duration-600 ease-in-out left-0 w-1/2 z-20">
            <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>"
                class="bg-white flex items-center justify-center flex-col h-full px-12 text-center">
                <input type="hidden" name="form_type" value="login">
                <?php if (!empty($registerSuccess)): ?>
                    <div class="w-full mb-4 p-3 bg-green-50 border border-green-200 rounded-lg text-left">
                        <div class="flex items-center text-sm">
                            <i class="fas fa-check-circle text-green-500 mr-2"></i>
                            <span class="text-green-700">
                                <?php echo htmlspecialchars($registerSuccess); ?>
                            </span>
                        </div>
                    </div>
                <?php endif; ?>
                <div class="inline-flex items-center justify-center w-16 h-16 rounded-full mb-4 bg-black">
                    <i class="fas fa-users text-white text-2xl"></i>
                </div>
                <h1 class="font-bold text-3xl mb-2">Admin System</h1>
                <p class="text-gray-600 mb-6">Sign in to your account</p>

                <?php if (!empty($loginError)): ?>
                    <div class="w-full mb-4 p-3 bg-red-50 border border-red-200 rounded-lg text-left">
                        <div class="flex items-center text-sm">
                            <i class="fas fa-exclamation-circle text-red-500 mr-2"></i>
                            <span class="text-red-700"><?php echo htmlspecialchars($loginError); ?></span>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="w-full space-y-4 text-left">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Email Address</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-envelope text-gray-400"></i>
                            </div>
                            <input type="email" name="Email" value="<?php echo htmlspecialchars($Email); ?>"
                                placeholder="Enter your email"
                                class="w-full pl-10 pr-4 py-3 bg-gray-100 border-none rounded-lg focus:ring-2 focus:ring-brand-500 outline-none <?php echo !empty($EmailErr) ? 'ring-2 ring-red-500' : ''; ?>"
                                required />
                        </div>
                        <?php if (!empty($EmailErr)): ?>
                            <p class="mt-1 text-xs text-red-600 flex items-center"><i
                                    class="fas fa-exclamation-circle mr-1"></i><?php echo htmlspecialchars($EmailErr); ?>
                            </p>
                        <?php endif; ?>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-lock text-gray-400"></i>
                            </div>
                            <input type="password" name="Password" id="loginPassword" placeholder="Enter your password"
                                class="w-full pl-10 pr-10 py-3 bg-gray-100 border-none rounded-lg focus:ring-2 focus:ring-brand-500 outline-none <?php echo !empty($passwordErr) ? 'ring-2 ring-red-500' : ''; ?>"
                                required />
                            <div class="absolute inset-y-0 right-0 pr-3 flex items-center cursor-pointer text-gray-400 hover:text-gray-600"
                                onclick="togglePassword('loginPassword', 'loginEyeIcon')">
                                <i class="fas fa-eye" id="loginEyeIcon"></i>
                            </div>
                        </div>
                        <?php if (!empty($passwordErr)): ?>
                            <p class="mt-1 text-xs text-red-600 flex items-center"><i
                                    class="fas fa-exclamation-circle mr-1"></i><?php echo htmlspecialchars($passwordErr); ?>
                            </p>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="flex items-center justify-between w-full mt-4 mb-6">
                    <div class="flex items-center">
                        <input id="remember-me" name="remember-me" type="checkbox"
                            class="h-4 w-4 text-black focus:ring-black border-gray-300 rounded">
                        <label for="remember-me" class="ml-2 block text-sm text-gray-700">Remember me</label>
                    </div>
                    <div class="text-sm">
                        <!-- Trigger Animation specific for mobile/layout logic if needed -->
                    </div>
                </div>

                <button type="submit"
                    class="bg-black text-white font-bold py-3 px-12 rounded-full tracking-wider uppercase transform transition-transform duration-80 active:scale-95 focus:outline-none w-full">
                    Login
                </button>
                <div class="mt-6 flex flex-col gap-2">
                    <a href="landing.php" class="text-sm text-gray-600 hover:text-black transition-colors">
                        <i class="fas fa-arrow-left mr-1"></i> Back to Home
                    </a>
                    <button type="button" onclick="toggleVerifyModal()"
                        class="text-xs text-indigo-600 font-semibold hover:underline">
                        Verify Account (Enter Code)
                    </button>
                </div>
            </form>
        </div>

        <!-- Overlay Container -->
        <div
            class="overlay-container absolute top-0 left-1/2 w-1/2 h-full overflow-hidden transition-transform duration-600 ease-in-out z-50">
            <div
                class="overlay bg-black text-white relative -left-full h-full w-[200%] transform transition-transform duration-600 ease-in-out bg-gradient-to-br from-gray-900 to-black">
                <!-- Overlay Left (Visible when panel is Right - Verification/Forgot Password context) -->
                <div
                    class="overlay-panel overlay-left absolute flex items-center justify-center flex-col p-10 text-center top-0 h-full w-1/2 transform transition-transform duration-600 ease-in-out -translate-x-[20%]">
                    <h1 class="font-bold text-3xl mb-4">Forgot Password?</h1>
                    <p class="mb-8 text-white/90">
                        Enter your personal details to recover your account instantly.
                    </p>
                    <div class="space-y-4 mb-8 text-left w-full max-w-xs mx-auto">
                        <div class="relative">
                            <input type="email" placeholder="Enter verification email"
                                class="w-full px-4 py-3 bg-white/10 border border-white/20 rounded-lg focus:border-white focus:outline-none text-white placeholder-gray-400" />
                        </div>
                        <button
                            class="w-full py-2 bg-white/20 hover:bg-white/30 rounded-lg text-sm font-semibold transition-colors">Send
                            Reset Link</button>
                    </div>

                    <button
                        class="ghost bg-transparent border border-white text-white font-bold py-3 px-12 rounded-full tracking-wider uppercase transform transition-transform duration-80 active:scale-95 focus:outline-none"
                        id="signIn">
                        Sign In
                    </button>
                </div>

                <!-- Overlay Right (Visible initially on Left - Welcome) -->
                <div
                    class="overlay-panel overlay-right absolute right-0 flex items-center justify-center flex-col p-10 text-center top-0 h-full w-1/2 transform transition-transform duration-600 ease-in-out translate-x-0">
                    <h1 class="font-bold text-3xl mb-4">Welcome to HR1</h1>
                    <p class="mb-8 text-white/90">
                        Streamline your human resources operations with our comprehensive management system.
                    </p>
                    <div class="space-y-2 text-sm text-left mb-8 opacity-80">
                        <div class="flex items-center"><i
                                class="fas fa-check bg-white/20 rounded-full p-1 mr-2 text-xs"></i> Manage employees and
                            candidates</div>
                        <div class="flex items-center"><i
                                class="fas fa-check bg-white/20 rounded-full p-1 mr-2 text-xs"></i> Track performance
                            and analytics</div>
                        <div class="flex items-center"><i
                                class="fas fa-check bg-white/20 rounded-full p-1 mr-2 text-xs"></i> Organize documents
                            and files</div>
                    </div>

                    <p class="mb-4 text-sm">Don't have an account?</p>
                    <button
                        class="ghost bg-transparent border border-white text-white font-bold py-3 px-12 rounded-full tracking-wider uppercase transform transition-transform duration-80 active:scale-95 focus:outline-none"
                        id="signUp">
                        Register
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Custom CSS for Sliding Animation -->
    <style>
        .form-container form {
            background-color: #FFFFFF;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-direction: column;
            padding: 0 50px;
            height: 100%;
            text-align: center;
        }

        /* Animation Logic */
        .container.right-panel-active .sign-in-container {
            transform: translateX(100%);
            opacity: 0;
            z-index: 1;
        }

        .container.right-panel-active .sign-up-container {
            transform: translateX(100%);
            opacity: 1;
            z-index: 5;
            animation: show 0.6s;
        }

        @keyframes show {

            0%,
            49.99% {
                opacity: 0;
                z-index: 1;
            }

            50%,
            100% {
                opacity: 1;
                z-index: 5;
            }
        }

        .container.right-panel-active .overlay-container {
            transform: translateX(-100%);
        }

        .container.right-panel-active .overlay {
            transform: translateX(50%);
        }

        .container.right-panel-active .overlay-left {
            transform: translateX(0);
        }

        .container.right-panel-active .overlay-right {
            transform: translateX(20%);
        }
    </style>

    <!-- Verification Modal (Hidden by Default) -->
    <div id="verifyModal" class="fixed inset-0 z-[100] hidden" aria-labelledby="modal-title" role="dialog"
        aria-modal="true">
        <!-- Backdrop -->
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity backdrop-blur-sm"
            onclick="toggleVerifyModal()"></div>

        <div class="fixed inset-0 z-10 w-screen overflow-y-auto">
            <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">

                <div
                    class="relative transform overflow-hidden rounded-2xl bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-sm border border-gray-100">
                    <div class="bg-white px-4 pb-4 pt-5 sm:p-6 sm:pb-4">
                        <div class="sm:flex sm:items-start justify-center">
                            <div class="mt-3 text-center sm:ml-4 sm:mt-0 sm:text-left w-full">
                                <form method="POST" action="">
                                    <input type="hidden" name="form_type" value="verify">

                                    <div
                                        class="mx-auto flex h-16 w-16 flex-shrink-0 items-center justify-center rounded-full bg-blue-100 sm:mx-0 sm:h-12 sm:w-12 mb-4 mx-auto block">
                                        <i class="fas fa-shield-alt text-blue-600 text-xl"></i>
                                    </div>

                                    <h3 class="text-xl font-bold leading-6 text-gray-900 text-center mb-2"
                                        id="modal-title">Verify Account</h3>
                                    <p class="text-sm text-gray-500 text-center mb-6">Enter the 6-digit code sent to
                                        your email.</p>

                                    <div class="mt-2">
                                        <input type="text" name="otp" maxlength="6"
                                            class="block w-full rounded-md border-0 py-3 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-xl sm:leading-6 text-center tracking-[0.5em] font-bold uppercase"
                                            placeholder="000000" required>
                                    </div>

                                    <button type="submit"
                                        class="mt-6 w-full rounded-md bg-black px-3 py-3 text-sm font-semibold text-white shadow-sm hover:bg-gray-800 transition-all">Verify
                                        Now</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script>
        const signUpButton = document.getElementById('signUp');
        const signInButton = document.getElementById('signIn');
        const container = document.getElementById('container');
        const verifyModal = document.getElementById('verifyModal');

        if (signUpButton && signInButton && container) {
            signUpButton.addEventListener('click', () => {
                container.classList.add("right-panel-active");
            });

            signInButton.addEventListener('click', () => {
                container.classList.remove("right-panel-active");
            });
        }

        // Toggle Password Visibility
        function togglePassword(inputId, iconId) {
            const input = document.getElementById(inputId);
            const icon = document.getElementById(iconId);

            if (input.type === "password") {
                input.type = "text";
                icon.classList.remove("fa-eye");
                icon.classList.add("fa-eye-slash");
            } else {
                input.type = "password";
                icon.classList.remove("fa-eye-slash");
                icon.classList.add("fa-eye");
            }
        }

        // Toggle Verification Modal
        function toggleVerifyModal() {
            verifyModal.classList.toggle('hidden');
        }

        <?php if (isset($showVerifyModal) && $showVerifyModal): ?>
            toggleVerifyModal();
        <?php endif; ?>
    </script>
    <?php if (!empty($registerError)): ?>
        <script>
            // Persist Register View on Error
            document.getElementById('container').classList.add("right-panel-active");
        </script>
    <?php endif; ?>
</body>

</html>
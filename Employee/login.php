<?php
session_start();
// Specifically for Employee folder
$root_path = '../'; 
require_once $root_path . "Database/Connections.php";

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    if (empty($email) || empty($password)) {
        $error = "Please enter both email and password.";
    } else {
        $stmt = $conn->prepare("SELECT * FROM logintbl WHERE Email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user) {
            $dbPassword = $user['Password'];
            $passwordMatches = false;
            
            if (strlen($dbPassword) > 20 && str_starts_with($dbPassword, '$')) {
                $passwordMatches = password_verify($password, $dbPassword);
            } else {
                $passwordMatches = ($dbPassword === $password);
            }

            if ($passwordMatches) {
                if ($user['Account_type'] == 3) {
                     $_SESSION['Email'] = $user['Email'];
                     $_SESSION['Account_type'] = 3; // Employee
                     // Fetch Name for session
                     $stmtName = $conn->prepare("SELECT full_name FROM candidates WHERE email = ? LIMIT 1");
                     $stmtName->execute([$user['Email']]);
                     $nameRow = $stmtName->fetch();
                     $_SESSION['GlobalName'] = $nameRow ? $nameRow['full_name'] : "Employee User";
                     
                     header("Location: Dashboard.php");
                     exit();
                } else {
                     $error = "Invalid credentials. This account is not authorized for the Employee Portal.";
                }
            } else {
                $error = "Incorrect email or password.";
            }
        } else {
            $error = "Account not found.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Login | HR1</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { 
            font-family: 'Poppins', sans-serif; 
            background: #0a0a0b; 
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
            position: relative;
            overflow: hidden;
        }
        .blob {
            position: absolute;
            width: 500px;
            height: 500px;
            background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
            filter: blur(80px);
            border-radius: 50%;
            z-index: -1;
            opacity: 0.15;
            animation: move 20s infinite alternate;
        }
        @keyframes move {
            from { transform: translate(-10%, -10%); }
            to { transform: translate(10%, 10%); }
        }
        .glass-container {
            background: rgba(17, 17, 19, 0.7);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.05);
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
        }
    </style>
</head>
<body>
    <div class="blob"></div>
    <div class="blob" style="right: -10%; top: -10%; background: #4338ca; animation-delay: -5s;"></div>

    <div class="glass-container w-full max-w-lg rounded-[40px] p-10 md:p-16 relative overflow-hidden">
        <!-- Brand -->
        <div class="flex flex-col items-center mb-12">
            <div class="w-16 h-16 bg-indigo-600 rounded-2xl flex items-center justify-center shadow-2xl shadow-indigo-600/20 mb-6 group hover:scale-110 transition-transform">
                <i class="fas fa-lock text-white text-2xl"></i>
            </div>
            <h1 class="text-3xl font-black text-white tracking-tighter uppercase text-center">Hello <span class="text-indigo-500 text-sm align-top ml-1 font-bold">Again!</span></h1>
            <p class="text-gray-500 text-sm mt-2 font-medium tracking-wide">Enter your employee credentials</p>
        </div>

        <?php if ($error): ?>
            <div class="bg-red-500/10 border border-red-500/20 text-red-500 p-4 rounded-2xl mb-8 text-xs font-bold uppercase tracking-widest text-center">
                <i class="fas fa-exclamation-triangle mr-2"></i> <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="space-y-6">
            <div>
                <label class="block text-[10px] font-black text-gray-500 uppercase tracking-[0.2em] mb-3 px-1">Corporate Email</label>
                <div class="relative group">
                    <i class="fas fa-envelope absolute left-5 top-1/2 -translate-y-1/2 text-gray-600 group-focus-within:text-indigo-500 transition-colors"></i>
                    <input type="email" name="email" placeholder="employee@company.com" class="w-full bg-white/5 border border-white/10 rounded-2xl pl-12 pr-6 py-4 text-sm font-medium text-white focus:ring-2 focus:ring-indigo-500/50 outline-none transition-all placeholder:text-gray-700" required autofocus>
                </div>
            </div>

            <div>
                <div class="flex justify-between items-center mb-3 px-1">
                    <label class="text-[10px] font-black text-gray-500 uppercase tracking-[0.2em]">Password</label>
                    <a href="#" class="text-[9px] font-black text-indigo-500 uppercase tracking-widest hover:text-indigo-400">Forgot?</a>
                </div>
                <div class="relative group">
                    <i class="fas fa-key absolute left-5 top-1/2 -translate-y-1/2 text-gray-600 group-focus-within:text-indigo-500 transition-colors"></i>
                    <input type="password" name="password" placeholder="••••••••" class="w-full bg-white/5 border border-white/10 rounded-2xl pl-12 pr-6 py-4 text-sm font-medium text-white focus:ring-2 focus:ring-indigo-500/50 outline-none transition-all placeholder:text-gray-700" required>
                </div>
            </div>

            <div class="pt-4">
                <button type="submit" class="w-full bg-indigo-600 text-white font-black text-[11px] uppercase tracking-[0.3em] py-5 rounded-2xl shadow-xl shadow-indigo-600/20 hover:bg-indigo-700 hover:scale-[1.02] active:scale-95 transition-all">
                    Secure Log In
                </button>
            </div>

            <div class="pt-6 text-center">
                <p class="text-xs text-gray-600 font-medium">New member? 
                    <a href="Register.php" class="text-indigo-400 font-bold hover:text-indigo-300 transition-colors ml-1 uppercase tracking-widest border-b border-indigo-400/30">Create Account</a>
                </p>
                <div class="mt-8">
                    <a href="../login.php" class="text-[10px] font-black text-gray-700 uppercase tracking-widest hover:text-gray-500 transition-colors">
                        <i class="fas fa-arrow-left mr-2"></i> Institutional Login
                    </a>
                </div>
            </div>
        </form>
    </div>
</body>
</html>

<?php
session_start();
// Specifically for Employee folder
$root_path = '../'; 
require_once $root_path . "Database/Connections.php";
require_once $root_path . "Register..php"; // Reuse existing registration logic if suitable, or implement here

$error = "";
$success = "";

// Include PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require $root_path . 'PHPMailer/src/Exception.php';
require $root_path . 'PHPMailer/src/PHPMailer.php';
require $root_path . 'PHPMailer/src/SMTP.php';

$registerError = "";

// Function to send verification email (Local)
function sendVerificationEmail($email, $name, $code)
{
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'linbilcelestre31@gmail.com';
        $mail->Password = 'tzkfuxtqocjawxsi'; // New App Password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port = 465;

        // Bypassing SSL verification for environments with old CA certificates
        $mail->SMTPOptions = array(
            'ssl' => array(
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            )
        );

        $mail->setFrom('linbilcelestre31@gmail.com', 'HR1 Employee Portal');
        $mail->addAddress($email, $name);

        $mail->isHTML(true);
        $mail->Subject = 'Your Employee Verification Code';
        $mail->Body = "
            <div style='font-family: Arial, sans-serif; padding: 20px; background-color: #f4f4f4;'>
                <div style='background-color: white; padding: 20px; border-radius: 8px; max-width: 500px; margin: auto;'>
                    <h2 style='color: #4F46E5; text-align: center;'>Employee Verification</h2>
                    <p>Hi $name,</p>
                    <p>Verify your identity to complete your registration for the Employee Portal:</p>
                    <div style='font-size: 24px; font-weight: bold; text-align: center; color: #4F46E5; padding: 10px; border: 1px dashed #4F46E5; margin: 20px 0;'>
                        $code
                    </div>
                </div>
            </div>
        ";
        $mail->send();
        return true;
    } catch (Exception $e) {
        return false;
    }
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['form_type']) && $_POST['form_type'] === 'register') {
    $name = trim($_POST['name'] ?? '');
    $email = strtolower(trim($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';
    $role = '3'; // Locked to Employee

    if (empty($name) || empty($email) || empty($password)) {
        $registerError = "All fields are required.";
    } else {
        try {
            $check = $conn->prepare("SELECT Email FROM logintbl WHERE Email = ?");
            $check->execute([$email]);
            if ($check->rowCount() > 0) {
                $registerError = "Email is already registered.";
            } else {
                $code = rand(100000, 999999);
                $_SESSION['registration_data'] = [
                    'name' => $name,
                    'email' => $email,
                    'password' => $password,
                    'role' => $role,
                    'code' => $code
                ];

                if (sendVerificationEmail($email, $name, $code)) {
                    header("Location: Verification.php");
                    exit;
                } else {
                    $registerError = "Failed to send email. Check connection.";
                }
            }
        } catch (Exception $e) {
            $registerError = "Error: " . $e->getMessage();
        }
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Registration | HR1</title>
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
            overflow-x: hidden;
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

    <div class="glass-container w-full max-w-xl rounded-[40px] p-10 md:p-16 relative overflow-hidden">
        <!-- Brand -->
        <div class="flex flex-col items-center mb-12">
            <div class="w-16 h-16 bg-indigo-600 rounded-2xl flex items-center justify-center shadow-2xl shadow-indigo-600/20 mb-6 group hover:scale-110 transition-transform">
                <i class="fas fa-user-plus text-white text-2xl"></i>
            </div>
            <h1 class="text-3xl font-black text-white tracking-tighter uppercase text-center">Join the <span class="text-indigo-500">Team</span></h1>
            <p class="text-gray-500 text-sm mt-2 font-medium tracking-wide">Employee Portal Registration</p>
        </div>

        <?php if (isset($registerError) && $registerError): ?>
            <div class="bg-red-500/10 border border-red-500/20 text-red-500 p-4 rounded-2xl mb-8 text-xs font-bold uppercase tracking-widest text-center">
                <i class="fas fa-exclamation-circle mr-2"></i> <?php echo $registerError; ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="" class="space-y-6">
            <input type="hidden" name="form_type" value="register">
            <input type="hidden" name="role" value="3"> <!-- Locked to Employee -->

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-[10px] font-black text-gray-500 uppercase tracking-[0.2em] mb-3 px-1">Full Name</label>
                    <div class="relative group">
                        <i class="fas fa-user absolute left-5 top-1/2 -translate-y-1/2 text-gray-600 group-focus-within:text-indigo-500 transition-colors"></i>
                        <input type="text" name="name" placeholder="John Doe" class="w-full bg-white/5 border border-white/10 rounded-2xl pl-12 pr-6 py-4 text-sm font-medium text-white focus:ring-2 focus:ring-indigo-500/50 outline-none transition-all placeholder:text-gray-700" required>
                    </div>
                </div>

                <div>
                    <label class="block text-[10px] font-black text-gray-500 uppercase tracking-[0.2em] mb-3 px-1">Email Address</label>
                    <div class="relative group">
                        <i class="fas fa-envelope absolute left-5 top-1/2 -translate-y-1/2 text-gray-600 group-focus-within:text-indigo-500 transition-colors"></i>
                        <input type="email" name="email" placeholder="john.doe@company.com" class="w-full bg-white/5 border border-white/10 rounded-2xl pl-12 pr-6 py-4 text-sm font-medium text-white focus:ring-2 focus:ring-indigo-500/50 outline-none transition-all placeholder:text-gray-700" required>
                    </div>
                </div>
            </div>

            <div>
                <label class="block text-[10px] font-black text-gray-500 uppercase tracking-[0.2em] mb-3 px-1">Temporary Password</label>
                <div class="relative group">
                    <i class="fas fa-lock absolute left-5 top-1/2 -translate-y-1/2 text-gray-600 group-focus-within:text-indigo-500 transition-colors"></i>
                    <input type="password" name="password" placeholder="••••••••" class="w-full bg-white/5 border border-white/10 rounded-2xl pl-12 pr-6 py-4 text-sm font-medium text-white focus:ring-2 focus:ring-indigo-500/50 outline-none transition-all placeholder:text-gray-700" required>
                </div>
            </div>

            <div class="pt-4">
                <button type="submit" class="w-full bg-indigo-600 text-white font-black text-[11px] uppercase tracking-[0.3em] py-5 rounded-2xl shadow-xl shadow-indigo-600/20 hover:bg-indigo-700 hover:scale-[1.02] active:scale-95 transition-all">
                    Register Account
                </button>
            </div>

            <div class="pt-6 text-center">
                <p class="text-xs text-gray-600 font-medium">Already have an account? 
                    <a href="login.php" class="text-indigo-400 font-bold hover:text-indigo-300 transition-colors ml-1 uppercase tracking-widest border-b border-indigo-400/30">Sign In</a>
                </p>
                <div class="mt-8">
                    <a href="../landing.php" class="text-[10px] font-black text-gray-700 uppercase tracking-widest hover:text-gray-500 transition-colors">
                        <i class="fas fa-arrow-left mr-2"></i> Back to Home
                    </a>
                </div>
            </div>
        </form>

        <!-- Decorative elements -->
        <div class="absolute -bottom-10 -right-10 w-40 h-40 bg-indigo-500/5 rounded-full blur-3xl"></div>
    </div>
</body>
</html>

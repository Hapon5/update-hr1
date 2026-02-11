<?php
session_start();
$root_path = '../'; 
require_once $root_path . "Database/Connections.php";

if (!isset($_SESSION['registration_data'])) {
    header("Location: login.php");
    exit;
}

$email = $_SESSION['registration_data']['email'];
$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $inputCode = trim($_POST['otp'] ?? '');

    if ($inputCode == $_SESSION['registration_data']['code']) {
        $regData = $_SESSION['registration_data'];
        $hashedPassword = password_hash($regData['password'], PASSWORD_DEFAULT);
        $accountType = 3; // Enforced for Employee folder registration
        $name = $regData['name'];

        try {
            $conn->beginTransaction();

            $stmt = $conn->prepare("INSERT INTO logintbl (Email, Password, Account_type) VALUES (?, ?, ?)");
            $stmt->execute([$email, $hashedPassword, $accountType]);

            // Sync name to candidates
            $stmtCandidate = $conn->prepare("INSERT INTO candidates (full_name, email, job_title, position, experience_years, age, contact_number, address, status, source) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'new', 'Employee Registration')");
            $stmtCandidate->execute([$name, $email, 'Employee', 'Employee', 0, 0, 'N/A', 'N/A']);

            $conn->commit();

            unset($_SESSION['registration_data']);
            $_SESSION['register_success'] = "Verification successful! You can now login.";
            header("Location: login.php?success=1");
            exit;

        } catch (Exception $e) {
            $conn->rollBack();
            $error = "System Error: " . $e->getMessage();
        }
    } else {
        $error = "Invalid verification code.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Account | HR1 Employee</title>
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

    <div class="glass-container w-full max-w-lg rounded-[40px] p-10 md:p-16 relative overflow-hidden text-center">
        <div class="flex flex-col items-center mb-10">
            <div class="w-20 h-20 bg-indigo-500/10 rounded-full flex items-center justify-center border border-indigo-500/20 mb-6">
                <i class="fas fa-shield-alt text-indigo-500 text-3xl"></i>
            </div>
            <h1 class="text-3xl font-black text-white tracking-tighter uppercase">Check Your <span class="text-indigo-500 uppercase tracking-widest text-sm block mt-1 font-bold">Email</span></h1>
            <p class="text-gray-500 text-sm mt-4 font-medium tracking-wide">Enter the 6-digit code sent to<br><span class="text-gray-300 font-bold"><?php echo htmlspecialchars($email); ?></span></p>
        </div>

        <?php if ($error): ?>
            <div class="bg-red-500/10 border border-red-500/20 text-red-500 p-4 rounded-2xl mb-8 text-xs font-bold uppercase tracking-widest">
                <i class="fas fa-times-circle mr-2"></i> <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="space-y-8">
            <div>
                <input type="text" name="otp" maxlength="6" placeholder="000000" class="w-full bg-white/5 border border-white/10 rounded-2xl px-6 py-6 text-4xl font-black text-white focus:ring-2 focus:ring-indigo-500/50 outline-none transition-all placeholder:text-gray-800 text-center tracking-[0.5em]" required autofocus>
            </div>

            <button type="submit" class="w-full bg-indigo-600 text-white font-black text-[11px] uppercase tracking-[0.3em] py-5 rounded-2xl shadow-xl shadow-indigo-600/20 hover:bg-indigo-700 hover:scale-[1.02] active:scale-95 transition-all">
                Verify Identity
            </button>

            <div class="text-center">
                <p class="text-[10px] text-gray-600 font-black uppercase tracking-widest">Didn't get the code?</p>
                <div class="mt-4 flex flex-col gap-4">
                    <button type="button" class="text-[10px] font-black text-indigo-400 uppercase tracking-widest hover:text-indigo-300">Resend Code</button>
                    <a href="Register.php" class="text-[10px] font-black text-gray-700 uppercase tracking-widest hover:text-gray-500">
                        <i class="fas fa-arrow-left mr-2"></i> Back to Register
                    </a>
                </div>
            </div>
        </form>
    </div>
</body>
</html>

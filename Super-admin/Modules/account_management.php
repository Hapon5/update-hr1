<?php
session_start();
// Specific root path for Super-admin/Modules
$root_path = '../../';
require_once $root_path . "Database/Connections.php";

if (!isset($_SESSION['Email']) || !in_array($_SESSION['Account_type'], [0, 1])) {
    header("Location: " . $root_path . "login.php");
    exit();
}

$msg = "";
$error = "";

// Handle Add Account
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['add_account'])) {
    $name = trim($_POST['name'] ?? '');
    $email = strtolower(trim($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? '3'; // Default to Employee

    if (empty($name) || empty($email) || empty($password)) {
        $error = "All fields are required.";
    } else {
        try {
            // Check if email exists
            $check = $conn->prepare("SELECT Email FROM logintbl WHERE Email = ?");
            $check->execute([$email]);
            if ($check->rowCount() > 0) {
                $error = "Email already registered.";
            } else {
                $hashed = password_hash($password, PASSWORD_DEFAULT);
                $conn->beginTransaction();

                // 1. Insert into logintbl
                $stmt = $conn->prepare("INSERT INTO logintbl (Email, Password, Account_type) VALUES (?, ?, ?)");
                $stmt->execute([$email, $hashed, $role]);

                // 2. Insert into candidates or employees based on role? 
                // For now, let's sync to candidates as it's the primary name source in this system
                $stmtCand = $conn->prepare("INSERT INTO candidates (full_name, email, status) VALUES (?, ?, 'new')");
                $stmtCand->execute([$name, $email]);

                $conn->commit();
                $msg = "<div class='bg-green-100 text-green-700 p-4 rounded-xl mb-6 shadow-sm border border-green-200'>Account created successfully for $name!</div>";
            }
        } catch (Exception $e) {
            $conn->rollBack();
            $error = "Error: " . $e->getMessage();
        }
    }
}

// Fetch all accounts
$accounts = [];
try {
    $stmt = $conn->query("SELECT l.LoginID, l.Email, l.Account_type, c.full_name 
                         FROM logintbl l 
                         LEFT JOIN candidates c ON l.Email = c.email 
                         ORDER BY l.LoginID DESC");
    $accounts = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}

$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Management | Super Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Poppins', sans-serif; background: #0a0a0b; color: white; }
        .glass-card {
            background: rgba(17, 17, 19, 0.8);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.05);
        }
        .custom-scrollbar::-webkit-scrollbar { width: 4px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: rgba(255, 255, 255, 0.1); border-radius: 10px; }
    </style>
</head>
<body class="min-h-screen">

    <?php include '../Components/sidebar.php'; ?>
    <?php include '../Components/header.php'; ?>

    <main class="ml-64 pt-28 px-8 pb-12 transition-all duration-300" id="mainContent">
        <div class="max-w-7xl mx-auto">
            <!-- Header -->
            <div class="mb-10">
                <h1 class="text-3xl font-black tracking-tighter uppercase">Account <span class="text-indigo-500">Management</span></h1>
                <p class="text-gray-500 text-sm mt-1">Create and manage system access accounts.</p>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Add Account Form -->
                <div class="lg:col-span-1">
                    <div class="glass-card rounded-[32px] p-8 sticky top-28">
                        <h3 class="text-lg font-bold mb-6 flex items-center gap-3">
                            <span class="w-8 h-8 rounded-lg bg-indigo-600/20 text-indigo-400 flex items-center justify-center">
                                <i class="fas fa-user-plus text-sm"></i>
                            </span>
                            Add New Account
                        </h3>

                        <?php if ($error): ?>
                            <div class="bg-red-500/10 border border-red-500/20 text-red-500 p-4 rounded-xl mb-6 text-xs font-bold uppercase tracking-wider">
                                <?php echo $error; ?>
                            </div>
                        <?php endif; ?>

                        <form method="POST" class="space-y-5">
                            <div>
                                <label class="block text-[10px] font-black text-gray-500 uppercase tracking-[0.2em] mb-2 px-1">Full Name</label>
                                <input type="text" name="name" placeholder="John Doe" class="w-full bg-white/5 border border-white/10 rounded-2xl px-5 py-4 text-sm font-medium focus:ring-2 focus:ring-indigo-500/50 outline-none transition-all placeholder:text-gray-600" required>
                            </div>

                            <div>
                                <label class="block text-[10px] font-black text-gray-500 uppercase tracking-[0.2em] mb-2 px-1">Email Address</label>
                                <input type="email" name="email" placeholder="john@example.com" class="w-full bg-white/5 border border-white/10 rounded-2xl px-5 py-4 text-sm font-medium focus:ring-2 focus:ring-indigo-500/50 outline-none transition-all placeholder:text-gray-600" required>
                            </div>

                            <div>
                                <label class="block text-[10px] font-black text-gray-500 uppercase tracking-[0.2em] mb-2 px-1">Temporary Password</label>
                                <input type="password" name="password" placeholder="••••••••" class="w-full bg-white/5 border border-white/10 rounded-2xl px-5 py-4 text-sm font-medium focus:ring-2 focus:ring-indigo-500/50 outline-none transition-all placeholder:text-gray-600" required>
                            </div>

                            <div>
                                <label class="block text-[10px] font-black text-gray-500 uppercase tracking-[0.2em] mb-2 px-1">Account Role</label>
                                <select name="role" class="w-full bg-white/5 border border-white/10 rounded-2xl px-5 py-4 text-sm font-medium focus:ring-2 focus:ring-indigo-500/50 outline-none transition-all appearance-none cursor-pointer">
                                    <option value="3" class="bg-gray-900">Employee</option>
                                    <option value="1" class="bg-gray-900">HR Admin</option>
                                    <option value="2" class="bg-gray-900">Staff</option>
                                    <option value="0" class="bg-gray-900">Super Admin</option>
                                </select>
                            </div>

                            <button type="submit" name="add_account" class="w-full bg-indigo-600 text-white font-black text-[11px] uppercase tracking-[0.2em] py-5 rounded-2xl shadow-xl shadow-indigo-600/20 hover:bg-indigo-700 hover:scale-[1.02] active:scale-95 transition-all mt-4">
                                Create Account
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Accounts List -->
                <div class="lg:col-span-2">
                    <div class="glass-card rounded-[32px] overflow-hidden">
                        <div class="px-8 py-6 border-b border-white/5 flex justify-between items-center bg-white/[0.02]">
                            <h3 class="text-sm font-black uppercase tracking-widest text-gray-400">System Accounts</h3>
                            <span class="px-4 py-1.5 bg-indigo-500/10 text-indigo-400 text-[10px] font-black uppercase rounded-full border border-indigo-500/20"><?php echo count($accounts); ?> Total</span>
                        </div>
                        
                        <div class="overflow-x-auto custom-scrollbar">
                            <table class="w-full">
                                <thead>
                                    <tr class="text-left">
                                        <th class="px-8 py-5 text-[10px] font-black text-gray-500 uppercase tracking-widest">User</th>
                                        <th class="px-8 py-5 text-[10px] font-black text-gray-500 uppercase tracking-widest">Email</th>
                                        <th class="px-8 py-5 text-[10px] font-black text-gray-500 uppercase tracking-widest">Role</th>
                                        <th class="px-8 py-5 text-[10px] font-black text-gray-500 uppercase tracking-widest text-right">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-white/5">
                                    <?php foreach ($accounts as $acc): ?>
                                    <tr class="hover:bg-white/[0.03] transition-colors group">
                                        <td class="px-8 py-5">
                                            <div class="flex items-center gap-3">
                                                <div class="w-10 h-10 rounded-xl bg-indigo-600/10 text-indigo-400 flex items-center justify-center font-bold text-sm border border-indigo-500/20">
                                                    <?php echo strtoupper(substr($acc['full_name'] ?? 'U', 0, 1)); ?>
                                                </div>
                                                <span class="text-sm font-bold text-gray-200"><?php echo htmlspecialchars($acc['full_name'] ?? 'System User'); ?></span>
                                            </div>
                                        </td>
                                        <td class="px-8 py-5">
                                            <span class="text-xs text-gray-500 font-medium"><?php echo htmlspecialchars($acc['Email']); ?></span>
                                        </td>
                                        <td class="px-8 py-5">
                                            <?php
                                            $roles = [
                                                '0' => ['Super Admin', 'bg-red-500/10 text-red-400 border-red-500/20'],
                                                '1' => ['HR Admin', 'bg-blue-500/10 text-blue-400 border-blue-500/20'],
                                                '2' => ['Staff', 'bg-amber-500/10 text-amber-400 border-amber-500/20'],
                                                '3' => ['Employee', 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20']
                                            ];
                                            $roleInfo = $roles[$acc['Account_type']] ?? ['Unknown', 'bg-gray-500/10 text-gray-400 border-gray-500/20'];
                                            ?>
                                            <span class="px-3 py-1 rounded-lg text-[9px] font-black uppercase tracking-widest border <?php echo $roleInfo[1]; ?>">
                                                <?php echo $roleInfo[0]; ?>
                                            </span>
                                        </td>
                                        <td class="px-8 py-5 text-right">
                                            <button class="w-8 h-8 rounded-lg bg-white/5 text-gray-400 hover:text-white hover:bg-white/10 transition-all">
                                                <i class="fas fa-ellipsis-v text-xs"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script>
        // Sidebar active handling (optional if not inherited)
        document.addEventListener('DOMContentLoaded', () => {
             const sidebar = document.getElementById('sidebar');
             const mainContent = document.getElementById('mainContent');
             const header = document.getElementById('header');
        });
    </script>
</body>
</html>

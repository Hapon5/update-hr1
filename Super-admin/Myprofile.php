<?php
session_start();
$root_path = '../'; 
require_once $root_path . "Database/Connections.php";

if (!isset($_SESSION['Email']) || $_SESSION['Account_type'] != 0) {
    header("Location: ../login.php");
    exit();
}

// Fetch dynamic user data for profile
$profile_user = [
    'name' => $_SESSION['GlobalName'] ?? 'Super Admin',
    'email' => $_SESSION['Email'] ?? '',
    'role' => 'System Architect',
    'photo' => ''
];

if (isset($_SESSION['Email'])) {
    try {
        $stmt = $conn->prepare("SELECT first_name, last_name, position, base64_image FROM employees WHERE email = ?");
        $stmt->execute([$_SESSION['Email']]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $profile_user['name'] = $row['first_name'] . ' ' . $row['last_name'];
            $profile_user['role'] = $row['position'];
            $profile_user['photo'] = $row['base64_image'];
        } else {
            $stmt = $conn->prepare("SELECT full_name FROM candidates WHERE email = ?");
            $stmt->execute([$_SESSION['Email']]);
            $cand = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($cand) {
                $profile_user['name'] = $cand['full_name'];
            }
        }
    } catch (Exception $e) {}
}

$msg = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    
    try {
        // Sync name to candidates since it's the primary name source
        $update = $conn->prepare("UPDATE candidates SET full_name = ? WHERE email = ?");
        $fullName = $first_name . ' ' . $last_name;
        if ($update->execute([$fullName, $_SESSION['Email']])) {
            $_SESSION['GlobalName'] = $fullName;
            $profile_user['name'] = $fullName;
            $msg = "<div class='bg-green-500/10 text-green-500 p-4 rounded-2xl mb-6 border border-green-500/20 text-xs font-bold uppercase tracking-wider'>Profile updated successfully!</div>";
        }
    } catch (Exception $e) {
        $msg = "<div class='bg-red-500/10 text-red-500 p-4 rounded-2xl mb-6 border border-red-500/20 text-xs font-bold uppercase tracking-wider'>Error: " . $e->getMessage() . "</div>";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile | Super Admin</title>
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
    </style>
</head>
<body class="min-h-screen">

    <?php include 'Components/sidebar.php'; ?>
    <?php include 'Components/header.php'; ?>

    <main class="ml-64 pt-28 px-8 pb-12 transition-all duration-300" id="mainContent">
        <div class="max-w-4xl mx-auto">
            <div class="mb-10">
                <h1 class="text-3xl font-black tracking-tighter uppercase">My <span class="text-indigo-500">Profile</span></h1>
                <p class="text-gray-500 text-sm mt-1">Manage your administrative identity and security.</p>
            </div>

            <?php echo $msg; ?>

            <div class="glass-card rounded-[40px] overflow-hidden">
                <!-- Banner -->
                <div class="h-40 bg-gradient-to-r from-indigo-600 to-purple-600 relative">
                    <div class="absolute -bottom-16 left-12">
                        <div class="relative group">
                            <div class="w-32 h-32 rounded-[32px] bg-gray-900 border-4 border-[#0a0a0b] shadow-2xl overflow-hidden flex items-center justify-center">
                                <?php if ($profile_user['photo']): ?>
                                    <img src="<?php echo $profile_user['photo']; ?>" class="w-full h-full object-cover">
                                <?php else: ?>
                                    <span class="text-4xl font-black text-indigo-500"><?php echo strtoupper(substr($profile_user['name'], 0, 1)); ?></span>
                                <?php endif; ?>
                            </div>
                            <button class="absolute bottom-2 right-2 w-8 h-8 bg-indigo-600 rounded-xl shadow-lg flex items-center justify-center text-white hover:scale-110 transition-all">
                                <i class="fas fa-camera text-xs"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <div class="pt-24 px-12 pb-12">
                    <div class="flex justify-between items-start mb-12">
                        <div>
                            <h2 class="text-2xl font-black text-white tracking-tight uppercase"><?php echo htmlspecialchars($profile_user['name']); ?></h2>
                            <p class="text-[10px] text-indigo-400 font-black uppercase tracking-[0.3em] mt-1"><?php echo htmlspecialchars($profile_user['role']); ?></p>
                        </div>
                        <div class="flex gap-2">
                             <span class="px-4 py-1.5 bg-indigo-500/10 text-indigo-400 text-[10px] font-black uppercase rounded-full border border-indigo-500/20 tracking-widest leading-none flex items-center">Super Admin</span>
                             <span class="px-4 py-1.5 bg-green-500/10 text-green-400 text-[10px] font-black uppercase rounded-full border border-green-500/20 tracking-widest leading-none flex items-center">System Online</span>
                        </div>
                    </div>

                    <form method="POST" class="grid grid-cols-1 md:grid-cols-2 gap-10">
                        <div class="space-y-6">
                            <p class="text-[10px] font-black text-gray-500 uppercase tracking-widest border-b border-white/5 pb-2">Public Identity</p>
                            
                            <?php
                            $nameParts = explode(' ', $profile_user['name']);
                            $firstName = $nameParts[0] ?? '';
                            $lastName = isset($nameParts[1]) ? implode(' ', array_slice($nameParts, 1)) : '';
                            ?>

                            <div>
                                <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2 px-1">First Name</label>
                                <input type="text" name="first_name" value="<?php echo htmlspecialchars($firstName); ?>" class="w-full bg-white/5 border border-white/10 rounded-2xl px-6 py-4 text-sm font-bold text-gray-200 focus:ring-2 focus:ring-indigo-500/50 outline-none transition-all" required>
                            </div>

                            <div>
                                <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2 px-1">Last Name</label>
                                <input type="text" name="last_name" value="<?php echo htmlspecialchars($lastName); ?>" class="w-full bg-white/5 border border-white/10 rounded-2xl px-6 py-4 text-sm font-bold text-gray-200 focus:ring-2 focus:ring-indigo-500/50 outline-none transition-all" required>
                            </div>
                        </div>

                        <div class="space-y-6">
                            <p class="text-[10px] font-black text-gray-500 uppercase tracking-widest border-b border-white/5 pb-2">System Credentials</p>
                            
                            <div>
                                <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2 px-1">Email (Read-only)</label>
                                <input type="text" value="<?php echo htmlspecialchars($profile_user['email']); ?>" class="w-full bg-white/[0.02] border border-white/5 rounded-2xl px-6 py-4 text-sm font-bold text-gray-600 cursor-not-allowed" readonly>
                            </div>

                            <button type="submit" name="update_profile" class="w-full bg-indigo-600 text-white font-black text-[11px] uppercase tracking-[0.2em] py-5 rounded-2xl shadow-xl shadow-indigo-600/20 hover:bg-indigo-700 hover:scale-[1.02] active:scale-95 transition-all mt-4 flex items-center justify-center gap-3">
                                <span>Save Changes</span>
                                <i class="fas fa-check-circle"></i>
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Security Preview -->
            <div class="mt-8 glass-card rounded-[32px] p-8 flex items-center justify-between group hover:border-indigo-500/30 transition-all">
                <div class="flex items-center gap-6">
                    <div class="w-14 h-14 rounded-2xl bg-amber-500/10 text-amber-500 flex items-center justify-center border border-amber-500/20 shadow-sm">
                        <i class="fas fa-shield-alt text-xl"></i>
                    </div>
                    <div>
                        <h4 class="text-sm font-black text-white uppercase tracking-tight">Security & Authentication</h4>
                        <p class="text-[10px] text-gray-500 font-medium mt-1 uppercase tracking-widest">Update password and manage session security.</p>
                    </div>
                </div>
                <button class="px-8 py-3 bg-white/5 text-gray-300 text-[10px] font-black uppercase rounded-xl border border-white/10 hover:bg-white/10 transition-all tracking-[0.2em]">Manage</button>
            </div>
        </div>
    </main>
</body>
</html>

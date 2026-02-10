<?php
session_start();
include('../Database/Connections.php');

if (!isset($_SESSION['Email']) || !in_array($_SESSION['Account_type'], [0, 1, 3])) {
    header("Location: login.php");
    exit();
}

$email = $_SESSION['Email'];
$employee = ['first_name' => 'Employee', 'last_name' => 'User', 'position' => 'Staff'];

try {
    // Revert to SELECT * to avoid "Column not found" errors
    $stmt = $conn->prepare("SELECT * FROM employees WHERE email = ?");
    $stmt->execute([$email]);
    $res = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($res) {
        $employee = array_merge($employee, $res);
        
        // Dynamic Name Handling (Fix for schema mismatch) like in Myprofile
        if (!isset($employee['first_name']) && isset($employee['full_name'])) {
            $names = explode(' ', trim($employee['full_name']), 2);
            $employee['first_name'] = $names[0];
            $employee['last_name'] = $names[1] ?? '';
        } elseif (!isset($employee['first_name']) && isset($employee['name'])) {
            $names = explode(' ', trim($employee['name']), 2);
            $employee['first_name'] = $names[0];
            $employee['last_name'] = $names[1] ?? '';
        }
    } else {
        // --- AUTO-CREATE Employee Record if missing ---
        // (Logic handled in MyProfile, just defaults here to prevent crash)
    }
} catch (Exception $e) {
    // Fallback
}

$photo = !empty($employee['base64_image']) ? $employee['base64_image'] : '';

// Fallback to Candidate Photo if Employee photo is empty
if (empty($photo)) {
    try {
        $cStmt = $conn->prepare("SELECT extracted_image_path FROM candidates WHERE email = ?");
        $cStmt->execute([$email]);
        $cand = $cStmt->fetch();
        if ($cand && !empty($cand['extracted_image_path'])) {
            $photo = '../Main/' . $cand['extracted_image_path'];
        }
    } catch (Exception $e) {}
}

if (empty($photo)) {
    $photo = 'https://ui-avatars.com/api/?name=' . urlencode(($employee['first_name'] ?? 'Employee') . ' ' . ($employee['last_name'] ?? ''));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>body { font-family: 'Poppins', sans-serif; }</style>
</head>
<body class="bg-gray-50 min-h-screen">
    <!-- Navbar -->
    <nav class="bg-gray-950 border-b border-gray-800 shadow-2xl">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-lg bg-indigo-600 flex items-center justify-center shadow-lg shadow-indigo-500/20">
                        <i class="fas fa-cube text-white text-sm"></i>
                    </div>
                    <span class="text-xl font-black text-white uppercase tracking-tighter">HR1 <span class="text-indigo-500 text-sm font-bold tracking-widest ml-1">Portal</span></span>
                </div>
                <div class="flex items-center gap-6">
                    <div class="text-right hidden sm:block">
                        <p class="text-xs font-black text-white uppercase leading-none">Welcome, <?php echo htmlspecialchars($employee['first_name'] ?? 'Employee'); ?></p>
                        <p class="text-[9px] font-bold text-gray-500 uppercase tracking-widest mt-1 italic">Employee Access</p>
                    </div>
                    <div class="relative group">
                        <img class="h-9 w-9 rounded-xl border-2 border-gray-800 group-hover:border-indigo-500 transition-all cursor-pointer shadow-lg" src="<?php echo $photo; ?>" alt="Profile">
                        <div class="absolute -top-1 -right-1 w-3 h-3 bg-green-500 border-2 border-gray-950 rounded-full"></div>
                    </div>
                    <a href="../logout.php" class="w-9 h-9 flex items-center justify-center rounded-xl bg-gray-900 border border-gray-800 text-gray-400 hover:text-red-500 hover:bg-red-500/10 hover:border-red-500/20 transition-all" title="Logout">
                        <i class="fas fa-power-off text-sm"></i>
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Header -->
        <div class="mb-8 flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Dashboard</h1>
                <p class="text-gray-500">Overview of your employment status</p>
            </div>
            <a href="https://hr3.cranecali-ms.com/" target="_blank" 
               class="flex items-center gap-3 bg-gradient-to-r from-indigo-600 to-blue-600 text-white px-6 py-3 rounded-2xl hover:from-indigo-700 hover:to-blue-700 transition-all shadow-lg shadow-indigo-500/20 group">
                <div class="w-10 h-10 rounded-xl bg-white/20 flex items-center justify-center group-hover:scale-110 transition-transform">
                    <i class="fas fa-external-link-alt text-sm"></i>
                </div>
                <div class="text-left">
                    <p class="text-[10px] font-black uppercase tracking-widest opacity-70">External Portal</p>
                    <p class="text-sm font-bold tracking-tight">HR3 Central System</p>
                </div>
            </a>
        </div>

        <!-- Stats Grid -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-blue-50 text-blue-600">
                        <i class="fas fa-calendar-check text-xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500">Attendance</p>
                        <p class="text-lg font-bold text-gray-900">Present</p>
                    </div>
                </div>
            </div>
            <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-green-50 text-green-600">
                        <i class="fas fa-money-bill-wave text-xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500">Payslip</p>
                        <p class="text-lg font-bold text-gray-900">Available</p>
                    </div>
                </div>
            </div>
            <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-purple-50 text-purple-600">
                        <i class="fas fa-clock text-xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500">Leave Balance</p>
                        <p class="text-lg font-bold text-gray-900">12 Days</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- Profile Card -->
            <div class="bg-white rounded-[32px] shadow-xl shadow-gray-200/50 border border-gray-100 overflow-hidden group hover:border-indigo-500/30 transition-all duration-500">
                <div class="px-8 py-6 border-b border-gray-50 bg-gray-50/50 flex justify-between items-center">
                    <h3 class="font-black text-gray-900 uppercase tracking-tighter text-sm flex items-center gap-2">
                        <span class="w-1.5 h-1.5 bg-indigo-500 rounded-full"></span>
                        My Profile
                    </h3>
                    <a href="Myprofile.php" class="text-[10px] font-black text-indigo-600 hover:text-indigo-800 uppercase tracking-widest bg-white px-4 py-2 rounded-xl shadow-sm border border-gray-100 transition-all hover:scale-105 active:scale-95">Edit Info</a>
                </div>
                <div class="p-8">
                    <div class="space-y-5">
                        <div class="flex justify-between items-center group/item hover:bg-gray-50 p-2 rounded-xl transition-colors">
                            <span class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Full Name</span>
                            <span class="text-sm font-bold text-gray-800 tracking-tight"><?php echo htmlspecialchars(($employee['first_name'] ?? 'Employee') . ' ' . ($employee['last_name'] ?? '')); ?></span>
                        </div>
                        <div class="flex justify-between items-center group/item hover:bg-gray-50 p-2 rounded-xl transition-colors">
                            <span class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Position</span>
                            <span class="text-[11px] font-black text-indigo-500 uppercase tracking-tight bg-indigo-50 px-3 py-1 rounded-lg"><?php echo htmlspecialchars($employee['position'] ?? 'Staff'); ?></span>
                        </div>
                        <div class="flex justify-between items-center group/item hover:bg-gray-50 p-2 rounded-xl transition-colors">
                            <span class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Department</span>
                            <span class="text-sm font-bold text-gray-800 tracking-tight"><?php echo htmlspecialchars($employee['department'] ?? 'General'); ?></span>
                        </div>
                        <div class="flex justify-between items-center group/item hover:bg-gray-50 p-2 rounded-xl transition-colors">
                            <span class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Date Hired</span>
                            <span class="text-sm font-bold text-gray-800 tracking-tight"><?php echo htmlspecialchars($employee['date_hired'] ?? 'N/A'); ?></span>
                        </div>
                        <div class="flex justify-between items-center group/item hover:bg-gray-50 p-2 rounded-xl transition-colors border-t border-gray-50 pt-4">
                            <span class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Age</span>
                            <span class="text-sm font-bold text-gray-800 tracking-tight"><?php echo htmlspecialchars($employee['age'] ?? '0'); ?> Years</span>
                        </div>
                        <div class="flex justify-between items-center group/item hover:bg-gray-50 p-2 rounded-xl transition-colors">
                            <span class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Experience</span>
                            <span class="text-sm font-bold text-gray-800 tracking-tight"><?php echo htmlspecialchars($employee['experience_years'] ?? '0'); ?> Years</span>
                        </div>
                        <div class="flex flex-col gap-2 group/item hover:bg-gray-50 p-2 rounded-xl transition-colors">
                            <span class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Key Skills</span>
                            <div class="flex flex-wrap gap-2 mt-1">
                                <?php 
                                $skills = explode(',', $employee['skills'] ?? '');
                                if (empty(trim($employee['skills'] ?? ''))) {
                                    echo '<span class="text-xs text-gray-400 italic">No skills listed</span>';
                                } else {
                                    foreach($skills as $skill) {
                                        echo '<span class="px-2 py-1 bg-gray-100 text-gray-600 rounded-lg text-[10px] font-bold uppercase tracking-tight">' . htmlspecialchars(trim($skill)) . '</span>';
                                    }
                                }
                                ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Announcements / Recent Activity -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 bg-gray-50">
                    <h3 class="font-bold text-gray-900">Announcements</h3>
                </div>
                <div class="p-6">
                    <div class="space-y-4">
                        <div class="flex items-start">
                            <div class="flex-shrink-0">
                                <span class="inline-flex items-center justify-center h-8 w-8 rounded-full bg-blue-100">
                                    <i class="fas fa-bullhorn text-blue-600 text-sm"></i>
                                </span>
                            </div>
                            <div class="ml-4">
                                <h4 class="text-sm font-bold text-gray-900">System Maintenance</h4>
                                <p class="text-sm text-gray-500 mt-1">Scheduled maintenance on Saturday, 10 PM. Please save your work.</p>
                                <span class="text-xs text-gray-400 mt-2 block">2 hours ago</span>
                            </div>
                        </div>
                        <div class="flex items-start">
                            <div class="flex-shrink-0">
                                <span class="inline-flex items-center justify-center h-8 w-8 rounded-full bg-green-100">
                                    <i class="fas fa-leaf text-green-600 text-sm"></i>
                                </span>
                            </div>
                            <div class="ml-4">
                                <h4 class="text-sm font-bold text-gray-900">Holiday Notice</h4>
                                <p class="text-sm text-gray-500 mt-1">Office will be closed next Monday for National Heroes Day.</p>
                                <span class="text-xs text-gray-400 mt-2 block">1 day ago</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
    <!-- Security Blackout & Auto-Logout -->
    <script src="../Js/security.js"></script>
</body>
</html>

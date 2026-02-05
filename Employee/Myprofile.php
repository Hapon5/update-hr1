<?php
session_start();
include('../Database/Connections.php');

if (!isset($_SESSION['Email']) || $_SESSION['Account_type'] != 3) {
    header("Location: login.php");
    exit();
}

$email = $_SESSION['Email'];
$employee = [
    'first_name' => 'Guest',
    'last_name' => '',
    'email' => $email,
    'position' => 'Employee',
    'department' => 'General',
    'contact_number' => '',
    'date_hired' => 'N/A',
    'base64_image' => ''
];
$msg = "";

try {
    // Revert to SELECT * to avoid "Column not found" errors if schema differs
    $stmt = $conn->prepare("SELECT * FROM employees WHERE email = ?");
    $stmt->execute([$email]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result) {
        $employee = array_merge($employee, $result);
        
        // Dynamic Name Handling (Fix for schema mismatch)
        if (!isset($employee['first_name']) && isset($employee['full_name'])) {
            $names = explode(' ', trim($employee['full_name']), 2);
            $employee['first_name'] = $names[0];
            $employee['last_name'] = $names[1] ?? '';
        } elseif (!isset($employee['first_name']) && isset($employee['name'])) {
            $names = explode(' ', trim($employee['name']), 2);
            $employee['first_name'] = $names[0];
            $employee['last_name'] = $names[1] ?? '';
        }

        // Ensure defaults if still missing
        $employee['first_name'] = $employee['first_name'] ?? 'Guest';
        $employee['last_name'] = $employee['last_name'] ?? '';
    }
} catch (Exception $e) {
    // Show detailed error for debugging
    $msg = "<div class='bg-red-100 text-red-700 p-3 rounded mb-4'>Error: " . htmlspecialchars($e->getMessage()) . "</div>";
}

// Handle Update
// Handle Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $contact = trim($_POST['contact_number']);
    
    // Determine which columns to update based on what we found in the SELECT phase
    // Note: This relies on $employee being populated from the SELECT above.
    
    try {
        if (array_key_exists('first_name', $result ?? [])) {
            // Standard Schema
            $update = $conn->prepare("UPDATE employees SET first_name = ?, last_name = ?, contact_number = ? WHERE email = ?");
            $params = [$first_name, $last_name, $contact, $email];
        } elseif (array_key_exists('full_name', $result ?? [])) {
            // Full Name Schema
            $full_name = $first_name . ' ' . $last_name;
            $update = $conn->prepare("UPDATE employees SET full_name = ?, contact_number = ? WHERE email = ?");
            $params = [$full_name, $contact, $email];
        } else {
             // Fallback: Try standard, but it might fail if columns missing. 
             // If we are here, SELECT * returned nothing or weird data.
             $update = $conn->prepare("UPDATE employees SET first_name = ?, last_name = ?, contact_number = ? WHERE email = ?");
             $params = [$first_name, $last_name, $contact, $email];
        }

        if ($update->execute($params)) {
             $msg = "<div class='bg-green-100 text-green-700 p-3 rounded mb-4 shadow-sm border border-green-200'>Profile updated successfully!</div>";
             
             // Refresh Data Logic
             $stmt->execute([$email]);
             $new_result = $stmt->fetch(PDO::FETCH_ASSOC);
             if ($new_result) {
                 $employee = array_merge($employee, $new_result);
                 // Re-apply name logic
                 if (!isset($employee['first_name']) && isset($employee['full_name'])) {
                    $names = explode(' ', trim($employee['full_name']), 2);
                    $employee['first_name'] = $names[0];
                    $employee['last_name'] = $names[1] ?? '';
                }
             }
        }
    } catch (Exception $e) {
        $msg = "<div class='bg-red-100 text-red-700 p-3 rounded mb-4'>Update failed: " . htmlspecialchars($e->getMessage()) . "</div>";
    }
}

$photo = !empty($employee['base64_image']) ? $employee['base64_image'] : 'https://ui-avatars.com/api/?name=' . urlencode(($employee['first_name'] ?? 'Guest') . ' ' . ($employee['last_name'] ?? ''));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile | Employee Portal</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Poppins', sans-serif; background: #f8fafc; }
        .glass-card {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 1);
        }
    </style>
</head>
<body class="min-h-screen">

    <!-- Navbar -->
    <nav class="bg-gray-950 border-b border-gray-800 shadow-2xl sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-20">
                <div class="flex items-center gap-4">
                    <a href="Dashboard.php" class="w-10 h-10 rounded-xl bg-indigo-600 flex items-center justify-center shadow-lg shadow-indigo-500/20 hover:scale-105 transition-transform">
                        <i class="fas fa-arrow-left text-white text-sm"></i>
                    </a>
                    <span class="text-xl font-black text-white uppercase tracking-tighter">My <span class="text-indigo-500 text-sm font-bold tracking-widest ml-1">Profile</span></span>
                </div>
                <div class="flex items-center gap-6">
                    <div class="relative group">
                        <img class="h-10 w-10 rounded-xl border-2 border-gray-800 group-hover:border-indigo-500 transition-all shadow-lg" src="<?php echo $photo; ?>" alt="Profile">
                        <div class="absolute -top-1 -right-1 w-3 h-3 bg-green-500 border-2 border-gray-950 rounded-full"></div>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <main class="max-w-4xl mx-auto px-4 py-12">
        <?php echo $msg; ?>

        <div class="glass-card rounded-[40px] shadow-2xl shadow-indigo-100 overflow-hidden border border-white">
            <!-- Header Section -->
            <div class="bg-indigo-600 h-32 relative">
                <div class="absolute -bottom-16 left-12">
                    <div class="relative group">
                        <img src="<?php echo $photo; ?>" class="w-32 h-32 rounded-[32px] border-4 border-white shadow-2xl object-cover bg-white">
                        <button class="absolute bottom-2 right-2 w-8 h-8 bg-white rounded-xl shadow-lg flex items-center justify-center text-indigo-600 hover:scale-110 transition-all border border-gray-100">
                            <i class="fas fa-camera text-xs"></i>
                        </button>
                    </div>
                </div>
            </div>

            <div class="pt-20 px-12 pb-12">
                <div class="flex justify-between items-start mb-10">
                    <div>
                        <h2 class="text-3xl font-black text-gray-900 tracking-tight uppercase"><?php echo htmlspecialchars(($employee['first_name'] ?? 'Guest') . ' ' . ($employee['last_name'] ?? '')); ?></h2>
                        <p class="text-[10px] text-indigo-500 font-black uppercase tracking-[0.3em] mt-1 italic"><?php echo htmlspecialchars($employee['position'] ?? 'Employee'); ?></p>
                    </div>
                    <span class="px-4 py-1.5 bg-green-50 text-green-600 text-[10px] font-black uppercase rounded-full border border-green-100 tracking-widest">Active Status</span>
                </div>

                <form method="POST" class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <!-- Read-only Info -->
                    <div class="space-y-6">
                        <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest border-b border-gray-100 pb-2">Employment Details (Read-Only)</p>
                        
                        <div class="relative group">
                            <label class="block text-[10px] font-black text-gray-500 uppercase tracking-wider mb-2">Email Address</label>
                            <input type="text" value="<?php echo htmlspecialchars($employee['email'] ?? $email); ?>" class="w-full bg-gray-50 border border-gray-100 rounded-2xl px-6 py-4 text-sm font-medium text-gray-400 cursor-not-allowed select-none" readonly>
                            <div class="absolute right-4 top-[38px] text-gray-300 group-hover:text-gray-400 transition-colors">
                                <i class="fas fa-lock"></i>
                            </div>
                        </div>

                        <div class="relative group">
                            <label class="block text-[10px] font-black text-gray-500 uppercase tracking-wider mb-2">Department</label>
                            <input type="text" value="<?php echo htmlspecialchars($employee['department'] ?? 'General'); ?>" class="w-full bg-gray-50 border border-gray-100 rounded-2xl px-6 py-4 text-sm font-medium text-gray-400 cursor-not-allowed select-none" readonly>
                            <div class="absolute right-4 top-[38px] text-gray-300 group-hover:text-gray-400 transition-colors">
                                <i class="fas fa-lock"></i>
                            </div>
                        </div>

                        <div class="relative group">
                            <label class="block text-[10px] font-black text-gray-500 uppercase tracking-wider mb-2">Date Hired</label>
                            <input type="text" value="<?php echo htmlspecialchars($employee['date_hired'] ?? 'N/A'); ?>" class="w-full bg-gray-50 border border-gray-100 rounded-2xl px-6 py-4 text-sm font-medium text-gray-400 cursor-not-allowed select-none" readonly>
                             <div class="absolute right-4 top-[38px] text-gray-300 group-hover:text-gray-400 transition-colors">
                                <i class="fas fa-lock"></i>
                            </div>
                        </div>
                        <p class="text-xs text-indigo-400 italic mt-2"><i class="fas fa-info-circle mr-1"></i> Contact HR Admin to update these details.</p>
                    </div>

                    <!-- Editable Info -->
                    <div class="space-y-6">
                        <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest border-b border-gray-100 pb-2">Personal Information</p>
                        
                        <div>
                            <label class="block text-[10px] font-black text-gray-500 uppercase tracking-wider mb-2">First Name</label>
                            <input type="text" name="first_name" value="<?php echo htmlspecialchars($employee['first_name'] ?? ''); ?>" class="w-full bg-white border border-gray-200 rounded-2xl px-6 py-4 text-sm font-medium text-gray-900 focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 transition-all outline-none" required>
                        </div>

                        <div>
                            <label class="block text-[10px] font-black text-gray-500 uppercase tracking-wider mb-2">Last Name</label>
                            <input type="text" name="last_name" value="<?php echo htmlspecialchars($employee['last_name'] ?? ''); ?>" class="w-full bg-white border border-gray-200 rounded-2xl px-6 py-4 text-sm font-medium text-gray-900 focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 transition-all outline-none" required>
                        </div>

                        <div>
                            <label class="block text-[10px] font-black text-gray-500 uppercase tracking-wider mb-2">Contact Number</label>
                            <input type="text" name="contact_number" value="<?php echo htmlspecialchars($employee['contact_number'] ?? ''); ?>" placeholder="Enter number" class="w-full bg-white border border-gray-200 rounded-2xl px-6 py-4 text-sm font-medium text-gray-900 focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 transition-all outline-none">
                        </div>

                        <div class="pt-4">
                            <button type="submit" name="update_profile" class="w-full bg-gray-900 text-white font-black text-[11px] uppercase tracking-[0.2em] py-5 rounded-[20px] shadow-xl hover:bg-black hover:shadow-2xl transition-all active:scale-95 flex items-center justify-center gap-3">
                                <span>Save Changes</span>
                                <i class="fas fa-check-circle"></i>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Security Section Preview -->
        <div class="mt-8 bg-white rounded-[32px] p-8 border border-gray-100 shadow-sm flex items-center justify-between">
            <div class="flex items-center gap-5">
                <div class="w-12 h-12 rounded-2xl bg-amber-50 text-amber-600 flex items-center justify-center border border-amber-100 shadow-sm">
                    <i class="fas fa-shield-alt"></i>
                </div>
                <div>
                    <h4 class="text-sm font-black text-gray-900 uppercase tracking-tight">Security & Password</h4>
                    <p class="text-[10px] text-gray-400 font-medium mt-0.5">Manage your account authentication and security.</p>
                </div>
            </div>
            <a href="#" class="px-6 py-3 bg-gray-50 text-gray-600 text-[10px] font-black uppercase rounded-xl border border-gray-100 hover:bg-gray-100 transition-all tracking-widest">Update Security</a>
        </div>
    </main>
</body>
</html>

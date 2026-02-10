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
    // 1. Self-Healing Schema: Ensure all required columns exist
    $required_columns = [
        'email' => "VARCHAR(191) NULL",
        'first_name' => "VARCHAR(100) DEFAULT 'Guest'",
        'last_name' => "VARCHAR(100) DEFAULT ''",
        'contact_number' => "VARCHAR(50) DEFAULT ''",
        'status' => "VARCHAR(50) DEFAULT 'Active'",
        'position' => "VARCHAR(100) DEFAULT 'Employee'",
        'department' => "VARCHAR(100) DEFAULT 'General'",
        'date_hired' => "DATE NULL",
        'address' => "TEXT NULL",
        'age' => "INT DEFAULT 0",
        'experience_years' => "INT DEFAULT 0",
        'skills' => "TEXT NULL",
        'notes' => "TEXT NULL",
        'manager_name' => "VARCHAR(255) NULL",
        'work_location' => "VARCHAR(255) NULL",
        'source' => "VARCHAR(100) DEFAULT 'Direct Registration'",
        'birth_date' => "DATE NULL",
        'job_title' => "VARCHAR(255) NULL",
        'base64_image' => "LONGTEXT NULL"
    ];

    foreach ($required_columns as $col => $def) {
        try {
            $checkCol = $conn->query("SHOW COLUMNS FROM employees LIKE '$col'");
            if ($checkCol->rowCount() == 0) {
                $conn->exec("ALTER TABLE employees ADD COLUMN $col $def");
            }
        } catch (Exception $e) {}
    }

    // 2. Fetch Employee Data
    $stmt = $conn->prepare("SELECT * FROM employees WHERE email = ?");
    $stmt->execute([$email]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // 3. Auto-Create Profile if missing (so user is not stuck)
    if (!$result) {
        // Derive defaults
        $nameParts = explode('@', $email);
        $tempName = ucfirst($nameParts[0]);
        
        // Insert depending on schema (full_name vs first_name vs name)
        try {
             // 1. Try standard first_name/last_name
             $ins = $conn->prepare("INSERT INTO employees (first_name, last_name, email, position, department, status, date_hired) VALUES (?, ?, ?, ?, ?, ?, NOW())");
             $ins->execute([$tempName, 'User', $email, 'New Hire', 'General', 'Active']);
        } catch (Exception $e1) {
            try {
                 // 2. Try full_name
                 $ins = $conn->prepare("INSERT INTO employees (full_name, email, position, department, status, date_hired) VALUES (?, ?, ?, ?, ?, NOW())");
                 $ins->execute(["$tempName User", $email, 'New Hire', 'General', 'Active']);
            } catch (Exception $e2) {
                try {
                     // 3. Try name (Legacy/Performance Manager Schema)
                     $ins = $conn->prepare("INSERT INTO employees (name, email, position, department, status, date_hired) VALUES (?, ?, ?, ?, ?, NOW())");
                     $ins->execute(["$tempName User", $email, 'New Hire', 'General', 'Active']);
                } catch (Exception $e3) {
                     // If all fail, we can't insert. Page will load with 'Guest'.
                }
            }
        }
        
        // Fetch again
        $stmt->execute([$email]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
    }

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
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $contact = trim($_POST['contact_number']);
    $address = trim($_POST['address']);
    $age = (int)$_POST['age'];
    $experience = (int)$_POST['experience_years'];
    $skills = trim($_POST['skills']);
    $work_location = trim($_POST['work_location']);
    $job_title = trim($_POST['job_title']);
    
    try {
        // Handle Image Upload
        $base64_image = $employee['base64_image'];
        if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
            $type = pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION);
            $data = file_get_contents($_FILES['profile_image']['tmp_name']);
            $base64_image = 'data:image/' . $type . ';base64,' . base64_encode($data);
        }

        // Fetch current schema columns to be sure
        $stmt_cols = $conn->query("DESCRIBE employees");
        $all_cols = $stmt_cols->fetchAll(PDO::FETCH_COLUMN);
        
        $sql = "UPDATE employees SET contact_number = ?, address = ?, age = ?, experience_years = ?, skills = ?, work_location = ?, job_title = ?, base64_image = ?";
        $params = [$contact, $address, $age, $experience, $skills, $work_location, $job_title, $base64_image];

        if (in_array('first_name', $all_cols)) {
            $sql .= ", first_name = ?, last_name = ?";
            $params[] = $first_name;
            $params[] = $last_name;
        } elseif (in_array('full_name', $all_cols)) {
            $sql .= ", full_name = ?";
            $params[] = $first_name . ' ' . $last_name;
        } else {
            $sql .= ", name = ?";
            $params[] = $first_name . ' ' . $last_name;
        }

        $sql .= " WHERE email = ?";
        $params[] = $email;

        $update = $conn->prepare($sql);
        if ($update->execute($params)) {
             $msg = "<div class='bg-green-100 text-green-700 p-3 rounded mb-4 shadow-sm border border-green-200 animate-bounce'><i class='fas fa-check-circle mr-2'></i>Profile updated successfully!</div>";
             
             // Refresh Data
             $stmt->execute([$email]);
             $new_result = $stmt->fetch(PDO::FETCH_ASSOC);
             if ($new_result) {
                 $employee = array_merge($employee, $new_result);
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
    $photo = 'https://ui-avatars.com/api/?name=' . urlencode(($employee['first_name'] ?? 'Guest') . ' ' . ($employee['last_name'] ?? ''));
}
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
        body { font-family: 'Poppins', sans-serif; background: #f0f2f5; }
        .glass-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.5);
        }
        .profile-header-gradient {
            background: linear-gradient(135deg, #4f46e5 0%, #3b82f6 100%);
        }
        .animate-fade-in {
            animation: fadeIn 0.5s ease-out forwards;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body class="min-h-screen pb-12">

    <!-- Navbar -->
    <nav class="bg-gray-950 border-b border-gray-800 shadow-2xl sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center gap-4">
                    <a href="Dashboard.php" class="w-9 h-9 rounded-xl bg-gray-900 border border-gray-800 flex items-center justify-center text-gray-400 hover:text-white transition-all">
                        <i class="fas fa-chevron-left text-xs"></i>
                    </a>
                    <span class="text-lg font-black text-white uppercase tracking-tighter">My <span class="text-indigo-500 text-xs font-bold tracking-widest ml-1">Profile</span></span>
                </div>
                <div class="flex items-center gap-4">
                    <button onclick="window.print()" class="text-gray-400 hover:text-white transition-colors">
                        <i class="fas fa-print"></i>
                    </button>
                    <div class="relative group">
                        <img class="h-9 w-9 rounded-xl border-2 border-gray-800 group-hover:border-indigo-500 transition-all shadow-lg" src="<?php echo $photo; ?>" alt="Profile">
                        <div class="absolute -top-1 -right-1 w-2.5 h-2.5 bg-green-500 border-2 border-gray-950 rounded-full"></div>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <main class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8 animate-fade-in">
        <?php echo $msg; ?>

        <!-- Main Profile Body (Premium Candidate View Style) -->
        <div class="bg-white rounded-[2rem] shadow-2xl shadow-indigo-200/50 overflow-hidden border border-gray-100">
            <div class="p-4 sm:p-10">
                <div class="flex flex-col md:flex-row gap-12">
                    <!-- Left Column: Identity Card -->
                    <div class="w-full md:w-1/3 flex flex-col items-center">
                        <div class="relative group mb-6">
                            <div class="w-48 h-48 rounded-[2.5rem] overflow-hidden border-4 border-indigo-50 shadow-xl relative">
                                <img src="<?php echo $photo; ?>" class="w-full h-full object-cover bg-gray-50" id="profileDisplay">
                                <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center cursor-pointer">
                                    <i class="fas fa-camera text-white text-2xl"></i>
                                </div>
                            </div>
                            <div class="absolute -bottom-2 -right-2 w-10 h-10 bg-green-500 border-4 border-white rounded-[1rem] shadow-lg flex items-center justify-center">
                                <i class="fas fa-check text-white text-xs"></i>
                            </div>
                        </div>

                        <h2 class="text-2xl font-black text-gray-900 uppercase tracking-tight text-center">
                            <?php echo htmlspecialchars(($employee['first_name'] ?? 'Guest') . ' ' . ($employee['last_name'] ?? '')); ?>
                        </h2>
                        <p class="text-[10px] text-gray-400 font-black uppercase tracking-[0.2em] mb-4 text-center">
                            <?php echo htmlspecialchars($employee['position'] ?? 'Employee'); ?>
                        </p>

                        <div class="flex justify-center mb-8">
                            <span class="px-5 py-1.5 bg-green-50 text-green-600 text-[10px] font-black uppercase rounded-full border border-green-100 tracking-widest">
                                <?php echo htmlspecialchars($employee['status'] ?? 'Active'); ?>
                            </span>
                        </div>

                        <!-- Info List -->
                        <div class="w-full space-y-4">
                            <div class="flex items-center gap-4 bg-gray-50 p-4 rounded-2xl border border-gray-100 hover:bg-white hover:border-indigo-100 transition-all group">
                                <div class="w-10 h-10 rounded-xl bg-indigo-50 text-indigo-500 flex items-center justify-center shrink-0 group-hover:bg-indigo-600 group-hover:text-white transition-colors">
                                    <i class="fas fa-envelope text-sm"></i>
                                </div>
                                <div class="overflow-hidden">
                                    <span class="text-[10px] font-black text-gray-400 uppercase tracking-widest block mb-0.5">Email</span>
                                    <span class="text-sm font-bold text-gray-700 block truncate"><?php echo htmlspecialchars($employee['email']); ?></span>
                                </div>
                            </div>

                            <div class="flex items-center gap-4 bg-gray-50 p-4 rounded-2xl border border-gray-100 hover:bg-white hover:border-indigo-100 transition-all group">
                                <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-500 flex items-center justify-center shrink-0 group-hover:bg-blue-600 group-hover:text-white transition-colors">
                                    <i class="fas fa-phone text-sm"></i>
                                </div>
                                <div class="overflow-hidden">
                                    <span class="text-[10px] font-black text-gray-400 uppercase tracking-widest block mb-0.5">Contact</span>
                                    <span class="text-sm font-bold text-gray-700 block"><?php echo htmlspecialchars($employee['contact_number'] ?: 'Not Provided'); ?></span>
                                </div>
                            </div>

                            <div class="flex items-center gap-4 bg-gray-50 p-4 rounded-2xl border border-gray-100 hover:bg-white hover:border-indigo-100 transition-all group">
                                <div class="w-10 h-10 rounded-xl bg-purple-50 text-purple-500 flex items-center justify-center shrink-0 group-hover:bg-purple-600 group-hover:text-white transition-colors">
                                    <i class="fas fa-map-marker-alt text-sm"></i>
                                </div>
                                <div class="overflow-hidden">
                                    <span class="text-[10px] font-black text-gray-400 uppercase tracking-widest block mb-0.5">Address</span>
                                    <span class="text-sm font-bold text-gray-700 block line-clamp-2"><?php echo htmlspecialchars($employee['address'] ?: 'Not Set'); ?></span>
                                </div>
                            </div>

                            <button onclick="openEditModal()" class="w-full mt-6 bg-indigo-600 text-white font-black text-[11px] uppercase tracking-widest py-4 rounded-2xl shadow-xl shadow-indigo-600/30 hover:bg-indigo-700 hover:-translate-y-1 transition-all active:scale-95 flex items-center justify-center gap-3">
                                <i class="fas fa-edit"></i>
                                <span>Edit Information</span>
                            </button>
                        </div>
                    </div>

                    <!-- Right Column: Details Grid -->
                    <div class="w-full md:w-2/3 space-y-10">
                        <!-- Professional Details Section -->
                        <div>
                            <h3 class="text-xs font-black text-gray-400 uppercase tracking-[0.3em] mb-6 flex items-center gap-3">
                                <span class="w-6 h-0.5 bg-indigo-500"></span>
                                PROFESSIONAL DETAILS
                            </h3>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div class="bg-gray-50 p-5 rounded-2xl border border-gray-100 hover:border-indigo-100 transition-all hover:bg-white group">
                                    <span class="text-[10px] font-black text-gray-400 uppercase tracking-widest block mb-2 group-hover:text-indigo-500 transition-colors">Job Title</span>
                                    <span class="text-sm font-bold text-gray-800"><?php echo htmlspecialchars($employee['job_title'] ?: ($employee['position'] ?? 'Not Set')); ?></span>
                                </div>
                                <div class="bg-gray-50 p-5 rounded-2xl border border-gray-100 hover:border-indigo-100 transition-all hover:bg-white group">
                                    <span class="text-[10px] font-black text-gray-400 uppercase tracking-widest block mb-2 group-hover:text-indigo-500 transition-colors">Experience</span>
                                    <span class="text-sm font-bold text-gray-800"><?php echo htmlspecialchars($employee['experience_years'] ?: '0'); ?> Years</span>
                                </div>
                                <div class="bg-gray-50 p-5 rounded-2xl border border-gray-100 hover:border-indigo-100 transition-all hover:bg-white group">
                                    <span class="text-[10px] font-black text-gray-400 uppercase tracking-widest block mb-2 group-hover:text-indigo-500 transition-colors">Department</span>
                                    <span class="text-sm font-bold text-gray-800"><?php echo htmlspecialchars($employee['department'] ?: 'General'); ?></span>
                                </div>
                                <div class="bg-gray-50 p-5 rounded-2xl border border-gray-100 hover:border-indigo-100 transition-all hover:bg-white group">
                                    <span class="text-[10px] font-black text-gray-400 uppercase tracking-widest block mb-2 group-hover:text-indigo-500 transition-colors">Date Hired</span>
                                    <span class="text-sm font-bold text-gray-800"><?php echo date('F d, Y', strtotime($employee['date_hired'])); ?></span>
                                </div>
                                <div class="bg-gray-50 p-5 rounded-2xl border border-gray-100 hover:border-indigo-100 transition-all hover:bg-white group">
                                    <span class="text-[10px] font-black text-gray-400 uppercase tracking-widest block mb-2 group-hover:text-indigo-500 transition-colors">Age</span>
                                    <span class="text-sm font-bold text-gray-800"><?php echo $employee['age'] ?: 'Not Set'; ?></span>
                                </div>
                                <div class="bg-gray-50 p-5 rounded-2xl border border-gray-100 hover:border-indigo-100 transition-all hover:bg-white group">
                                    <span class="text-[10px] font-black text-gray-400 uppercase tracking-widest block mb-2 group-hover:text-indigo-500 transition-colors">Assigned Manager</span>
                                    <span class="text-sm font-bold text-gray-800"><?php echo htmlspecialchars($employee['manager_name'] ?: 'Not Assigned'); ?></span>
                                </div>
                                <div class="bg-gray-50 p-5 rounded-2xl border border-gray-100 hover:border-indigo-100 transition-all hover:bg-white group col-span-1 sm:col-span-2">
                                    <span class="text-[10px] font-black text-gray-400 uppercase tracking-widest block mb-2 group-hover:text-indigo-500 transition-colors">Work Location</span>
                                    <span class="text-sm font-bold text-gray-800"><?php echo htmlspecialchars($employee['work_location'] ?: 'Not Set'); ?></span>
                                </div>
                            </div>
                        </div>

                        <!-- Skills Section -->
                        <div>
                            <h3 class="text-xs font-black text-gray-400 uppercase tracking-[0.3em] mb-6 flex items-center gap-3">
                                <span class="w-6 h-0.5 bg-indigo-500"></span>
                                SKILLS
                            </h3>
                            <div class="flex flex-wrap gap-2">
                                <?php 
                                if (!empty($employee['skills'])) {
                                    $skills = explode(',', $employee['skills']);
                                    foreach ($skills as $skill) {
                                        echo '<span class="px-4 py-2 bg-indigo-50 text-indigo-700 text-[10px] font-black uppercase rounded-xl border border-indigo-100 hover:bg-indigo-600 hover:text-white hover:-translate-y-1 transition-all cursor-default">' . trim($skill) . '</span>';
                                    }
                                } else {
                                    echo '<span class="text-sm text-gray-400 italic">No skills added yet.</span>';
                                }
                                ?>
                            </div>
                        </div>

                         <!-- Notes Section -->
                         <div>
                            <h3 class="text-xs font-black text-gray-400 uppercase tracking-[0.3em] mb-6 flex items-center gap-3">
                                <span class="w-6 h-0.5 bg-indigo-500"></span>
                                ADDITIONAL NOTES
                            </h3>
                            <div class="relative bg-amber-50 rounded-[2rem] p-8 border border-amber-100 shadow-sm overflow-hidden group">
                                <i class="fas fa-quote-left absolute -top-4 -left-4 text-amber-200 text-6xl opacity-20 group-hover:scale-110 transition-transform"></i>
                                <p class="relative z-10 text-gray-700 text-sm leading-relaxed italic">
                                    <?php echo nl2br(htmlspecialchars($employee['notes'] ?: 'No additional notes or background information provided. Click edit to add details about your career goals or expertise.')); ?>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Edit Profile Modal -->
    <div id="editModal" class="hidden fixed inset-0 z-[100] flex items-center justify-center p-4 bg-gray-950/80 backdrop-blur-md overflow-y-auto">
        <div class="bg-white rounded-[2.5rem] shadow-2xl w-full max-w-2xl overflow-hidden transform transition-all animate-fade-in my-auto">
            <div class="bg-indigo-600 p-8 text-white flex justify-between items-center">
                <div>
                    <h3 class="text-xl font-black uppercase tracking-tight">Edit Profile</h3>
                    <p class="text-indigo-100 text-xs font-bold uppercase tracking-widest mt-1">Update your personal & professional details</p>
                </div>
                <button onclick="closeEditModal()" class="w-10 h-10 rounded-full bg-white/20 hover:bg-white/40 transition-colors flex items-center justify-center">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <form method="POST" enctype="multipart/form-data" class="p-8 space-y-6">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                    <div class="col-span-full">
                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Change Profile Picture</label>
                        <input type="file" name="profile_image" accept="image/*"
                            class="w-full px-5 py-3 border border-gray-200 rounded-xl focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 outline-none transition-all text-sm font-bold text-gray-700">
                    </div>
                    <div>
                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">First Name</label>
                        <input type="text" name="first_name" value="<?php echo htmlspecialchars($employee['first_name']); ?>" required
                            class="w-full px-5 py-3 border border-gray-200 rounded-xl focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 outline-none transition-all text-sm font-bold text-gray-700">
                    </div>
                    <div>
                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Last Name</label>
                        <input type="text" name="last_name" value="<?php echo htmlspecialchars($employee['last_name']); ?>" required
                            class="w-full px-5 py-3 border border-gray-200 rounded-xl focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 outline-none transition-all text-sm font-bold text-gray-700">
                    </div>
                    <div>
                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Contact Number</label>
                        <input type="text" name="contact_number" value="<?php echo htmlspecialchars($employee['contact_number']); ?>"
                            class="w-full px-5 py-3 border border-gray-200 rounded-xl focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 outline-none transition-all text-sm font-bold text-gray-700">
                    </div>
                    <div>
                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Age</label>
                        <input type="number" name="age" value="<?php echo htmlspecialchars($employee['age']); ?>"
                            class="w-full px-5 py-3 border border-gray-200 rounded-xl focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 outline-none transition-all text-sm font-bold text-gray-700">
                    </div>
                    <div class="col-span-full">
                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Job Title / Current Role</label>
                        <input type="text" name="job_title" value="<?php echo htmlspecialchars($employee['job_title'] ?: $employee['position']); ?>"
                            class="w-full px-5 py-3 border border-gray-200 rounded-xl focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 outline-none transition-all text-sm font-bold text-gray-700">
                    </div>
                    <div>
                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Total Years Experience</label>
                        <input type="number" name="experience_years" value="<?php echo htmlspecialchars($employee['experience_years']); ?>"
                            class="w-full px-5 py-3 border border-gray-200 rounded-xl focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 outline-none transition-all text-sm font-bold text-gray-700">
                    </div>
                    <div>
                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Work Location</label>
                        <input type="text" name="work_location" value="<?php echo htmlspecialchars($employee['work_location']); ?>"
                            class="w-full px-5 py-3 border border-gray-200 rounded-xl focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 outline-none transition-all text-sm font-bold text-gray-700">
                    </div>
                    <div class="col-span-full">
                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Home Address</label>
                        <textarea name="address" rows="2"
                            class="w-full px-5 py-3 border border-gray-200 rounded-xl focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 outline-none transition-all text-sm font-bold text-gray-700"><?php echo htmlspecialchars($employee['address']); ?></textarea>
                    </div>
                    <div class="col-span-full">
                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Skills (Comma separated)</label>
                        <input type="text" name="skills" value="<?php echo htmlspecialchars($employee['skills']); ?>" placeholder="PHP, JavaScript, UX Design"
                            class="w-full px-5 py-3 border border-gray-200 rounded-xl focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 outline-none transition-all text-sm font-bold text-gray-700">
                    </div>
                </div>

                <div class="flex gap-4 pt-4">
                    <button type="button" onclick="closeEditModal()" class="flex-1 px-8 py-4 border border-gray-200 text-gray-500 font-black text-[11px] uppercase tracking-widest rounded-2xl hover:bg-gray-50 transition-all">Cancel</button>
                    <button type="submit" name="update_profile" class="flex-1 px-8 py-4 bg-indigo-600 text-white font-black text-[11px] uppercase tracking-widest rounded-2xl shadow-xl shadow-indigo-600/30 hover:bg-indigo-700 transition-all">Save Profile</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openEditModal() {
            document.getElementById('editModal').classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        }
        function closeEditModal() {
            document.getElementById('editModal').classList.add('hidden');
            document.body.style.overflow = '';
        }
    </script>
</body>
</html>

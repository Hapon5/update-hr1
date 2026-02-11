<?php
session_start();
// Specific root path for Super-admin/Modules
$root_path = '../../';
require_once $root_path . "Database/Connections.php";

// Migration for account archiving
try { $conn->exec("ALTER TABLE logintbl ADD COLUMN is_archived TINYINT(1) DEFAULT 0"); } catch (Exception $e) {}
try { $conn->exec("ALTER TABLE candidates ADD COLUMN is_archived TINYINT(1) DEFAULT 0"); } catch (Exception $e) {}

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
                // Using all required fields to prevent SQL default value errors (Age, Address included)
                $stmtCand = $conn->prepare("INSERT INTO candidates (full_name, email, status, job_title, position, contact_number, experience_years, age, address) VALUES (?, ?, 'new', ?, ?, 'N/A', 0, 0, 'N/A')");
                $jobTitle = ($role == '0' || $role == '1') ? 'Administrator' : 'Employee';
                $position = ($role == '0' || $role == '1') ? 'System Controller' : 'Staff';
                $stmtCand->execute([$name, $email, $jobTitle, $position]);

                $conn->commit();
                $msg = "<div class='bg-indigo-50 text-indigo-700 p-4 rounded-xl mb-6 shadow-sm border border-indigo-100 text-[10px] font-black uppercase tracking-widest text-center'>Account created successfully for $name!</div>";
            }
        } catch (Exception $e) {
            $conn->rollBack();
            $error = "Error: " . $e->getMessage();
        }
    }
}

// Handle Delete Account
if (isset($_GET['delete'])) {
    $deleteId = $_GET['delete'];
    try {
        // Prevent deleting yourself
        $stmtEmail = $conn->prepare("SELECT Email FROM logintbl WHERE LoginID = ?");
        $stmtEmail->execute([$deleteId]);
        $row = $stmtEmail->fetch();
        
        if ($row && $row['Email'] !== $_SESSION['Email']) {
            // Security: HR Admin (1) cannot delete Super Admin (0)
            $stmtTarget = $conn->prepare("SELECT Account_type FROM logintbl WHERE LoginID = ?");
            $stmtTarget->execute([$deleteId]);
            $target = $stmtTarget->fetch();
            
            if ($_SESSION['Account_type'] == 1 && $target && $target['Account_type'] == 0) {
                $error = "Access Denied: HR Admins cannot delete Super Admin accounts.";
            } else {
                $conn->beginTransaction();
                // Archive in logintbl
                $stmtDel = $conn->prepare("UPDATE logintbl SET is_archived = 1 WHERE LoginID = ?");
                if ($stmtDel->execute([$deleteId])) {
                    // Also archive in candidates if applicable
                    $stmtDelCand = $conn->prepare("UPDATE candidates SET is_archived = 1 WHERE email = ?");
                    $stmtDelCand->execute([$row['Email']]);
                    
                    $conn->commit();
                    $msg = "<div class='bg-amber-50 text-amber-600 p-4 rounded-xl mb-6 border border-amber-100 text-[10px] font-black uppercase tracking-widest text-center'>Account archived successfully.</div>";
                }
            }
        } else {
            $error = "Access Denied: Cannot delete the active session account.";
        }
    } catch (Exception $e) {
        $conn->rollBack();
        $error = "Delete failed: " . $e->getMessage();
    }
}

// Fetch all accounts
$accounts = [];
try {
    $stmt = $conn->query("SELECT l.LoginID, l.Email, l.Account_type, c.full_name 
                         FROM logintbl l 
                         LEFT JOIN candidates c ON l.Email = c.email 
                         WHERE l.is_archived = 0
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
        body { font-family: 'Poppins', sans-serif; background: #ffffff; color: #1e293b; }
        .glass-card {
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(0, 0, 0, 0.05);
            box-shadow: 0 20px 40px -10px rgba(0, 0, 0, 0.05);
        }
        .custom-scrollbar::-webkit-scrollbar { width: 4px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: rgba(0, 0, 0, 0.1); border-radius: 10px; }

        .tab-btn.active {
            background: white;
            color: #4f46e5;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        }
    </style>
</head>
<body class="min-h-screen">

    <?php include '../Components/sidebar.php'; ?>
    <?php include '../Components/header.php'; ?>

    <main class="ml-64 pt-28 px-8 pb-12 transition-all duration-300" id="mainContent">
        <div class="max-w-7xl mx-auto">
            <!-- Header -->
            <div class="mb-10">
                <h1 class="text-3xl font-black tracking-tighter uppercase text-gray-900">Account <span class="text-indigo-600">Management</span></h1>
                <p class="text-gray-400 text-[10px] font-black uppercase tracking-[0.3em] mt-1">Unified System Control Terminal</p>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Add Account Form -->
                <div class="lg:col-span-1">
                    <div class="glass-card rounded-[40px] p-8 sticky top-28">
                        <h3 class="text-sm font-black uppercase tracking-widest text-gray-900 mb-8 border-b border-gray-100 pb-4">
                            New Account
                        </h3>

                        <?php if ($error): ?>
                            <div class="bg-red-50 border border-red-100 text-red-500 p-4 rounded-2xl mb-6 text-[10px] font-black uppercase tracking-widest">
                                <?php echo $error; ?>
                            </div>
                        <?php endif; ?>

                        <?php if ($msg): ?>
                            <?php echo $msg; ?>
                        <?php endif; ?>

                        <form method="POST" class="space-y-6">
                            <div>
                                <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2 px-1">Full Name</label>
                                <input type="text" name="name" placeholder="John Doe" class="w-full bg-gray-50 border border-gray-100 rounded-2xl px-5 py-4 text-sm font-bold text-gray-900 focus:ring-2 focus:ring-indigo-500/20 outline-none transition-all placeholder:text-gray-300" required>
                            </div>

                            <div>
                                <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2 px-1">Email Address</label>
                                <input type="email" name="email" placeholder="john@example.com" class="w-full bg-gray-50 border border-gray-100 rounded-2xl px-5 py-4 text-sm font-bold text-gray-900 focus:ring-2 focus:ring-indigo-500/20 outline-none transition-all placeholder:text-gray-300" required>
                            </div>

                            <div>
                                <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2 px-1">Temporary Password</label>
                                <input type="password" name="password" placeholder="••••••••" class="w-full bg-gray-50 border border-gray-100 rounded-2xl px-5 py-4 text-sm font-bold text-gray-900 focus:ring-2 focus:ring-indigo-500/20 outline-none transition-all placeholder:text-gray-300" required>
                            </div>

                            <div>
                                <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2 px-1">Account Role</label>
                                <div class="relative">
                                    <select name="role" class="w-full bg-gray-50 border border-gray-100 rounded-2xl px-5 py-4 text-sm font-bold text-gray-900 focus:ring-2 focus:ring-indigo-500/20 outline-none transition-all appearance-none cursor-pointer">
                                        <option value="3">Employee</option>
                                        <option value="1">HR Admin</option>
                                        <option value="2">Staff</option>
                                        <option value="0">Super Admin</option>
                                    </select>
                                    <i class="fas fa-chevron-down absolute right-5 top-1/2 -translate-y-1/2 text-gray-400 text-[10px] pointer-events-none"></i>
                                </div>
                            </div>

                            <button type="submit" name="add_account" class="w-full bg-indigo-600 text-white font-black text-[11px] uppercase tracking-[0.25em] py-5 rounded-2xl shadow-xl shadow-indigo-100 hover:bg-indigo-700 hover:scale-[1.02] active:scale-95 transition-all mt-4">
                                Create Account
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Accounts List -->
                <div class="lg:col-span-2">
                    <!-- Tabs -->
                    <div class="flex gap-2 mb-6 bg-gray-100/50 p-1.5 rounded-2xl w-fit border border-gray-100">
                        <button onclick="filterAccounts('all')" id="tab-all" class="tab-btn active px-6 py-2.5 rounded-xl text-[10px] font-black uppercase tracking-widest transition-all">All</button>
                        <button onclick="filterAccounts('admin')" id="tab-admin" class="tab-btn px-6 py-2.5 rounded-xl text-[10px] font-black uppercase tracking-widest transition-all text-gray-400 hover:text-gray-600">Admin List</button>
                        <button onclick="filterAccounts('employee')" id="tab-employee" class="tab-btn px-6 py-2.5 rounded-xl text-[10px] font-black uppercase tracking-widest transition-all text-gray-400 hover:text-gray-600">Employee List</button>
                    </div>

                    <div class="glass-card rounded-[40px] overflow-hidden">
                        <div class="px-10 py-8 border-b border-gray-50 flex justify-between items-center bg-gray-50/30">
                            <div>
                                <h3 class="text-xs font-black uppercase tracking-[0.2em] text-gray-900 mb-1" id="listTitle">System Accounts</h3>
                                <p class="text-[9px] font-bold text-gray-400 uppercase tracking-widest">System Overview</p>
                            </div>
                            <span class="px-4 py-2 bg-indigo-50 text-indigo-600 text-[10px] font-black uppercase rounded-xl border border-indigo-100 shadow-sm" id="recordCount"><?php echo count($accounts); ?> Records</span>
                        </div>
                        
                        <div class="overflow-x-auto custom-scrollbar">
                            <table class="w-full">
                                <thead>
                                    <tr class="text-left">
                                        <th class="px-10 py-6 text-[10px] font-black text-gray-400 uppercase tracking-[0.2em]">User Identity</th>
                                        <th class="px-10 py-6 text-[10px] font-black text-gray-400 uppercase tracking-[0.2em]">Email</th>
                                        <th class="px-10 py-6 text-[10px] font-black text-gray-400 uppercase tracking-[0.2em]">Access Role</th>
                                        <th class="px-10 py-6 text-[10px] font-black text-gray-400 uppercase tracking-[0.2em] text-right">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-50" id="accountTableBody">
                                    <?php foreach ($accounts as $acc): 
                                        $is_admin = in_array($acc['Account_type'], [0, 1]);
                                        $category = $is_admin ? 'admin' : 'employee';
                                    ?>
                                    <tr class="account-row hover:bg-gray-50/50 transition-colors group" data-category="<?php echo $category; ?>">
                                        <td class="px-10 py-6">
                                            <div class="flex items-center gap-4">
                                                <div class="w-11 h-11 rounded-2xl bg-indigo-50 text-indigo-500 flex items-center justify-center font-black text-xs border border-indigo-100 shadow-sm scale-95 group-hover:scale-100 transition-transform">
                                                    <?php echo strtoupper(substr($acc['full_name'] ?? 'U', 0, 1)); ?>
                                                </div>
                                                <div>
                                                    <span class="text-sm font-black text-gray-900 block truncate max-w-[150px]"><?php echo htmlspecialchars($acc['full_name'] ?? 'System User'); ?></span>
                                                    <span class="text-[9px] font-black text-green-500 uppercase tracking-widest">Active</span>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-10 py-6">
                                            <span class="text-xs text-gray-500 font-bold"><?php echo htmlspecialchars($acc['Email']); ?></span>
                                        </td>
                                        <td class="px-10 py-6">
                                            <?php
                                            $roles = [
                                                '0' => ['Super Admin', 'bg-red-50 text-red-500 border-red-100 shadow-red-50/20'],
                                                '1' => ['HR Admin', 'bg-blue-50 text-blue-500 border-blue-100 shadow-blue-50/20'],
                                                '2' => ['Staff', 'bg-amber-50 text-amber-500 border-amber-100 shadow-amber-50/20'],
                                                '3' => ['Employee', 'bg-emerald-50 text-emerald-500 border-emerald-100 shadow-emerald-50/20']
                                            ];
                                            $roleInfo = $roles[$acc['Account_type']] ?? ['Unknown', 'bg-gray-50 text-gray-400 border-gray-100'];
                                            ?>
                                            <span class="px-4 py-2 rounded-xl text-[9px] font-black uppercase tracking-widest border shadow-sm <?php echo $roleInfo[1]; ?>">
                                                <?php echo $roleInfo[0]; ?>
                                            </span>
                                        </td>
                                        <td class="px-10 py-6 text-right">
                                             <div class="flex justify-end gap-3 opacity-60 group-hover:opacity-100 transition-opacity">
                                                 <button class="w-9 h-9 rounded-xl bg-gray-50 text-gray-400 hover:text-indigo-600 hover:bg-white hover:shadow-lg transition-all border border-transparent hover:border-indigo-100">
                                                     <i class="fas fa-edit text-[10px]"></i>
                                                 </button>
                                                 <a href="?delete=<?php echo $acc['LoginID']; ?>" 
                                                    onclick="return confirm('Archive Account: Are you sure you want to move this account to archives?')"
                                                    class="w-9 h-9 rounded-xl bg-gray-50 text-gray-400 hover:text-amber-500 hover:bg-white hover:shadow-lg transition-all border border-transparent hover:border-amber-100 flex items-center justify-center">
                                                     <i class="fas fa-box-archive text-[10px]"></i>
                                                 </a>
                                             </div>
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
        function filterAccounts(category) {
            // Update tabs
            document.querySelectorAll('.tab-btn').forEach(btn => {
                btn.classList.remove('active', 'text-gray-900');
                btn.classList.add('text-gray-400', 'hover:text-gray-600');
            });
            const activeBtn = document.getElementById('tab-' + category);
            activeBtn.classList.add('active', 'text-gray-900');
            activeBtn.classList.remove('text-gray-400', 'hover:text-gray-600');

            // Filter rows
            const rows = document.querySelectorAll('.account-row');
            let visibleCount = 0;
            
            rows.forEach(row => {
                if (category === 'all' || row.dataset.category === category) {
                    row.style.display = 'table-row';
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                }
            });

            // Update header
            const titles = {
                'all': 'System Accounts',
                'admin': 'Administrative List',
                'employee': 'Employee Directory'
            };
            document.getElementById('listTitle').innerText = titles[category];
            document.getElementById('recordCount').innerText = visibleCount + ' Records';
        }

        document.addEventListener('DOMContentLoaded', () => {
             // Initial load logic if needed
        });
    </script>
</body>
</html>

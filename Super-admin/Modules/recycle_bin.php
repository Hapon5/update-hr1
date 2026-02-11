<?php
session_start();
include '../../Database/Connections.php';

// Handle Actions (Restore/Delete)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    $input = json_decode(file_get_contents('php://input'), true);
    
    try {
        if (isset($input['action'])) {
            $table = '';
            if ($input['type'] === 'interview') $table = 'interviews';
            elseif ($input['type'] === 'candidate') $table = 'candidates';
            
            if ($table) {
                if ($input['action'] === 'restore') {
                    $stmt = $conn->prepare("UPDATE $table SET is_archived = 0 WHERE id = ?");
                    $stmt->execute([$input['id']]);
                    echo json_encode(['status' => 'success', 'message' => 'Item restored successfully']);
                } elseif ($input['action'] === 'delete') {
                    $stmt = $conn->prepare("DELETE FROM $table WHERE id = ?");
                    $stmt->execute([$input['id']]);
                    echo json_encode(['status' => 'success', 'message' => 'Item permanently deleted']);
                }
            } elseif ($input['type'] === 'account') {
                 // Special handling for accounts (logintbl)
                 if ($input['action'] === 'restore') {
                    $stmt = $conn->prepare("UPDATE logintbl SET is_archived = 0 WHERE LoginID = ?");
                    $stmt->execute([$input['id']]);
                    // Also try to restore in candidates by email
                    $stmtEmail = $conn->prepare("SELECT Email FROM logintbl WHERE LoginID = ?");
                    $stmtEmail->execute([$input['id']]);
                    if($row = $stmtEmail->fetch()){
                        $conn->prepare("UPDATE candidates SET is_archived = 0 WHERE email = ?")->execute([$row['Email']]);
                    }
                    echo json_encode(['status' => 'success', 'message' => 'Account restored successfully']);
                } elseif ($input['action'] === 'delete') {
                    $stmt = $conn->prepare("DELETE FROM logintbl WHERE LoginID = ?");
                    $stmt->execute([$input['id']]);
                    echo json_encode(['status' => 'success', 'message' => 'Account permanently deleted']);
                }
            } else {
                 echo json_encode(['status' => 'error', 'message' => 'Invalid type']);
            }
        }
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit;
}

// Fetch Archived Items
$archived_interviews = [];
try {
    $stmt = $conn->query("SELECT * FROM interviews WHERE is_archived = 1 ORDER BY start_time DESC");
    if($stmt) $archived_interviews = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // Table might not exist or column missing
}

$archived_candidates = [];
try {
    $stmt = $conn->query("SELECT * FROM candidates WHERE is_archived = 1 ORDER BY id DESC");
    if($stmt) $archived_candidates = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // Table might not exist or column missing
}

$archived_accounts = [];
try {
    $stmt = $conn->query("SELECT l.LoginID, l.Email, l.Account_type, c.full_name 
                         FROM logintbl l 
                         LEFT JOIN candidates c ON l.Email = c.email 
                         WHERE l.is_archived = 1
                         ORDER BY l.LoginID DESC");
    if($stmt) $archived_accounts = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}

$root_path = '../../';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recycle Bin | HR1 Admin</title>
    <!-- Fonts & Icons -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['Poppins', 'sans-serif'] },
                    colors: {
                        brand: { 500: '#6366f1', 600: '#4f46e5', 50: '#eef2ff' },
                    }
                }
            }
        }
    </script>
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        .custom-scrollbar::-webkit-scrollbar { width: 6px; height: 6px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: #f1f1f1; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #c7c7c7; border-radius: 10px; }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #a0a0a0; }
    </style>
</head>
<body class="bg-[#f8f9fa] text-gray-800 font-sans">

    <?php include '../Components/sidebar.php'; ?>

    <div class="ml-64 transition-all duration-300 min-h-screen flex flex-col">
        <?php include '../Components/header.php'; ?>

        <main class="p-8 mt-20 flex-grow">
            <!-- Header -->
            <div class="mb-8">
                <h1 class="text-2xl font-black text-gray-900 uppercase tracking-tight">Recycle Bin</h1>
                <p class="text-[10px] text-gray-400 uppercase font-bold tracking-widest mt-1">Manage deleted records and restore data.</p>
            </div>

            <!-- Tabs -->
            <div class="flex gap-4 mb-6 border-b border-gray-200">
                <button onclick="switchTab('interviews')" id="tab-interviews" class="px-6 py-3 text-sm font-bold uppercase tracking-widest text-indigo-600 border-b-2 border-indigo-600 transition-all hover:text-indigo-800">
                    Interviews <span class="ml-2 bg-indigo-100 text-indigo-600 px-2 py-0.5 rounded-full text-[10px]"><?= count($archived_interviews) ?></span>
                </button>
                <button onclick="switchTab('candidates')" id="tab-candidates" class="px-6 py-3 text-sm font-bold uppercase tracking-widest text-gray-400 border-b-2 border-transparent transition-all hover:text-gray-600 hover:bg-gray-50">
                    Candidates <span class="ml-2 bg-gray-100 text-gray-500 px-2 py-0.5 rounded-full text-[10px]"><?= count($archived_candidates) ?></span>
                </button>
                <button onclick="switchTab('accounts')" id="tab-accounts" class="px-6 py-3 text-sm font-bold uppercase tracking-widest text-gray-400 border-b-2 border-transparent transition-all hover:text-gray-600 hover:bg-gray-50">
                    Accounts <span class="ml-2 bg-gray-100 text-gray-500 px-2 py-0.5 rounded-full text-[10px]"><?= count($archived_accounts) ?></span>
                </button>
            </div>

            <!-- Interviews Content -->
            <div id="content-interviews" class="tab-content transition-all duration-300">
                <?php if (empty($archived_interviews)): ?>
                    <div class="flex flex-col items-center justify-center p-12 bg-white rounded-xl shadow-sm border border-gray-100 dashed-border">
                        <div class="w-16 h-16 bg-gray-50 rounded-full flex items-center justify-center mb-4">
                            <i class="fas fa-trash-alt text-gray-300 text-2xl"></i>
                        </div>
                        <p class="text-gray-400 font-medium">No archived interviews found.</p>
                    </div>
                <?php else: ?>
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm text-left">
                                <thead class="text-[10px] text-gray-400 uppercase bg-gray-50 border-b border-gray-100 tracking-widest font-bold">
                                    <tr>
                                        <th class="px-6 py-4">Candidate</th>
                                        <th class="px-6 py-4">Position</th>
                                        <th class="px-6 py-4">Integration Date</th>
                                        <th class="px-6 py-4 text-right">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    <?php foreach ($archived_interviews as $row): ?>
                                    <tr class="hover:bg-gray-50 transition-colors group">
                                        <td class="px-6 py-4">
                                            <div class="font-bold text-gray-900 uppercase tracking-tight"><?= htmlspecialchars($row['candidate_name']) ?></div>
                                            <div class="text-[10px] text-gray-400 uppercase font-bold"><?= htmlspecialchars($row['email']) ?></div>
                                        </td>
                                        <td class="px-6 py-4 text-gray-500"><?= htmlspecialchars($row['position']) ?></td>
                                        <td class="px-6 py-4 text-gray-400 text-xs">
                                            <?= date('M d, Y h:i A', strtotime($row['start_time'])) ?>
                                        </td>
                                        <td class="px-6 py-4 text-right space-x-2">
                                            <button onclick="restoreItem('interview', <?= $row['id'] ?>)" class="text-indigo-600 hover:text-indigo-900 font-bold text-xs uppercase tracking-wider transition-colors">
                                                <i class="fas fa-undo mr-1"></i> Restore
                                            </button>
                                            <button onclick="deleteItem('interview', <?= $row['id'] ?>)" class="text-red-400 hover:text-red-600 font-bold text-xs uppercase tracking-wider transition-colors ml-3">
                                                <i class="fas fa-trash mr-1"></i> Delete
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Candidates Content -->
            <div id="content-candidates" class="tab-content hidden transition-all duration-300">
                <?php if (empty($archived_candidates)): ?>
                    <div class="flex flex-col items-center justify-center p-12 bg-white rounded-xl shadow-sm border border-gray-100 dashed-border">
                         <div class="w-16 h-16 bg-gray-50 rounded-full flex items-center justify-center mb-4">
                            <i class="fas fa-user-slash text-gray-300 text-2xl"></i>
                        </div>
                        <p class="text-gray-400 font-medium">No archived candidates found.</p>
                    </div>
                <?php else: ?>
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm text-left">
                                <thead class="text-[10px] text-gray-400 uppercase bg-gray-50 border-b border-gray-100 tracking-widest font-bold">
                                    <tr>
                                        <th class="px-6 py-4">Full Name</th>
                                        <th class="px-6 py-4">Email</th>
                                        <th class="px-6 py-4">Job Applied</th>
                                        <th class="px-6 py-4 text-right">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    <?php foreach ($archived_candidates as $row): ?>
                                    <tr class="hover:bg-gray-50 transition-colors group">
                                        <td class="px-6 py-4">
                                            <div class="font-bold text-gray-900 uppercase tracking-tight"><?= htmlspecialchars($row['full_name'] ?? 'N/A') ?></div>
                                        </td>
                                        <td class="px-6 py-4 text-gray-500"><?= htmlspecialchars($row['email'] ?? 'N/A') ?></td>
                                        <td class="px-6 py-4 text-gray-500 text-xs uppercase tracking-wider font-bold">
                                            <?= htmlspecialchars($row['job_title'] ?? $row['position'] ?? 'N/A') ?>
                                        </td>
                                        <td class="px-6 py-4 text-right space-x-2">
                                            <button onclick="restoreItem('candidate', <?= $row['id'] ?>)" class="text-indigo-600 hover:text-indigo-900 font-bold text-xs uppercase tracking-wider transition-colors">
                                                <i class="fas fa-undo mr-1"></i> Restore
                                            </button>
                                            <button onclick="deleteItem('candidate', <?= $row['id'] ?>)" class="text-red-400 hover:text-red-600 font-bold text-xs uppercase tracking-wider transition-colors ml-3">
                                                <i class="fas fa-trash mr-1"></i> Delete
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Accounts Content -->
            <div id="content-accounts" class="tab-content hidden transition-all duration-300">
                <?php if (empty($archived_accounts)): ?>
                    <div class="flex flex-col items-center justify-center p-12 bg-white rounded-xl shadow-sm border border-gray-100 dashed-border">
                         <div class="w-16 h-16 bg-gray-50 rounded-full flex items-center justify-center mb-4">
                            <i class="fas fa-users-slash text-gray-300 text-2xl"></i>
                        </div>
                        <p class="text-gray-400 font-medium">No archived accounts found.</p>
                    </div>
                <?php else: ?>
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm text-left">
                                <thead class="text-[10px] text-gray-400 uppercase bg-gray-50 border-b border-gray-100 tracking-widest font-bold">
                                    <tr>
                                        <th class="px-6 py-4">User</th>
                                        <th class="px-6 py-4">Email</th>
                                        <th class="px-6 py-4">Role</th>
                                        <th class="px-6 py-4 text-right">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    <?php foreach ($archived_accounts as $row): 
                                        $roleMap = ['0' => 'Super Admin', '1' => 'HR Admin', '2' => 'Staff', '3' => 'Employee'];
                                        $role = $roleMap[$row['Account_type']] ?? 'Unknown';
                                    ?>
                                    <tr class="hover:bg-gray-50 transition-colors group">
                                        <td class="px-6 py-4">
                                            <div class="font-bold text-gray-900 uppercase tracking-tight"><?= htmlspecialchars($row['full_name'] ?? 'System User') ?></div>
                                        </td>
                                        <td class="px-6 py-4 text-gray-500"><?= htmlspecialchars($row['Email']) ?></td>
                                        <td class="px-6 py-4">
                                            <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-widest bg-gray-100 text-gray-500">
                                                <?= $role ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-right space-x-2">
                                            <button onclick="restoreItem('account', <?= $row['LoginID'] ?>)" class="text-indigo-600 hover:text-indigo-900 font-bold text-xs uppercase tracking-wider transition-colors">
                                                <i class="fas fa-undo mr-1"></i> Restore
                                            </button>
                                            <button onclick="deleteItem('account', <?= $row['LoginID'] ?>)" class="text-red-400 hover:text-red-600 font-bold text-xs uppercase tracking-wider transition-colors ml-3">
                                                <i class="fas fa-trash mr-1"></i> Delete
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

        </main>
    </div>

    <!-- Scripts -->
    <script>
        function switchTab(tab) {
            // Hide all
            document.querySelectorAll('.tab-content').forEach(el => el.classList.add('hidden'));
            document.querySelectorAll('button[id^="tab-"]').forEach(el => {
                el.classList.remove('text-indigo-600', 'border-indigo-600');
                el.classList.add('text-gray-400', 'border-transparent');
            });

            // Show selected
            document.getElementById('content-' + tab).classList.remove('hidden');
            const btn = document.getElementById('tab-' + tab);
            btn.classList.remove('text-gray-400', 'border-transparent');
            btn.classList.add('text-indigo-600', 'border-indigo-600');
        }

        function restoreItem(type, id) {
            Swal.fire({
                title: 'Restore Item?',
                text: "This item will be moved back to the active list.",
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#6366f1',
                confirmButtonText: 'Yes, Restore'
            }).then((result) => {
                if (result.isConfirmed) {
                    performAction('restore', type, id);
                }
            });
        }

        function deleteItem(type, id) {
            Swal.fire({
                title: 'Delete Permanently?',
                text: "You won't be able to revert this!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                confirmButtonText: 'Yes, Delete'
            }).then((result) => {
                if (result.isConfirmed) {
                    performAction('delete', type, id);
                }
            });
        }

        function performAction(action, type, id) {
            fetch('', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action, type, id })
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    Swal.fire('Success', data.message, 'success').then(() => location.reload());
                } else {
                    Swal.fire('Error', data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire('Error', 'An error occurred', 'error');
            });
        }
    </script>
</body>
</html>

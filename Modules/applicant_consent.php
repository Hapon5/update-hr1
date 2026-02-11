<?php
session_start();
include("../Database/Connections.php");

// Fetch active employees
$query = "SELECT * FROM employees WHERE status='active' ORDER BY created_at DESC";
$employees = $conn->query($query)->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="icon" type="image/x-icon" href="../Image/logo.png">
    <meta charset="UTF-8">
    <title>Applicant Consent & Records - HR Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.1/css/all.min.css">
    <style>
        body { font-family: 'Poppins', sans-serif; background-color: #f8fafc; }
        .consent-badge { font-size: 10px; font-weight: 800; padding: 2px 8px; border-radius: 99px; text-transform: uppercase; }
        ::-webkit-scrollbar { width: 4px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
    </style>
</head>
<body class="text-slate-800">
    <?php 
    $root_path = '../';
    include '../Components/sidebar_admin.php'; 
    include '../Components/header_admin.php';
    ?>

    <div class="ml-64 p-8 pt-24 min-h-screen">
        <div class="flex justify-between items-center mb-8">
            <div>
                <h1 class="text-3xl font-black text-slate-900 uppercase tracking-tight">Applicant Records</h1>
                <p class="text-xs text-slate-500 font-bold uppercase tracking-widest mt-1">Consent Forms & Employee Data</p>
            </div>
            <div class="bg-white px-6 py-2 rounded-xl shadow-sm border border-slate-100 flex items-center gap-3">
                <i class="fas fa-file-contract text-indigo-500"></i>
                <span class="text-xs font-bold text-slate-600 uppercase tracking-widest">Verified Records: <?= count($employees) ?></span>
            </div>
        </div>

        <!-- Employee Info Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach($employees as $emp): 
                $photoPath = $emp['photo_path'] ?? '';
                if (!empty($photoPath) && !str_starts_with($photoPath, 'http')) {
                    $photoPath = str_ireplace('profile/', 'Profile/', $photoPath);
                    if (!str_starts_with($photoPath, 'Profile/')) {
                        $photoPath = 'Profile/' . $photoPath;
                    }
                    $photo = "../" . htmlspecialchars($photoPath);
                } else {
                    $photo = "https://ui-avatars.com/api/?name=" . urlencode($emp['name']) . "&background=random";
                }
            ?>
            <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-100 hover:shadow-lg transition-all group">
                <div class="flex justify-between items-start mb-4">
                    <div class="flex items-center gap-4">
                        <img src="<?= $photo ?>" 
                             onerror="this.onerror=null; this.src='https://ui-avatars.com/api/?name=<?= urlencode($emp['name']) ?>&background=random'"
                             class="w-12 h-12 rounded-xl object-cover border border-slate-200">
                        <div>
                            <h3 class="font-bold text-slate-900 leading-tight"><?= htmlspecialchars($emp['name']) ?></h3>
                            <p class="text-xs text-slate-500 font-medium uppercase tracking-wide"><?= htmlspecialchars($emp['position']) ?></p>
                        </div>
                    </div>
                    <span class="consent-badge bg-green-50 text-green-600 border border-green-100"><i class="fas fa-check-circle"></i> VERIFIED</span>
                </div>
                
                <div class="space-y-3 border-t border-slate-50 pt-4 mb-4">
                    <div class="flex justify-between text-xs">
                        <span class="text-slate-400 font-bold uppercase tracking-widest">Employee ID</span>
                        <span class="font-mono font-bold text-slate-700">EMP-<?= str_pad($emp['id'], 3, '0', STR_PAD_LEFT) ?></span>
                    </div>
                    <div class="flex justify-between text-xs">
                        <span class="text-slate-400 font-bold uppercase tracking-widest">Email</span>
                        <span class="font-medium text-slate-700 truncate max-w-[150px]"><?= htmlspecialchars($emp['email']) ?></span>
                    </div>
                    <div class="flex justify-between text-xs">
                        <span class="text-slate-400 font-bold uppercase tracking-widest">Phone</span>
                        <span class="font-medium text-slate-700"><?= htmlspecialchars($emp['contact_number'] ?? 'N/A') ?></span>
                    </div>
                    <div class="flex justify-between text-xs">
                        <span class="text-slate-400 font-bold uppercase tracking-widest">Joined</span>
                        <span class="font-medium text-slate-700"><?= date('M d, Y', strtotime($emp['created_at'])) ?></span>
                    </div>
                </div>

                <div class="flex gap-2">
                    <button onclick="viewConsent(<?= htmlspecialchars(json_encode($emp)) ?>)" class="flex-1 py-2 bg-slate-50 text-slate-600 rounded-lg text-[10px] font-black uppercase tracking-widest hover:bg-slate-900 hover:text-white transition-colors border border-slate-200">
                        View Consent
                    </button>
                    <button class="px-3 py-2 bg-indigo-50 text-indigo-600 rounded-lg hover:bg-indigo-600 hover:text-white transition-colors border border-indigo-100">
                        <i class="fas fa-download"></i>
                    </button>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Consent Modal -->
    <div id="consentModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm hidden z-[9999] flex items-center justify-center p-4">
        <div class="bg-white rounded-3xl shadow-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto custom-scrollbar transform transition-all scale-95 opacity-0" id="modalContainer">
            <div class="p-8 border-b border-slate-50 flex justify-between items-center sticky top-0 bg-white z-10">
                <div>
                    <h2 class="text-xl font-black text-slate-900 uppercase tracking-tight">Applicant Consent Form</h2>
                    <p class="text-xs text-slate-400 font-bold uppercase tracking-widest mt-1">Data Privacy Agreement</p>
                </div>
                <button onclick="closeModal()" class="w-8 h-8 rounded-full bg-slate-50 text-slate-400 hover:text-slate-600 hover:bg-slate-100 flex items-center justify-center transition-colors"><i class="fas fa-times"></i></button>
            </div>
            
            <div class="p-8 space-y-6">
                <div class="bg-slate-50 p-6 rounded-2xl border border-slate-100">
                    <p class="text-xs font-medium text-slate-600 leading-relaxed text-justify mb-4">
                        I, <span id="modalName" class="font-bold underline text-slate-900 bg-yellow-100 px-1"></span>, hereby allow <strong>Cali Crane HR</strong> to collect and process my personal data for employment purposes. I understand that my information will be stored securely and used only for recruitment, payroll, and company records in accordance with the Data Privacy Act.
                    </p>
                    <div class="flex justify-between items-center border-t border-slate-200 pt-4 mt-4">
                        <div>
                            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Signed Electronically</p>
                            <p class="text-xs font-bold text-slate-900 mt-1" id="modalSignedDate"></p>
                        </div>
                        <div class="text-right">
                            <i class="fas fa-fingerprint text-4xl text-slate-200"></i>
                        </div>
                    </div>
                </div>

                <div>
                    <h3 class="text-xs font-black text-slate-900 uppercase tracking-widest mb-4">Personal Information Sheet</h3>
                    <div class="grid grid-cols-2 gap-4">
                        <div class="bg-white border border-slate-100 p-4 rounded-xl">
                            <p class="text-[10px] text-slate-400 uppercase font-black tracking-widest mb-1">Full Address</p>
                            <p class="text-sm font-bold text-slate-800" id="modalAddress">--</p>
                        </div>
                        <div class="bg-white border border-slate-100 p-4 rounded-xl">
                            <p class="text-[10px] text-slate-400 uppercase font-black tracking-widest mb-1">Emergency Contact</p>
                            <p class="text-sm font-bold text-slate-800" id="modalContact">--</p>
                        </div>
                        <div class="bg-white border border-slate-100 p-4 rounded-xl col-span-2">
                            <p class="text-[10px] text-slate-400 uppercase font-black tracking-widest mb-1">System Account Type</p>
                            <p class="text-sm font-bold text-slate-800" id="modalRole">--</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="p-6 bg-slate-50 border-t border-slate-100 flex justify-end">
                <button onclick="closeModal()" class="px-6 py-3 bg-slate-900 text-white rounded-xl font-bold uppercase tracking-widest text-[10px] hover:bg-indigo-600 transition-colors shadow-lg shadow-slate-200">Close Record</button>
            </div>
        </div>
    </div>

    <script>
        const modal = document.getElementById('consentModal');
        const container = document.getElementById('modalContainer');

        function viewConsent(data) {
            // Populate Data
            document.getElementById('modalName').textContent = data.name;
            document.getElementById('modalSignedDate').textContent = new Date(data.created_at).toLocaleString();
            document.getElementById('modalAddress').textContent = data.address || 'Not Provided';
            document.getElementById('modalContact').textContent = data.contact_number || 'Not Provided';
            document.getElementById('modalRole').textContent = data.account_type === '1' ? 'Admin / HR' : 'Standard Employee';

            // Show Modal
            modal.classList.remove('hidden');
            setTimeout(() => {
                container.classList.remove('scale-95', 'opacity-0');
            }, 10);
        }

        function closeModal() {
            container.classList.add('scale-95', 'opacity-0');
            setTimeout(() => {
                modal.classList.add('hidden');
            }, 300);
        }
    </script>
</body>
</html>

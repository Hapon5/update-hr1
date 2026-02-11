<?php
session_start();
include("../Database/Connections.php");

// Ensure recruitment_process table exists
try {
    $conn->exec("CREATE TABLE IF NOT EXISTS recruitment_process (
        id INT AUTO_INCREMENT PRIMARY KEY,
        candidate_id INT NOT NULL,
        license_number VARCHAR(50),
        license_expiry DATE,
        license_type VARCHAR(50),
        verification_method VARCHAR(100),
        fit_to_work_status ENUM('Pending', 'Fit', 'Unfit', 'Conditional') DEFAULT 'Pending',
        assessment_notes TEXT,
        submission_method VARCHAR(50) DEFAULT 'Online',
        assessed_by VARCHAR(100),
        assessment_date DATETIME DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (candidate_id) REFERENCES candidates(id) ON DELETE CASCADE
    )");
} catch (Exception $e) {
    // Table might exist or constraint issue
}

// Handle Form Submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    $action = $_POST['action'];

    try {
        if ($action === 'assess_candidate') {
            $candidate_id = $_POST['candidate_id'];
            $license_number = $_POST['license_number'];
            $license_expiry = $_POST['license_expiry'];
            $license_type = $_POST['license_type'];
            $verification_method = $_POST['verification_method'];
            $fit_to_work_status = $_POST['fit_to_work_status'];
            $assessment_notes = $_POST['assessment_notes'];
            $assessed_by = $_SESSION['GlobalName'] ?? 'Admin';
            $assessment_date = date('Y-m-d H:i:s');

            // Check if record exists
            $stmt = $conn->prepare("SELECT id FROM recruitment_process WHERE candidate_id = ?");
            $stmt->execute([$candidate_id]);
            $existing = $stmt->fetch();

            if ($existing) {
                $sql = "UPDATE recruitment_process SET 
                        license_number=?, license_expiry=?, license_type=?, 
                        verification_method=?, fit_to_work_status=?, assessment_notes=?, 
                        assessed_by=?, assessment_date=? 
                        WHERE candidate_id=?";
                $stmt = $conn->prepare($sql);
                $stmt->execute([
                    $license_number, $license_expiry, $license_type,
                    $verification_method, $fit_to_work_status, $assessment_notes,
                    $assessed_by, $assessment_date, $candidate_id
                ]);
            } else {
                $sql = "INSERT INTO recruitment_process 
                        (candidate_id, license_number, license_expiry, license_type, verification_method, fit_to_work_status, assessment_notes, assessed_by, assessment_date) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->execute([
                    $candidate_id, $license_number, $license_expiry, $license_type,
                    $verification_method, $fit_to_work_status, $assessment_notes,
                    $assessed_by, $assessment_date
                ]);
            }
            
            // Optionally update candidate status
            if ($fit_to_work_status === 'Fit') {
                $conn->prepare("UPDATE candidates SET status = 'shortlisted' WHERE id = ?")->execute([$candidate_id]);
            } elseif ($fit_to_work_status === 'Unfit') {
                $conn->prepare("UPDATE candidates SET status = 'rejected' WHERE id = ?")->execute([$candidate_id]);
            }

            echo json_encode(['status' => 'success', 'message' => 'Assessment saved successfully']);
        }
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit();
}

// Fetch Candidates joined with Assessment Data
$query = "SELECT c.id, c.full_name, c.email, c.position, c.created_at, c.extracted_image_path, c.source,
                 rp.license_number, rp.license_expiry, rp.fit_to_work_status, rp.assessment_date, rp.verification_method
          FROM candidates c
          LEFT JOIN recruitment_process rp ON c.id = rp.candidate_id
          WHERE c.is_archived = 0 AND c.status NOT IN ('rejected', 'hired')
          ORDER BY c.created_at DESC";
$candidates = $conn->query($query)->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="icon" type="image/x-icon" href="../Image/logo.png">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recruitment Process - HR Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.1/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { font-family: 'Poppins', sans-serif; background-color: #f8fafc; }
        .assessment-badge { font-size: 10px; padding: 2px 8px; border-radius: 9999px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; }
        .status-fit { background-color: #dcfce7; color: #166534; }
        .status-unfit { background-color: #fee2e2; color: #991b1b; }
        .status-pending { background-color: #f3f4f6; color: #4b5563; }
        .status-conditional { background-color: #fef9c3; color: #854d0e; }
        
        .license-valid { color: #16a34a; }
        .license-expired { color: #dc2626; font-weight: bold; }
        .license-warning { color: #d97706; }

        /* Custom Scrollbar */
        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-track { background: #f1f1f1; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
        ::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
    </style>
</head>
<body class="bg-gray-50 text-slate-800">
    <?php 
    $root_path = '../';
    include '../Components/sidebar_admin.php'; 
    include '../Components/header_admin.php';
    ?>

    <div class="ml-64 p-8 pt-24 min-h-screen">
        <div class="flex justify-between items-center mb-8">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Recruitment Process</h1>
                <p class="text-sm text-gray-500 mt-1">Detailed processing of applicants (Verification & Assessment)</p>
            </div>
            <div class="flex gap-2">
                <div class="bg-white px-4 py-2 rounded-lg shadow-sm border border-gray-100 flex items-center gap-2">
                    <div class="w-3 h-3 rounded-full bg-green-500"></div>
                    <span class="text-xs font-semibold text-gray-600">License Valid</span>
                </div>
                <div class="bg-white px-4 py-2 rounded-lg shadow-sm border border-gray-100 flex items-center gap-2">
                    <div class="w-3 h-3 rounded-full bg-red-500"></div>
                    <span class="text-xs font-semibold text-gray-600">License Expired</span>
                </div>
            </div>
        </div>

        <!-- KanBan / Grid Layout for Processing -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            
            <!-- LIST SECTION -->
            <div class="lg:col-span-1 space-y-4">
                <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100 sticky top-24">
                    <h2 class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-4">Pending Assessment</h2>
                    <div class="space-y-3 max-h-[70vh] overflow-y-auto pr-2 custom-scrollbar">
                        <?php foreach($candidates as $c): ?>
                            <div onclick="selectCandidate(<?= htmlspecialchars(json_encode($c)) ?>)" 
                                 class="p-4 rounded-xl border border-gray-100 hover:border-indigo-500 hover:shadow-md cursor-pointer transition-all bg-white group hover:bg-indigo-50/30">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-full bg-gray-200 overflow-hidden flex-shrink-0 border border-gray-300">
                                        <?php if($c['extracted_image_path']): ?>
                                            <img src="../Main/<?= $c['extracted_image_path'] ?>" class="w-full h-full object-cover">
                                        <?php else: ?>
                                            <img src="https://ui-avatars.com/api/?name=<?= urlencode($c['full_name']) ?>&background=random" class="w-full h-full object-cover">
                                        <?php endif; ?>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <h3 class="text-sm font-bold text-gray-900 truncate group-hover:text-indigo-600"><?= htmlspecialchars($c['full_name']) ?></h3>
                                        <p class="text-xs text-gray-500 truncate"><?= htmlspecialchars($c['position']) ?> • <?= htmlspecialchars($c['source'] ?? 'Direct') ?></p>
                                    </div>
                                    <?php 
                                        $statusClass = 'status-pending';
                                        if($c['fit_to_work_status'] == 'Fit') $statusClass = 'status-fit';
                                        if($c['fit_to_work_status'] == 'Unfit') $statusClass = 'status-unfit';
                                    ?>
                                    <span class="w-2 h-2 rounded-full <?= $c['fit_to_work_status'] == 'Fit' ? 'bg-green-500' : ($c['fit_to_work_status'] == 'Unfit' ? 'bg-red-500' : 'bg-gray-300') ?>"></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        <?php if(empty($candidates)): ?>
                            <p class="text-center text-xs text-gray-400 py-4">No candidates pending.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- DETAIL / ACTION SECTION -->
            <div class="lg:col-span-2">
                <div id="emptyState" class="bg-white rounded-xl shadow-sm border border-gray-100 p-10 text-center h-full flex flex-col justify-center items-center">
                    <div class="w-20 h-20 bg-gray-50 rounded-full flex items-center justify-center mb-4">
                        <i class="fas fa-id-card text-gray-300 text-3xl"></i>
                    </div>
                    <h3 class="text-gray-900 font-bold">Select a Candidate</h3>
                    <p class="text-sm text-gray-500 mt-2">Choose an applicant from the list to Verify License & Assess Fit to Work.</p>
                </div>

                <div id="assessmentForm" class="hidden bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="p-6 border-b border-gray-50 bg-gray-50/50 flex justify-between items-start">
                        <div class="flex items-center gap-4">
                            <img id="candImg" src="" class="w-16 h-16 rounded-xl object-cover border border-gray-200 shadow-sm">
                            <div>
                                <h2 id="candName" class="text-xl font-bold text-gray-900"></h2>
                                <p id="candPos" class="text-sm text-gray-500 font-medium"></p>
                                <div class="flex gap-2 mt-2">
                                    <span id="submissionSource" class="text-[10px] bg-blue-50 text-blue-600 px-2 py-0.5 rounded border border-blue-100 font-bold uppercase tracking-wider"></span>
                                    <span id="dateApplied" class="text-[10px] bg-gray-100 text-gray-500 px-2 py-0.5 rounded border border-gray-200 uppercase tracking-wider"></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <form id="processForm" class="p-8">
                        <input type="hidden" name="action" value="assess_candidate">
                        <input type="hidden" name="candidate_id" id="candidateId">

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                            <!-- Section 1: License Verification -->
                            <div class="space-y-4">
                                <h3 class="text-xs font-bold text-gray-900 uppercase tracking-widest border-b border-gray-100 pb-2">
                                    <i class="fas fa-id-card mr-2 text-indigo-500"></i> License Verification
                                </h3>
                                
                                <div>
                                    <label class="block text-xs font-bold text-gray-500 mb-1">License Number</label>
                                    <input type="text" name="license_number" id="licenseNumber" class="w-full text-sm border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 p-2.5 border bg-gray-50" placeholder="LTO License No.">
                                </div>

                                <div>
                                    <label class="block text-xs font-bold text-gray-500 mb-1">License Type</label>
                                    <select name="license_type" id="licenseType" class="w-full text-sm border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 p-2.5 border bg-white">
                                        <option value="Non-Professional">Non-Professional</option>
                                        <option value="Professional">Professional</option>
                                        <option value="Conductor">Conductor</option>
                                        <option value="Student Permit">Student Permit</option>
                                    </select>
                                </div>

                                <div>
                                    <label class="block text-xs font-bold text-gray-500 mb-1">Expiration Date</label>
                                    <input type="date" name="license_expiry" id="licenseExpiry" onchange="checkExpiry()" class="w-full text-sm border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 p-2.5 border bg-white">
                                    <p id="expiryStatus" class="text-xs mt-1 font-bold"></p>
                                </div>

                                <div>
                                    <label class="block text-xs font-bold text-gray-500 mb-1">Verification Method / Notes</label>
                                    <textarea name="verification_method" id="verificationMethod" rows="2" class="w-full text-sm border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 p-2.5 border bg-white placeholder:text-gray-300" placeholder="e.g. Checked ID physical copy, LTO Portal..."></textarea>
                                </div>
                            </div>

                            <!-- Section 2: Fit to Work Assessment -->
                            <div class="space-y-4">
                                <h3 class="text-xs font-bold text-gray-900 uppercase tracking-widest border-b border-gray-100 pb-2">
                                    <i class="fas fa-user-md mr-2 text-green-600"></i> Health & Fit to Work
                                </h3>
                                
                                <div>
                                    <label class="block text-xs font-bold text-gray-500 mb-1">Assessment Status</label>
                                    <div class="grid grid-cols-2 gap-2">
                                        <label class="cursor-pointer">
                                            <input type="radio" name="fit_to_work_status" value="Fit" class="peer sr-only">
                                            <div class="text-center py-2 rounded-lg border border-gray-200 peer-checked:bg-green-50 peer-checked:border-green-500 peer-checked:text-green-700 hover:bg-gray-50 transition-all text-xs font-semibold">
                                                <i class="fas fa-check-circle mr-1"></i> FIT TO WORK
                                            </div>
                                        </label>
                                        <label class="cursor-pointer">
                                            <input type="radio" name="fit_to_work_status" value="Unfit" class="peer sr-only">
                                            <div class="text-center py-2 rounded-lg border border-gray-200 peer-checked:bg-red-50 peer-checked:border-red-500 peer-checked:text-red-700 hover:bg-gray-50 transition-all text-xs font-semibold">
                                                <i class="fas fa-times-circle mr-1"></i> UNFIT
                                            </div>
                                        </label>
                                        <label class="cursor-pointer">
                                            <input type="radio" name="fit_to_work_status" value="Conditional" class="peer sr-only">
                                            <div class="text-center py-2 rounded-lg border border-gray-200 peer-checked:bg-yellow-50 peer-checked:border-yellow-500 peer-checked:text-yellow-700 hover:bg-gray-50 transition-all text-xs font-semibold">
                                                <i class="fas fa-exclamation-circle mr-1"></i> CONDITIONAL
                                            </div>
                                        </label>
                                        <label class="cursor-pointer">
                                            <input type="radio" name="fit_to_work_status" value="Pending" class="peer sr-only">
                                            <div class="text-center py-2 rounded-lg border border-gray-200 peer-checked:bg-gray-100 peer-checked:border-gray-500 peer-checked:text-gray-700 hover:bg-gray-50 transition-all text-xs font-semibold">
                                                Pending
                                            </div>
                                        </label>
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-xs font-bold text-gray-500 mb-1">Brief Assessment Notes</label>
                                    <textarea name="assessment_notes" id="assessmentNotes" rows="4" class="w-full text-sm border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 p-2.5 border bg-white placeholder:text-gray-300" placeholder="Medical findings, physical test results, interview notes..."></textarea>
                                </div>

                                <div class="bg-blue-50 p-3 rounded-lg border border-blue-100 text-xs text-blue-800">
                                    <p class="font-bold mb-1"><i class="fas fa-info-circle"></i> Info:</p>
                                    System automatically records submission source and assessment details for compliance tracking.
                                </div>
                            </div>
                        </div>

                        <div class="mt-8 flex justify-end gap-3 border-t border-gray-100 pt-6">
                            <button type="submit" class="px-6 py-2.5 bg-gray-900 text-white font-bold rounded-lg shadow-lg hover:bg-gray-800 transition-all flex items-center gap-2 text-sm">
                                <i class="fas fa-save"></i> Save Assessment
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Notification Toast -->
    <div id="toast" class="fixed bottom-5 right-5 z-[9999] opacity-0 transition-opacity duration-300"></div>

    <script>
        function selectCandidate(data) {
            // Parse data if stringent
            if (typeof data === 'string') data = JSON.parse(data);

            const empty = document.getElementById('emptyState');
            const formObj = document.getElementById('assessmentForm');

            empty.classList.add('hidden');
            formObj.classList.remove('hidden');

            // Populate Header
            document.getElementById('candidateId').value = data.id;
            document.getElementById('candName').textContent = data.full_name;
            document.getElementById('candPos').textContent = data.position;
            document.getElementById('submissionSource').innerHTML = `<i class="fas fa-rss mr-1"></i> ${data.source || 'Direct App'}`;
            
            const date = new Date(data.created_at);
            document.getElementById('dateApplied').textContent = date.toLocaleDateString();

            if(data.extracted_image_path) {
                document.getElementById('candImg').src = "../Main/" + data.extracted_image_path;
            } else {
                document.getElementById('candImg').src = `https://ui-avatars.com/api/?name=${encodeURIComponent(data.full_name)}&background=random`;
            }

            // Populate Form Fields
            document.getElementById('licenseNumber').value = data.license_number || '';
            document.getElementById('licenseExpiry').value = data.license_expiry || '';
            document.getElementById('verificationMethod').value = data.verification_method || '';
            document.getElementById('assessmentNotes').value = data.assessment_notes || '';
            
            // Radio Button Logic
            const status = data.fit_to_work_status || 'Pending';
            const radios = document.getElementsByName('fit_to_work_status');
            for(let r of radios) {
                if(r.value === status) r.checked = true;
            }

            // Check expiry visualization
            checkExpiry();

            // Smooth scroll to form on mobile
            if(window.innerWidth < 1024) {
                formObj.scrollIntoView({ behavior: 'smooth' });
            }
        }

        function checkExpiry() {
            const dateInput = document.getElementById('licenseExpiry').value;
            const statusEl = document.getElementById('expiryStatus');
            
            if (!dateInput) {
                statusEl.textContent = "";
                statusEl.className = "";
                return;
            }

            const expiry = new Date(dateInput);
            const today = new Date();
            
            // Reset hours for accurate date comparison
            today.setHours(0,0,0,0);
            
            if (expiry < today) {
                statusEl.innerHTML = "<i class='fas fa-exclamation-triangle'></i> LICENSE EXPIRED";
                statusEl.className = "text-xs mt-1 font-bold text-red-600 animate-pulse";
            } else {
                const diffTime = Math.abs(expiry - today);
                const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24)); 
                
                if(diffDays < 30) {
                    statusEl.innerHTML = `<i class='fas fa-exclamation-circle'></i> Expiring soon (${diffDays} days)`;
                    statusEl.className = "text-xs mt-1 font-bold text-orange-500";
                } else {
                    statusEl.innerHTML = "<i class='fas fa-check-circle'></i> License Valid";
                    statusEl.className = "text-xs mt-1 font-bold text-green-600";
                }
            }
        }

        document.getElementById('processForm').onsubmit = (e) => {
            e.preventDefault();
            const formData = new FormData(e.target);
            
            // Visual feedback
            const btn = e.target.querySelector('button[type="submit"]');
            const originalText = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
            btn.disabled = true;

            fetch('', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                showToast(data.message, data.status);
                btn.innerHTML = originalText;
                btn.disabled = false;
                if(data.status === 'success') {
                    // Update the list item visual status immediately without reload
                    updateListItemStatus(formData.get('fit_to_work_status'));
                }
            })
            .catch(err => {
                showToast('Error occurred', 'error');
                btn.innerHTML = originalText;
                btn.disabled = false;
            });
        };

        function updateListItemStatus(status) {
            // Reload page to reflect changes properly in list
            setTimeout(() => location.reload(), 1000);
        }

        function showToast(message, type = 'success') {
            const toast = document.getElementById('toast');
            toast.className = `fixed bottom-5 right-5 z-[9999] px-6 py-3 rounded-lg text-white font-bold shadow-xl transition-all duration-300 transform translate-y-0 ${type === 'success' ? 'bg-gray-900' : 'bg-red-600'}`;
            toast.innerHTML = `<i class="fas ${type === 'success' ? 'fa-check' : 'fa-times'} mr-2"></i> ${message}`;
            toast.style.opacity = '1';
            
            setTimeout(() => {
                toast.style.opacity = '0';
                toast.style.transform = 'translateY(20px)';
            }, 3000);
        }
    </script>
</body>
</html>

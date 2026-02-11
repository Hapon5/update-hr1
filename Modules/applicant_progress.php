<?php
session_start();
include("../Database/Connections.php");

// Ensure candidates has is_archived
try { $conn->exec("ALTER TABLE candidates ADD COLUMN is_archived TINYINT(1) DEFAULT 0"); } catch (Exception $e) {}

// Archive filter: active | archived
$archive_filter = isset($_GET['archive_filter']) && $_GET['archive_filter'] === 'archived' ? 'archived' : 'active';
$is_archived = $archive_filter === 'archived' ? 1 : 0;

// Handle archive candidate (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'archive_candidate' && !empty($_POST['id'])) {
    header('Content-Type: application/json');
    try {
        $stmt = $conn->prepare("UPDATE candidates SET is_archived = 1 WHERE id = ?");
        $stmt->execute([(int)$_POST['id']]);
        echo json_encode(['status' => 'success', 'message' => 'Candidate archived.']);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit;
}

// Fetch candidates with error handling
$candidates = [];
try {
    $tableExists = $conn->query("SHOW TABLES LIKE 'interview_schedule'")->rowCount() > 0;
    if ($tableExists) {
        $sql = "SELECT c.*, i.interview_status, i.date_time as interview_date 
                FROM candidates c 
                LEFT JOIN interview_schedule i ON i.candidate_id = c.id
                WHERE c.is_archived = ? ORDER BY c.created_at DESC";
    } else {
        $sql = "SELECT c.*, NULL as interview_status, NULL as interview_date 
                FROM candidates c 
                WHERE c.is_archived = ? ORDER BY c.created_at DESC";
    }
    $stmt = $conn->prepare($sql);
    $stmt->execute([$is_archived]);
    $candidates = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    try {
        $stmt = $conn->prepare("SELECT * FROM candidates WHERE is_archived = ?");
        $stmt->execute([$is_archived]);
        $candidates = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $ex) {
        $candidates = [];
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="icon" type="image/x-icon" href="../Image/logo.png">
    <meta charset="UTF-8">
    <title>Applicant Progress Tracker - HR Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.1/css/all.min.css">
    <style>
        body { font-family: 'Poppins', sans-serif; background-color: #f8fafc; }
        ::-webkit-scrollbar { width: 4px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
        
        .step { position: relative; }
        .step::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 100%;
            width: 100%;
            height: 2px;
            background: #e2e8f0;
            transform: translateY(-50%);
            z-index: 0;
        }
        .step:last-child::after { display: none; }
        .step.active .step-circle { background: #4f46e5; color: white; border-color: #4f46e5; }
        .step.completed .step-circle { background: #dcfce7; color: #166534; border-color: #bbf7d0; }
        .step.active::after { background: #4f46e5; }
        .step-circle { z-index: 10; position: relative; width: 2rem; height: 2rem; border-radius: 9999px; display: flex; items-center; justify-center; background: white; border: 2px solid #e2e8f0; font-weight: bold; font-size: 0.75rem; transition: all 0.3s; }
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
                <h1 class="text-3xl font-black text-slate-900 uppercase tracking-tight">Applicant <span class="text-indigo-600">Progress</span></h1>
                <p class="text-xs text-slate-500 font-bold uppercase tracking-widest mt-1">Status Pipeline (Screening &rarr; Hired)</p>
            </div>
            
            <div class="flex items-center gap-4">
                <div class="flex items-center gap-2">
                    <span class="text-sm font-semibold text-slate-600">Show:</span>
                    <a href="?archive_filter=active" class="px-3 py-1.5 rounded-lg text-sm font-medium <?= $archive_filter === 'active' ? 'bg-slate-900 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' ?>">Active</a>
                    <a href="?archive_filter=archived" class="px-3 py-1.5 rounded-lg text-sm font-medium <?= $archive_filter === 'archived' ? 'bg-slate-900 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' ?>">Archived</a>
                </div>
                <div class="bg-white px-4 py-2 rounded-xl shadow-sm border border-slate-100 flex items-center gap-3">
                    <span class="w-3 h-3 rounded-full bg-green-500 animate-pulse"></span>
                    <span class="text-xs font-bold text-slate-600 uppercase tracking-widest">Live Updates</span>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-[32px] shadow-xl shadow-slate-200/50 overflow-hidden border border-slate-100">
            <table class="w-full text-left">
                <thead class="bg-slate-50 border-b border-slate-100">
                    <tr>
                        <th class="px-8 py-5 text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Candidate</th>
                        <th class="px-8 py-5 text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] w-1/2">Pipeline Progress</th>
                        <th class="px-8 py-5 text-right text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Current Status</th>
                        <?php if ($archive_filter === 'active'): ?>
                        <th class="px-8 py-5 text-right text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Actions</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    <?php foreach($candidates as $c): 
                        // Determine Progress Level
                        $progress = 1; // 1=Screening (Default)
                        $statusText = "Screening Phase";
                        $textColor = "text-slate-500";
                        
                        // Check Interview
                        if($c['interview_status'] == 'Scheduled' || $c['interview_status'] == 'Completed') {
                            $progress = 2; // Interview
                            $statusText = "Interview Scheduled";
                            $textColor = "text-blue-600";
                        }
                        
                        // Check Assessment/Shortlist
                        if($c['status'] == 'shortlisted' || $c['status'] == 'Fit') {
                            $progress = 3; // Assessment
                            $statusText = "Assessment / Fit to Work";
                            $textColor = "text-purple-600";
                        }

                        // Check Hired
                        if($c['status'] == 'Hired') {
                            $progress = 4; // Hired
                            $statusText = "Hired & Onboarded";
                            $textColor = "text-green-600";
                        } elseif ($c['status'] == 'Rejected' || $c['status'] == 'Unfit') {
                            $statusText = "Application Rejected";
                            $textColor = "text-red-500";
                            $progress = 0; // Failed
                        }
                    ?>
                    <tr class="group hover:bg-slate-50/50 transition-colors">
                        <td class="px-8 py-6">
                            <div class="flex items-center gap-4">
                                <div class="w-10 h-10 rounded-xl bg-slate-100 flex items-center justify-center font-black text-slate-400 text-xs">
                                    <?= substr($c['full_name'], 0, 1) ?>
                                </div>
                                <div>
                                    <h3 class="font-bold text-slate-900 text-sm"><?= htmlspecialchars($c['full_name']) ?></h3>
                                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest"><?= htmlspecialchars($c['position']) ?></p>
                                </div>
                            </div>
                        </td>
                        <td class="px-8 py-6">
                            <div class="flex justify-between w-full max-w-md mx-auto relative">
                                <!-- Step 1: Screening -->
                                <div class="step flex-1 <?= $progress >= 1 ? 'completed' : '' ?>">
                                    <div class="step-circle mx-auto"><i class="fas fa-file-alt"></i></div>
                                    <p class="text-[9px] text-center font-bold uppercase tracking-widest mt-2 text-slate-400">Screening</p>
                                </div>
                                <!-- Step 2: Interview -->
                                <div class="step flex-1 <?= $progress >= 2 ? 'completed' : '' ?>">
                                    <div class="step-circle mx-auto"><i class="fas fa-comments"></i></div>
                                    <p class="text-[9px] text-center font-bold uppercase tracking-widest mt-2 text-slate-400">Interview</p>
                                </div>
                                <!-- Step 3: Assessment -->
                                <div class="step flex-1 <?= $progress >= 3 ? 'completed' : '' ?>">
                                    <div class="step-circle mx-auto"><i class="fas fa-clipboard-check"></i></div>
                                    <p class="text-[9px] text-center font-bold uppercase tracking-widest mt-2 text-slate-400">Assess</p>
                                </div>
                                <!-- Step 4: Hired -->
                                <div class="step flex-1 <?= $progress >= 4 ? 'completed' : '' ?>" style="flex: 0;">
                                    <div class="step-circle mx-auto"><i class="fas fa-flag-checkered"></i></div>
                                    <p class="text-[9px] text-center font-bold uppercase tracking-widest mt-2 text-slate-400">Hired</p>
                                </div>
                            </div>
                        </td>
                        <td class="px-8 py-6 text-right">
                            <span class="font-black text-xs uppercase tracking-widest <?= $textColor ?> bg-white px-3 py-1 rounded-lg border border-slate-100 shadow-sm">
                                <?= $statusText ?>
                            </span>
                        </td>
                        <?php if ($archive_filter === 'active'): ?>
                        <td class="px-8 py-6 text-right">
                            <button type="button" onclick="archiveCandidate(<?= (int)$c['id'] ?>)" class="text-xs font-bold uppercase tracking-widest text-slate-500 hover:text-orange-500 transition-colors flex items-center gap-1 ml-auto" title="Archive">
                                <i class="fas fa-box-archive"></i> Archive
                            </button>
                        </td>
                        <?php endif; ?>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <script>
        function archiveCandidate(id) {
            if (!confirm('Archive this candidate? You can view under Archived filter.')) return;
            const fd = new FormData();
            fd.append('action', 'archive_candidate');
            fd.append('id', id);
            fetch('applicant_progress.php', { method: 'POST', body: fd })
                .then(r => r.json())
                .then(data => {
                    if (data.status === 'success') {
                        alert(data.message);
                        location.reload();
                    } else alert(data.message || 'Error');
                });
        }
    </script>
</body>
</html>

<?php
session_start();
include("../Database/Connections.php");

// Check admin
if (!isset($_SESSION['Email']) || (isset($_SESSION['Account_type']) && $_SESSION['Account_type'] !== '1')) {
    header("Location: ../login.php");
    exit();
}

// Stats Queries
// Initialize stats with default values to prevent undefined variable errors
$total_employees = 0;
$active_employees = 0;
$total_candidates = 0;
$new_apps_month = 0;
$screened = 0;
$interviewed = 0;
$hired = 0;
$positions = []; // Ensure array is initialized

try {
    // 1. Employee Count
    $total_employees = $conn->query("SELECT COUNT(*) FROM employees")->fetchColumn() ?: 0;
    $active_employees = $conn->query("SELECT COUNT(*) FROM employees WHERE status = 'active'")->fetchColumn() ?: 0;
    
    // 2. Recruitment Stats
    $total_candidates = $conn->query("SELECT COUNT(*) FROM candidates")->fetchColumn() ?: 0;
    $new_apps_month = $conn->query("SELECT COUNT(*) FROM candidates WHERE MONTH(created_at) = MONTH(CURRENT_DATE())")->fetchColumn() ?: 0;
    
    // 3. Department/Position Distribution
    $positions = $conn->query("SELECT position, COUNT(*) as count FROM employees GROUP BY position")->fetchAll(PDO::FETCH_ASSOC) ?: [];
    
    // 4. Recruitment Funnel (Approximate)
    // Check if interview_schedule table exists first to avoid fatal error
    $checkTable = $conn->query("SHOW TABLES LIKE 'interview_schedule'")->fetch();
    if($checkTable) {
        $interviewed = $conn->query("SELECT COUNT(*) FROM interview_schedule WHERE status = 'Scheduled' OR status = 'Completed'")->fetchColumn() ?: 0;
    }

    $screened = $conn->query("SELECT COUNT(*) FROM candidates WHERE status != 'New'")->fetchColumn() ?: 0;
    $hired = $conn->query("SELECT COUNT(*) FROM candidates WHERE status = 'Hired'")->fetchColumn() ?: 0;

} catch(Exception $e) {
    // Log error but continue loading page with 0 stats
    error_log("Analytics Error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="icon" type="image/x-icon" href="../Image/logo.png">
    <meta charset="UTF-8">
    <title>HR Analytics</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.1/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="bg-gray-50 font-sans text-slate-800">
    <?php 
    $root_path = '../';
    include '../Components/sidebar_admin.php'; 
    include '../Components/header_admin.php';
    ?>

    <div class="ml-64 p-8 pt-24">
        <div class="mb-8">
            <h1 class="text-3xl font-black text-gray-900 uppercase">Data Analytics</h1>
            <p class="text-sm text-gray-500 font-bold uppercase tracking-widest">HR Metrics & Insights</p>
        </div>

        <!-- KPI Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
                <div class="flex justify-between items-start">
                    <div>
                        <p class="text-xs font-bold text-gray-400 uppercase tracking-widest">Total Employees</p>
                        <h3 class="text-3xl font-black text-gray-900 mt-2"><?= $total_employees ?></h3>
                    </div>
                    <div class="p-3 bg-indigo-50 rounded-xl text-indigo-600"><i class="fas fa-users"></i></div>
                </div>
                <div class="mt-4 flex items-center gap-2 text-xs">
                    <span class="text-green-500 font-bold"><i class="fas fa-arrow-up"></i> <?= $active_employees ?></span>
                    <span class="text-gray-400">active now</span>
                </div>
            </div>

            <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
                <div class="flex justify-between items-start">
                    <div>
                        <p class="text-xs font-bold text-gray-400 uppercase tracking-widest">Candidates</p>
                        <h3 class="text-3xl font-black text-gray-900 mt-2"><?= $total_candidates ?></h3>
                    </div>
                    <div class="p-3 bg-blue-50 rounded-xl text-blue-600"><i class="fas fa-user-tie"></i></div>
                </div>
                <div class="mt-4 flex items-center gap-2 text-xs">
                    <span class="text-blue-500 font-bold">+<?= $new_apps_month ?></span>
                    <span class="text-gray-400">this month</span>
                </div>
            </div>

            <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
                <div class="flex justify-between items-start">
                    <div>
                        <p class="text-xs font-bold text-gray-400 uppercase tracking-widest">Interviews</p>
                        <h3 class="text-3xl font-black text-gray-900 mt-2"><?= $interviewed ?></h3>
                    </div>
                    <div class="p-3 bg-purple-50 rounded-xl text-purple-600"><i class="fas fa-comments"></i></div>
                </div>
            </div>

            <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
                <div class="flex justify-between items-start">
                    <div>
                        <p class="text-xs font-bold text-gray-400 uppercase tracking-widest">Hires</p>
                        <h3 class="text-3xl font-black text-gray-900 mt-2"><?= $hired ?></h3>
                    </div>
                    <div class="p-3 bg-green-50 rounded-xl text-green-600"><i class="fas fa-check-circle"></i></div>
                </div>
            </div>
        </div>

        <!-- Charts -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <div class="bg-white p-6 rounded-2xl shadow-lg shadow-gray-200/50 border border-gray-100">
                <h3 class="text-sm font-bold text-gray-900 uppercase tracking-wide mb-6">Employee Distribution</h3>
                <canvas id="posChart"></canvas>
            </div>

            <div class="bg-white p-6 rounded-2xl shadow-lg shadow-gray-200/50 border border-gray-100">
                <h3 class="text-sm font-bold text-gray-900 uppercase tracking-wide mb-6">Recruitment Funnel</h3>
                <canvas id="funnelChart"></canvas>
            </div>
        </div>
    </div>

    <script>
        // Position Distribution
        const posData = <?= json_encode($positions) ?>;
        new Chart(document.getElementById('posChart'), {
            type: 'doughnut',
            data: {
                labels: posData.map(p => p.position),
                datasets: [{
                    data: posData.map(p => p.count),
                    backgroundColor: ['#4f46e5', '#3b82f6', '#0ea5e9', '#6366f1', '#8b5cf6', '#d946ef']
                }]
            },
            options: { cutout: '70%', responsive: true }
        });

        // Funnel
        new Chart(document.getElementById('funnelChart'), {
            type: 'bar',
            data: {
                labels: ['Applied', 'Screened', 'Interviewed', 'Hired'],
                datasets: [{
                    label: 'Candidates',
                    data: [<?= $total_candidates ?>, <?= $screened ?>, <?= $interviewed ?>, <?= $hired ?>],
                    backgroundColor: '#4f46e5',
                    borderRadius: 8
                }]
            },
            options: {
                responsive: true,
                scales: { y: { beginAtZero: true } }
            }
        });
    </script>
</body>
</html>

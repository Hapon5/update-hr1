<?php
session_start();
include '../Database/Connections.php';

// Helper for safe counts
function getCount($conn, $sql)
{
    try {
        $stmt = $conn->query($sql);
        return $stmt->fetchColumn();
    } catch (Exception $e) {
        return 0;
    }
}

// Stats
$total_employees = getCount($conn, "SELECT COUNT(*) FROM employees");
$active_employees = getCount($conn, "SELECT COUNT(*) FROM employees WHERE status='active'");
$inactive_employees = getCount($conn, "SELECT COUNT(*) FROM employees WHERE status='inactive'");
$open_jobs = getCount($conn, "SELECT COUNT(*) FROM job_postings WHERE status='active'");
$scheduled_interviews = getCount($conn, "SELECT COUNT(*) FROM interviews WHERE status='scheduled'");

// Applicants (Try 'applications' then 'candidates')
$applicants_today = 0;
$applicants_month = 0;
try {
    $applicants_today = getCount($conn, "SELECT COUNT(*) FROM applications WHERE DATE(applied_at) = CURDATE()");
    $applicants_month = getCount($conn, "SELECT COUNT(*) FROM applications WHERE MONTH(applied_at) = MONTH(CURDATE()) AND YEAR(applied_at) = YEAR(CURDATE())");
} catch (Exception $e) {
    try {
        $applicants_today = getCount($conn, "SELECT COUNT(*) FROM candidates WHERE DATE(created_at) = CURDATE()");
        $applicants_month = getCount($conn, "SELECT COUNT(*) FROM candidates WHERE MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE())");
    } catch (Exception $ex) {
    }
}

// Performance
$top_performers = [];
$low_performers = [];
try {
    $top_performers = $conn->query("SELECT e.name, e.position, e.photo_path, AVG(a.rating) as rating FROM employees e JOIN appraisals a ON e.id = a.employee_id GROUP BY e.id ORDER BY rating DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
    $low_performers = $conn->query("SELECT e.name, e.position, e.photo_path, AVG(a.rating) as rating FROM employees e JOIN appraisals a ON e.id = a.employee_id GROUP BY e.id ORDER BY rating ASC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
}

// Safety Incidents
$recent_incidents = [];
$incidents_by_month = array_fill(0, 12, 0); // Jan-Dec
try {
    $recent_incidents = $conn->query("SELECT * FROM safety_incidents WHERE status='reported' ORDER BY incident_date DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);

    // Group by month for chart
    $stmt = $conn->query("SELECT MONTH(incident_date) as m, COUNT(*) as c FROM safety_incidents GROUP BY m");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $incidents_by_month[$row['m'] - 1] = $row['c'];
    }
} catch (Exception $e) {
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HR1-CRANE | DASHBOARD</title>
    <link rel="icon" type="image/x-icon" href="../Image/logo.png">

    <!-- Fonts & Icons -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">
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
                        success: '#10b981',
                        warning: '#f59e0b',
                        danger: '#ef4444'
                    }
                }
            }
        }
    </script>

    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
        /* Glassmorphism & Custom Scrollbar */
        .glass-panel {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.5);
        }

        ::-webkit-scrollbar {
            width: 6px;
        }

        ::-webkit-scrollbar-track {
            background: #f1f1f1;
        }

        ::-webkit-scrollbar-thumb {
            background: #c7c7c7;
            border-radius: 10px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: #a0a0a0;
        }

        .card-hover:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }
    </style>
</head>

<body class="bg-gray-50 text-gray-800 font-sans">

    <!-- Sidebar -->
    <?php include 'Components/sidebar.php'; ?>

    <!-- Main Content Wrapper -->
    <div class="ml-64 transition-all duration-300 min-h-screen flex flex-col" id="mainContent">

        <!-- Header -->
        <?php include 'Components/header.php'; ?>

        <!-- Content Area -->
        <main class="p-8 mt-20 flex-grow">

            <!-- Quick Stats Row -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <!-- Total Employees -->
                <div
                    class="bg-white rounded-xl shadow-sm p-6 card-hover transition-all duration-300 border-l-4 border-blue-500 relative overflow-hidden">
                    <div class="absolute right-0 top-0 h-full w-16 bg-blue-50 transform skew-x-12 translate-x-8"></div>
                    <div class="flex justify-between items-start relative z-10">
                        <div>
                            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Total Employees</p>
                            <h3 class="text-3xl font-bold text-gray-800 mt-2"><?= $total_employees ?></h3>
                            <div class="flex items-center gap-2 mt-2 text-xs">
                                <span
                                    class="text-green-600 bg-green-100 px-2 py-0.5 rounded-full font-medium"><?= $active_employees ?>
                                    Active</span>
                                <span
                                    class="text-gray-500 bg-gray-100 px-2 py-0.5 rounded-full"><?= $inactive_employees ?>
                                    Inactive</span>
                            </div>
                        </div>
                        <div class="p-3 bg-blue-100 rounded-lg text-blue-600">
                            <i class="fas fa-users text-xl"></i>
                        </div>
                    </div>
                </div>

                <!-- Job Openings -->
                <a href="../Modules/job_posting.php"
                    class="block bg-white rounded-xl shadow-sm p-6 card-hover transition-all duration-300 border-l-4 border-purple-500 relative overflow-hidden">
                    <div class="absolute right-0 top-0 h-full w-16 bg-purple-50 transform skew-x-12 translate-x-8">
                    </div>
                    <div class="flex justify-between items-start relative z-10">
                        <div>
                            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Job Openings</p>
                            <h3 class="text-3xl font-bold text-gray-800 mt-2"><?= $open_jobs ?></h3>
                            <p class="text-xs text-purple-600 mt-2 font-medium">Accepting Applications</p>
                        </div>
                        <div class="p-3 bg-purple-100 rounded-lg text-purple-600">
                            <i class="fas fa-briefcase text-xl"></i>
                        </div>
                    </div>
                </a>

                <!-- Applicants -->
                <a href="../Main/candidate_sourcing_&_tracking.php"
                    class="block bg-white rounded-xl shadow-sm p-6 card-hover transition-all duration-300 border-l-4 border-pink-500 relative overflow-hidden">
                    <div class="absolute right-0 top-0 h-full w-16 bg-pink-50 transform skew-x-12 translate-x-8"></div>
                    <div class="flex justify-between items-start relative z-10">
                        <div>
                            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">New Applicants</p>
                            <div class="flex items-baseline gap-2 mt-2">
                                <h3 class="text-3xl font-bold text-gray-800"><?= $applicants_today ?></h3>
                                <span class="text-xs text-gray-500">Today</span>
                            </div>
                            <p class="text-xs text-gray-500 mt-1"><?= $applicants_month ?> this month</p>
                        </div>
                        <div class="p-3 bg-pink-100 rounded-lg text-pink-600">
                            <i class="fas fa-user-clock text-xl"></i>
                        </div>
                    </div>
                </a>

                <!-- Interviews -->
                <a href="../Modules/Interviewschedule.php"
                    class="block bg-white rounded-xl shadow-sm p-6 card-hover transition-all duration-300 border-l-4 border-yellow-500 relative overflow-hidden">
                    <div class="absolute right-0 top-0 h-full w-16 bg-yellow-50 transform skew-x-12 translate-x-8">
                    </div>
                    <div class="flex justify-between items-start relative z-10">
                        <div>
                            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Interviews</p>
                            <h3 class="text-3xl font-bold text-gray-800 mt-2"><?= $scheduled_interviews ?></h3>
                            <p class="text-xs text-yellow-600 mt-2 font-medium">Scheduled</p>
                        </div>
                        <div class="p-3 bg-yellow-100 rounded-lg text-yellow-600">
                            <i class="fas fa-calendar-check text-xl"></i>
                        </div>
                    </div>
                </a>
            </div>

            <!-- Content Grid 1: Charts & Incidents -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-8">

                <!-- Main Charts Column -->
                <div class="lg:col-span-2 space-y-8">
                    <!-- Hiring Chart -->
                    <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
                        <div class="flex justify-between items-center mb-6">
                            <h4 class="font-bold text-gray-800">Hiring Trends</h4>
                        </div>
                        <div class="h-64">
                            <canvas id="hiringChart"></canvas>
                        </div>
                    </div>

                    <!-- Secondary Charts Row -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Incident Chart -->
                        <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
                            <h4 class="font-bold text-gray-800 mb-4 text-sm">Accidents per Month</h4>
                            <div class="h-48">
                                <canvas id="incidentChart"></canvas>
                            </div>
                        </div>
                        <!-- Turnover (Active vs Inactive) -->
                        <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
                            <h4 class="font-bold text-gray-800 mb-4 text-sm">Employee Status</h4>
                            <div class="h-48 relative flex items-center justify-center">
                                <canvas id="turnoverChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Safety Incident Alerts Panel -->
                <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100 flex flex-col h-full">
                    <div class="flex justify-between items-center mb-4">
                        <h4 class="font-bold text-gray-800 flex items-center gap-2">
                            <i class="fas fa-exclamation-triangle text-amber-500"></i> Safety Alerts
                        </h4>
                        <span
                            class="bg-red-100 text-red-600 text-xs px-2 py-1 rounded-full font-bold"><?= count($recent_incidents) ?>
                            Active</span>
                    </div>

                    <div class="flex-grow overflow-y-auto space-y-4 max-h-[500px] pr-2 custom-scrollbar">
                        <?php if (empty($recent_incidents)): ?>
                            <div class="text-center py-8 text-gray-400">
                                <i class="fas fa-check-circle text-4xl mb-2 text-green-100"></i>
                                <p class="text-sm">No reported incidents.</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($recent_incidents as $incident): ?>
                                <div
                                    class="p-4 bg-red-50 rounded-lg border border-red-100 relative group hover:bg-red-100 transition-colors">
                                    <div class="flex justify-between items-start mb-1">
                                        <p class="text-sm font-bold text-gray-800">
                                            <?= htmlspecialchars($incident['incident_type']) ?>
                                        </p>
                                        <span
                                            class="text-[10px] text-gray-500"><?= date('M d', strtotime($incident['incident_date'])) ?></span>
                                    </div>
                                    <p class="text-xs text-gray-600 mb-2"><?= htmlspecialchars($incident['incident_details']) ?>
                                    </p>
                                    <div class="flex items-center gap-2">
                                        <span
                                            class="text-[10px] uppercase font-bold px-1.5 py-0.5 rounded bg-white text-red-600 border border-red-200"><?= htmlspecialchars($incident['severity']) ?></span>
                                        <span class="text-[10px] text-gray-500"><i
                                                class="fas fa-map-marker-alt mr-1"></i><?= htmlspecialchars($incident['location']) ?></span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>

                        <!-- Static Notification Placeholders if needed to fill space -->
                        <div class="p-4 bg-gray-50 rounded-lg border border-gray-100">
                            <div class="flex gap-3">
                                <div
                                    class="w-8 h-8 rounded-full bg-yellow-100 flex items-center justify-center flex-shrink-0 text-yellow-600">
                                    <i class="fas fa-id-card text-xs"></i>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-gray-800">License Expiring</p>
                                    <p class="text-xs text-gray-500">Sarah J. - Safety Officer Cert</p>
                                    <p class="text-[10px] text-red-500 mt-1 font-medium">Expires: 3 Days</p>
                                </div>
                            </div>
                        </div>

                        <div class="p-4 bg-gray-50 rounded-lg border border-gray-100">
                            <div class="flex gap-3">
                                <div
                                    class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center flex-shrink-0 text-blue-600">
                                    <i class="fas fa-clock text-xs"></i>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-gray-800">Attendance Alert</p>
                                    <p class="text-xs text-gray-500">3 Late arrivals today.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Content Grid 2: Performance & Notifications -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

                <!-- Performance Summary -->
                <div class="lg:col-span-2 bg-white rounded-xl shadow-sm p-6 border border-gray-100">
                    <div class="flex justify-between items-center mb-6">
                        <h4 class="font-bold text-gray-800">Performance Summary</h4>
                        <div class="flex bg-gray-100 rounded-lg p-1">
                            <button id="btnTop" onclick="togglePerformance('top')"
                                class="px-4 py-1.5 text-xs font-medium rounded-md bg-white text-gray-800 shadow-sm transition-all">Top
                                Performers</button>
                            <button id="btnLow" onclick="togglePerformance('low')"
                                class="px-4 py-1.5 text-xs font-medium rounded-md text-gray-500 hover:text-gray-700 transition-all">Low
                                Performers</button>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-sm text-left">
                            <thead class="text-xs text-gray-500 uppercase bg-gray-50/50 border-b">
                                <tr>
                                    <th class="px-4 py-3">Employee</th>
                                    <th class="px-4 py-3">Position</th>
                                    <th class="px-4 py-3">Status</th>
                                    <th class="px-4 py-3 text-right">Rating</th>
                                </tr>
                            </thead>
                            <tbody id="performanceBody" class="divide-y divide-gray-100">
                                <!-- JS will populate this -->
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Notifications & Approvals -->
                <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
                    <h4 class="font-bold text-gray-800 mb-4">Pending Approvals</h4>

                    <div class="space-y-4">
                        <!-- Item 1 -->
                        <div class="flex gap-3 items-start">
                            <div
                                class="w-10 h-10 rounded-full bg-indigo-50 flex items-center justify-center flex-shrink-0 text-indigo-600 font-bold text-sm">
                                <i class="fas fa-briefcase"></i>
                            </div>
                            <div class="flex-grow">
                                <div class="flex justify-between items-start">
                                    <p class="text-sm font-medium text-gray-800">Recruitment Team</p>
                                    <span class="text-[10px] text-gray-400">1h ago</span>
                                </div>
                                <p class="text-xs text-gray-500 mt-0.5">New Job Posting: Senior Safety Officer</p>
                                <div class="flex gap-2 mt-3">
                                    <a href="../Modules/job_posting.php"
                                        class="text-[10px] bg-indigo-600 text-white px-3 py-1.5 rounded hover:bg-indigo-700 transition-colors">Review
                                        Post</a>
                                </div>
                            </div>
                        </div>
                        <hr class="border-gray-50">
                        <!-- Item 2 -->
                        <div class="flex gap-3 items-start">
                            <div
                                class="w-10 h-10 rounded-full bg-pink-50 flex items-center justify-center flex-shrink-0 text-pink-600 font-bold text-sm">
                                <i class="fas fa-chart-pie"></i>
                            </div>
                            <div class="flex-grow">
                                <div class="flex justify-between items-start">
                                    <p class="text-sm font-medium text-gray-800">Performance Manager</p>
                                    <span class="text-[10px] text-gray-400">4h ago</span>
                                </div>
                                <p class="text-xs text-gray-500 mt-0.5">25 Employee Appraisals pending review.</p>
                                <div class="flex gap-2 mt-3">
                                    <a href="../Modules/performance_and_appraisals.php"
                                        class="text-[10px] bg-indigo-600 text-white px-3 py-1.5 rounded hover:bg-indigo-700 transition-colors">Go
                                        to Appraisals</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

        </main>
    </div>

    <!-- Scripts -->
    <script>
        // --- DATA ---
        const topPerformers = <?= json_encode($top_performers) ?>;
        const lowPerformers = <?= json_encode($low_performers) ?>;
        const incidentData = <?= json_encode(array_values($incidents_by_month)) ?>;
        const turnoverData = [<?= $active_employees ?>, <?= $inactive_employees ?>];

        // --- CHARTS ---

        // 1. Hiring Trends
        new Chart(document.getElementById('hiringChart').getContext('2d'), {
            type: 'line',
            data: {
                labels: ['Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
                datasets: [
                    {
                        label: 'Applicants',
                        data: [65, 59, 80, 81, 56, <?= $applicants_today + 12 ?>],
                        borderColor: '#6366f1',
                        backgroundColor: 'rgba(99, 102, 241, 0.1)',
                        tension: 0.4, fill: true
                    },
                    {
                        label: 'Hired',
                        data: [28, 48, 40, 19, 26, 5],
                        borderColor: '#10b981',
                        borderDash: [5, 5],
                        tension: 0.4
                    }
                ]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: { legend: { position: 'top', align: 'end', labels: { usePointStyle: true, boxWidth: 8 } } },
                scales: {
                    y: { grid: { borderDash: [2, 4], color: '#f3f4f6' }, border: { display: false } },
                    x: { grid: { display: false }, border: { display: false } }
                },
                interaction: { intersect: false, mode: 'index' }
            }
        });

        // 2. Incident Chart (Bar)
        new Chart(document.getElementById('incidentChart').getContext('2d'), {
            type: 'bar',
            data: {
                labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
                datasets: [{
                    label: 'Incidents',
                    data: incidentData,
                    backgroundColor: '#ef4444',
                    borderRadius: 4
                }]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: { y: { beginAtZero: true, grid: { display: false } }, x: { grid: { display: false } } }
            }
        });

        // 3. Turnover/Status Chart (Doughnut)
        new Chart(document.getElementById('turnoverChart').getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: ['Active', 'Inactive'],
                datasets: [{
                    data: turnoverData,
                    backgroundColor: ['#10b981', '#9ca3af'],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                cutout: '70%',
                plugins: { legend: { position: 'right', labels: { boxWidth: 10 } } }
            }
        });

        // --- PERFORMANCE TOGGLE ---
        const perfBody = document.getElementById('performanceBody');
        const btnTop = document.getElementById('btnTop');
        const btnLow = document.getElementById('btnLow');

        function renderPerformance(data, type) {
            perfBody.innerHTML = '';
            if (data.length === 0) {
                perfBody.innerHTML = '<tr><td colspan="4" class="text-center py-4 text-gray-500">No data available.</td></tr>';
                return;
            }
            data.forEach(p => {
                const badge = type === 'top'
                    ? '<span class="bg-green-100 text-green-700 text-xs px-2 py-0.5 rounded-full">Exceeding</span>'
                    : '<span class="bg-red-100 text-red-700 text-xs px-2 py-0.5 rounded-full">Needs Imp.</span>';

                const initial = p.name ? p.name.charAt(0) : '?';

                const row = `
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-4 py-3 font-medium text-gray-900 flex items-center gap-3">
                            <div class="w-8 h-8 rounded-full bg-gray-100 flex items-center justify-center text-gray-600 font-bold text-xs">
                                ${initial}
                            </div>
                            ${p.name}
                        </td>
                        <td class="px-4 py-3 text-gray-500">${p.position}</td>
                        <td class="px-4 py-3">${badge}</td>
                        <td class="px-4 py-3 text-right font-bold ${type === 'top' ? 'text-indigo-600' : 'text-orange-500'}">${parseFloat(p.rating).toFixed(1)}/5.0</td>
                    </tr>
                `;
                perfBody.innerHTML += row;
            });
        }

        function togglePerformance(type) {
            if (type === 'top') {
                renderPerformance(topPerformers, 'top');
                btnTop.className = "px-4 py-1.5 text-xs font-medium rounded-md bg-white text-gray-800 shadow-sm transition-all";
                btnLow.className = "px-4 py-1.5 text-xs font-medium rounded-md text-gray-500 hover:text-gray-700 transition-all";
            } else {
                renderPerformance(lowPerformers, 'low');
                btnLow.className = "px-4 py-1.5 text-xs font-medium rounded-md bg-white text-gray-800 shadow-sm transition-all";
                btnTop.className = "px-4 py-1.5 text-xs font-medium rounded-md text-gray-500 hover:text-gray-700 transition-all";
            }
        }

        // Init
        togglePerformance('top');

    </script>
</body>

</html>
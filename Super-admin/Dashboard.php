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

// HR Requests
$hr_requests = [];
try {
    $stmt = $conn->query("SELECT * FROM user_notifications WHERE type='hr_request' ORDER BY created_at DESC LIMIT 5");
    $hr_requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}

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
                        brand: { 500: '#6366f1', 600: '#4f46e5', 50: '#1e1b4b' },
                        success: '#10b981',
                        warning: '#f59e0b',
                        danger: '#ef4444',
                        dark: {
                            950: '#030712',
                            900: '#111827',
                            800: '#1f2937',
                            700: '#374151'
                        }
                    }
                }
            }
        }
    </script>

    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
        /* Glassmorphism & Custom Scrollbar */
        /* Glassmorphism & Custom Scrollbar */
        .glass-panel {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(0, 0, 0, 0.05);
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
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.05);
        }
    </style>
</head>

<body class="bg-[#f8f9fa] text-gray-800 font-sans">

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
                    class="bg-white rounded-xl shadow-sm p-6 card-hover transition-all duration-300 border border-gray-100 border-l-4 border-l-blue-500 relative overflow-hidden group">
                    <div class="absolute right-0 top-0 h-full w-16 bg-blue-500/5 transform skew-x-12 translate-x-8"></div>
                    <div class="flex justify-between items-start relative z-10">
                        <div>
                            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Total Employees</p>
                            <h3 class="text-3xl font-bold text-gray-900 mt-2"><?= $total_employees ?></h3>
                            <div class="flex items-center gap-2 mt-2 text-xs">
                                <span
                                    class="text-green-600 bg-green-50 px-2 py-0.5 rounded-full font-medium"><?= $active_employees ?>
                                    Active</span>
                                <span
                                    class="text-gray-500 bg-gray-50 px-2 py-0.5 rounded-full"><?= $inactive_employees ?>
                                    Inactive</span>
                            </div>
                        </div>
                        <div class="p-3 bg-blue-50 rounded-lg text-blue-500">
                            <i class="fas fa-users text-xl"></i>
                        </div>
                    </div>
                </div>
 
                <!-- Job Openings -->
                <a href="../Modules/Job_posting.php"
                    class="block bg-white rounded-xl shadow-sm p-6 card-hover transition-all duration-300 border border-gray-100 border-l-4 border-l-purple-500 relative overflow-hidden group">
                    <div class="absolute right-0 top-0 h-full w-16 bg-purple-500/5 transform skew-x-12 translate-x-8">
                    </div>
                    <div class="flex justify-between items-start relative z-10">
                        <div>
                            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Job Openings</p>
                            <h3 class="text-3xl font-bold text-gray-900 mt-2"><?= $open_jobs ?></h3>
                            <p class="text-xs text-purple-600 mt-2 font-medium">Accepting Applications</p>
                        </div>
                        <div class="p-3 bg-purple-50 rounded-lg text-purple-500">
                            <i class="fas fa-briefcase text-xl"></i>
                        </div>
                    </div>
                </a>

                <!-- Applicants -->
                <a href="../Modules/candidate_sourcing_&_tracking.php"
                    class="block bg-white rounded-xl shadow-sm p-6 card-hover transition-all duration-300 border border-gray-100 border-l-4 border-l-pink-500 relative overflow-hidden group">
                    <div class="absolute right-0 top-0 h-full w-16 bg-pink-500/5 transform skew-x-12 translate-x-8"></div>
                    <div class="flex justify-between items-start relative z-10">
                        <div>
                            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">New Applicants</p>
                            <div class="flex items-baseline gap-2 mt-2">
                                <h3 class="text-3xl font-bold text-gray-900"><?= $applicants_today ?></h3>
                                <span class="text-xs text-gray-400">Today</span>
                            </div>
                            <p class="text-xs text-gray-400 mt-1"><?= $applicants_month ?> this month</p>
                        </div>
                        <div class="p-3 bg-pink-50 rounded-lg text-pink-500">
                            <i class="fas fa-user-clock text-xl"></i>
                        </div>
                    </div>
                </a>
 
                <!-- Interviews -->
                <a href="../Modules/Interviewschedule.php"
                    class="block bg-white rounded-xl shadow-sm p-6 card-hover transition-all duration-300 border border-gray-100 border-l-4 border-l-yellow-500 relative overflow-hidden group">
                    <div class="absolute right-0 top-0 h-full w-16 bg-yellow-500/5 transform skew-x-12 translate-x-8">
                    </div>
                    <div class="flex justify-between items-start relative z-10">
                        <div>
                            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Interviews</p>
                            <h3 class="text-3xl font-bold text-gray-900 mt-2"><?= $scheduled_interviews ?></h3>
                            <p class="text-xs text-yellow-600 mt-2 font-medium">Scheduled</p>
                        </div>
                        <div class="p-3 bg-yellow-50 rounded-lg text-yellow-600">
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
                            <h4 class="font-bold text-gray-800 uppercase text-xs tracking-widest">Hiring Trends</h4>
                        </div>
                        <div class="h-64">
                            <canvas id="hiringChart"></canvas>
                        </div>
                    </div>

                    <!-- Secondary Charts Row -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Incident Chart -->
                        <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
                            <h4 class="font-bold text-gray-800 mb-4 text-xs uppercase tracking-widest">Accidents per Month</h4>
                            <div class="h-48">
                                <canvas id="incidentChart"></canvas>
                            </div>
                        </div>
                        <!-- Turnover (Active vs Inactive) -->
                        <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
                            <h4 class="font-bold text-gray-800 mb-4 text-xs uppercase tracking-widest">Employee Status</h4>
                            <div class="h-48 relative flex items-center justify-center">
                                <canvas id="turnoverChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Safety Incident Alerts Panel -->
                <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100 flex flex-col h-full">
                    <div class="flex justify-between items-center mb-4">
                        <h4 class="font-bold text-gray-800 flex items-center gap-2 uppercase text-xs tracking-widest">
                            <i class="fas fa-exclamation-triangle text-amber-500"></i> Safety Alerts
                        </h4>
                        <span
                            class="bg-red-50 text-red-600 text-[10px] px-2 py-1 rounded-full font-bold border border-red-100 uppercase tracking-tighter"><?= count($recent_incidents) ?>
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
                                        <p class="text-sm font-bold text-red-600">
                                            <?= htmlspecialchars($incident['incident_type']) ?>
                                        </p>
                                        <span
                                            class="text-[10px] text-gray-400"><?= date('M d', strtotime($incident['incident_date'])) ?></span>
                                    </div>
                                    <p class="text-xs text-gray-600 mb-2 font-light"><?= htmlspecialchars($incident['incident_details']) ?>
                                    </p>
                                    <div class="flex items-center gap-2">
                                        <span
                                            class="text-[10px] uppercase font-bold px-1.5 py-0.5 rounded bg-red-500 text-white shadow-sm shadow-red-200"><?= htmlspecialchars($incident['severity']) ?></span>
                                        <span class="text-[10px] text-gray-400"><i
                                                class="fas fa-map-marker-alt mr-1"></i><?= htmlspecialchars($incident['location']) ?></span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>

                        <!-- Static Notification Placeholders if needed to fill space -->
                        <div class="p-4 bg-gray-50 rounded-lg border border-gray-100">
                            <div class="flex gap-3">
                                <div
                                    class="w-8 h-8 rounded-full bg-yellow-50 flex items-center justify-center flex-shrink-0 text-yellow-600 border border-yellow-100">
                                    <i class="fas fa-id-card text-xs"></i>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-gray-800">License Expiring</p>
                                    <p class="text-xs text-gray-500">Sarah J. - Safety Officer Cert</p>
                                    <p class="text-[10px] text-red-600 mt-1 font-medium">Expires: 3 Days</p>
                                </div>
                            </div>
                        </div>

                        <div class="p-4 bg-gray-50 rounded-lg border border-gray-100">
                            <div class="flex gap-3">
                                <div
                                    class="w-8 h-8 rounded-full bg-blue-50 flex items-center justify-center flex-shrink-0 text-blue-600 border border-blue-100">
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
                        <h4 class="font-bold text-gray-800 uppercase text-xs tracking-widest">Performance Summary</h4>
                        <div class="flex bg-gray-50 rounded-lg p-1 border border-gray-200">
                            <button id="btnTop" onclick="togglePerformance('top')"
                                class="px-4 py-1.5 text-xs font-bold rounded-md bg-indigo-600 text-white shadow-lg transition-all">Top
                                Performers</button>
                            <button id="btnLow" onclick="togglePerformance('low')"
                                class="px-4 py-1.5 text-xs font-bold rounded-md text-gray-400 hover:text-gray-600 transition-all">Low
                                Performers</button>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-sm text-left">
                            <thead class="text-xs text-gray-500 uppercase bg-gray-50 border-b border-gray-100">
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
                    <h4 class="font-bold text-gray-800 mb-4 uppercase text-xs tracking-widest">Pending HR Requests</h4>

                    <div class="space-y-4">
                        <?php if (empty($hr_requests)): ?>
                            <div class="text-center py-4 text-gray-400 text-sm">No pending requests.</div>
                        <?php else: ?>
                            <?php foreach ($hr_requests as $req): 
                                $data = json_decode($req['data'], true);
                                $name = ($data['name'] ?? '') . ' ' . ($data['lastname'] ?? '');
                                $pos = $data['position'] ?? 'N/A';
                            ?>
                                <div class="flex gap-3 items-start group">
                                    <div class="w-10 h-10 rounded-full bg-indigo-50 flex items-center justify-center flex-shrink-0 text-indigo-600 font-bold text-sm border border-indigo-100">
                                        <i class="fas fa-user-plus"></i>
                                    </div>
                                    <div class="flex-grow">
                                        <div class="flex justify-between items-start">
                                            <p class="text-sm font-medium text-gray-900"><?= htmlspecialchars($name) ?></p>
                                            <span class="text-[10px] text-gray-400"><?= date('M d', strtotime($req['created_at'])) ?></span>
                                        </div>
                                        <p class="text-xs text-gray-500 mt-0.5 font-light">Application for: <?= htmlspecialchars($pos) ?></p>
                                        <div class="flex gap-2 mt-3">
                                            <a href="https://admin.cranecali-ms.com/" target="_blank"
                                                class="text-[10px] bg-indigo-600 text-white px-3 py-1.5 rounded hover:bg-indigo-700 transition-colors">
                                                View in Remote Portal <i class="fas fa-external-link-alt ml-1"></i>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                                <hr class="border-gray-100 last:hidden">
                            <?php endforeach; ?>
                        <?php endif; ?>
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
                    y: { grid: { borderDash: [2, 4], color: '#e5e7eb' }, border: { display: false }, ticks: { color: '#9ca3af', font: { size: 10 } } },
                    x: { grid: { display: false }, border: { display: false }, ticks: { color: '#9ca3af', font: { size: 10 } } }
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
                scales: { 
                    y: { beginAtZero: true, grid: { display: false }, ticks: { color: '#9ca3af', font: { size: 10 } } }, 
                    x: { grid: { display: false }, ticks: { color: '#9ca3af', font: { size: 10 } } } 
                }
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
                plugins: { legend: { position: 'right', labels: { boxWidth: 10, color: '#9ca3af', font: { size: 10 } } } }
            }
        });

        // --- PERFORMANCE TOGGLE ---
        const perfBody = document.getElementById('performanceBody');
        const btnTop = document.getElementById('btnTop');
        const btnLow = document.getElementById('btnLow');

        function renderPerformance(data, type) {
            perfBody.innerHTML = '';
            if (data.length === 0) {
                perfBody.innerHTML = '<tr><td colspan="4" class="text-center py-8 text-gray-500 font-light">No data available.</td></tr>';
                return;
            }
            data.forEach(p => {
                const badge = type === 'top'
                    ? '<span class="bg-indigo-500/10 text-indigo-400 text-[10px] font-bold px-2 py-0.5 rounded border border-indigo-500/20 lowercase tracking-wider">exceeding</span>'
                    : '<span class="bg-red-500/10 text-red-400 text-[10px] font-bold px-2 py-0.5 rounded border border-red-500/20 lowercase tracking-wider">needs imp.</span>';

                const initial = p.name ? p.name.charAt(0) : '?';

                const row = `
                    <tr class="hover:bg-gray-50 transition-colors group">
                        <td class="px-4 py-4 font-medium text-gray-900 flex items-center gap-3">
                            <div class="w-8 h-8 rounded-full bg-gray-100 flex items-center justify-center text-indigo-600 font-bold text-xs ring-1 ring-gray-200">
                                ${initial}
                            </div>
                            <span class="group-hover:text-indigo-600 transition-colors">${p.name}</span>
                        </td>
                        <td class="px-4 py-3 text-gray-500 font-light">${p.position}</td>
                        <td class="px-4 py-3">${badge}</td>
                        <td class="px-4 py-3 text-right font-bold ${type === 'top' ? 'text-indigo-600' : 'text-orange-500'}">${parseFloat(p.rating).toFixed(1)}</td>
                    </tr>
                `;
                perfBody.innerHTML += row;
            });
        }

        function togglePerformance(type) {
            if (type === 'top') {
                renderPerformance(topPerformers, 'top');
                btnTop.className = "px-4 py-1.5 text-xs font-bold rounded-md bg-indigo-600 text-white shadow-lg transition-all";
                btnLow.className = "px-4 py-1.5 text-xs font-bold rounded-md text-gray-500 hover:text-gray-300 transition-all";
            } else {
                renderPerformance(lowPerformers, 'low');
                btnLow.className = "px-4 py-1.5 text-xs font-bold rounded-md bg-indigo-600 text-white shadow-lg transition-all";
                btnTop.className = "px-4 py-1.5 text-xs font-bold rounded-md text-gray-500 hover:text-gray-300 transition-all";
            }
        }

        // Init
        togglePerformance('top');

    </script>
</body>

</html>
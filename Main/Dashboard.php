<?php
session_start();
// require_once "Connections.php"; // This should be uncommented on your live server

// Make sure session exists
if (!isset($_SESSION['Email']) || !isset($_SESSION['Account_type'])) {
    // header("Location: login.php");
    // exit();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>HR1 ADMIN | Dashboard</title>
    <link rel="icon" type="image/x-icon" href="../Image/logo.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.1/css/all.min.css" />
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @import url("https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap");

        :root {
            --primary-color: #000;
            --secondary-color: #0a0a0a;
            --background-light: #f8f9fa;
            --background-card: #ffffff;
            --text-dark: #333;
            --text-light: #f4f4f4;
            --shadow-subtle: 0 4px 12px rgba(0, 0, 0, 0.08);
            --border-radius: 12px;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: "Poppins", sans-serif;
        }

        body {
            background-color: var(--background-light);
            min-height: 100vh;
            color: var(--text-dark);
        }


        /* --- Main Content Layout --- */
        /* --- Main Content Layout --- */
        .main-content {
            margin-left: 16rem;
            /* Match w-64 of sidebar */
            padding: 110px 2.5rem 2.5rem;
            /* 110px top padding (70px header + 40px gap) */
            min-height: 100vh;
            transition: all 0.3s ease;
            position: relative;
            z-index: 10;
        }

        .stat-card {
            background: #fff;
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            border: 1px solid rgba(0, 0, 0, 0.05);
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        }

        .icon-box {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-bottom: 16px;
        }

        .dashboard-container {
            padding: 2rem;
            max-width: 1400px;
            margin: 0 auto;
        }

        .top-navbar {
            display: flex;
            justify-content: space-between;
            /* Para maghiwalay ang menu at oras */
            align-items: center;
            margin-bottom: 20px;
        }

        .menu-toggle {
            font-size: 1.5rem;
            cursor: pointer;
            color: var(--secondary-color);
        }

        .datetime-display {
            font-size: 1rem;
            font-weight: 500;
            color: #555;
        }

        .dashboard-header {
            text-align: center;
            margin-bottom: 30px;
        }

        /* --- Chart Section (INAYOS) --- */
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 25px;
            flex-grow: 1;
            /* Para mag-expand ang grid */
        }

        .chart-container {
            background-color: var(--background-card);
            padding: 20px;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-subtle);
            display: flex;
            flex-direction: column;
            /* Tinanggal ang fixed height para mag-stretch */
        }

        .chart-container h3 {
            text-align: center;
            margin-bottom: 15px;
            color: var(--primary-color);
            font-size: 1.1rem;
            flex-shrink: 0;
        }

        .chart-wrapper {
            position: relative;
            flex-grow: 1;
            width: 100%;
        }

        /* --- Media Queries --- */
        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
                padding: 15px;
                width: 100%;
            }

            .sidebar.close~.main-content {
                margin-left: 0;
            }

            .dashboard-grid {
                grid-template-columns: 1fr;
            }

            .datetime-display {
                display: none;
            }
        }
    </style>
</head>

<body>

    <?php include '../Components/sidebar_admin.php'; ?>

    <div class="main-content">
        <?php include '../Components/header_admin.php'; ?>

        <!-- Welcome Section -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-800">Dashboard Overview</h1>
            <p class="text-gray-500 mt-2">Welcome back, Administrator. Here's what's happening today.</p>
        </div>

        <!-- Stats Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <!-- Stat Card 1 -->
            <div class="stat-card">
                <div class="flex justify-between items-start">
                    <div>
                        <p class="text-gray-500 text-sm font-medium mb-1">Total Employees</p>
                        <h3 class="text-3xl font-bold text-gray-800">124</h3>
                        <p class="text-green-500 text-sm mt-2 font-medium"><i class="fas fa-arrow-up mr-1"></i> +4 new
                            this month</p>
                    </div>
                    <div class="icon-box bg-blue-50 text-blue-600">
                        <i class="fas fa-users"></i>
                    </div>
                </div>
            </div>

            <!-- Stat Card 2 -->
            <div class="stat-card">
                <div class="flex justify-between items-start">
                    <div>
                        <p class="text-gray-500 text-sm font-medium mb-1">Open Positions</p>
                        <h3 class="text-3xl font-bold text-gray-800">8</h3>
                        <p class="text-gray-500 text-sm mt-2">Active job postings</p>
                    </div>
                    <div class="icon-box bg-purple-50 text-purple-600">
                        <i class="fas fa-briefcase"></i>
                    </div>
                </div>
            </div>

            <!-- Stat Card 3 -->
            <div class="stat-card">
                <div class="flex justify-between items-start">
                    <div>
                        <p class="text-gray-500 text-sm font-medium mb-1">Applications</p>
                        <h3 class="text-3xl font-bold text-gray-800">45</h3>
                        <p class="text-green-500 text-sm mt-2 font-medium"><i class="fas fa-arrow-up mr-1"></i> +12 this
                            week</p>
                    </div>
                    <div class="icon-box bg-orange-50 text-orange-600">
                        <i class="fas fa-file-alt"></i>
                    </div>
                </div>
            </div>

            <!-- Stat Card 4 -->
            <div class="stat-card">
                <div class="flex justify-between items-start">
                    <div>
                        <p class="text-gray-500 text-sm font-medium mb-1">Interviews</p>
                        <h3 class="text-3xl font-bold text-gray-800">6</h3>
                        <p class="text-blue-500 text-sm mt-2 font-medium">Scheduled for today</p>
                    </div>
                    <div class="icon-box bg-emerald-50 text-emerald-600">
                        <i class="fas fa-calendar-check"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts & Activity Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Charts Column -->
            <div class="lg:col-span-2 space-y-8">
                <!-- Main Chart -->
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
                    <div class="flex justify-between items-center mb-6">
                        <h3 class="text-lg font-bold text-gray-800">Recruitment Activity</h3>
                        <select
                            class="text-sm border-gray-200 rounded-lg text-gray-500 focus:ring-blue-500 focus:border-blue-500 px-3 py-1">
                            <option>Last 6 Months</option>
                            <option>This Year</option>
                        </select>
                    </div>
                    <div class="chart-wrapper h-[300px]">
                        <canvas id="recruitmentChart"></canvas>
                    </div>
                </div>

                <!-- Secondary Chart Row -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
                        <h3 class="text-lg font-bold text-gray-800 mb-4">Employee Status</h3>
                        <div class="chart-wrapper h-[200px] flex justify-center">
                            <canvas id="statusChart"></canvas>
                        </div>
                    </div>

                    <!-- Quick Tasks -->
                    <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
                        <h3 class="text-lg font-bold text-gray-800 mb-4">Quick Actions</h3>
                        <div class="grid grid-cols-2 gap-4">
                            <a href="../Modules/job_posting.php"
                                class="p-4 rounded-xl bg-blue-50 hover:bg-blue-100 transition-colors text-center group">
                                <i
                                    class="fas fa-plus text-blue-600 text-xl mb-2 group-hover:scale-110 transition-transform"></i>
                                <p class="text-sm font-medium text-gray-700">Post Job</p>
                            </a>
                            <a href="../Main/candidate_sourcing_&_tracking.php"
                                class="p-4 rounded-xl bg-purple-50 hover:bg-purple-100 transition-colors text-center group">
                                <i
                                    class="fas fa-user-plus text-purple-600 text-xl mb-2 group-hover:scale-110 transition-transform"></i>
                                <p class="text-sm font-medium text-gray-700">Add Candidate</p>
                            </a>
                            <a href="../Modules/recognition.php"
                                class="p-4 rounded-xl bg-yellow-50 hover:bg-yellow-100 transition-colors text-center group">
                                <i
                                    class="fas fa-star text-yellow-600 text-xl mb-2 group-hover:scale-110 transition-transform"></i>
                                <p class="text-sm font-medium text-gray-700">Recognize</p>
                            </a>
                            <a href="../Modules/learning.php"
                                class="p-4 rounded-xl bg-red-50 hover:bg-red-100 transition-colors text-center group">
                                <i
                                    class="fas fa-shield-alt text-red-600 text-xl mb-2 group-hover:scale-110 transition-transform"></i>
                                <p class="text-sm font-medium text-gray-700">Report Issue</p>
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Activity Sidebar -->
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 h-fit">
                <h3 class="text-lg font-bold text-gray-800 mb-6">Recent Activity</h3>

                <div class="space-y-6">
                    <div class="flex gap-4">
                        <div class="mt-1">
                            <div
                                class="w-8 h-8 rounded-full bg-green-100 flex items-center justify-center text-green-600">
                                <i class="fas fa-check"></i>
                            </div>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-800">New Employee Hired</p>
                            <p class="text-xs text-gray-500 mt-0.5">Sarah Jenkins joined as UX Designer</p>
                            <p class="text-[10px] text-gray-400 mt-1">2 mins ago</p>
                        </div>
                    </div>

                    <div class="flex gap-4">
                        <div class="mt-1">
                            <div
                                class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center text-blue-600">
                                <i class="fas fa-file-alt"></i>
                            </div>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-800">Application Received</p>
                            <p class="text-xs text-gray-500 mt-0.5">John Doe applied for Senior Dev</p>
                            <p class="text-[10px] text-gray-400 mt-1">1 hour ago</p>
                        </div>
                    </div>

                    <div class="flex gap-4">
                        <div class="mt-1">
                            <div
                                class="w-8 h-8 rounded-full bg-purple-100 flex items-center justify-center text-purple-600">
                                <i class="fas fa-comment fa-sm"></i>
                            </div>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-800">Interview Scheduled</p>
                            <p class="text-xs text-gray-500 mt-0.5">With Michael Brown for PM Role</p>
                            <p class="text-[10px] text-gray-400 mt-1">3 hours ago</p>
                        </div>
                    </div>

                    <div class="flex gap-4">
                        <div class="mt-1">
                            <div
                                class="w-8 h-8 rounded-full bg-yellow-100 flex items-center justify-center text-yellow-600">
                                <i class="fas fa-trophy fa-sm"></i>
                            </div>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-800">Employee Recognition</p>
                            <p class="text-xs text-gray-500 mt-0.5">Mark attained "Star of Month"</p>
                            <p class="text-[10px] text-gray-400 mt-1">Yesterday</p>
                        </div>
                    </div>
                </div>

                <button
                    class="w-full mt-6 py-2.5 text-sm font-medium text-blue-600 bg-blue-50 hover:bg-blue-100 rounded-lg transition-colors">
                    View All Activity
                </button>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Recruitment Chart
            const ctx1 = document.getElementById('recruitmentChart').getContext('2d');
            new Chart(ctx1, {
                type: 'line',
                data: {
                    labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
                    datasets: [{
                        label: 'Applications',
                        data: [65, 59, 80, 81, 56, 95],
                        borderColor: '#2563eb',
                        backgroundColor: 'rgba(37, 99, 235, 0.1)',
                        tension: 0.4,
                        fill: true
                    }, {
                        label: 'Hired',
                        data: [28, 48, 40, 19, 36, 27],
                        borderColor: '#10b981',
                        backgroundColor: 'rgba(16, 185, 129, 0.1)',
                        tension: 0.4,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });

            // Status Chart
            const ctx2 = document.getElementById('statusChart').getContext('2d');
            new Chart(ctx2, {
                type: 'doughnut',
                data: {
                    labels: ['Active', 'On Leave', 'Remote', 'Training'],
                    datasets: [{
                        data: [300, 50, 100, 40],
                        backgroundColor: ['#2563eb', '#f59e0b', '#8b5cf6', '#10b981'],
                        hoverOffset: 4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'right',
                            labels: {
                                boxWidth: 12,
                                usePointStyle: true,
                            }
                        }
                    }
                }
            });
        });
    </script>
</body>

</html>
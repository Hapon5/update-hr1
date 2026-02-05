<?php
session_start();
include('../Database/Connections.php');

// Simple Shortcuts Page
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shortcuts | HR Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        @import url("https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap");
        body { font-family: 'Poppins', sans-serif; background-color: #f8fafc; }
        .shortcut-card:hover i { transform: scale(1.1); }
    </style>
</head>
<body class="flex">

    <?php include '../Components/sidebar_admin.php'; ?>

    <div class="flex-1 ml-64 p-10 transition-all duration-300">
        <?php include '../Components/header_admin.php'; ?>

        <div class="max-w-6xl mx-auto mt-10">
            <h1 class="text-3xl font-bold text-gray-900 mb-2">Quick Shortcuts</h1>
            <p class="text-gray-500 mb-8">Access frequently used tools and modules efficiently.</p>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                
                <!-- Safety Incident Report -->
                <a href="learning.php" class="shortcut-card bg-white p-6 rounded-2xl shadow-sm border border-gray-100 hover:shadow-md transition-all group flex items-start gap-4">
                    <div class="w-12 h-12 rounded-xl bg-red-50 text-red-500 flex items-center justify-center text-xl group-hover:bg-red-500 group-hover:text-white transition-colors">
                        <i class="fas fa-shield-alt text-lg transition-transform duration-300"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-gray-800 group-hover:text-red-600 transition-colors">Safety Incidents</h3>
                        <p class="text-sm text-gray-500 mt-1">Report injuries, hazards, and view safety compliance logs.</p>
                    </div>
                </a>

                <!-- Add Candidate -->
                <a href="../Main/candidate_sourcing_&_tracking.php" class="shortcut-card bg-white p-6 rounded-2xl shadow-sm border border-gray-100 hover:shadow-md transition-all group flex items-start gap-4">
                    <div class="w-12 h-12 rounded-xl bg-indigo-50 text-indigo-500 flex items-center justify-center text-xl group-hover:bg-indigo-500 group-hover:text-white transition-colors">
                        <i class="fas fa-user-plus text-lg transition-transform duration-300"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-gray-800 group-hover:text-indigo-600 transition-colors">Add Candidate</h3>
                        <p class="text-sm text-gray-500 mt-1">Manually add a new candidate or upload a resume.</p>
                    </div>
                </a>

                <!-- Post a Job -->
                <a href="job_posting.php" class="shortcut-card bg-white p-6 rounded-2xl shadow-sm border border-gray-100 hover:shadow-md transition-all group flex items-start gap-4">
                    <div class="w-12 h-12 rounded-xl bg-blue-50 text-blue-500 flex items-center justify-center text-xl group-hover:bg-blue-500 group-hover:text-white transition-colors">
                        <i class="fas fa-briefcase text-lg transition-transform duration-300"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-gray-800 group-hover:text-blue-600 transition-colors">Post Job</h3>
                        <p class="text-sm text-gray-500 mt-1">Create and publish a new job opening.</p>
                    </div>
                </a>

                <!-- Check Attendance -->
                <a href="../Employee/Dashboard.php" class="shortcut-card bg-white p-6 rounded-2xl shadow-sm border border-gray-100 hover:shadow-md transition-all group flex items-start gap-4">
                    <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-500 flex items-center justify-center text-xl group-hover:bg-emerald-500 group-hover:text-white transition-colors">
                        <i class="fas fa-clock text-lg transition-transform duration-300"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-gray-800 group-hover:text-emerald-600 transition-colors">Attendance</h3>
                        <p class="text-sm text-gray-500 mt-1">View employee attendance and time logs.</p>
                    </div>
                </a>

            </div>
        </div>
    </div>
</body>
</html>

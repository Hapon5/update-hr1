<?php
session_start();
include('../Database/Connections.php');

if (!isset($_SESSION['Email']) || $_SESSION['Account_type'] != 2) {
    header("Location: login.php");
    exit();
}

$email = $_SESSION['Email'];
// Fetch generic staff details or use email
// If you have a 'staff' table, fetch from there. For now, use email.
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Dashboard - HR1</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>body { font-family: 'Poppins', sans-serif; }</style>
</head>
<body class="bg-gray-100 flex">
    
    <!-- Sidebar -->
    <?php include 'Components/sidebar.php'; ?>

    <!-- Main Content -->
    <div class="flex-1 flex flex-col">
        <!-- Topbar -->
        <header class="bg-white shadow-sm py-4 px-6 flex justify-between items-center">
            <div class="flex items-center">
                <button class="md:hidden text-gray-500 mr-4"><i class="fas fa-bars text-xl"></i></button>
                <h2 class="text-xl font-semibold text-gray-800">Overview</h2>
            </div>
            <div class="flex items-center gap-4">
                <span class="text-gray-600 font-medium"><?php echo htmlspecialchars($email); ?></span>
                <div class="h-8 w-8 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-600 font-bold">
                    <?php echo strtoupper(substr($email, 0, 1)); ?>
                </div>
                <a href="../logout.php" class="text-gray-400 hover:text-red-500 ml-2">
                    <i class="fas fa-sign-out-alt"></i>
                </a>
            </div>
        </header>

        <!-- Content Body -->
        <main class="p-6 overflow-y-auto">
            <!-- Stats -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <div class="bg-white p-6 rounded-lg shadow-sm border-l-4 border-indigo-500">
                    <div class="flex justify-between items-center">
                        <div>
                            <p class="text-gray-500 text-sm">Total Candidates</p>
                            <h3 class="text-2xl font-bold text-gray-800">124</h3>
                        </div>
                        <div class="bg-indigo-50 p-3 rounded-full text-indigo-600">
                            <i class="fas fa-user-friends"></i>
                        </div>
                    </div>
                </div>
                <div class="bg-white p-6 rounded-lg shadow-sm border-l-4 border-yellow-500">
                    <div class="flex justify-between items-center">
                        <div>
                            <p class="text-gray-500 text-sm">Pending Reviews</p>
                            <h3 class="text-2xl font-bold text-gray-800">8</h3>
                        </div>
                        <div class="bg-yellow-50 p-3 rounded-full text-yellow-600">
                            <i class="fas fa-clock"></i>
                        </div>
                    </div>
                </div>
                 <div class="bg-white p-6 rounded-lg shadow-sm border-l-4 border-red-500">
                    <div class="flex justify-between items-center">
                        <div>
                            <p class="text-gray-500 text-sm">Issues Reported</p>
                            <h3 class="text-2xl font-bold text-gray-800">2</h3>
                        </div>
                        <div class="bg-red-50 p-3 rounded-full text-red-600">
                            <i class="fas fa-exclamation-triangle"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Activity Table Placeholder -->
            <div class="bg-white rounded-lg shadow-sm p-6">
                <h3 class="text-lg font-bold text-gray-800 mb-4">Recent Applications</h3>
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr>
                                <th class="py-3 px-4 bg-gray-50 font-medium text-gray-600 border-b">Applicant</th>
                                <th class="py-3 px-4 bg-gray-50 font-medium text-gray-600 border-b">Position</th>
                                <th class="py-3 px-4 bg-gray-50 font-medium text-gray-600 border-b">Date</th>
                                <th class="py-3 px-4 bg-gray-50 font-medium text-gray-600 border-b">Status</th>
                                <th class="py-3 px-4 bg-gray-50 font-medium text-gray-600 border-b">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="py-3 px-4 border-b">John Doe</td>
                                <td class="py-3 px-4 border-b">Software Developer</td>
                                <td class="py-3 px-4 border-b">Oct 24, 2025</td>
                                <td class="py-3 px-4 border-b"><span class="bg-yellow-100 text-yellow-800 text-xs px-2 py-1 rounded-full">Pending</span></td>
                                <td class="py-3 px-4 border-b"><button class="text-indigo-600 hover:underline">View</button></td>
                            </tr>
                             <tr>
                                <td class="py-3 px-4 border-b">Jane Smith</td>
                                <td class="py-3 px-4 border-b">HR Assistant</td>
                                <td class="py-3 px-4 border-b">Oct 23, 2025</td>
                                <td class="py-3 px-4 border-b"><span class="bg-green-100 text-green-800 text-xs px-2 py-1 rounded-full">Interview</span></td>
                                <td class="py-3 px-4 border-b"><button class="text-indigo-600 hover:underline">View</button></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

        </main>
    </div>
</body>
</html>

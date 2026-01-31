<?php
session_start();
include('../Database/Connections.php');

if (!isset($_SESSION['Email']) || $_SESSION['Account_type'] != 3) {
    header("Location: login.php");
    exit();
}

$email = $_SESSION['Email'];
$employee = ['first_name' => 'Employee', 'last_name' => 'User', 'position' => 'Staff'];

try {
    // Fetch details from employees table
    $stmt = $conn->prepare("SELECT * FROM employees WHERE email = ?");
    $stmt->execute([$email]);
    $res = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($res) {
        $employee = $res;
    }
} catch (Exception $e) {
    // Fallback if table doesn't exist or error
}

$photo = !empty($employee['base64_image']) ? $employee['base64_image'] : 'https://ui-avatars.com/api/?name=' . urlencode($employee['first_name'] . ' ' . $employee['last_name']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>body { font-family: 'Poppins', sans-serif; }</style>
</head>
<body class="bg-gray-50 min-h-screen">
    <!-- Navbar -->
    <nav class="bg-white shadow-sm border-b border-gray-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <span class="text-2xl font-bold text-blue-600">HR1 Portal</span>
                </div>
                <div class="flex items-center gap-4">
                    <span class="text-gray-700 font-medium hidden sm:block">Welcome, <?php echo htmlspecialchars($employee['first_name']); ?></span>
                    <img class="h-8 w-8 rounded-full border border-gray-200" src="<?php echo $photo; ?>" alt="Profile">
                    <a href="../logout.php" class="text-gray-500 hover:text-red-600 transition-colors" title="Logout">
                        <i class="fas fa-sign-out-alt text-lg"></i>
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-2xl font-bold text-gray-900">Dashboard</h1>
            <p class="text-gray-500">Overview of your employment status</p>
        </div>

        <!-- Stats Grid -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-blue-50 text-blue-600">
                        <i class="fas fa-calendar-check text-xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500">Attendance</p>
                        <p class="text-lg font-bold text-gray-900">Present</p>
                    </div>
                </div>
            </div>
            <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-green-50 text-green-600">
                        <i class="fas fa-money-bill-wave text-xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500">Payslip</p>
                        <p class="text-lg font-bold text-gray-900">Available</p>
                    </div>
                </div>
            </div>
            <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-purple-50 text-purple-600">
                        <i class="fas fa-clock text-xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500">Leave Balance</p>
                        <p class="text-lg font-bold text-gray-900">12 Days</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- Profile Card -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 bg-gray-50">
                    <h3 class="font-bold text-gray-900">My Profile</h3>
                </div>
                <div class="p-6">
                    <div class="space-y-4">
                        <div class="flex justify-between border-b border-gray-50 pb-2">
                            <span class="text-gray-500">Full Name</span>
                            <span class="font-medium"><?php echo htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name']); ?></span>
                        </div>
                        <div class="flex justify-between border-b border-gray-50 pb-2">
                            <span class="text-gray-500">Position</span>
                            <span class="font-medium"><?php echo htmlspecialchars($employee['position']); ?></span>
                        </div>
                        <div class="flex justify-between border-b border-gray-50 pb-2">
                            <span class="text-gray-500">Department</span>
                            <span class="font-medium"><?php echo htmlspecialchars($employee['department'] ?? 'General'); ?></span>
                        </div>
                        <div class="flex justify-between border-b border-gray-50 pb-2">
                            <span class="text-gray-500">Date Hired</span>
                            <span class="font-medium"><?php echo htmlspecialchars($employee['date_hired'] ?? 'N/A'); ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Announcements / Recent Activity -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 bg-gray-50">
                    <h3 class="font-bold text-gray-900">Announcements</h3>
                </div>
                <div class="p-6">
                    <div class="space-y-4">
                        <div class="flex items-start">
                            <div class="flex-shrink-0">
                                <span class="inline-flex items-center justify-center h-8 w-8 rounded-full bg-blue-100">
                                    <i class="fas fa-bullhorn text-blue-600 text-sm"></i>
                                </span>
                            </div>
                            <div class="ml-4">
                                <h4 class="text-sm font-bold text-gray-900">System Maintenance</h4>
                                <p class="text-sm text-gray-500 mt-1">Scheduled maintenance on Saturday, 10 PM. Please save your work.</p>
                                <span class="text-xs text-gray-400 mt-2 block">2 hours ago</span>
                            </div>
                        </div>
                        <div class="flex items-start">
                            <div class="flex-shrink-0">
                                <span class="inline-flex items-center justify-center h-8 w-8 rounded-full bg-green-100">
                                    <i class="fas fa-leaf text-green-600 text-sm"></i>
                                </span>
                            </div>
                            <div class="ml-4">
                                <h4 class="text-sm font-bold text-gray-900">Holiday Notice</h4>
                                <p class="text-sm text-gray-500 mt-1">Office will be closed next Monday for National Heroes Day.</p>
                                <span class="text-xs text-gray-400 mt-2 block">1 day ago</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
</body>
</html>

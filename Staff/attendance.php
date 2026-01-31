<?php
session_start();
include('../Database/Connections.php');

if (!isset($_SESSION['Email']) || $_SESSION['Account_type'] != 2) {
    header("Location: login.php");
    exit();
}

$email = $_SESSION['Email'];

// Attendance logs
$attendance = [
    ['name' => 'John Doe', 'time_in' => '08:00 AM', 'time_out' => '05:00 PM', 'status' => 'On Time'],
    ['name' => 'Jane Smith', 'time_in' => '08:15 AM', 'time_out' => '05:00 PM', 'status' => 'Late'],
    ['name' => 'Andy Ferrer', 'time_in' => '07:55 AM', 'time_out' => '05:00 PM', 'status' => 'On Time'],
];

try {
    // You can fetch from 'attendance' table if it exists
    // $stmt = $conn->query("SELECT * FROM attendance ORDER BY date DESC");
    // $attendance = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance - HR1 Staff</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>body { font-family: 'Poppins', sans-serif; }</style>
</head>
<body class="bg-gray-100 flex">
    
    <?php include 'Components/sidebar.php'; ?>

    <!-- Main Content -->
    <div class="flex-1 flex flex-col">
        <!-- Topbar -->
        <header class="bg-white shadow-sm py-4 px-6 flex justify-between items-center">
            <div class="flex items-center">
                <button class="md:hidden text-gray-500 mr-4"><i class="fas fa-bars text-xl"></i></button>
                <h2 class="text-xl font-semibold text-gray-800">Attendance Monitoring</h2>
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
            <div class="bg-white rounded-lg shadow-sm p-6 text-center py-12">
                <div class="mb-4">
                    <i class="fas fa-calendar-alt text-6xl text-indigo-100"></i>
                </div>
                <h3 class="text-xl font-bold text-gray-800">Daily Attendance Log</h3>
                <p class="text-gray-500 mb-8">Friday, October 24, 2025</p>

                <div class="overflow-x-auto text-left">
                    <table class="w-full border-collapse">
                        <thead>
                            <tr>
                                <th class="py-3 px-4 bg-gray-50 font-medium text-gray-600 border-b">Employee Name</th>
                                <th class="py-3 px-4 bg-gray-50 font-medium text-gray-600 border-b">Time In</th>
                                <th class="py-3 px-4 bg-gray-50 font-medium text-gray-600 border-b">Time Out</th>
                                <th class="py-3 px-4 bg-gray-50 font-medium text-gray-600 border-b">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($attendance as $log): ?>
                                <tr>
                                    <td class="py-3 px-4 border-b font-medium text-gray-700"><?php echo htmlspecialchars($log['name']); ?></td>
                                    <td class="py-3 px-4 border-b text-gray-600"><?php echo htmlspecialchars($log['time_in']); ?></td>
                                    <td class="py-3 px-4 border-b text-gray-600"><?php echo htmlspecialchars($log['time_out']); ?></td>
                                    <td class="py-3 px-4 border-b">
                                        <span class="<?php echo $log['status'] == 'On Time' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'; ?> text-xs px-2 py-1 rounded-full">
                                            <?php echo htmlspecialchars($log['status']); ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
</body>
</html>

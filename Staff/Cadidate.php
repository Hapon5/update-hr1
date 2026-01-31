<?php
session_start();
include('../Database/Connections.php');

if (!isset($_SESSION['Email']) || $_SESSION['Account_type'] != 2) {
    header("Location: login.php");
    exit();
}

$email = $_SESSION['Email'];

// Fetch candidates
$candidates = [];
try {
    $stmt = $conn->query("SELECT * FROM candidates ORDER BY ID DESC");
    $candidates = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // Table might not exist yet or error
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Candidates - HR1 Staff</title>
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
                <h2 class="text-xl font-semibold text-gray-800">Candidate Management</h2>
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
            <div class="bg-white rounded-lg shadow-sm p-6">
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-lg font-bold text-gray-800">All Candidates</h3>
                    <div class="flex gap-2">
                        <input type="text" placeholder="Search candidates..." class="px-4 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr>
                                <th class="py-3 px-4 bg-gray-50 font-medium text-gray-600 border-b">Candidate</th>
                                <th class="py-3 px-4 bg-gray-50 font-medium text-gray-600 border-b">Applied Job</th>
                                <th class="py-3 px-4 bg-gray-50 font-medium text-gray-600 border-b">Email</th>
                                <th class="py-3 px-4 bg-gray-50 font-medium text-gray-600 border-b">Status</th>
                                <th class="py-3 px-4 bg-gray-50 font-medium text-gray-600 border-b">Date</th>
                                <th class="py-3 px-4 bg-gray-50 font-medium text-gray-600 border-b">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($candidates)): ?>
                                <tr>
                                    <td colspan="6" class="py-8 text-center text-gray-400">No candidates found.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($candidates as $c): ?>
                                    <tr>
                                        <td class="py-3 px-4 border-b font-medium text-gray-800"><?php echo htmlspecialchars($c['CandidateName'] ?? ($c['full_name'] ?? 'N/A')); ?></td>
                                        <td class="py-3 px-4 border-b text-gray-600"><?php echo htmlspecialchars($c['JobTitle'] ?? ($c['position'] ?? 'N/A')); ?></td>
                                        <td class="py-3 px-4 border-b text-gray-600"><?php echo htmlspecialchars($c['Email'] ?? 'N/A'); ?></td>
                                        <td class="py-3 px-4 border-b">
                                            <?php 
                                            $status = $c['Status'] ?? ($c['status'] ?? 'Pending');
                                            $color = 'bg-gray-100 text-gray-800';
                                            if ($status == 'Hired') $color = 'bg-green-100 text-green-800';
                                            if ($status == 'Rejected') $color = 'bg-red-100 text-red-800';
                                            if ($status == 'Interview' || $status == 'Interviewed') $color = 'bg-blue-100 text-blue-800';
                                            ?>
                                            <span class="<?php echo $color; ?> text-xs px-2 py-1 rounded-full"><?php echo $status; ?></span>
                                        </td>
                                        <td class="py-3 px-4 border-b text-gray-500 text-sm"><?php echo htmlspecialchars($c['Date_Applied'] ?? ($c['created_at'] ?? 'N/A')); ?></td>
                                        <td class="py-3 px-4 border-b">
                                            <button class="text-indigo-600 hover:text-indigo-900 font-medium text-sm mr-3">View</button>
                                            <button class="text-gray-400 hover:text-gray-600 text-sm"><i class="fas fa-ellipsis-v"></i></button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
</body>
</html>

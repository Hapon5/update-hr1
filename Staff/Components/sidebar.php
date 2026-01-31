<?php
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!-- Sidebar -->
<aside class="w-64 bg-indigo-900 text-white min-h-screen hidden md:block">
    <div class="p-6">
        <h1 class="text-2xl font-bold">HR1 Staff</h1>
        <p class="text-indigo-200 text-sm">Management Portal</p>
    </div>
    <nav class="mt-6">
        <a href="Dashboard.php" class="block py-3 px-6 <?php echo $current_page == 'Dashboard.php' ? 'bg-indigo-800 border-l-4 border-white' : 'hover:bg-indigo-800 hover:border-l-4 hover:border-indigo-400 transition-all'; ?>">
            <i class="fas fa-th-large mr-3"></i> Dashboard
        </a>
        <a href="Cadidate.php" class="block py-3 px-6 <?php echo $current_page == 'Cadidate.php' ? 'bg-indigo-800 border-l-4 border-white' : 'hover:bg-indigo-800 hover:border-l-4 hover:border-indigo-400 transition-all'; ?>">
            <i class="fas fa-user-tie mr-3"></i> Candidates
        </a>
        <a href="attendance.php" class="block py-3 px-6 <?php echo $current_page == 'attendance.php' ? 'bg-indigo-800 border-l-4 border-white' : 'hover:bg-indigo-800 hover:border-l-4 hover:border-indigo-400 transition-all'; ?>">
            <i class="fas fa-clipboard-list mr-3"></i> Attendance
        </a>
    </nav>
</aside>

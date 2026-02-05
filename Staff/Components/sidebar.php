<?php
// Ensure root_path is defined
$root_path = $root_path ?? '../';
$current_page = basename($_SERVER['PHP_SELF']);

// Fetch user info for sidebar
$sidebar_user = [
    'name' => 'Staff Member',
    'role' => 'Recruitment',
    'photo' => $root_path . 'Image/logo.png'
];

if (isset($_SESSION['Email'])) {
    $email = $_SESSION['Email'];
    try {
        // Use SELECT * to avoid column errors
        $stmt = $conn->prepare("SELECT * FROM employees WHERE email = ?");
        $stmt->execute([$email]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($row) {
            // Dynamic Name Detection
            if (isset($row['first_name'])) {
                 $sidebar_user['name'] = $row['first_name'] . ' ' . ($row['last_name'] ?? '');
            } elseif (isset($row['full_name'])) {
                 $sidebar_user['name'] = $row['full_name'];
            } elseif (isset($row['name'])) {
                 $sidebar_user['name'] = $row['name'];
            }

            $sidebar_user['role'] = $row['position'] ?? 'Staff Member';
            
            if (!empty($row['base64_image'])) {
                $sidebar_user['photo'] = $row['base64_image'];
            }
        } else {
            // Fallback to candidates
            $stmt = $conn->prepare("SELECT full_name FROM candidates WHERE email = ?");
            $stmt->execute([$email]);
            $candidate = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($candidate) {
                $sidebar_user['name'] = $candidate['full_name'];
                $sidebar_user['role'] = 'Staff Member';
                $sidebar_user['name'] = $_SESSION['GlobalName'] ?? $candidate['full_name'];
            }
        }
    } catch (Exception $e) {
        // Fallback to defaults
    }
}

// Helper function to check if a link is active
function isActive($pageName, $current_page)
{
    return $pageName === $current_page ? 'bg-indigo-600/30 text-indigo-400 shadow-sm border border-indigo-500/20' : 'text-gray-400 hover:bg-white/5 hover:text-white transition-all';
}

function getIconColor($pageName, $current_page)
{
    return $pageName === $current_page ? 'text-indigo-400' : 'text-gray-500 group-hover:text-white';
}
?>

<!-- Sidebar -->
<aside
    class="fixed top-0 left-0 h-screen w-64 bg-gray-950 border-r border-gray-800 transition-transform duration-300 z-50 overflow-y-auto custom-scrollbar flex flex-col shadow-2xl"
    id="sidebar">
    <!-- User Profile Header -->
    <div class="flex items-center gap-3 px-6 h-20 border-b border-gray-800 mb-2 flex-shrink-0">
        <div class="relative group">
            <div class="w-10 h-10 rounded-xl bg-gray-900 flex items-center justify-center shadow-lg overflow-hidden border border-gray-800 group-hover:border-indigo-500/50 transition-all">
                <img src="<?php echo $sidebar_user['photo']; ?>" alt="Profile" class="w-full h-full object-cover">
            </div>
            <div class="absolute -bottom-1 -right-1 w-3 h-3 bg-green-500 border-2 border-gray-950 rounded-full"></div>
        </div>
        <div class="overflow-hidden">
            <h2 class="text-white font-bold tracking-tight text-sm truncate uppercase"><?php echo htmlspecialchars($sidebar_user['name']); ?></h2>
            <p class="text-[10px] text-gray-500 font-bold uppercase tracking-widest leading-none mt-1 truncate"><?php echo htmlspecialchars($sidebar_user['role']); ?></p>
        </div>
    </div>

    <!-- Navigation -->
    <nav class="px-3 py-4 space-y-1 flex-grow">
        <p class="px-4 text-[10px] font-bold text-gray-600 uppercase tracking-widest mb-2 mt-2">Core</p>

        <a href="Dashboard.php"
            class="flex items-center gap-3 px-4 py-2.5 rounded-xl group <?php echo isActive('Dashboard.php', $current_page); ?>">
            <i class="fas fa-th-large w-5 text-center <?php echo getIconColor('Dashboard.php', $current_page); ?>"></i>
            <span class="font-medium text-sm">Dashboard</span>
        </a>

        <p class="px-4 text-[10px] font-bold text-gray-600 uppercase tracking-widest mb-2 mt-6">Recruitment</p>

        <a href="Cadidate.php"
            class="flex items-center gap-3 px-4 py-2.5 rounded-xl group <?php echo isActive('Cadidate.php', $current_page); ?>">
            <i class="fas fa-user-tie w-5 text-center <?php echo getIconColor('Cadidate.php', $current_page); ?>"></i>
            <span class="font-medium text-sm">Candidates</span>
        </a>

        <a href="attendance.php"
            class="flex items-center gap-3 px-4 py-2.5 rounded-xl group <?php echo isActive('attendance.php', $current_page); ?>">
            <i class="fas fa-clipboard-list w-5 text-center <?php echo getIconColor('attendance.php', $current_page); ?>"></i>
            <span class="font-medium text-sm">Attendance</span>
        </a>
    </nav>

    <!-- Footer / Logout -->
    <div class="px-4 py-6 border-t border-gray-800 flex-shrink-0">
        <a href="../logout.php"
            class="flex items-center gap-3 px-4 py-3 rounded-xl text-gray-400 hover:bg-red-500/10 hover:text-red-400 transition-all duration-200 group">
            <i class="fas fa-sign-out-alt w-5 text-center group-hover:scale-110 transition-transform"></i>
            <span class="font-semibold text-sm">Logout</span>
        </a>
    </div>
</aside>

<style>
    .custom-scrollbar::-webkit-scrollbar { width: 4px; }
    .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
    .custom-scrollbar::-webkit-scrollbar-thumb { background: rgba(255, 255, 255, 0.2); border-radius: 10px; }
</style>

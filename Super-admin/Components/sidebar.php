<?php
// Ensure root_path is defined
$root_path = $root_path ?? '../';
$current_page = basename($_SERVER['PHP_SELF']);

// Ensure database connection is available
if (!isset($conn)) {
    $conn_path = ($root_path === './' || $root_path === '') ? "../Database/Connections.php" : $root_path . "Database/Connections.php";
    if (file_exists($conn_path)) {
        require_once $conn_path;
    }
}

// Fetch user info for sidebar
$sidebar_user = [
    'name' => 'HR Admin',
    'role' => 'Management',
    'photo' => $root_path . 'Image/logo.png'
];

if (isset($_SESSION['Email'])) {
    $email = $_SESSION['Email'];
    try {
        $stmt = $conn->prepare("SELECT first_name, last_name, position, base64_image FROM employees WHERE email = ?");
        $stmt->execute([$email]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $sidebar_user['name'] = $row['first_name'] . ' ' . $row['last_name'];
            $sidebar_user['role'] = $row['position'];
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
                $sidebar_user['role'] = $_SESSION['Account_type'] == 1 ? 'HR Admin' : 'Staff';
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
    <!-- Brand / User Profile -->
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
        <p class="px-4 text-[10px] font-bold text-gray-600 uppercase tracking-widest mb-2 mt-2">Core Module</p>

        <a href="<?php echo $root_path; ?>Super-admin/Dashboard.php"
            class="flex items-center gap-3 px-4 py-2.5 rounded-xl group <?php echo isActive('Dashboard.php', $current_page); ?>">
            <i class="fas fa-th-large w-5 text-center <?php echo getIconColor('Dashboard.php', $current_page); ?>"></i>
            <span class="font-medium text-sm">Dashboard</span>
        </a>

        <a href="<?php echo $root_path; ?>Employee/Dashboard.php"
            class="flex items-center gap-3 px-4 py-2.5 group hover:bg-indigo-600/20 text-indigo-400/80 hover:text-indigo-400 transition-all rounded-xl mt-1">
            <i class="fas fa-user-circle w-5 text-center"></i>
            <span class="font-medium text-sm">Employee Portal</span>
        </a>

        <p class="px-4 text-[10px] font-bold text-gray-600 uppercase tracking-widest mb-2 mt-6">Recruitment</p>

        <a href="<?php echo $root_path; ?>Super-admin/Modules/Job_posting.php"
            class="flex items-center gap-3 px-4 py-2.5 rounded-xl group <?php echo isActive('Job_posting.php', $current_page); ?>">
            <i
                class="fas fa-briefcase w-5 text-center <?php echo getIconColor('Job_posting.php', $current_page); ?>"></i>
            <span class="font-medium text-sm">Job Posting</span>
        </a>

        <a href="<?php echo $root_path; ?>Super-admin/Modules/candidate_sourcing_&_tracking.php"
            class="flex items-center gap-3 px-4 py-2.5 rounded-xl group <?php echo isActive('candidate_sourcing_&_tracking.php', $current_page); ?>">
            <i
                class="fas fa-user-tie w-5 text-center <?php echo getIconColor('candidate_sourcing_&_tracking.php', $current_page); ?>"></i>
            <span class="font-medium text-sm">Candidate Tracking</span>
        </a>

        <a href="<?php echo $root_path; ?>Super-admin/Modules/Interviewschedule.php"
            class="flex items-center gap-3 px-4 py-2.5 rounded-xl group <?php echo isActive('Interviewschedule.php', $current_page); ?>">
            <i
                class="fas fa-calendar-check w-5 text-center <?php echo getIconColor('Interviewschedule.php', $current_page); ?>"></i>
            <span class="font-medium text-sm">Interview Schedule</span>
        </a>

        <p class="px-4 text-[10px] font-bold text-gray-600 uppercase tracking-widest mb-2 mt-6">Operations</p>

        <a href="<?php echo $root_path; ?>Super-admin/Modules/performance_and_appraisals.php"
            class="flex items-center gap-3 px-4 py-2.5 rounded-xl group <?php echo isActive('performance_and_appraisals.php', $current_page); ?>">
            <i
                class="fas fa-chart-line w-5 text-center <?php echo getIconColor('performance_and_appraisals.php', $current_page); ?>"></i>
            <span class="font-medium text-sm">Performance</span>
        </a>

        <a href="<?php echo $root_path; ?>Super-admin/Modules/safety_management.php"
            class="flex items-center gap-3 px-4 py-2.5 rounded-xl group <?php echo isActive('safety_management.php', $current_page); ?>">
            <i
                class="fas fa-hard-hat w-5 text-center <?php echo getIconColor('safety_management.php', $current_page); ?>"></i>
            <span class="font-medium text-sm">Safety Module</span>
        </a>

        <a href="<?php echo $root_path; ?>Super-admin/Modules/recognition.php"
            class="flex items-center gap-3 px-4 py-2.5 rounded-xl group <?php echo isActive('recognition.php', $current_page); ?>">
            <i class="fas fa-trophy w-5 text-center <?php echo getIconColor('recognition.php', $current_page); ?>"></i>
            <span class="font-medium text-sm">Recognition</span>
        </a>

        <p class="px-4 text-[10px] font-bold text-gray-600 uppercase tracking-widest mb-2 mt-6">System</p>

        <a href="<?php echo $root_path; ?>Main/about_us.php"
            class="flex items-center gap-3 px-4 py-2.5 rounded-xl group <?php echo isActive('about_us.php', $current_page); ?>">
            <i
                class="fas fa-info-circle w-5 text-center <?php echo getIconColor('about_us.php', $current_page); ?>"></i>
            <span class="font-medium text-sm">About Us</span>
        </a>

        <a href="<?php echo $root_path; ?>Super-admin/Modules/account_management.php"
            class="flex items-center gap-3 px-4 py-2.5 rounded-xl group <?php echo isActive('account_management.php', $current_page); ?>">
            <i class="fas fa-users-cog w-5 text-center <?php echo getIconColor('account_management.php', $current_page); ?>"></i>
            <span class="font-medium text-sm">Account List</span>
        </a>

        <a href="<?php echo $root_path; ?>Employee/login.php"
            class="flex items-center gap-3 px-4 py-2.5 rounded-xl group <?php echo isActive('login.php', $current_page); ?>">
            <i class="fas fa-user-cog w-5 text-center <?php echo getIconColor('login.php', $current_page); ?>"></i>
            <span class="font-medium text-sm">Account</span>
        </a>
    </nav>

    <!-- Footer / Logout -->
    <div class="px-4 py-6 border-t border-gray-800 flex-shrink-0">
        <a href="<?php echo $root_path; ?>logout.php"
            class="flex items-center gap-3 px-4 py-3 rounded-xl text-gray-400 hover:bg-red-500/10 hover:text-red-400 transition-all duration-200 group">
            <i class="fas fa-sign-out-alt w-5 text-center group-hover:scale-110 transition-transform"></i>
            <span class="font-semibold text-sm">Logout</span>
        </a>
    </div>
</aside>

<link rel="stylesheet" href="<?php echo $root_path; ?>Css/loader.css">

<!-- Loader Overlay HTML (Navigation/Load) -->
<div id="pageLoader" class="loader-overlay active">
    <div class="crane-container">
        <div class="tower"></div>
        <div class="counterweight"></div>
        <div class="peak"></div>
        <div class="support-cable"></div>
        <div class="jib"></div>
        <div class="cab"></div>
        <div class="trolley-container">
            <div class="trolley"></div>
            <div class="hoist-cable">
                <div class="hook-block">
                    <div class="hook"></div>
                </div>
            </div>
        </div>
    </div>
    <div class="loading-text">
        <h1 class="loading-title">Loading...</h1>
        <div class="loading-sub">
            Please wait
            <div class="dots inline-block ml-2">
                <span></span><span></span><span></span>
            </div>
        </div>
    </div>
</div>

<!-- Idle Screensaver (Black Screen) -->
<div id="idleScreensaver" class="idle-screensaver"></div>

<style>
    .custom-scrollbar::-webkit-scrollbar {
        width: 4px;
    }

    .custom-scrollbar::-webkit-scrollbar-track {
        background: transparent;
    }

    .custom-scrollbar::-webkit-scrollbar-thumb {
        background: rgba(255, 255, 255, 0.2);
        border-radius: 10px;
    }

    .custom-scrollbar:hover::-webkit-scrollbar-thumb {
        background: rgba(255, 255, 255, 0.3);
    }

    /* New style for idle screensaver */
    .idle-screensaver {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.95);
        /* Darker background */
        z-index: 9998;
        /* Below loader, above content */
        opacity: 0;
        visibility: hidden;
        transition: opacity 0.5s ease, visibility 0.5s ease;
    }

    .idle-screensaver.active {
        opacity: 1;
        visibility: visible;
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const loader = document.getElementById('pageLoader');
        const screensaver = document.getElementById('idleScreensaver');
        let idleTimer;

        // Configuration
        const IDLE_TIMEOUT = 10000; // 10 seconds

        // --------------------------------------------------------
        // IDLE SCREENSAVER LOGIC
        // --------------------------------------------------------
        function showScreensaver() {
            // Only show if not navigating (loader active)
            if (!loader.classList.contains('active')) {
                screensaver.classList.add('active');
            }
        }

        function resetIdleTimer() {
            // Hide screensaver if active
            if (screensaver.classList.contains('active')) {
                screensaver.classList.remove('active');
            }
            clearTimeout(idleTimer);
            idleTimer = setTimeout(showScreensaver, IDLE_TIMEOUT);
        }

        // Initialize and listen
        resetIdleTimer();
        ['mousemove', 'mousedown', 'keypress', 'scroll', 'touchstart'].forEach(evt => {
            document.addEventListener(evt, resetIdleTimer, { passive: true });
        });

        // --------------------------------------------------------
        // 1. ON PAGE LOAD
        // --------------------------------------------------------
        setTimeout(() => {
            loader.classList.remove('active');
            resetIdleTimer();
        }, 1500);

        // --------------------------------------------------------
        // 2. ON NAVIGATION
        // --------------------------------------------------------
        const sidebarLinks = document.querySelectorAll('#sidebar nav a, #sidebar .border-t a');

        sidebarLinks.forEach(link => {
            link.addEventListener('click', (e) => {
                const href = link.getAttribute('href');
                if (!href || href === '#' || href.startsWith('javascript:')) return;

                e.preventDefault();

                // Stop screensaver logic
                clearTimeout(idleTimer);
                ['mousemove', 'mousedown', 'keypress', 'scroll', 'touchstart'].forEach(evt => {
                    document.removeEventListener(evt, resetIdleTimer);
                });

                // Show loader (Crane)
                loader.classList.add('active');

                setTimeout(() => {
                    window.location.href = link.href;
                }, 3000);
            });
        });
    });
</script>
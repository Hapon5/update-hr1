<?php
// Ensure root_path is defined
$root_path = $root_path ?? '../';
$current_page = basename($_SERVER['PHP_SELF']);

// Ensure database connection is available
if (!isset($conn)) {
    $conn_path = ($root_path === './' || $root_path === '') ? "Database/Connections.php" : $root_path . "Database/Connections.php";
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
    return $pageName === $current_page ? 'bg-indigo-600/30 text-indigo-400 border-l-4 border-indigo-500 shadow-lg shadow-indigo-500/10' : 'text-gray-400 hover:bg-white/5 hover:text-white transition-all border-l-4 border-transparent';
}

function getIconColor($pageName, $current_page)
{
    return $pageName === $current_page ? 'text-indigo-400' : 'text-gray-500 group-hover:text-white';
}
?>

<!-- Admin Sidebar -->
<aside
    class="fixed top-0 left-0 h-screen w-64 bg-gray-950 border-r border-gray-800 transition-transform duration-300 z-50 overflow-y-auto custom-scrollbar flex flex-col shadow-2xl"
    id="sidebar">
    <!-- Brand / User Profile -->
    <div class="flex items-center gap-3 px-6 h-20 border-b border-gray-800 mb-2 flex-shrink-0">
        <div class="relative group">
            <div class="w-10 h-10 rounded-xl bg-gray-900 flex items-center justify-center shadow-lg overflow-hidden border border-gray-800 group-hover:border-indigo-500/50 transition-all">
                <?php 
                // Check if photo is base64 or file path
                if (strpos($sidebar_user['photo'], 'data:image') === 0) {
                    // It's a base64 image
                    echo '<img src="' . $sidebar_user['photo'] . '" alt="Profile" class="w-full h-full object-cover" onerror="this.src=\'' . $root_path . 'Image/logo.png\'">';
                } else {
                    // It's a file path
                    echo '<img src="' . htmlspecialchars($sidebar_user['photo']) . '" alt="Profile" class="w-full h-full object-cover" onerror="this.src=\'' . $root_path . 'Image/logo.png\'">';
                }
                ?>
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
        <p class="px-4 text-[10px] font-bold text-gray-500 uppercase tracking-widest mb-2 mt-2">Core</p>

        <a href="<?php echo $root_path; ?>Main/Dashboard.php"
            class="flex items-center gap-3 px-4 py-2.5 group <?php echo isActive('Dashboard.php', $current_page); ?>">
            <i
                class="fas fa-tachometer-alt w-5 text-center <?php echo getIconColor('Dashboard.php', $current_page); ?>"></i>
            <span class="font-medium text-sm">Dashboard</span>
        </a>

        <p class="px-4 text-[10px] font-bold text-gray-500 uppercase tracking-widest mb-2 mt-6">Recruitment</p>

        <a href="<?php echo $root_path; ?>Modules/job_posting.php"
            class="flex items-center gap-3 px-4 py-2.5 group <?php echo isActive('job_posting.php', $current_page); ?>">
            <i
                class="fas fa-bullhorn w-5 text-center <?php echo getIconColor('job_posting.php', $current_page); ?>"></i>
            <span class="font-medium text-sm">Job Posting</span>
        </a>

        <a href="<?php echo $root_path; ?>Main/candidate_sourcing_&_tracking.php"
            class="flex items-center gap-3 px-4 py-2.5 group <?php echo isActive('candidate_sourcing_&_tracking.php', $current_page); ?>">
            <i
                class="fas fa-users w-5 text-center <?php echo getIconColor('candidate_sourcing_&_tracking.php', $current_page); ?>"></i>
            <span class="font-medium text-sm">Candidates</span>
        </a>

        <a href="<?php echo $root_path; ?>Main/Interviewschedule.php"
            class="flex items-center gap-3 px-4 py-2.5 group <?php echo isActive('Interviewschedule.php', $current_page); ?>">
            <i
                class="fas fa-calendar-alt w-5 text-center <?php echo getIconColor('Interviewschedule.php', $current_page); ?>"></i>
            <span class="font-medium text-sm">Interviews</span>
        </a>

        <a href="<?php echo $root_path; ?>Modules/recruitment_process.php"
            class="flex items-center gap-3 px-4 py-2.5 group <?php echo isActive('recruitment_process.php', $current_page); ?>">
            <i
                class="fas fa-clipboard-check w-5 text-center <?php echo getIconColor('recruitment_process.php', $current_page); ?>"></i>
            <span class="font-medium text-sm">Recruitment Process</span>
        </a>

        <p class="px-4 text-[10px] font-bold text-gray-500 uppercase tracking-widest mb-2 mt-6">Operations</p>

        <a href="<?php echo $root_path; ?>Modules/performance_and_appraisals.php"
            class="flex items-center gap-3 px-4 py-2.5 group <?php echo isActive('performance_and_appraisals.php', $current_page); ?>">
            <i
                class="fas fa-user-check w-5 text-center <?php echo getIconColor('performance_and_appraisals.php', $current_page); ?>"></i>
            <span class="font-medium text-sm">Performance</span>
        </a>

        <a href="<?php echo $root_path; ?>Modules/recognition.php"
            class="flex items-center gap-3 px-4 py-2.5 group <?php echo isActive('recognition.php', $current_page); ?>">
            <i class="fas fa-star w-5 text-center <?php echo getIconColor('recognition.php', $current_page); ?>"></i>
            <span class="font-medium text-sm">Recognition</span>
        </a>

        <a href="<?php echo $root_path; ?>Modules/fleet_management.php"
            class="flex items-center gap-3 px-4 py-2.5 group <?php echo isActive('fleet_management.php', $current_page); ?>">
            <i class="fas fa-truck w-5 text-center <?php echo getIconColor('fleet_management.php', $current_page); ?>"></i>
            <span class="font-medium text-sm">Fleet Management</span>
        </a>

        <a href="<?php echo $root_path; ?>Super-admin/Modules/safety_management.php"
            class="flex items-center gap-3 px-4 py-2.5 group <?php echo isActive('safety_management.php', $current_page); ?>">
            <i class="fas fa-hard-hat w-5 text-center <?php echo getIconColor('safety_management.php', $current_page); ?>"></i>
            <span class="font-medium text-sm">Safety Module</span>
        </a>

        <p class="px-4 text-[10px] font-bold text-gray-500 uppercase tracking-widest mb-2 mt-6">Data & Reports</p>

        <a href="<?php echo $root_path; ?>Modules/data_analytics.php"
            class="flex items-center gap-3 px-4 py-2.5 group <?php echo isActive('data_analytics.php', $current_page); ?>">
            <i class="fas fa-chart-pie w-5 text-center <?php echo getIconColor('data_analytics.php', $current_page); ?>"></i>
            <span class="font-medium text-sm">Data Analytics</span>
        </a>

        <a href="<?php echo $root_path; ?>Modules/applicant_consent.php"
            class="flex items-center gap-3 px-4 py-2.5 group <?php echo isActive('applicant_consent.php', $current_page); ?>">
            <i class="fas fa-file-contract w-5 text-center <?php echo getIconColor('applicant_consent.php', $current_page); ?>"></i>
            <span class="font-medium text-sm">Applicant Records</span>
        </a>

        <a href="<?php echo $root_path; ?>Modules/applicant_progress.php"
            class="flex items-center gap-3 px-4 py-2.5 group <?php echo isActive('applicant_progress.php', $current_page); ?>">
            <i class="fas fa-tasks w-5 text-center <?php echo getIconColor('applicant_progress.php', $current_page); ?>"></i>
            <span class="font-medium text-sm">Applicant Progress</span>
        </a>

        <p class="px-4 text-[10px] font-bold text-gray-500 uppercase tracking-widest mb-2 mt-6">System</p>

        <a href="<?php echo $root_path; ?>Super-admin/Modules/account_management.php"
            class="flex items-center gap-3 px-4 py-2.5 group <?php echo isActive('account_management.php', $current_page); ?>">
            <i class="fas fa-users-cog w-5 text-center <?php echo getIconColor('account_management.php', $current_page); ?>"></i>
            <span class="font-medium text-sm">Account List</span>
        </a>



        <a href="<?php echo $root_path; ?>Super-admin/Modules/recycle_bin.php"
            class="flex items-center gap-3 px-4 py-2.5 group <?php echo isActive('recycle_bin.php', $current_page); ?>">
            <i class="fas fa-trash-restore w-5 text-center <?php echo getIconColor('recycle_bin.php', $current_page); ?>"></i>
            <span class="font-medium text-sm">Recycle Bin</span>
        </a>

        <a href="<?php echo $root_path; ?>Main/about_us.php"
            class="flex items-center gap-3 px-4 py-2.5 group <?php echo isActive('about_us.php', $current_page); ?>">
            <i
                class="fas fa-info-circle w-5 text-center <?php echo getIconColor('about_us.php', $current_page); ?>"></i>
            <span class="font-medium text-sm">About Us</span>
        </a>
    </nav>

    <!-- Footer / Logout -->
    <div class="px-4 py-6 border-t border-white/5 flex-shrink-0">
        <button onclick="confirmLogout()"
            class="flex items-center gap-3 px-4 py-3 rounded-lg text-red-500 hover:bg-red-500/10 transition-all duration-200 group w-full text-left">
            <i class="fas fa-sign-out-alt w-5 text-center group-hover:scale-110 transition-transform"></i>
            <span class="font-semibold text-sm">Logout</span>
        </button>
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
<!-- Idle Screensaver (Black Screen) -->
<div id="idleScreensaver" class="idle-screensaver flex items-center justify-center backdrop-blur-sm">
    <div class="screensaver-modal bg-gray-900/90 border border-gray-700/50 p-8 rounded-2xl shadow-2xl text-center max-w-sm w-full mx-4 transform scale-90 opacity-0 transition-all duration-300 delay-100">
        <div class="w-16 h-16 bg-indigo-500/10 rounded-full flex items-center justify-center mx-auto mb-4 ring-1 ring-indigo-500/20">
            <i class="fas fa-moon text-2xl text-indigo-400"></i>
        </div>
        <h3 class="text-xl font-bold text-white mb-2 tracking-tight">System Halted</h3>
        <p class="text-gray-400 mb-6 text-sm leading-relaxed">System is in idle mode to save resources.</p>
        <button id="exitScreensaverBtn" class="w-full px-6 py-3 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl font-semibold transition-all hover:scale-[1.02] active:scale-[0.98] shadow-lg shadow-indigo-600/20">
            Exit
        </button>
    </div>
</div>

<style>
    .custom-scrollbar::-webkit-scrollbar {
        width: 4px;
    }

    .custom-scrollbar::-webkit-scrollbar-track {
        background: transparent;
    }

    .custom-scrollbar::-webkit-scrollbar-thumb {
        background: rgba(255, 255, 255, 0.1);
        border-radius: 10px;
    }

    .custom-scrollbar:hover::-webkit-scrollbar-thumb {
        background: rgba(255, 255, 255, 0.2);
    }

    .idle-screensaver.active {
        opacity: 1;
        visibility: visible;
    }

    .idle-screensaver.active .screensaver-modal {
        transform: scale(1);
        opacity: 1;
    }

    /* Sidebar Toggle Support */
    #sidebar.close {
        width: 80px;
    }

    #sidebar.close .sidebar-label,
    #sidebar.close span,
    #sidebar.close p,
    #sidebar.close h2,
    #sidebar.close div div:last-child {
        display: none;
    }

    #sidebar.close .px-6 {
        padding-left: 0;
        padding-right: 0;
        justify-content: center;
    }

    #sidebar.close .px-3 {
        padding-left: 0.5rem;
        padding-right: 0.5rem;
    }

    #sidebar.close a {
        justify-content: center;
        padding-left: 0;
        padding-right: 0;
    }

    #sidebar.close i {
        margin: 0;
    }
</style>


<script>
    document.addEventListener('DOMContentLoaded', () => {
        const loader = document.getElementById('pageLoader');
        const screensaver = document.getElementById('idleScreensaver');
        let idleTimer;

        // Configuration
        const IDLE_TIMEOUT = 120000; // 2 minutes

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
            // Only reset timer if screensaver is NOT active
            if (!screensaver.classList.contains('active')) {
                clearTimeout(idleTimer);
                idleTimer = setTimeout(showScreensaver, IDLE_TIMEOUT);
            }
        }

        // Exit Screensaver Logic
        const exitBtn = document.getElementById('exitScreensaverBtn');
        if (exitBtn) {
            exitBtn.addEventListener('click', () => {
                screensaver.classList.remove('active');
                resetIdleTimer();
            });
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

    function confirmLogout() {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'Logout?',
                text: "Are you sure you want to end your session?",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#f3f4f6',
                confirmButtonText: 'Yes, Logout',
                cancelButtonText: 'Cancel',
                customClass: {
                    cancelButton: 'text-gray-800'
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = '<?php echo $root_path; ?>logout.php';
                }
            });
        } else {
            if (confirm('Are you sure you want to logout?')) {
                window.location.href = '<?php echo $root_path; ?>logout.php';
            }
        }
    }
</script>
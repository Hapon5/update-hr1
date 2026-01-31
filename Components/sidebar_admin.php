<?php
// Ensure root_path is defined
$root_path = $root_path ?? '../';
$current_page = basename($_SERVER['PHP_SELF']);

// Helper function to check if a link is active
function isActive($pageName, $current_page)
{
    return $pageName === $current_page ? 'bg-white/10 text-white border-l-4 border-white' : 'text-gray-400 hover:bg-white/5 hover:text-white transition-all border-l-4 border-transparent';
}

function getIconColor($pageName, $current_page)
{
    return $pageName === $current_page ? 'text-white' : 'text-gray-500 group-hover:text-white';
}
?>

<!-- Admin Sidebar -->
<aside
    class="fixed top-0 left-0 h-screen w-64 bg-black border-r border-white/10 transition-transform duration-300 z-50 overflow-y-auto custom-scrollbar flex flex-col shadow-2xl"
    id="sidebar">
    <!-- Brand -->
    <div class="flex items-center gap-3 px-6 h-20 border-b border-white/5 mb-2 flex-shrink-0">
        <div class="w-10 h-10 rounded-full bg-white flex items-center justify-center p-0.5 overflow-hidden">
            <img src="<?php echo $root_path; ?>Image/logo.png" alt="Logo" class="w-full h-full object-cover">
        </div>
        <div>
            <h2 class="text-white font-bold tracking-tight text-sm">HR Admin</h2>
            <p class="text-[10px] text-gray-400 font-bold uppercase tracking-widest leading-none mt-1">Management</p>
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

        <a href="<?php echo $root_path; ?>Modules/learning.php"
            class="flex items-center gap-3 px-4 py-2.5 group <?php echo isActive('learning.php', $current_page); ?>">
            <i class="fas fa-shield-alt w-5 text-center <?php echo getIconColor('learning.php', $current_page); ?>"></i>
            <span class="font-medium text-sm">Safety</span>
        </a>

        <p class="px-4 text-[10px] font-bold text-gray-500 uppercase tracking-widest mb-2 mt-6">System</p>

        <a href="<?php echo $root_path; ?>Main/about_us.php"
            class="flex items-center gap-3 px-4 py-2.5 group <?php echo isActive('about_us.php', $current_page); ?>">
            <i
                class="fas fa-info-circle w-5 text-center <?php echo getIconColor('about_us.php', $current_page); ?>"></i>
            <span class="font-medium text-sm">About Us</span>
        </a>
    </nav>

    <!-- Footer / Logout -->
    <div class="px-4 py-6 border-t border-white/5 flex-shrink-0">
        <a href="<?php echo $root_path; ?>logout.php"
            class="flex items-center gap-3 px-4 py-3 rounded-lg text-red-500 hover:bg-red-500/10 transition-all duration-200 group">
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
        background: rgba(255, 255, 255, 0.1);
        border-radius: 10px;
    }

    .custom-scrollbar:hover::-webkit-scrollbar-thumb {
        background: rgba(255, 255, 255, 0.2);
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
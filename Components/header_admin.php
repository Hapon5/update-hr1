<?php
$root_path = $root_path ?? '../';
?>
<link rel="stylesheet" href="<?php echo $root_path; ?>Css/Components/header_admin.css">

<style>
    /* Inline fix for Header Positioning */
    .admin-header {
        position: fixed;
        top: 0;
        left: 16rem;
        /* Default sidebar width (256px) */
        width: calc(100% - 16rem);
        height: 70px;
        background-color: #ffffff;
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 0 30px;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
        z-index: 99;
        transition: all 0.3s ease;
        margin: 0 !important;
        /* Override any negative margins */
    }

    /* When sidebar is closed (handled via JS toggling 'expand' class on header) */
    .admin-header.expand {
        left: 80px;
        /* Collapsed sidebar width */
        width: calc(100% - 80px);
    }

    /* Responsive */
    @media (max-width: 768px) {
        .admin-header {
            left: 0;
            width: 100%;
        }
    }
</style>

<header class="admin-header">
    <div class="header-left">
        <i class="fas fa-bars cursor-pointer text-gray-500 hover:text-indigo-600 transition-colors" id="sidebar-toggle"
            style="font-size: 1.5rem;"></i>
    </div>

    <div class="header-center">
        <h2 class="text-xl font-bold text-gray-800 tracking-tight">Welcome To HR1 Admin</h2>
    </div>

    <div class="header-right flex items-center gap-6">
        <!-- Clock -->
        <div
            class="hidden md:flex items-center gap-2 text-sm text-gray-500 bg-gray-50 px-4 py-2 rounded-full border border-gray-100 shadow-sm">
            <i class="far fa-clock text-indigo-500"></i>
            <span id="liveTime" class="font-medium tracking-wide">Loading...</span>
        </div>

        <!-- Profile Dropdown -->
        <div class="relative cursor-pointer" id="profileDropdownBtn">
            <div class="flex items-center gap-3 p-1 rounded-full hover:bg-gray-50 transition-colors">
                <div
                    class="w-10 h-10 rounded-full bg-indigo-50 text-indigo-600 flex items-center justify-center text-lg border border-indigo-100 shadow-sm">
                    <i class="far fa-user"></i>
                </div>
                <div class="hidden sm:flex items-center gap-2">
                    <span class="text-sm font-semibold text-gray-700">
                        <?php
                        // Fallback mechanism for name display
                        $displayName = $_SESSION['GlobalName'] ?? 'Admin User';
                        if ($displayName === 'System User')
                            $displayName = 'Admin';
                        echo htmlspecialchars($displayName);
                        ?>
                    </span>
                    <i id="dropdownArrow"
                        class="fas fa-chevron-down text-gray-400 text-xs transition-transform duration-200"></i>
                </div>
            </div>

            <!-- Dropdown Menu -->
            <div id="profileDropdownMenu"
                class="absolute top-full right-0 mt-2 w-56 bg-white rounded-xl shadow-lg border border-gray-100 py-2 hidden transform transition-all duration-200 origin-top-right z-50">
                <div class="px-4 py-3 border-b border-gray-50">
                    <p class="text-sm font-semibold text-gray-800">
                        <?php echo htmlspecialchars($displayName); ?>
                    </p>
                    <p class="text-xs text-gray-500">Administrator</p>
                </div>
                <div class="py-1">
                    <a href="<?php echo $root_path; ?>Myprofile.php"
                        class="block px-4 py-2 text-sm text-gray-600 hover:bg-indigo-50 hover:text-indigo-600 transition-colors">
                        <i class="far fa-user-circle mr-2"></i>My Profile
                    </a>
                    <a href="<?php echo $root_path; ?>Setting.php"
                        class="block px-4 py-2 text-sm text-gray-600 hover:bg-indigo-50 hover:text-indigo-600 transition-colors">
                        <i class="fas fa-cog mr-2"></i>Settings
                    </a>
                </div>
                <div class="border-t border-gray-50 mt-1 py-1">
                    <a href="<?php echo $root_path; ?>logout.php"
                        class="block px-4 py-2 text-sm text-red-600 hover:bg-red-50 transition-colors">
                        <i class="fas fa-sign-out-alt mr-2"></i>Logout
                    </a>
                </div>
            </div>
        </div>
    </div>
</header>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        // Sidebar Toggle Logic
        const sidebar = document.querySelector(".sidebar");
        const header = document.querySelector(".admin-header");
        const toggleBtn = document.getElementById("sidebar-toggle");

        if (toggleBtn && sidebar) {
            toggleBtn.addEventListener("click", () => {
                sidebar.classList.toggle("close");
                if (header) {
                    header.classList.toggle("expand");
                }

                // Also adjust main content if it exists
                const mainContent = document.querySelector(".main-content");
                if (mainContent) {
                    if (sidebar.classList.contains("close")) {
                        mainContent.style.marginLeft = "80px";
                    } else {
                        mainContent.style.marginLeft = "16rem";
                    }
                }
            });
        }

        // Live Date Time Logic (Updated Format)
        function updateDateTime() {
            const now = new Date();
            const options = { weekday: 'short', month: 'short', day: 'numeric', hour: 'numeric', minute: '2-digit', hour12: true };
            const formattedDate = now.toLocaleString('en-US', options);
            const dateEl = document.getElementById("liveTime");
            if (dateEl) {
                dateEl.textContent = formattedDate;
            }
        }

        // Run immediately and then every second
        updateDateTime();
        setInterval(updateDateTime, 1000);

        // Profile Dropdown Logic
        const profileBtn = document.getElementById('profileDropdownBtn');
        const profileMenu = document.getElementById('profileDropdownMenu');
        const dropdownArrow = document.getElementById('dropdownArrow');

        if (profileBtn && profileMenu) {
            profileBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                const isHidden = profileMenu.classList.contains('hidden');
                if (isHidden) {
                    profileMenu.classList.remove('hidden');
                    if (dropdownArrow) dropdownArrow.classList.add('rotate-180');
                } else {
                    profileMenu.classList.add('hidden');
                    if (dropdownArrow) dropdownArrow.classList.remove('rotate-180');
                }
            });

            // Close when clicking outside
            document.addEventListener('click', (e) => {
                if (!profileBtn.contains(e.target)) {
                    profileMenu.classList.add('hidden');
                    if (dropdownArrow) dropdownArrow.classList.remove('rotate-180');
                }
            });
        }
    });
</script>
<header
    class="fixed top-0 left-0 right-0 h-20 bg-black border-b border-gray-800 flex items-center justify-between px-8 z-40 transition-all duration-300 ml-64"
    id="header">
    <div class="flex items-center gap-4">
        <button id="sidebarToggle"
            class="text-gray-400 hover:text-white focus:outline-none transform transition-transform active:scale-95">
            <i class="fas fa-bars text-2xl"></i>
        </button>
        <h2 class="text-2xl font-bold text-white tracking-tight">HR1 Super Admin</h2>
    </div>

    <div class="flex items-center gap-6">
        <!-- Clock -->
        <div
            class="flex items-center gap-2 text-sm text-gray-400 bg-gray-900 px-4 py-2 rounded-full border border-gray-800 shadow-sm">
            <i class="far fa-clock text-indigo-400"></i>
            <span id="liveTime" class="font-medium tracking-wide">Loading...</span>
        </div>

        <!-- Profile Dropdown -->
        <div class="relative cursor-pointer" id="profileDropdownBtn">
            <div class="flex items-center gap-3 p-1 rounded-full hover:bg-gray-900 transition-colors">
                <div
                    class="w-10 h-10 rounded-full bg-indigo-500/10 text-indigo-400 flex items-center justify-center text-lg border border-indigo-500/20 shadow-sm">
                    <i class="far fa-user"></i>
                </div>
                <div class="hidden sm:flex items-center gap-2">
                    <span class="text-sm font-semibold text-gray-200"><?php
                    // Using the same sidebar_user logic if available or fetch here
                    $header_user_name = $sidebar_user['name'] ?? ($_SESSION['GlobalName'] ?? 'Super Admin');
                    echo htmlspecialchars($header_user_name);
                    ?></span>
                    <i id="dropdownArrow"
                        class="fas fa-chevron-down text-gray-500 text-xs transition-transform duration-200"></i>
                </div>
            </div>
 
            <!-- Dropdown Menu -->
            <div id="profileDropdownMenu"
                class="absolute top-full right-0 mt-2 w-56 bg-gray-900 rounded-xl shadow-2xl border border-gray-800 py-2 hidden transform transition-all duration-200 origin-top-right z-50">
                <div class="px-4 py-3 border-b border-gray-800">
                    <p class="text-sm font-semibold text-white">
                        <?php echo htmlspecialchars($header_user_name); ?>
                    </p>
                    <p class="text-xs text-gray-500"><?php echo htmlspecialchars($sidebar_user['role'] ?? 'Management'); ?></p>
                </div>
                <div class="py-1">
                    <a href="<?php echo $root_path; ?>Super-admin/Myprofile.php"
                        class="block px-4 py-2 text-sm text-gray-400 hover:bg-white/5 hover:text-white transition-colors">
                        <i class="far fa-user-circle mr-2"></i>My Profile
                    </a>
                    <a href="#"
                        class="block px-4 py-2 text-sm text-gray-400 hover:bg-white/5 hover:text-white transition-colors">
                        <i class="fas fa-cog mr-2"></i>Settings
                    </a>
                </div>
                <div class="border-t border-gray-800 mt-1 py-1">
                    <a href="../logout.php"
                        class="block px-4 py-2 text-sm text-red-500 hover:bg-red-500/10 transition-colors">
                        <i class="fas fa-sign-out-alt mr-2"></i>Logout
                    </a>
                </div>
            </div>
        </div>
    </div>
</header>

<script>
    function updateHeaderTime() {
        const now = new Date();
        const options = { weekday: 'short', month: 'short', day: 'numeric', hour: 'numeric', minute: '2-digit', hour12: true };
        const timeString = now.toLocaleString('en-US', options);
        document.getElementById('liveTime').textContent = timeString;
    }
    updateHeaderTime();
    setInterval(updateHeaderTime, 1000);

    // Sidebar Toggle Logic
    document.getElementById('sidebarToggle').addEventListener('click', function () {
        const sidebar = document.getElementById('sidebar');
        const header = document.getElementById('header');
        const mainContent = document.getElementById('mainContent');

        if (sidebar && mainContent && header) {
            sidebar.classList.toggle('-translate-x-full');

            if (sidebar.classList.contains('-translate-x-full')) {
                mainContent.classList.remove('ml-64');
                header.classList.remove('ml-64');
            } else {
                mainContent.classList.add('ml-64');
                header.classList.add('ml-64');
            }
        }
    });

    // Profile Dropdown Toggle Logic
    const profileBtn = document.getElementById('profileDropdownBtn');
    const profileMenu = document.getElementById('profileDropdownMenu');
    const dropdownArrow = document.getElementById('dropdownArrow');

    // Toggle menu
    profileBtn.addEventListener('click', function (e) {
        e.stopPropagation();
        const isHidden = profileMenu.classList.contains('hidden');
        if (isHidden) {
            profileMenu.classList.remove('hidden');
            dropdownArrow.classList.add('rotate-180');
        } else {
            profileMenu.classList.add('hidden');
            dropdownArrow.classList.remove('rotate-180');
        }
    });

    // Close when clicking outside
    document.addEventListener('click', function (e) {
        if (!profileBtn.contains(e.target)) {
            profileMenu.classList.add('hidden');
            dropdownArrow.classList.remove('rotate-180');
        }
    });
</script>

<!-- Security Blackout & Auto-Logout -->
<script src="../Js/security.js"></script>
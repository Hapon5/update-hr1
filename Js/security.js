/**
 * Security Inactivity Monitor
 * Black screen after 2–3 mins inactivity, then auto logout.
 */

(function () {
    let blackoutTimeout;
    let logoutTimeout;
    const blackoutTime = 2.5 * 60 * 1000; // 2.5 minutes idle → show black screen
    const logoutTime = 5 * 1000;          // 5 seconds after black → auto logout

    // Create Blackout Overlay
    const overlay = document.createElement('div');
    overlay.id = 'security-blackout-overlay';
    overlay.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: black;
        z-index: 999999;
        display: none;
        cursor: none;
        transition: opacity 0.5s ease;
        opacity: 0;
    `;
    document.body.appendChild(overlay);

    function showBlackout() {
        overlay.style.display = 'block';
        setTimeout(() => overlay.style.opacity = '1', 10);

        // Start Logout Timer
        clearTimeout(logoutTimeout);
        logoutTimeout = setTimeout(doLogout, logoutTime);
    }

    function hideBlackout() {
        if (overlay.style.display === 'block') {
            overlay.style.opacity = '0';
            setTimeout(() => {
                overlay.style.display = 'none';
            }, 500);
            resetTimers();
        }
    }

    function doLogout() {
        // Redirect to logout script
        // We need to know where logout.php is. Typically at root.
        // Assuming logout.php is in the root directory
        const currentPath = window.location.pathname;
        let logoutUrl = 'logout.php';

        if (currentPath.includes('/Main/') || currentPath.includes('/Super-admin/') || currentPath.includes('/Staff/') || currentPath.includes('/Employee/')) {
            logoutUrl = '../logout.php';
        } else if (currentPath.includes('/Modules/')) {
            logoutUrl = '../logout.php';
        }

        window.location.href = logoutUrl;
    }

    function resetTimers() {
        clearTimeout(blackoutTimeout);
        clearTimeout(logoutTimeout);

        if (overlay.style.display === 'none') {
            blackoutTimeout = setTimeout(showBlackout, blackoutTime);
        }
    }

    // Activity Listeners
    const events = ['mousedown', 'mousemove', 'keypress', 'scroll', 'touchstart'];
    events.forEach(name => {
        document.addEventListener(name, () => {
            if (overlay.style.display === 'block') {
                hideBlackout();
            } else {
                resetTimers();
            }
        }, true);
    });

    // Initialize
    resetTimers();
})();

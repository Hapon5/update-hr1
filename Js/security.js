/**
 * Security Inactivity Monitor
 * Black screen after 2–3 mins inactivity, then modal "Do you want to logout?" then auto logout.
 */

(function () {
    let blackoutTimeout;
    let logoutTimeout;
    const blackoutTime = 2.5 * 60 * 1000; // 2.5 minutes idle → show black + modal
    const logoutTime = 5 * 1000;          // 5 seconds after modal → auto logout if no choice

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
        transition: opacity 0.5s ease;
        opacity: 0;
    `;
    document.body.appendChild(overlay);

    // Create Logout Modal (on top of black overlay)
    const modal = document.createElement('div');
    modal.id = 'security-logout-modal';
    modal.innerHTML = `
        <div style="
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: white;
            padding: 1.5rem 2rem;
            border-radius: 1rem;
            box-shadow: 0 25px 50px -12px rgba(0,0,0,0.5);
            z-index: 1000000;
            min-width: 320px;
            text-align: center;
            font-family: system-ui, -apple-system, sans-serif;
        ">
            <p style="font-size: 1.125rem; font-weight: 600; color: #1f2937; margin-bottom: 0.5rem;">You have been inactive.</p>
            <p style="font-size: 0.9375rem; color: #6b7280; margin-bottom: 1.5rem;">Do you want to logout?</p>
            <div style="display: flex; gap: 0.75rem; justify-content: center; flex-wrap: wrap;">
                <button type="button" id="security-stay-btn" style="
                    padding: 0.5rem 1.25rem;
                    background: #e5e7eb;
                    color: #374151;
                    border: none;
                    border-radius: 0.5rem;
                    font-weight: 600;
                    cursor: pointer;
                    font-size: 0.875rem;
                ">Stay logged in</button>
                <button type="button" id="security-logout-btn" style="
                    padding: 0.5rem 1.25rem;
                    background: #1f2937;
                    color: white;
                    border: none;
                    border-radius: 0.5rem;
                    font-weight: 600;
                    cursor: pointer;
                    font-size: 0.875rem;
                ">Logout</button>
            </div>
        </div>
    `;
    modal.style.cssText = 'display: none; position: fixed; inset: 0; z-index: 1000000;';
    document.body.appendChild(modal);

    const stayBtn = document.getElementById('security-stay-btn');
    const logoutBtn = document.getElementById('security-logout-btn');

    function showBlackout() {
        overlay.style.display = 'block';
        modal.style.display = 'block';
        setTimeout(() => overlay.style.opacity = '1', 10);

        clearTimeout(logoutTimeout);
        logoutTimeout = setTimeout(doLogout, logoutTime);
    }

    function hideBlackout() {
        if (overlay.style.display === 'block') {
            overlay.style.opacity = '0';
            modal.style.display = 'none';
            clearTimeout(blackoutTimeout);
            clearTimeout(logoutTimeout);
            setTimeout(() => {
                overlay.style.display = 'none';
                blackoutTimeout = setTimeout(showBlackout, blackoutTime);
            }, 500);
        }
    }

    function doLogout() {
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
        if (overlay.style.display === 'none' || overlay.style.display === '') {
            blackoutTimeout = setTimeout(showBlackout, blackoutTime);
        }
    }

    if (stayBtn) {
        stayBtn.addEventListener('click', function () {
            hideBlackout();
        });
    }
    if (logoutBtn) {
        logoutBtn.addEventListener('click', function () {
            doLogout();
        });
    }

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

    resetTimers();
})();

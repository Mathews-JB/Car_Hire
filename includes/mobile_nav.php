<?php
// mobile_nav.php - Extreme Reliability Redesign
$current_page = basename($_SERVER['PHP_SELF']);
$is_in_portal = strpos($_SERVER['PHP_SELF'], 'portal-') !== false;
$portal_prefix = $is_in_portal ? '' : 'portal-customer/';
$logout_path = $is_in_portal ? '../logout.php' : 'logout.php';
$user_role = isset($_SESSION['user_role']) ? $_SESSION['user_role'] : 'guest';
$is_logged_in = isset($_SESSION['user_id']);

if ($is_logged_in) {
    // 1. Fetch User Unread Notifications
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
    $stmt->execute([$_SESSION['user_id']]);
    $unread_notifications_count = $stmt->fetchColumn();

    if ($user_role === 'admin') {
        $stmt = $pdo->query("SELECT COUNT(*) FROM support_messages WHERE status = 'new'");
        $unread_support = $stmt->fetchColumn();
        $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE verification_status = 'pending'");
        $pending_reviews = $stmt->fetchColumn();
        $more_unread = ($unread_support > 0 || $unread_notifications_count > 0);
    } else {
        $more_unread = ($unread_notifications_count > 0);
    }
}
?>

<!-- RELIABILITY STYLES - Ensures drawer is hidden even if external CSS fails -->
<style>
    #v3MoreDrawer {
        display: none;
        position: fixed;
        inset: 0;
        z-index: 200000;
    background: rgba(8, 12, 23, 0.6) !important;
    backdrop-filter: blur(40px) saturate(180%);
    -webkit-backdrop-filter: blur(40px) saturate(180%);
    }
    #v3MoreDrawer.active {
        display: block !important;
    }
    .v3-drawer-panel {
        position: absolute;
        bottom: 0;
        left: 0;
        right: 0;
        background: #1a1a1f;
        border-radius: 30px 30px 0 0;
        padding: 30px 20px 40px;
    transform: translateY(100%) scale(1.05); /* Added scale shift */
    transition: transform 0.5s cubic-bezier(0.33, 1, 0.68, 1);
    border-top: 1px solid rgba(255,255,255,0.1);
    max-height: 85vh;
    overflow-y: auto;
}
#v3MoreDrawer.active .v3-drawer-panel {
    transform: translateY(0) scale(1);
}
    .v3-menu-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 12px;
        margin-bottom: 30px;
    }
    .v3-menu-item {
        background: rgba(255,255,255,0.03);
        border: 1px solid rgba(255,255,255,0.06);
        border-radius: 16px;
        padding: 15px 10px;
        text-align: center;
        text-decoration: none;
        color: white;
        transition: 0.3s;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        position: relative;
    }
    .v3-menu-item i {
        font-size: 1.4rem;
        margin-bottom: 8px;
        display: block;
        background: linear-gradient(135deg, #3b82f6, #60a5fa);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
    }
    .v3-menu-item span {
        font-size: 0.75rem;
        font-weight: 600;
        display: block;
        opacity: 0.9;
    }
    .v3-menu-section-title {
        color: rgba(255,255,255,0.4);
        font-size: 0.7rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 2px;
        margin: 20px 0 12px 5px;
        grid-column: span 2;
    }
    .v3-close-btn {
        position: sticky;
        top: 0;
        float: right;
        width: 35px;
        height: 35px;
        background: rgba(255,255,255,0.1);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 1rem;
        z-index: 10;
        margin-top: -10px;
    }
    .v3-badge {
        position: absolute;
        top: 8px;
        right: 12px;
        background: #ef4444;
        color: white;
        font-size: 0.6rem;
        padding: 2px 6px;
        border-radius: 4px; /* Changed from potentially rounder if it was 6px/50% */
        font-weight: 800;
        box-shadow: 0 2px 5px rgba(0,0,0,0.3);
    }
</style>

<!-- More Menu Drawer -->
<?php if ($is_logged_in): ?>
<div id="v3MoreDrawer">
    <div class="v3-drawer-panel">
        <div class="v3-close-btn" onclick="v3CloseDrawer()">
            <i class="fas fa-times"></i>
        </div>

        <?php if ($user_role === 'admin'): ?>
            <h3 style="color:white; margin: 0 0 5px 10px; font-size: 1.3rem; font-weight: 900; letter-spacing: -0.5px;">Control Panel</h3>
            <p style="color:rgba(255,255,255,0.4); margin: 0 0 20px 10px; font-size: 0.8rem;">Select a module to manage</p>

            <div class="v3-menu-grid">
                <!-- User Group -->
                <div class="v3-menu-section-title">My Account</div>
                <a href="profile.php" class="v3-menu-item">
                    <i class="fas fa-user-circle"></i>
                    <span>My Profile</span>
                </a>
                <a href="notifications.php" class="v3-menu-item">
                    <i class="fas fa-bell"></i>
                    <span>Alerts</span>
                    <?php if ($unread_notifications_count > 0): ?><span class="v3-badge"><?php echo $unread_notifications_count; ?></span><?php endif; ?>
                </a>

                <!-- Fleet Group -->
                <div class="v3-menu-section-title">Fleet Management</div>
                <a href="tracking.php" class="v3-menu-item">
                    <i class="fas fa-map-marker-alt"></i>
                    <span>Live Tracker</span>
                </a>
                <a href="maintenance.php" class="v3-menu-item">
                    <i class="fas fa-tools"></i>
                    <span>Service Logs</span>
                </a>

                <!-- Ops Group -->
                <div class="v3-menu-section-title">Operations</div>
                <a href="payments.php" class="v3-menu-item">
                    <i class="fas fa-receipt"></i>
                    <span>Payments</span>
                </a>
                <a href="contracts.php" class="v3-menu-item">
                    <i class="fas fa-file-contract"></i>
                    <span>Agreements</span>
                </a>
                <a href="support-inbox.php" class="v3-menu-item" style="border-color: rgba(59, 130, 246, 0.3); background: rgba(59, 130, 246, 0.03);">
                    <i class="fas fa-car-side" style="color: #60a5fa;"></i>
                    <span style="color: #60a5fa;">Event Quotes</span>
                </a>
                <a href="vouchers.php" class="v3-menu-item">
                    <i class="fas fa-ticket-alt"></i>
                    <span>Coupons</span>
                </a>
                <a href="support-inbox.php" class="v3-menu-item">
                    <i class="fas fa-envelope"></i>
                    <span>Messages</span>
                    <?php if ($unread_support > 0): ?><span class="v3-badge"><?php echo $unread_support; ?></span><?php endif; ?>
                </a>

                <!-- Insights Group -->
                <div class="v3-menu-section-title">Business Intelligence</div>
                <a href="analytics.php" class="v3-menu-item">
                    <i class="fas fa-chart-pie"></i>
                    <span>Analytics</span>
                </a>
                <a href="reports-financial.php" class="v3-menu-item">
                    <i class="fas fa-file-invoice-dollar"></i>
                    <span>Financials</span>
                </a>
                <a href="expenses.php" class="v3-menu-item">
                    <i class="fas fa-wallet"></i>
                    <span>Operating Expenses</span>
                </a>

                <!-- System Group -->
                <div class="v3-menu-section-title">System & Security</div>
                <a href="users.php" class="v3-menu-item">
                    <i class="fas fa-users-cog"></i>
                    <span>Users & Roles</span>
                </a>
                <a href="branches.php" class="v3-menu-item">
                    <i class="fas fa-building"></i>
                    <span>Locations</span>
                </a>
                <a href="whatsapp-ocr.php" class="v3-menu-item">
                    <i class="fab fa-whatsapp"></i>
                    <span>WhatsApp / OCR</span>
                </a>
                <a href="settings.php" class="v3-menu-item">
                    <i class="fas fa-sliders-h"></i>
                    <span>Settings</span>
                </a>
                <a href="monitoring.php" class="v3-menu-item">
                    <i class="fas fa-satellite"></i>
                    <span>Business Map</span>
                </a>
                <a href="translations.php" class="v3-menu-item">
                    <i class="fas fa-language"></i>
                    <span>Localize (ZED)</span>
                </a>
                <a href="audit-logs.php" class="v3-menu-item">
                    <i class="fas fa-fingerprint"></i>
                    <span>Audit Logs</span>
                </a>
                <a href="backups.php" class="v3-menu-item">
                    <i class="fas fa-database"></i>
                    <span>Backups</span>
                </a>
                <a href="security.php" class="v3-menu-item">
                    <i class="fas fa-shield-alt"></i>
                    <span>Hardening</span>
                </a>

                <!-- Theme Selection for Admin Mobile Drawer -->
                <div class="v3-menu-section-title">Interface Theme</div>
                <div class="v3-menu-item" style="grid-column: span 2; flex-direction: row; gap: 20px; padding: 20px;">
                     <?php include __DIR__ . '/theme_switcher.php'; ?>
                     <span style="font-size: 0.9rem; font-weight: 800;">Toggle Display Mode</span>
                </div>

                <!-- Logout -->
                <a href="<?php echo $logout_path; ?>" class="v3-menu-item" style="grid-column: span 2; background: rgba(239, 68, 68, 0.08); border-color: rgba(239, 68, 68, 0.15); margin-top: 10px; padding: 18px;">
                    <i class="fas fa-power-off" style="background:none; -webkit-text-fill-color: #ef4444; color:#ef4444; font-size: 1.2rem;"></i>
                    <span style="color: #ef4444; font-weight: 800; font-size: 0.85rem;">SIGN OUT OF PANEL</span>
                </a>
            </div>
        <?php else: ?>
            <h3 style="color:white; margin: 0 0 25px 10px; font-size: 1.2rem; font-weight: 800; letter-spacing: 1px;">MY ACCOUNT</h3>
            <div class="v3-menu-grid">
                <a href="<?php echo $portal_prefix; ?>notifications.php" class="v3-menu-item">
                    <i class="fas fa-bell"></i>
                    <span>Notifications</span>
                    <?php if ($unread_notifications_count > 0): ?><span class="v3-badge"><?php echo $unread_notifications_count; ?></span><?php endif; ?>
                </a>
                <a href="<?php echo $portal_prefix; ?>support.php" class="v3-menu-item">
                    <i class="fas fa-headset"></i>
                    <span>Help Center</span>
                </a>
                <a href="<?php echo $portal_prefix; ?>profile.php" class="v3-menu-item">
                    <i class="fas fa-user-circle"></i>
                    <span>My Profile</span>
                </a>
                
                <!-- Theme Selection in Mobile Drawer -->
                <div class="v3-menu-section-title">Display Theme</div>
                <div class="v3-menu-item" style="grid-column: span 2; flex-direction: row; gap: 20px; padding: 20px;">
                     <?php include __DIR__ . '/theme_switcher.php'; ?>
                     <span style="font-size: 0.9rem; font-weight: 700;">Switch UI Theme</span>
                </div>

                <a href="<?php echo $logout_path; ?>" class="v3-menu-item" style="grid-column: span 2; background: rgba(239, 68, 68, 0.1); border-color: rgba(239, 68, 68, 0.2); margin-top: 10px;">
                    <i class="fas fa-sign-out-alt" style="background:none; -webkit-text-fill-color: #ef4444; color:#ef4444;"></i>
                    <span style="color: #ef4444;">Sign Out</span>
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<div class="mobile-nav">
    <?php if ($is_logged_in): ?>
        <?php if ($user_role === 'admin'): ?>
            <a href="dashboard.php" class="mobile-nav-item <?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>">
                <i class="fas fa-chart-line"></i>
                <span>Dash</span>
            </a>
            <a href="bookings.php" class="mobile-nav-item <?php echo $current_page == 'bookings.php' ? 'active' : ''; ?>">
                <i class="fas fa-calendar-check"></i>
                <span>Bookings</span>
            </a>
            <a href="admin-reviews.php" class="mobile-nav-item <?php echo $current_page == 'admin-reviews.php' ? 'active' : ''; ?>">
                <i class="fas fa-user-check"></i>
                <span>Reviews</span>
                <?php if ($pending_reviews > 0): ?>
                    <span class="nav-badge"><?php echo $pending_reviews; ?></span>
                <?php endif; ?>
            </a>
            <a href="fleet.php" class="mobile-nav-item <?php echo $current_page == 'fleet.php' ? 'active' : ''; ?>">
                <i class="fas fa-car-side"></i>
                <span>Fleet</span>
            </a>
            <div class="mobile-nav-item" onclick="v3OpenDrawer()" style="cursor:pointer;">
                <i class="fas fa-ellipsis-h"></i>
                <span>More</span>
                <?php if ($more_unread): ?>
                    <span class="nav-badge" style="background: #3b82f6;">!</span>
                <?php endif; ?>
            </div>

        <?php elseif ($user_role === 'agent' || $user_role === 'vendor'): ?>
            <a href="dashboard.php" class="mobile-nav-item <?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>">
                <i class="fas fa-home"></i>
                <span>Home</span>
            </a>
            <a href="reservations.php" class="mobile-nav-item <?php echo $current_page == 'reservations.php' ? 'active' : ''; ?>">
                <i class="fas fa-list-ul"></i>
                <span>List</span>
            </a>
            <a href="fleet.php" class="mobile-nav-item <?php echo $current_page == 'fleet.php' ? 'active' : ''; ?>">
                <i class="fas fa-car-side"></i>
                <span>Cars</span>
            </a>
            <div class="mobile-nav-item" onclick="v3OpenDrawer()" style="cursor:pointer;">
                <i class="fas fa-ellipsis-h"></i>
                <span>More</span>
                <?php if ($more_unread): ?>
                    <span class="nav-badge" style="background: #3b82f6;">!</span>
                <?php endif; ?>
            </div>
        <?php else: // Customer ?>
            <a href="<?php echo $portal_prefix; ?>dashboard.php" class="mobile-nav-item <?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>">
                <i class="fas fa-home"></i>
                <span>Home</span>
            </a>
            <a href="<?php echo $portal_prefix; ?>browse-vehicles.php" class="mobile-nav-item <?php echo $current_page == 'browse-vehicles.php' ? 'active' : ''; ?>">
                <i class="fas fa-car-side"></i>
                <span>Fleet</span>
            </a>
            <a href="<?php echo $portal_prefix; ?>my-bookings.php" class="mobile-nav-item <?php echo $current_page == 'my-bookings.php' ? 'active' : ''; ?>">
                <i class="fas fa-calendar-alt"></i>
                <span>Bookings</span>
            </a>
            <a href="<?php echo $portal_prefix; ?>profile.php" class="mobile-nav-item <?php echo $current_page == 'profile.php' ? 'active' : ''; ?>">
                <i class="fas fa-user-circle"></i>
                <span>Profile</span>
            </a>
            <div class="mobile-nav-item" onclick="v3OpenDrawer()" style="cursor:pointer;">
                <i class="fas fa-ellipsis-h"></i>
                <span>More</span>
                <?php if ($more_unread): ?>
                    <span class="nav-badge" style="background: #3b82f6;">!</span>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    <?php else: // Guest ?>
        <a href="index.php" class="mobile-nav-item <?php echo $current_page == 'index.php' ? 'active' : ''; ?>">
            <i class="fas fa-home"></i>
            <span>Home</span>
        </a>
        <a href="our-fleet.php" class="mobile-nav-item <?php echo $current_page == 'our-fleet.php' ? 'active' : ''; ?>">
            <i class="fas fa-car-side"></i>
            <span>Fleet</span>
        </a>
        <a href="login.php" class="mobile-nav-item <?php echo $current_page == 'login.php' ? 'active' : ''; ?>">
            <i class="fas fa-sign-in-alt"></i>
            <span>Login</span>
        </a>
        <a href="register.php" class="mobile-nav-item <?php echo $current_page == 'register.php' ? 'active' : ''; ?>">
            <i class="fas fa-user-plus"></i>
            <span>Join</span>
        </a>
    <?php endif; ?>
</div>

<script>
    function v3OpenDrawer() {
        const d = document.getElementById('v3MoreDrawer');
        if(d) {
            d.style.display = 'block';
            setTimeout(() => {
                d.classList.add('active');
            }, 10);
            document.body.style.overflow = 'hidden';
        }
    }
    function v3CloseDrawer() {
        const d = document.getElementById('v3MoreDrawer');
        if(d) {
            d.classList.remove('active');
            setTimeout(() => {
                d.style.display = 'none';
            }, 400);
            document.body.style.overflow = '';
        }
    }

    // --- UNIVERSAL MOBILE BACK BUTTON HANDLER ---
    // Works for Capacitor APKs, PWAs, and standard mobile browsers
    (function() {
        // 1. Initial history trap to ensure back button has something to hit
        if (window.history.length === 1) {
            window.history.pushState({ entry: true }, "");
        }

        window.addEventListener('popstate', function (event) {
            console.log("Back button detected");
            
            // If more menu drawer is open, close it and stay on page
            const drawer = document.getElementById('v3MoreDrawer');
            if (drawer && drawer.classList.contains('active')) {
                v3CloseDrawer();
                // Push forward again to keep the trap active
                window.history.pushState({ entry: true }, "");
                return;
            }

            // Check if we are on a critical exit page
            const path = window.location.pathname;
            const isHome = path.endsWith('index.php') || path.endsWith('dashboard.php') || path.endsWith('Car_Higher/') || path === '/';

            if (isHome) {
                // If on home, let standard behavior happen (which might exit)
            } else {
                // Otherwise, the browser naturally goes back one page
            }
        });

        // 2. Capacitor-Specific Listener fallback if bridge is present
        document.addEventListener('backbutton', function (e) {
            e.preventDefault();
            const drawer = document.getElementById('v3MoreDrawer');
            if (drawer && drawer.classList.contains('active')) {
                v3CloseDrawer();
            } else {
                window.history.back();
            }
        }, false);
    })();

    // --- PWA & APK INSTALLATION LOGIC ---
    let deferredPrompt;
    let installVisible = false;
    const isMobile = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);

    function showInstallProvisions() {
        if (installVisible) return;
        
        const banner = document.getElementById('pwa-install-banner');
        const landingBanner = document.getElementById('landing-pwa-banner');

        // Only show if mobile OR deferredPrompt is present (Desktop PWA)
        if (!isMobile && !deferredPrompt) return;

        installVisible = true;

        // Delayed appearance for "Premium" feel
        setTimeout(() => {
            if (banner) banner.style.display = 'flex';
            
            if (landingBanner) {
                landingBanner.style.display = 'flex';
                setTimeout(() => {
                    landingBanner.style.opacity = '1';
                    landingBanner.style.transform = 'translateX(-50%) translateY(0)';
                }, 50);

                // Auto-hide after 12 seconds if no interaction
                setTimeout(() => {
                    if (landingBanner.style.opacity === '1' && !window.installingApp) {
                        hideLandingBanner();
                    }
                }, 12000);
            }
        }, 3500);
    }

    function hideLandingBanner() {
        const landingBanner = document.getElementById('landing-pwa-banner');
        if (landingBanner) {
            landingBanner.style.opacity = '0';
            landingBanner.style.transform = 'translateX(-50%) translateY(50px)';
            setTimeout(() => { landingBanner.style.display = 'none'; }, 600);
        }
    }

    // Trigger for Desktop PWA
    window.addEventListener('beforeinstallprompt', (e) => {
        e.preventDefault();
        deferredPrompt = e;
        showInstallProvisions();
    });

    // Auto-trigger for Mobile APK
    if (isMobile) {
        window.addEventListener('load', () => {
            showInstallProvisions();
        });
    }

    const installBtns = [document.getElementById('pwa-install-btn'), document.getElementById('landing-pwa-btn')];
    installBtns.forEach(btn => {
        if (btn) {
            btn.addEventListener('click', (e) => {
                const textEl = btn.querySelector('#pwa-text') || { innerHTML: '' };
                const iconEl = btn.querySelector('#pwa-icon') || { className: '' };
                const progressContainer = document.getElementById('pwa-progress-container');
                const progressBar = document.getElementById('pwa-progress-bar');

                window.installingApp = true;
                
                if (isMobile) {
                    // --- MOBILE: FULLY AUTOMATED BACKGROUND APK INSTALL ---
                    btn.style.pointerEvents = 'none';
                    if (textEl) textEl.innerHTML = 'Installing...';
                    if (iconEl) {
                        iconEl.className = 'fas fa-cog fa-spin';
                        iconEl.style.color = '#cbd5e1';
                    }
                    if (progressContainer) progressContainer.style.display = 'block';

                    const apkPath = window.location.pathname.includes('/portal-') ? '../CarHire_Professional_v5.apk' : 'CarHire_Professional_v5.apk';

                    const xhr = new XMLHttpRequest();
                    xhr.open('GET', apkPath, true);
                    xhr.responseType = 'blob';

                    xhr.onprogress = (event) => {
                        if (event.lengthComputable) {
                            const percent = (event.loaded / event.total) * 100;
                            if (progressBar) progressBar.style.width = percent + '%';
                        }
                    };

                    xhr.onload = function() {
                        if (this.status === 200) {
                            // UI Feedback for completion
                            if (textEl) textEl.innerHTML = 'Finished!';
                            if (iconEl) {
                                iconEl.className = 'fas fa-check-circle';
                                iconEl.style.color = '#10b981';
                            }
                            if (progressBar) progressBar.style.background = '#10b981';

                            // Trigger the actual file start
                            const blob = this.response;
                            const link = document.createElement('a');
                            link.href = window.URL.createObjectURL(blob);
                            link.download = 'CarHire_Professional_v5.apk';
                            document.body.appendChild(link);
                            link.click();
                            document.body.removeChild(link);
                            
                            // Auto-exit after a short delay
                            setTimeout(() => {
                                hideLandingBanner();
                                if (document.getElementById('pwa-install-banner')) document.getElementById('pwa-install-banner').style.display = 'none';
                            }, 1500);
                        }
                    };

                    xhr.send();

                } else if (deferredPrompt) {
                    // --- DESKTOP: PREMIUM PWA INSTALL ---
                    if (textEl) textEl.innerHTML = 'Getting App...';
                    deferredPrompt.prompt();
                    deferredPrompt.userChoice.then((result) => {
                        if (result.outcome === 'accepted') {
                            if (textEl) textEl.innerHTML = 'Installed!';
                            setTimeout(() => {
                                if (document.getElementById('pwa-install-banner')) document.getElementById('pwa-install-banner').style.display = 'none';
                                hideLandingBanner();
                            }, 2000);
                        } else {
                            if (textEl) textEl.innerHTML = 'INSTALL';
                            window.installingApp = false;
                        }
                        deferredPrompt = null;
                    });
                }
            });
        }
    });

    // Hide banner once app is installed
    window.addEventListener('appinstalled', (evt) => {
        console.log('Car Hire was installed');
        const banner = document.getElementById('pwa-install-banner');
        if (banner) banner.style.display = 'none';
        hideLandingBanner();
    });

    // --- UNIVERSAL SERVICE WORKER REGISTRATION ---
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', () => {
            // Locate sw.js based on path depth
            const swPath = window.location.pathname.includes('/portal-') ? '../sw.js' : 'sw.js';
            navigator.serviceWorker.register(swPath)
                .then(reg => console.log('PWA Service Worker Active'))
                .catch(err => console.log('SW Registration Error', err));
        });
    }
</script>

<?php
    // Global Modal Transition System
    $modal_js_path = $is_in_portal ? '../public/js/modal-transitions.js' : 'public/js/modal-transitions.js';
?>
<script src="<?php echo $modal_js_path; ?>"></script>

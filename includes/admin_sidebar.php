<?php
$current_page = basename($_SERVER['PHP_SELF']);

// Fetch unread counts for badges
$support_count = $pdo->query("SELECT COUNT(*) FROM support_messages WHERE status = 'new'")->fetchColumn();
$verification_count = $pdo->query("SELECT COUNT(*) FROM users WHERE verification_status = 'pending'")->fetchColumn();
$total_alerts = $support_count + $verification_count;

// Logic to keep dropdowns open if an internal page is active
$intelligence_pages = ['analytics.php', 'reports-financial.php', 'expenses.php'];
$operations_pages = ['bookings.php', 'booking-details.php', 'contracts.php', 'vouchers.php', 'admin-reviews.php', 'support-inbox.php', 'payments.php', 'fleet-quote.php'];
$fleet_pages = ['fleet.php', 'maintenance.php', 'live-fleet.php', 'tracking.php'];
$admin_pages = ['users.php', 'branches.php', 'settings.php', 'whatsapp-ocr.php', 'translations.php', 'audit-logs.php', 'backups.php'];
?>
<style>
/* =========================================
   COLLAPSIBLE SIDEBAR: FORCED STYLES
   ========================================= */
.sidebar {
    transition: width 0.3s cubic-bezier(0.4, 0, 0.2, 1) !important;
}
.sidebar.collapsed {
    width: 90px !important;
}
.sidebar.collapsed .sidebar-header {
    padding: 30px 0 20px !important;
    text-align: center;
}
.sidebar.collapsed .logo-text-wrapper,
.sidebar.collapsed .sidebar-menu span:not(.nav-badge-inline):not(.menu-alert-dot),
.sidebar.collapsed .sidebar-footer span,
.sidebar.collapsed .dropdown-trigger .arrow,
.sidebar.collapsed .nav-badge-inline,
.sidebar.collapsed .menu-alert-dot,
.sidebar.collapsed .sidebar-notif-bell,
.sidebar.collapsed .menu-text-wrap {
    display: none !important;
}
.sidebar.collapsed .sidebar-header > div {
    flex-direction: column !important;
    justify-content: center !important;
    gap: 15px !important;
}
.sidebar.collapsed .sidebar-header > div > div {
    justify-content: center !important;
    width: 100% !important;
    margin: 0 !important;
}
.sidebar.collapsed .logo-img {
    width: 45px !important;
    margin: 0 auto !important;
    display: block !important;
}
.sidebar.collapsed #sidebarToggleBtn {
    position: static !important;
    margin: 0 auto !important;
    display: block !important;
    width: 100% !important;
    text-align: center !important;
}
.sidebar.collapsed #sidebarToggleBtn i {
    font-size: 1.4rem !important;
}
.sidebar.collapsed .sidebar-menu a,
.sidebar.collapsed .sidebar-footer a,
.sidebar.collapsed .dropdown-trigger {
    display: flex !important;
    justify-content: center !important;
    align-items: center !important;
    padding: 15px 0 !important;
    margin: 6px 15px !important;
    border-radius: 12px !important;
}
.sidebar.collapsed .sidebar-menu a i,
.sidebar.collapsed .sidebar-footer a i,
.sidebar.collapsed .dropdown-trigger i:first-child {
    font-size: 1.4rem !important;
    margin: 0 !important;
}
.sidebar.collapsed .dropdown-content {
    display: none !important;
}

/* Override body margin for fixed sidebar */
body.sidebar-collapsed .main-content {
    margin-left: 90px !important;
    width: calc(100% - 90px) !important;
}
</style>

<aside class="sidebar" id="adminSidebar">
    <div class="sidebar-header" style="position: relative;">
        <!-- Sidebar Toggle Button (Desktop) -->
        <button id="sidebarToggleBtn" class="desktop-only" style="position: absolute; right: 20px; top: 40px; background: transparent; border: none; color: var(--text-secondary); cursor: pointer; font-size: 1.2rem; z-index: 10; transition: color 0.3s;" onclick="toggleSidebar()" title="Toggle Sidebar">
            <i class="fas fa-bars"></i>
        </button>
        
        <div style="display: flex; justify-content: space-between; align-items: center; width: 100%;">
            <div style="display: flex; align-items: center; gap: 16px;">
                <img src="../car_hire_logo_1772881088456.png" alt="Car Hire Logo" class="logo-img" style="width: 55px; height: auto;">
                <div class="logo-text-wrapper">
                    <h2 class="logo" style="padding-left: 0 !important; font-size: 1.5rem;">Car Hire</h2>
                    <div class="logo-sub-wrapper">
                        <div class="logo-sub-flipper">
                            <span>Management Portal</span>
                            <span>Fleet Operations</span>
                            <span>Revenue Insights</span>
                            <span>System Control</span>
                            <span>Audit Security</span>
                        </div>
                    </div>
                </div>
            </div>
            <?php
            $notif_stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
            $notif_stmt->execute([$_SESSION['user_id']]);
            $notif_count = $notif_stmt->fetchColumn();
            ?>
            <div class="sidebar-notif-bell" onclick="window.location.href='notifications.php'" style="cursor: pointer; position: relative;">
                <i class="fas fa-bell"></i>
                <?php if ($notif_count > 0): ?>
                    <span class="bell-badge">
                        <?php echo $notif_count; ?>
                    </span>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <nav class="sidebar-menu">
        <!-- Primary Access -->
        <a href="dashboard.php" class="<?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>" title="Dashboard">
            <i class="fas fa-th-large"></i> <span>Dashboard</span>
        </a>

        <!-- 1. Fleet Section -->
        <div class="sidebar-dropdown <?php echo in_array($current_page, $fleet_pages) ? 'active open' : ''; ?>" title="Fleet Management">
            <button class="dropdown-trigger">
                <i class="fas fa-car"></i> <span>Fleet</span> <i class="fas fa-chevron-right arrow"></i>
            </button>
            <div class="dropdown-content">
                <a href="fleet.php" class="<?php echo $current_page == 'fleet.php' ? 'active' : ''; ?>" title="Vehicles"><i class="far fa-dot-circle"></i><span>Vehicles</span></a>
                <a href="live-fleet.php" class="<?php echo $current_page == 'live-fleet.php' ? 'active' : ''; ?>" title="Live Tracker"><i class="fas fa-satellite-dish"></i><span>Live Fleet Tracker</span></a>
                <a href="maintenance.php" class="<?php echo $current_page == 'maintenance.php' ? 'active' : ''; ?>" title="Service Logs"><i class="far fa-dot-circle"></i><span>Service Logs</span></a>
            </div>
        </div>

        <div class="sidebar-separator"></div>

        <!-- 2. Operations Section -->
        <div class="sidebar-dropdown <?php echo in_array($current_page, $operations_pages) ? 'active open' : ''; ?>" title="Operations">
            <button class="dropdown-trigger">
                <i class="fas fa-calendar-check"></i> 
                <span class="menu-text-wrap">
                    Operations
                    <?php if ($total_alerts > 0): ?>
                        <span class="menu-alert-dot"></span>
                    <?php endif; ?>
                </span>
                <i class="fas fa-chevron-right arrow"></i>
            </button>
            <div class="dropdown-content">
                <a href="bookings.php" class="<?php echo in_array($current_page, ['bookings.php', 'booking-details.php']) ? 'active' : ''; ?>" title="Bookings"><i class="far fa-dot-circle"></i><span>Bookings</span></a>
                <a href="payments.php" class="<?php echo $current_page == 'payments.php' ? 'active' : ''; ?>" title="Payments"><i class="far fa-dot-circle"></i><span>Payments</span></a>
                <a href="admin-reviews.php" class="<?php echo $current_page == 'admin-reviews.php' ? 'active' : ''; ?>" title="Verifications">
                    <i class="far fa-dot-circle"></i><span>Verifications <?php if($verification_count > 0): ?> <span class="nav-badge-inline"><?php echo $verification_count; ?></span> <?php endif; ?></span>
                </a>
                <a href="contracts.php" class="<?php echo $current_page == 'contracts.php' ? 'active' : ''; ?>" title="Agreements"><i class="far fa-dot-circle"></i><span>Agreements</span></a>
                <a href="vouchers.php" class="<?php echo $current_page == 'vouchers.php' ? 'active' : ''; ?>" title="Coupons"><i class="far fa-dot-circle"></i><span>Coupons</span></a>
                <a href="support-inbox.php" class="<?php echo $current_page == 'support-inbox.php' ? 'active' : ''; ?>" title="Support & Events">
                    <i class="far fa-dot-circle"></i><span>Support & Events <?php if($support_count > 0): ?> <span class="nav-badge-inline"><?php echo $support_count; ?></span> <?php endif; ?></span>
                </a>
                <a href="support-inbox.php?filter=events" class="<?php echo ($current_page == 'fleet-quote.php') ? 'active' : ''; ?>" title="Fleet Event Quotes">
                    <i class="fas fa-car-side" style="color: var(--accent-color);"></i><span style="color: var(--accent-color); font-weight: 700;">Fleet Event Quotes</span>
                </a>
            </div>
        </div>

        <div class="sidebar-separator"></div>

        <!-- 3. Insights Section -->
        <div class="sidebar-dropdown <?php echo in_array($current_page, $intelligence_pages) ? 'active open' : ''; ?>" title="Insights">
            <button class="dropdown-trigger">
                <i class="fas fa-chart-line"></i> <span>Insights</span> <i class="fas fa-chevron-right arrow"></i>
            </button>
            <div class="dropdown-content">
                <a href="analytics.php" class="<?php echo $current_page == 'analytics.php' ? 'active' : ''; ?>" title="Analytics"><i class="far fa-dot-circle"></i><span>Analytics</span></a>
                <a href="reports-financial.php" class="<?php echo $current_page == 'reports-financial.php' ? 'active' : ''; ?>" title="Financials"><i class="far fa-dot-circle"></i><span>Financials</span></a>
                <a href="expenses.php" class="<?php echo $current_page == 'expenses.php' ? 'active' : ''; ?>" title="Operating Expenses"><i class="far fa-dot-circle"></i><span>Operating Expenses</span></a>
            </div>
        </div>

        <div class="sidebar-separator"></div>

        <!-- 4. System Section -->
        <div class="sidebar-dropdown <?php echo in_array($current_page, $admin_pages) ? 'active open' : ''; ?>" title="System">
            <button class="dropdown-trigger">
                <i class="fas fa-cogs"></i> <span>System</span> <i class="fas fa-chevron-right arrow"></i>
            </button>
            <div class="dropdown-content">
                <a href="users.php" class="<?php echo $current_page == 'users.php' ? 'active' : ''; ?>" title="Users & Roles"><i class="far fa-dot-circle"></i><span>Users & Roles</span></a>
                <a href="branches.php" class="<?php echo $current_page == 'branches.php' ? 'active' : ''; ?>" title="Locations"><i class="far fa-dot-circle"></i><span>Locations</span></a>
                <a href="settings.php" class="<?php echo $current_page == 'settings.php' ? 'active' : ''; ?>" title="Settings"><i class="far fa-dot-circle"></i><span>Settings</span></a>
                <a href="whatsapp-ocr.php" class="<?php echo $current_page == 'whatsapp-ocr.php' ? 'active' : ''; ?>" style="color: #25D366;" title="WhatsApp & OCR">
                    <i class="fab fa-whatsapp"></i><span>WhatsApp &amp; OCR</span>
                </a>
                <a href="translations.php" class="<?php echo $current_page == 'translations.php' ? 'active' : ''; ?>" title="Translations">
                    <i class="fas fa-language"></i><span>Languages & Translation</span>
                </a>
                <a href="audit-logs.php" class="<?php echo $current_page == 'audit-logs.php' ? 'active' : ''; ?>" title="Audit Logs">
                    <i class="fas fa-history"></i><span>Audit Logs</span>
                </a>
                <a href="backups.php" class="<?php echo $current_page == 'backups.php' ? 'active' : ''; ?>" title="Database Backups">
                    <i class="fas fa-database"></i><span>Database Backups</span>
                </a>
            </div>
        </div>

        <div class="sidebar-footer">
            <a href="profile.php" class="<?php echo $current_page == 'profile.php' ? 'active' : ''; ?>" title="My Profile">
                <i class="fas fa-user-circle"></i> <span>My Profile</span>
            </a>
            <div class="sidebar-separator" style="margin: 10px 0; opacity: 0.5;"></div>
            <a href="../logout.php" class="logout-link" title="Log Out">
                <i class="fas fa-sign-out-alt"></i> <span>Log Out</span>
            </a>
        </div>
    </nav>
</aside>

<script>
document.querySelectorAll('.dropdown-trigger').forEach(trigger => {
    trigger.addEventListener('click', (e) => {
        const parent = trigger.parentElement;
        const sidebar = document.getElementById('adminSidebar');
        
        // If sidebar is collapsed, clicking a dropdown should expand the sidebar first
        if(sidebar.classList.contains('collapsed')) {
            toggleSidebar();
        }
        
        parent.classList.toggle('open');
    });
});

// Sidebar Toggle Logic
function toggleSidebar() {
    const sidebar = document.getElementById('adminSidebar');
    const body = document.body;
    
    sidebar.classList.toggle('collapsed');
    body.classList.toggle('sidebar-collapsed');
    
    // Save preference to localStorage
    if (sidebar.classList.contains('collapsed')) {
        localStorage.setItem('sidebarState', 'collapsed');
    } else {
        localStorage.setItem('sidebarState', 'expanded');
    }
}

// Auto-apply saved sidebar state on load
document.addEventListener('DOMContentLoaded', () => {
    const savedState = localStorage.getItem('sidebarState');
    if (savedState === 'collapsed') {
        document.getElementById('adminSidebar').classList.add('collapsed');
        document.body.classList.add('sidebar-collapsed');
    }
});
</script>

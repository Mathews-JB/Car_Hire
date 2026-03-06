<?php
$current_page = basename($_SERVER['PHP_SELF']);

// Fetch unread counts for badges
$support_count = $pdo->query("SELECT COUNT(*) FROM support_messages WHERE status = 'new'")->fetchColumn();
$verification_count = $pdo->query("SELECT COUNT(*) FROM users WHERE verification_status = 'pending'")->fetchColumn();
$total_alerts = $support_count + $verification_count;

// Logic to keep dropdowns open if an internal page is active
$intelligence_pages = ['analytics.php', 'reports-financial.php'];
$operations_pages = ['bookings.php', 'booking-details.php', 'contracts.php', 'vouchers.php', 'admin-reviews.php', 'support-inbox.php', 'payments.php'];
$fleet_pages = ['fleet.php', 'maintenance.php', 'live-fleet.php', 'tracking.php'];
$admin_pages = ['users.php', 'branches.php', 'settings.php', 'whatsapp-ocr.php', 'translations.php'];
?>

<aside class="sidebar">
    <div class="sidebar-header">
        <div style="display: flex; justify-content: space-between; align-items: center; width: 100%;">
            <div>
                <h2 class="logo">Car Hire</h2>
                <span class="logo-sub">Management Portal</span>
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
        <a href="dashboard.php" class="<?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>">
            <i class="fas fa-th-large"></i> <span>Dashboard</span>
        </a>

        <!-- 1. Fleet Section -->
        <div class="sidebar-dropdown <?php echo in_array($current_page, $fleet_pages) ? 'active open' : ''; ?>">
            <button class="dropdown-trigger">
                <i class="fas fa-car"></i> <span>Fleet</span> <i class="fas fa-chevron-right arrow"></i>
            </button>
            <div class="dropdown-content">
                <a href="fleet.php" class="<?php echo $current_page == 'fleet.php' ? 'active' : ''; ?>">Vehicles</a>
                <a href="live-fleet.php" class="<?php echo $current_page == 'live-fleet.php' ? 'active' : ''; ?>"><i class="fas fa-satellite-dish" style="margin-right:6px;"></i>Live Fleet Tracker</a>
                <a href="maintenance.php" class="<?php echo $current_page == 'maintenance.php' ? 'active' : ''; ?>">Service Logs</a>
            </div>
        </div>

        <div class="sidebar-separator"></div>

        <!-- 2. Operations Section -->
        <div class="sidebar-dropdown <?php echo in_array($current_page, $operations_pages) ? 'active open' : ''; ?>">
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
                <a href="bookings.php" class="<?php echo in_array($current_page, ['bookings.php', 'booking-details.php']) ? 'active' : ''; ?>">Bookings</a>
                <a href="payments.php" class="<?php echo $current_page == 'payments.php' ? 'active' : ''; ?>">Payments</a>
                <a href="admin-reviews.php" class="<?php echo $current_page == 'admin-reviews.php' ? 'active' : ''; ?>">
                    Verifications <?php if($verification_count > 0): ?> <span class="nav-badge-inline"><?php echo $verification_count; ?></span> <?php endif; ?>
                </a>
                <a href="contracts.php" class="<?php echo $current_page == 'contracts.php' ? 'active' : ''; ?>">Agreements</a>
                <a href="vouchers.php" class="<?php echo $current_page == 'vouchers.php' ? 'active' : ''; ?>">Coupons</a>
                <a href="support-inbox.php" class="<?php echo $current_page == 'support-inbox.php' ? 'active' : ''; ?>">
                    Messages <?php if($support_count > 0): ?> <span class="nav-badge-inline"><?php echo $support_count; ?></span> <?php endif; ?>
                </a>
            </div>
        </div>

        <div class="sidebar-separator"></div>

        <!-- 3. Insights Section -->
        <div class="sidebar-dropdown <?php echo in_array($current_page, $intelligence_pages) ? 'active open' : ''; ?>">
            <button class="dropdown-trigger">
                <i class="fas fa-chart-line"></i> <span>Insights</span> <i class="fas fa-chevron-right arrow"></i>
            </button>
            <div class="dropdown-content">
                <a href="analytics.php" class="<?php echo $current_page == 'analytics.php' ? 'active' : ''; ?>">Analytics</a>
                <a href="reports-financial.php" class="<?php echo $current_page == 'reports-financial.php' ? 'active' : ''; ?>">Financials</a>
            </div>
        </div>

        <div class="sidebar-separator"></div>

        <!-- 4. System Section -->
        <div class="sidebar-dropdown <?php echo in_array($current_page, $admin_pages) ? 'active open' : ''; ?>">
            <button class="dropdown-trigger">
                <i class="fas fa-cogs"></i> <span>System</span> <i class="fas fa-chevron-right arrow"></i>
            </button>
            <div class="dropdown-content">
                <a href="users.php" class="<?php echo $current_page == 'users.php' ? 'active' : ''; ?>">Users & Roles</a>
                <a href="branches.php" class="<?php echo $current_page == 'branches.php' ? 'active' : ''; ?>">Locations</a>
                <a href="settings.php" class="<?php echo $current_page == 'settings.php' ? 'active' : ''; ?>">Settings</a>
                <a href="whatsapp-ocr.php" class="<?php echo $current_page == 'whatsapp-ocr.php' ? 'active' : ''; ?>" style="color: #25D366;">
                    <i class="fab fa-whatsapp" style="margin-right:8px; font-size:0.9rem;"></i><span>WhatsApp &amp; OCR</span>
                </a>
                <a href="translations.php" class="<?php echo $current_page == 'translations.php' ? 'active' : ''; ?>">
                    <i class="fas fa-language" style="margin-right:8px; font-size:0.9rem;"></i><span>Languages & Translation</span>
                </a>
            </div>
        </div>

        <div class="sidebar-footer">
            <a href="profile.php" class="<?php echo $current_page == 'profile.php' ? 'active' : ''; ?>">
                <i class="fas fa-user-circle"></i> <span>My Profile</span>
            </a>
            <div class="sidebar-separator" style="margin: 10px 0; opacity: 0.5;"></div>
            <a href="../logout.php" class="logout-link">
                <i class="fas fa-sign-out-alt"></i> <span>Log Out</span>
            </a>
        </div>
    </nav>
</aside>

<script>
document.querySelectorAll('.dropdown-trigger').forEach(trigger => {
    trigger.addEventListener('click', (e) => {
        const parent = trigger.parentElement;
        parent.classList.toggle('open');
    });
});
</script>

</script>

<?php
$current_page = basename($_SERVER['PHP_SELF']);
?>
<aside class="sidebar">
    <div class="sidebar-header">
        <h2 class="logo">Car Hire</h2>
        <span class="logo-sub">Agent Portal</span>
    </div>
    <nav class="sidebar-menu">
        <a href="dashboard.php" class="<?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>">
            <i class="fas fa-grid-2"></i> <span>Dashboard</span>
        </a>
        <a href="monitoring.php" class="<?php echo $current_page == 'monitoring.php' ? 'active' : ''; ?>">
            <i class="fas fa-eye"></i> <span>Monitoring</span>
        </a>
        <a href="tracking.php" class="<?php echo $current_page == 'tracking.php' ? 'active' : ''; ?>">
            <i class="fas fa-location-arrow"></i> <span>Tracking</span>
        </a>

        <div class="sidebar-separator"></div>
        <a href="reservations.php" class="<?php echo $current_page == 'reservations.php' ? 'active' : ''; ?>">
            <i class="fas fa-calendar-check"></i> <span>Reservations</span>
        </a>
        <a href="fleet.php" class="<?php echo $current_page == 'fleet.php' ? 'active' : ''; ?>">
            <i class="fas fa-car"></i> <span>Fleet</span>
        </a>

        <div class="sidebar-separator"></div>
        <a href="notifications.php" class="<?php echo $current_page == 'notifications.php' ? 'active' : ''; ?>">
            <i class="fas fa-bell"></i> <span>Notifications</span>
        </a>
        <a href="profile.php" class="<?php echo $current_page == 'profile.php' ? 'active' : ''; ?>">
            <i class="fas fa-user-circle"></i> <span>My Profile</span>
        </a>
        
        <div class="sidebar-footer">
            <a href="../logout.php" class="logout-link"><i class="fas fa-power-off"></i> <span>Sign Out</span></a>
        </div>
    </nav>
</aside>

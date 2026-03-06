<?php
// mobile_header.php - Action Bar with Back Button
$current_page = basename($_SERVER['PHP_SELF']);
$dir = dirname($_SERVER['PHP_SELF']);
$is_portal = strpos($dir, 'portal-') !== false;

$back_url = 'dashboard.php'; // Default for portal

if ($current_page == 'booking-details.php' || $current_page == 'track-booking.php' || $current_page == 'payment.php' || $current_page == 'receipt.php') {
    $back_url = 'my-bookings.php';
} elseif ($current_page == 'vehicle-details.php') {
    $back_url = 'browse-vehicles.php';
} elseif ($current_page == 'login.php' || $current_page == 'register.php') {
    $back_url = 'index.php';
} elseif ($current_page == 'dashboard.php' || $current_page == 'index.php') {
    $back_url = ''; // No back button on main pages
}

// Map page names to display titles
$page_titles = [
    'dashboard.php' => 'Portal Home',
    'browse-vehicles.php' => 'Browse Fleet',
    'my-bookings.php' => 'My Bookings',
    'profile.php' => 'My Profile',
    'support.php' => 'Help Center',
    'booking-details.php' => 'Booking Details',
    'track-booking.php' => 'Live Tracking',
    'payment.php' => 'Secure Payment',
    'receipt.php' => 'Booking Receipt',
    'notifications.php' => 'Notifications',
    'verify-profile.php' => 'Identity Verification',
    'vehicle-details.php' => 'Vehicle Info',
    'login.php' => 'Account Login',
    'register.php' => 'Create Account'
];

$display_title = $page_titles[$current_page] ?? 'Car Hire';
?>

<style>
    .mobile-action-bar {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        height: 65px;
        background: rgba(15, 23, 42, 0.8);
        backdrop-filter: blur(15px);
        -webkit-backdrop-filter: blur(15px);
        z-index: 10000;
        align-items: center;
        padding: 0 15px;
        border-bottom: 1px solid rgba(255, 255, 255, 0.08);
        justify-content: space-between;
    }

    @media (max-width: 768px) {
        .mobile-action-bar {
            display: flex;
        }
        /* Offset content to prevent overlap */
        body {
            padding-top: 65px !important;
        }
        /* Hide existing redundant headers if any */
        .hub-bar {
            display: none !important;
        }
    }

    .m-back-btn {
        width: 40px;
        height: 40px;
        border-radius: 12px;
        background: rgba(255, 255, 255, 0.05);
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        text-decoration: none;
        font-size: 1.1rem;
        transition: 0.2s;
    }

    .m-back-btn:active {
        background: rgba(255, 255, 255, 0.15);
        transform: scale(0.9);
    }

    .m-page-title {
        font-weight: 800;
        color: white;
        font-size: 1rem;
        letter-spacing: -0.5px;
        position: absolute;
        left: 50%;
        transform: translateX(-50%);
        white-space: nowrap;
    }

    .m-action-right {
        width: 40px;
        display: flex;
        justify-content: flex-end;
    }
</style>

<div class="mobile-action-bar">
    <?php if ($back_url): ?>
        <a href="<?php echo $back_url; ?>" class="m-back-btn">
            <i class="fas fa-chevron-left"></i>
        </a>
    <?php else: ?>
        <div style="width: 40px;"></div>
    <?php endif; ?>

    <div class="m-page-title"><?php echo $display_title; ?></div>

    <div class="m-action-right">
        <?php if ($current_page != 'login.php' && $current_page != 'register.php'): ?>
            <a href="notifications.php" style="color: white; font-size: 1.2rem; opacity: 0.8;">
                <i class="fas fa-bell"></i>
            </a>
        <?php endif; ?>
    </div>
</div>

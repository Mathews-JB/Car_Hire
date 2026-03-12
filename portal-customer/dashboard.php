<?php
include_once '../includes/db.php';
include_once '../includes/functions.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'customer') {
    header("Location: ../login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];

// Fetch User Loyalty and Verification Data
$stmt = $pdo->prepare("SELECT loyalty_points, membership_tier, verification_status FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$udata = $stmt->fetch();
$loyalty_points = $udata['loyalty_points'] ?? 0;
$membership_tier = $udata['membership_tier'] ?? 'Bronze';
$verification_status = $udata['verification_status'] ?? 'none';

// Calculate Tier Progress
$next_tier = 'Silver';
$target_points = 300;
if ($membership_tier === 'Silver') {
    $next_tier = 'Gold';
    $target_points = 1000;
} elseif ($membership_tier === 'Gold') {
    $next_tier = 'Ultimate';
    $target_points = 5000; // Future goal
}
$tier_progress = min(100, ($loyalty_points / $target_points) * 100);

// Fetch Statistics
$stmt = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE user_id = ?");
$stmt->execute([$user_id]);
$total_bookings = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE user_id = ? AND status = 'confirmed'");
$stmt->execute([$user_id]);
$active_rentals = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT SUM(total_price) FROM bookings WHERE user_id = ? AND status IN ('confirmed', 'completed')");
$stmt->execute([$user_id]);
$total_spent = $stmt->fetchColumn() ?: 0;

// Fetch Recent Bookings (Limit 3)
$stmt = $pdo->prepare("SELECT b.*, v.make, v.model, v.image_url 
                       FROM bookings b 
                       JOIN vehicles v ON b.vehicle_id = v.id 
                       WHERE b.user_id = ? 
                       ORDER BY b.created_at DESC LIMIT 3");
$stmt->execute([$user_id]);
$recent_bookings = $stmt->fetchAll();

// Featured Booking (Next upcoming or most recent)
$stmt = $pdo->prepare("SELECT b.*, v.make, v.model, v.image_url 
                       FROM bookings b 
                       JOIN vehicles v ON b.vehicle_id = v.id 
                       WHERE b.user_id = ? AND b.status IN ('pending', 'confirmed')
                       ORDER BY b.pickup_date ASC LIMIT 1");
$stmt->execute([$user_id]);
$featured_booking = $stmt->fetch();

// Fleet Status Counts (Global Transparency)
$stmt = $pdo->query("SELECT COUNT(*) FROM vehicles WHERE status = 'available'");
$fleet_available = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM vehicles WHERE status = 'rented'");
$fleet_active = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM vehicles WHERE status = 'maintenance'");
$fleet_maintenance = $stmt->fetchColumn();

// Reserved/Upcoming (Bookings starting in future)
$stmt = $pdo->query("SELECT COUNT(*) FROM bookings WHERE status = 'confirmed' AND pickup_date > NOW()");
$fleet_reserved = $stmt->fetchColumn();

// Awaiting Pickup (Bookings starting today)
$stmt = $pdo->query("SELECT COUNT(*) FROM bookings WHERE status = 'confirmed' AND DATE(pickup_date) = CURDATE()");
$fleet_awaiting = $stmt->fetchColumn();

// Fetch Latest Platform Notifications (Global or User-Specific, excluding sensitive/redundant types)
$stmt = $pdo->prepare("SELECT * FROM notifications 
                       WHERE (user_id = ? OR user_id IS NULL) 
                       AND type NOT IN ('security', 'system', 'error', 'verify') 
                       ORDER BY created_at DESC LIMIT 3");
$stmt->execute([$user_id]);
$platform_notifications = $stmt->fetchAll();

// Fetch Unread Notification Count
$stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
$stmt->execute([$user_id]);
$unread_count = $stmt->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>Customer Dashboard | Car Hire</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../public/css/style.css?v=2.6">
    <!-- Theme System -->
    <link rel="stylesheet" href="../public/css/theme.css?v=4.0">
    <script src="../public/js/theme-switcher.js?v=4.0"></script>
    <style>
        /* Aggressive Fix for Scrolling Nav Bar on Mobile */
        .mobile-nav {
            position: fixed !important;
            bottom: calc(20px + env(safe-area-inset-bottom, 0px)) !important;
            left: 50% !important;
            transform: translateX(-50%) translateZ(0) !important;
            display: flex !important;
            z-index: 999999 !important;
        }
        
        /* Ensure entrance animation doesn't create a persistent transform */
        .portal-content {
            animation: premiumEntrance 1s ease-out forwards !important;
        }
        
        @keyframes premiumEntrance {
            0% { opacity: 0; transform: translateY(15px); }
            100% { opacity: 1; transform: none !important; }
        }
        .notif-card-mini:hover {
            transform: translateY(-3px);
            background: rgba(255,255,255,0.06) !important;
            border-color: rgba(255,255,255,0.1) !important;
            box-shadow: 0 10px 20px rgba(0,0,0,0.2);
        }
    </style>
    <script>
        // Hoist Nav to Body to bypass any containing block issues
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(() => {
                const nav = document.querySelector('.mobile-nav');
                if (nav && nav.parentElement !== document.body) {
                    document.body.appendChild(nav);
                    nav.style.display = 'flex';
                }
            }, 50);
        });
    </script>
    <style>
        /* Modernized Stable Background - Handled by stabilized-car-bg */
        /* Desktop How It Works Sizing */
        .how-it-works-step {
            min-height: 250px !important;
            padding: 15px 20px !important;
            display: flex !important;
            flex-direction: column !important;
        }
        .how-it-works-step .step-icon {
            width: 50px !important;
            height: 50px !important;
            margin-bottom: 10px !important;
        }
        .how-it-works-step .step-icon i {
            font-size: 1.5rem !important;
        }
        .how-it-works-step h3 {
            font-size: 1rem !important;
            margin-bottom: 5px !important;
        }
        .how-it-works-step p {
            font-size: 0.8rem !important;
            line-height: 1.4 !important;
            margin-bottom: 10px !important;
        }
        .how-it-works-step a, .how-it-works-step span {
            margin-top: auto !important;
        }

        @media (max-width: 768px) {
            .dashboard-header {
                flex-direction: column;
                text-align: center;
                gap: 20px;
                padding-bottom: 20px;
            }
            .dashboard-header .btn {
                padding: 10px 16px !important;
                font-size: 0.8rem !important;
                flex: 1;
                min-width: 140px;
            }
            .dashboard-header div[style*="display: flex"] {
                width: 100%;
                display: flex !important;
                gap: 8px !important;
            }
            .notification-banner {
                padding: 10px 15px !important;
                gap: 12px !important;
                margin-bottom: 15px !important;
            }
            .notification-banner div[style*="width: 45px"] {
                width: 32px !important;
                height: 32px !important;
                min-width: 32px !important;
                font-size: 0.9rem !important;
            }
            .notification-banner div[style*="width: 40px"] {
                width: 30px !important;
                height: 30px !important;
                min-width: 30px !important;
                font-size: 0.8rem !important;
            }
            .notification-banner h3 {
                font-size: 0.85rem !important;
            }
            .notification-banner p {
                font-size: 0.7rem !important;
                line-height: 1.2 !important;
            }
            .notification-banner .btn {
                padding: 6px 12px !important;
                font-size: 0.75rem !important;
                width: auto !important;
            }
            .stats-grid {
                grid-template-columns: repeat(2, 1fr) !important;
                gap: 10px !important;
                align-items: stretch !important;
            }
            .stat-card {
                padding: 15px 10px !important;
                display: flex !important;
                flex-direction: column !important;
                align-items: center !important;
                justify-content: center !important;
                height: 115px !important; /* Slightly larger height */
                margin: 0 !important;
                text-align: center !important;
            }
            .stat-card i {
                font-size: 1.3rem !important;
                margin-bottom: 6px !important;
            }
            .stat-card h3 {
                font-size: 0.95rem !important;
                margin: 0 !important;
                line-height: 1.1 !important;
                white-space: nowrap !important;
                overflow: hidden !important;
                text-overflow: ellipsis !important;
                width: 100% !important;
                font-weight: 800 !important;
            }
            .stat-card p {
                font-size: 0.65rem !important;
                margin: 4px 0 0 !important;
                opacity: 0.7 !important;
                text-transform: uppercase !important;
                letter-spacing: 0.5px !important;
            }
            .dashboard-grid {
                grid-template-columns: 1fr !important;
            }
            .upcoming-card {
                padding: 15px !important;
            }
            .upcoming-content {
                flex-direction: column !important;
                text-align: center;
            }
            .upcoming-img {
                width: 100% !important;
                height: 150px !important;
            }
            #dashMiniMap {
                height: 150px !important;
            }
            .activity-container {
                padding: 15px !important;
            }
            .vehicle-mini div {
                display: none !important;
            }
            .data-table th:nth-child(3), .data-table td:nth-child(3) {
                display: none !important;
            }
            .right-section .data-card:first-child {
                padding: 15px !important;
            }
            .right-section .data-card:first-child div[style*="width: 100px"] {
                width: 60px !important;
                height: 60px !important;
                margin: 0 auto 10px !important;
            }
            .right-section .data-card:first-child div[style*="width: 100px"] i {
                font-size: 2rem !important;
            }
            .right-section .data-card:first-child h3 {
                font-size: 0.95rem !important;
                margin-bottom: 2px !important;
            }
            .right-section .data-card:first-child .loyalty-badge {
                font-size: 0.65rem !important;
                padding: 2px 8px !important;
            }
            .right-section .data-card:first-child small {
                font-size: 0.6rem !important;
            }
            .right-section .data-card:first-child .btn-outline {
                padding: 8px !important;
                font-size: 0.75rem !important;
                margin-top: 10px !important;
            }
            /* How It Works Mobile Styles */
            .how-it-works-grid {
                grid-template-columns: repeat(2, 1fr) !important;
                gap: 12px !important;
            }
            .how-it-works-header {
                margin-bottom: 25px !important;
            }
            .how-it-works-header h2 {
                font-size: 1.5rem !important;
                margin-bottom: 5px !important;
            }
            .how-it-works-header p {
                font-size: 0.85rem !important;
            }
            .how-it-works-step {
                padding: 12px !important;
                min-height: 155px !important; 
                display: flex !important;
                flex-direction: column !important;
            }
            .how-it-works-step .step-number {
                width: 32px !important;
                height: 32px !important;
                font-size: 0.9rem !important;
                top: -10px !important;
                left: 10px !important;
            }
            .how-it-works-step .step-icon {
                width: 40px !important;
                height: 40px !important;
                margin-bottom: 8px !important;
            }
            .how-it-works-step .step-icon i {
                font-size: 1.2rem !important;
            }
            .how-it-works-step h3 {
                font-size: 0.85rem !important;
                margin-bottom: 5px !important;
            }
            .how-it-works-content {
                margin-top: 15px !important;
            }
            .how-it-works-step p {
                font-size: 0.7rem !important;
                line-height: 1.3 !important;
                margin-bottom: 5px !important;
            }
            .how-it-works-step a, 
            .how-it-works-step span {
                font-size: 0.7rem !important;
                margin-top: auto !important;
            }
            .pro-tip-card {
                padding: 15px !important;
                margin-top: 10px !important;
            }
            .pro-tip-card p {
                font-size: 0.75rem !important;
            }
            .pro-tip-buttons {
                flex-direction: column !important;
                gap: 10px !important;
            }
            .pro-tip-buttons a {
                width: 100% !important;
                text-align: center !important;
            }
        }
    </style>
</head>
<body class="stabilized-car-bg">
    <?php include_once '../includes/mobile_header.php'; ?>

    <nav class="hub-bar">
        <a href="dashboard.php" class="logo">Car Hire</a>
        <div class="hub-nav">
            <a href="dashboard.php" class="active"><?php echo __('dashboard'); ?></a>
            <a href="browse-vehicles.php"><?php echo __('browse_fleet'); ?></a>
            <a href="my-bookings.php"><?php echo __('my_bookings'); ?></a>
            <a href="support.php"><?php echo __('support'); ?></a>
            <a href="profile.php"><?php echo __('profile'); ?></a>
        </div>
        <div class="hub-user">
            <!-- Theme Switcher -->
            <?php include '../includes/theme_switcher.php'; ?>
            
            <?php 
                $display_name = $_SESSION['user_name'] ?? 'User';
                $first_name = explode(' ', $display_name)[0];
                $initial = !empty($display_name) ? strtoupper($display_name[0]) : 'U';
            ?>
            <!-- Notifications Bell -->
            <a href="notifications.php" style="position: relative; margin-right: 15px; text-decoration: none; color: white; display: flex; align-items: center; justify-content: center; width: 35px; height: 35px; background: rgba(255,255,255,0.05); border-radius: 50%;">
                <i class="fas fa-bell" style="font-size: 1.1rem; opacity: 0.8;"></i>
                <?php if ($unread_count > 0): ?>
                    <span style="position: absolute; top: -5px; right: -5px; background: var(--danger); color: white; font-size: 0.65rem; padding: 2px 6px; border-radius: 50%; font-weight: 800; border: 2px solid #0f172a;"><?php echo $unread_count; ?></span>
                <?php endif; ?>
            </a>

            <span class="hub-user-name"><?php echo htmlspecialchars($first_name); ?></span>
            <div class="hub-avatar"><?php echo htmlspecialchars($initial); ?></div>
            <a href="../logout.php" style="color: var(--danger); margin-left: 10px; font-size: 0.85rem;"><i class="fas fa-sign-out-alt"></i></a>
        </div>
    </nav>

    <div class="portal-content">
        <div class="container">
            <div class="dashboard-header">
                <div>
                    <h1><?php echo __('welcome_back'); ?></h1>
                    <p><?php echo __('adventure_ready'); ?></p>
                </div>

                <div style="display: flex; gap: 10px; flex-wrap: wrap; justify-content: center;">
                    <a href="event-booking.php" class="btn btn-outline"><i class="fas fa-gem"></i> Event Fleet</a>
                    <a href="browse-vehicles.php" class="btn btn-primary btn-lg"><i class="fas fa-plus"></i> <?php echo __('new_booking'); ?></a>
                </div>
            </div>


            <!-- Verification Guard Banner -->
            <?php if ($verification_status !== 'approved'): ?>
                <div class="notification-banner" style="background: <?php echo $verification_status === 'pending' ? 'rgba(245, 158, 11, 0.15)' : 'rgba(239, 68, 68, 0.15)'; ?>; border: 1px solid <?php echo $verification_status === 'pending' ? 'var(--accent-color)' : 'var(--danger)'; ?>; padding: 20px; border-radius: 16px; margin-bottom: 30px; display: flex; align-items: center; gap: 20px; color: white;">
                    <div style="background: <?php echo $verification_status === 'pending' ? 'var(--accent-color)' : 'var(--danger)'; ?>; width: 45px; height: 45px; min-width: 45px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.2rem;">
                        <i class="fas <?php echo $verification_status === 'pending' ? 'fa-clock' : 'fa-user-shield'; ?>"></i>
                    </div>
                    <div style="flex: 1;">
                        <h3 style="margin: 0; font-size: 1rem;"><?php echo __('verification_status'); ?> <?php echo ucfirst($verification_status); ?></h3>
                        <p style="margin: 4px 0 0; opacity: 0.8; font-size: 0.8rem;">
                            <?php if ($verification_status === 'pending'): ?>
                                <?php echo __('verification_pending'); ?>
                            <?php else: ?>
                                <?php echo __('verification_required'); ?>
                            <?php endif; ?>
                        </p>
                    </div>
                    <?php if ($verification_status !== 'pending'): ?>
                        <a href="verify-profile.php" class="btn btn-primary" style="background: var(--accent-vibrant); border: none; white-space: nowrap;"><?php echo __('verify_now'); ?></a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <!-- Approval Notification (Modernized) -->
            <?php 
            if ($featured_booking && $featured_booking['status'] === 'confirmed'): 
            ?>
            <div class="notification-banner" style="background: rgba(16, 185, 129, 0.1); border: 1px solid rgba(16, 185, 129, 0.3); backdrop-filter: blur(15px); -webkit-backdrop-filter: blur(15px); padding: 20px 25px; border-radius: 20px; margin-bottom: 30px; display: flex; align-items: center; gap: 20px; border-left: 5px solid #10b981; box-shadow: 0 15px 35px rgba(0,0,0,0.3);">
                <div style="background: rgba(16, 185, 129, 0.2); width: 45px; height: 45px; min-width: 45px; border-radius: 12px; display: flex; align-items: center; justify-content: center; color: #10b981; font-size: 1.4rem;">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div style="flex: 1;">
                    <h3 style="margin: 0; font-size: 1.1rem; color: white; font-weight: 800; letter-spacing: -0.5px;"><?php echo __('booking_approved'); ?></h3>
                    <p style="margin: 4px 0 0; color: rgba(255,255,255,0.7); font-size: 0.9rem;">
                        Your reservation for <span style="color: #10b981; font-weight: 700;"><?php echo htmlspecialchars($featured_booking['make']); ?></span> is ready for pickup.
                    </p>
                </div>
                <div>
                    <a href="track-vehicle.php" class="btn btn-primary" style="background: #10b981; color: white; border: none; padding: 10px 20px; font-weight: 700; border-radius: 10px;">
                        <i class="fas fa-satellite"></i> 3D Radar
                    </a>
                </div>
            </div>
            <?php endif; ?>



            <!-- Stats Grid -->
            <h3 style="font-size: 1.1rem; color: white; margin-bottom: 15px; opacity: 0.8;"><?php echo __('my_activity'); ?></h3>
            <div class="stats-grid">
                <div class="stat-card">
                    <i class="fas fa-car-side" style="color: var(--primary-color);"></i>
                    <h3><?php echo $active_rentals; ?></h3>
                    <p><?php echo __('active_rentals'); ?></p>
                </div>
                <div class="stat-card">
                    <i class="fas fa-history" style="color: var(--accent-color);"></i>
                    <h3><?php echo $total_bookings; ?></h3>
                    <p><?php echo __('total_bookings'); ?></p>
                </div>
                <div class="stat-card">
                    <i class="fas fa-wallet" style="color: var(--success);"></i>
                    <h3>ZMW <?php echo number_format($total_spent, 0); ?></h3>
                    <p><?php echo __('total_spent'); ?></p>
                </div>
                <div class="stat-card">
                    <i class="fas fa-award" style="color: #ffd700;"></i>
                    <h3><?php echo $membership_tier; ?></h3>
                    <p><?php echo __('member_status'); ?></p>
                </div>
            </div>

            <!-- How It Works Section -->
            <div style="margin: 30px 0;">
                <div class="how-it-works-header" style="text-align: center; margin-bottom: 40px;">
                    <h2 style="font-size: 2rem; color: white; margin-bottom: 10px;"><?php echo __('how_it_works'); ?></h2>
                    <p style="color: rgba(255,255,255,0.7); font-size: 1rem;"><?php echo __('how_it_works_desc'); ?></p>
                </div>

                <div class="how-it-works-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 25px; margin-bottom: 30px;">
                    <!-- Step 1 -->
                    <div class="data-card how-it-works-step" style="background: linear-gradient(135deg, rgba(37, 99, 235, 0.15), rgba(37, 99, 235, 0.05)) !important; border-left: 4px solid #2563eb !important; position: relative; overflow: visible;">
                        <div class="step-number" style="position: absolute; top: -15px; left: 20px; background: #2563eb; width: 50px; height: 50px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 1.3rem; color: white; box-shadow: 0 5px 15px rgba(37, 99, 235, 0.4);">1</div>
                        <div class="how-it-works-content" style="margin-top: 25px;">
                            <div class="step-icon" style="background: rgba(37, 99, 235, 0.1); width: 60px; height: 60px; border-radius: 12px; display: flex; align-items: center; justify-content: center; margin-bottom: 15px;">
                                <i class="fas fa-search" style="font-size: 1.8rem; color: #2563eb;"></i>
                            </div>
                            <h3 style="font-size: 1.1rem; margin-bottom: 10px; color: white;"><?php echo __('browse_vehicles'); ?></h3>
                            <p style="font-size: 0.85rem; opacity: 0.8; line-height: 1.6;"><?php echo __('browse_vehicles_desc'); ?></p>
                            <a href="browse-vehicles.php" style="display: inline-flex; align-items: center; gap: 5px; color: #2563eb; font-size: 0.85rem; font-weight: 600; margin-top: 10px; text-decoration: none;">
                                <?php echo __('browse_vehicles'); ?> <i class="fas fa-arrow-right"></i>
                            </a>
                        </div>
                    </div>

                    <!-- Step 2 -->
                    <div class="data-card how-it-works-step" style="background: linear-gradient(135deg, rgba(16, 185, 129, 0.15), rgba(16, 185, 129, 0.05)) !important; border-left: 4px solid #10b981 !important; position: relative; overflow: visible;">
                        <div class="step-number" style="position: absolute; top: -15px; left: 20px; background: #10b981; width: 50px; height: 50px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 1.3rem; color: white; box-shadow: 0 5px 15px rgba(16, 185, 129, 0.4);">2</div>
                        <div class="how-it-works-content" style="margin-top: 25px;">
                            <div class="step-icon" style="background: rgba(16, 185, 129, 0.1); width: 60px; height: 60px; border-radius: 12px; display: flex; align-items: center; justify-content: center; margin-bottom: 15px;">
                                <i class="fas fa-calendar-check" style="font-size: 1.8rem; color: #10b981;"></i>
                            </div>
                            <h3 style="font-size: 1.1rem; margin-bottom: 10px; color: white;"><?php echo __('book_confirm'); ?></h3>
                            <p style="font-size: 0.85rem; opacity: 0.8; line-height: 1.6;"><?php echo __('book_confirm_desc'); ?></p>
                            <span style="display: inline-flex; align-items: center; gap: 5px; color: #10b981; font-size: 0.85rem; font-weight: 600; margin-top: 10px;">
                                <i class="fas fa-check-circle"></i> <?php echo __('book_confirm'); ?>
                            </span>
                        </div>
                    </div>

                    <!-- Step 3 -->
                    <div class="data-card how-it-works-step" style="background: linear-gradient(135deg, rgba(245, 158, 11, 0.15), rgba(245, 158, 11, 0.05)) !important; border-left: 4px solid #f59e0b !important; position: relative; overflow: visible;">
                        <div class="step-number" style="position: absolute; top: -15px; left: 20px; background: #f59e0b; width: 50px; height: 50px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 1.3rem; color: white; box-shadow: 0 5px 15px rgba(245, 158, 11, 0.4);">3</div>
                        <div class="how-it-works-content" style="margin-top: 25px;">
                            <div class="step-icon" style="background: rgba(245, 158, 11, 0.1); width: 60px; height: 60px; border-radius: 12px; display: flex; align-items: center; justify-content: center; margin-bottom: 15px;">
                                <i class="fas fa-credit-card" style="font-size: 1.8rem; color: #f59e0b;"></i>
                            </div>
                            <h3 style="font-size: 1.1rem; margin-bottom: 10px; color: white;"><?php echo __('make_payment'); ?></h3>
                            <p style="font-size: 0.85rem; opacity: 0.8; line-height: 1.6;"><?php echo __('make_payment_desc'); ?></p>
                            <span style="display: inline-flex; align-items: center; gap: 5px; color: #f59e0b; font-size: 0.85rem; font-weight: 600; margin-top: 10px;">
                                <i class="fas fa-shield-alt"></i> <?php echo __('make_payment'); ?>
                            </span>
                        </div>
                    </div>

                    <!-- Step 4 -->
                    <div class="data-card how-it-works-step" style="background: linear-gradient(135deg, rgba(139, 92, 246, 0.15), rgba(139, 92, 246, 0.05)) !important; border-left: 4px solid #8b5cf6 !important; position: relative; overflow: visible;">
                        <div class="step-number" style="position: absolute; top: -15px; left: 20px; background: #8b5cf6; width: 50px; height: 50px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 1.3rem; color: white; box-shadow: 0 5px 15px rgba(139, 92, 246, 0.4);">4</div>
                        <div class="how-it-works-content" style="margin-top: 25px;">
                            <div class="step-icon" style="background: rgba(139, 92, 246, 0.1); width: 60px; height: 60px; border-radius: 12px; display: flex; align-items: center; justify-content: center; margin-bottom: 15px;">
                                <i class="fas fa-car-side" style="font-size: 1.8rem; color: #8b5cf6;"></i>
                            </div>
                            <h3 style="font-size: 1.1rem; margin-bottom: 10px; color: white;"><?php echo __('pickup_drive'); ?></h3>
                            <p style="font-size: 0.85rem; opacity: 0.8; line-height: 1.6;"><?php echo __('pickup_drive_desc'); ?></p>
                            <span style="display: inline-flex; align-items: center; gap: 5px; color: #8b5cf6; font-size: 0.85rem; font-weight: 600; margin-top: 10px;">
                                <i class="fas fa-map-marked-alt"></i> <?php echo __('pickup_drive'); ?>
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Quick Tips -->
                <div class="data-card pro-tip-card" style="background: linear-gradient(135deg, rgba(236, 72, 153, 0.1), transparent) !important; border: 1px dashed rgba(236, 72, 153, 0.3) !important; text-align: center;">
                    <div style="display: inline-flex; align-items: center; gap: 10px; background: rgba(236, 72, 153, 0.1); padding: 8px 16px; border-radius: 20px; margin-bottom: 15px;">
                        <i class="fas fa-lightbulb" style="color: #ec4899;"></i>
                        <span style="font-weight: 700; color: #ec4899; font-size: 0.85rem;">PRO TIP</span>
                    </div>
                    <p style="font-size: 0.95rem; margin-bottom: 15px; line-height: 1.6;">
                        <strong>Download your documents:</strong> After booking, you'll receive an Invoice and Rental Agreement via email. 
                        Download them for your records and bring a copy when picking up your vehicle.
                    </p>
                    <div class="pro-tip-buttons" style="display: flex; gap: 15px; justify-content: center; flex-wrap: wrap; margin-top: 20px;">
                        <a href="my-bookings.php" class="btn btn-outline" style="padding: 10px 20px; font-size: 0.85rem;">
                            <i class="fas fa-file-invoice"></i> View My Bookings
                        </a>
                        <a href="support.php" class="btn btn-outline" style="padding: 10px 20px; font-size: 0.85rem;">
                            <i class="fas fa-question-circle"></i> Need Help?
                        </a>
                    </div>
                </div>
            </div>

            <div class="dashboard-grid">
                <!-- Left: Featured & Recent -->
                <div class="left-section">
                    <?php 
                    // Special Live Tracking Card for Confirmed Journeys
                    if ($featured_booking && $featured_booking['status'] === 'confirmed'): 
                    ?>
                        <div class="data-card" style="margin-bottom: 30px; border: 1px solid rgba(255,255,255,0.05); background: rgba(30, 30, 35, 0.4) !important; padding: 25px;">
                            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; border-bottom: 1px solid rgba(255,255,255,0.05); padding-bottom: 15px;">
                                <div style="display:flex; align-items:center; gap:10px;">
                                    <span class="status-badge" style="background: var(--primary-color); color: white; padding: 6px 14px; border-radius: 8px; font-weight: 800; font-size: 0.7rem; text-transform: uppercase; letter-spacing: 1px;"><i class="fas fa-satellite"></i> LIVE JOURNEY</span>
                                    <span style="font-size: 0.85rem; color: rgba(255,255,255,0.5); font-weight: 600;">#<?php echo $featured_booking['id']; ?></span>
                                </div>
                                <a href="track-vehicle.php" style="color: var(--primary-color); font-size: 0.85rem; font-weight: 700; text-decoration: none; display: flex; align-items: center; gap: 5px;">Radar <i class="fas fa-chevron-right" style="font-size: 0.7rem;"></i></a>
                            </div>
                            
                            <div style="position: relative; height: 180px; border-radius: 16px; overflow: hidden; margin-bottom: 0px; background: #0f172a;">
                                <!-- Tech Radar Grid Background -->
                                <div style="position: absolute; inset: 0; opacity: 0.3; background-image: radial-gradient(#3b82f6 0.5px, transparent 0.5px), radial-gradient(#3b82f6 0.5px, transparent 0.5px); background-size: 20px 20px; background-position: 0 0, 10px 10px;"></div>
                                <div style="position: absolute; inset: 0; background: radial-gradient(circle at center, transparent 0%, rgba(30, 30, 35, 0.8) 100%);"></div>
                                
                                <div style="position: absolute; inset: 0; display: flex; align-items: center; justify-content: center; background: radial-gradient(circle, rgba(37, 99, 235, 0.15), transparent);">
                                    <div style="text-align: center;">
                                        <a href="track-vehicle.php" class="btn btn-primary" style="background: var(--primary-color); border: none; box-shadow: 0 10px 40px rgba(37, 99, 235, 0.5); padding: 12px 30px; border-radius: 12px; font-weight: 800; font-size: 0.85rem; position: relative; z-index: 2;">
                                            <i class="fas fa-satellite"></i> ACTIVATE SATELLITE 3D
                                        </a>
                                        <p style="color: rgba(255,255,255,0.4); font-size: 0.7rem; margin-top: 15px; font-weight: 600; text-transform: uppercase; letter-spacing: 1px;">
                                            <i class="fas fa-crosshairs"></i> Synchronizing Global Position...
                                        </p>
                                    </div>
                                </div>
                                
                                <!-- Decorative Radar Pulse -->
                                <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); width: 300px; height: 300px; border: 1px solid rgba(59, 130, 246, 0.1); border-radius: 50%; pointer-events: none;"></div>
                                <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); width: 150px; height: 150px; border: 1px solid rgba(59, 130, 246, 0.05); border-radius: 50%; pointer-events: none;"></div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if ($featured_booking && $featured_booking['status'] !== 'confirmed'): ?>
                        <div class="data-card upcoming-card" style="margin-bottom: 30px; border-left: 4px solid var(--accent-color) !important;">
                            <div style="display:flex; justify-content:space-between; align-items:start; margin-bottom:15px;">
                                <span class="status-badge" style="background: var(--accent-color); color: var(--dark-color);"><?php echo __('upcoming_rental'); ?></span>
                                <span class="loyalty-badge" style="background: rgba(16, 185, 129, 0.1); border-color: rgba(16, 185, 129, 0.3); color: #10b981; font-size: 0.65rem;">
                                    <i class="fas fa-check-shield"></i> Maintenance Verified
                                </span>
                            </div>
                            <div class="upcoming-content" style="display: flex; gap: 20px; align-items: center; flex-wrap: wrap;">
                                <img src="<?php echo !empty($featured_booking['image_url']) ? '../' . $featured_booking['image_url'] : 'https://via.placeholder.com/300x180'; ?>" 
                                     class="upcoming-img" style="border-radius: 12px; object-fit: cover;">
                                <div>
                                    <h2 style="margin-bottom: 8px; font-size: 1.6rem;"><?php echo $featured_booking['make'] . ' ' . $featured_booking['model']; ?></h2>
                                    <p style="margin-bottom: 4px;"><i class="fas fa-calendar-alt"></i> Starts: <?php echo date('d M Y', strtotime($featured_booking['pickup_date'])); ?></p>
                                    <p style="margin-bottom: 15px;"><i class="fas fa-map-marker-alt"></i> <?php echo $featured_booking['pickup_location']; ?></p>
                                    <a href="my-bookings.php" class="btn btn-outline" style="padding: 8px 20px;"><?php echo __('manage_booking'); ?></a>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <div class="table-container activity-container" style="background: rgba(30, 30, 35, 0.4) !important;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
                            <h3><?php echo __('recent_activity'); ?></h3>
                            <a href="my-bookings.php" style="font-size: 0.85rem; color: var(--primary-color);"><?php echo __('view_all'); ?></a>
                        </div>
                        <?php if (count($recent_bookings) > 0): ?>
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Vehicle</th>
                                        <th>Status</th>
                                        <th>Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recent_bookings as $b): ?>
                                    <tr onclick="window.location.href='booking-details.php?id=<?php echo $b['id']; ?>'" style="cursor: pointer;">
                                        <td data-label="Vehicle">
                                            <div class="vehicle-mini">
                                                <img src="<?php echo !empty($b['image_url']) ? '../' . $b['image_url'] : 'https://via.placeholder.com/80x50'; ?>">
                                                <div>
                                                    <strong><?php echo $b['make']; ?></strong>
                                                    <small><?php echo $b['model']; ?></small>
                                                </div>
                                            </div>
                                        </td>
                                        <td data-label="Status">
                                            <span class="status-pill <?php echo $b['status']; ?>"><?php echo strtoupper($b['status']); ?></span>
                                        </td>
                                        <td data-label="Total">
                                            <strong>ZMW <?php echo number_format($b['total_price']); ?></strong>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php else: ?>
                            <p style="text-align: center; opacity: 0.5; padding: 20px;">No recent rentals.</p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Right: Profile Summary -->
                <div class="right-section">
                    <div class="data-card" style="text-align: center; margin-bottom: 30px;">
                        <div style="width: 100px; height: 100px; background: rgba(255,255,255,0.05); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px; border: 2px solid var(--primary-color);">
                            <i class="fas fa-user-circle" style="font-size: 3.5rem; color: white;"></i>
                        </div>
                        <h3 style="margin-bottom: 5px;"><?php echo htmlspecialchars($user_name); ?></h3>
                        <div style="margin-bottom: 20px; display:flex; flex-direction:column; align-items:center; gap:5px;">
                            <span class="loyalty-badge tier-<?php echo strtolower($membership_tier); ?>"><?php echo $membership_tier; ?> <?php echo __('member'); ?></span>
                            <small style="opacity:0.6;"><?php echo number_format($loyalty_points); ?> <?php echo __('points'); ?></small>
                        </div>

                        <div style="margin-bottom: 25px; text-align: left;">
                            <div style="display:flex; justify-content:space-between; font-size:0.7rem; margin-bottom:6px;">
                                <span style="opacity:0.6;"><?php echo __('progress_to'); ?> <?php echo $next_tier; ?></span>
                                <span style="color:var(--accent-color);"><?php echo round($tier_progress); ?>%</span>
                            </div>
                            <div class="progress-bar-bg" style="height:6px; border-radius:10px;">
                                <div class="progress-bar-fill bg-primary" style="width:<?php echo $tier_progress; ?>%; height:6px; border-radius:10px;"></div>
                            </div>
                        </div>

                        <a href="profile.php" class="btn btn-outline" style="width: 100%;"><?php echo __('reward_center'); ?></a>
                    </div>
                    
                    <div class="data-card" style="background: linear-gradient(rgba(37, 99, 235, 0.1), transparent) !important;">
                        <h4 style="margin-bottom: 15px;"><?php echo __('need_help'); ?></h4>
                        <p style="font-size: 0.85rem; opacity: 0.7; margin-bottom: 20px;">Our 24/7 team is ready to assist you.</p>
                        <a href="support.php" class="btn btn-primary" style="width: 100%;"><i class="fas fa-headset"></i> <?php echo __('support'); ?></a>
                    </div>

            </div> <!-- Close dashboard-grid -->

            <!-- Platform Notifications (Full Width for Balance) -->
            <div class="data-card" style="margin-top: 30px; padding: 25px; border: 1px solid rgba(255,255,255,0.05); background: rgba(30, 30, 35, 0.4) !important; border-radius: 20px;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
                    <h4 style="font-size: 1.1rem; display: flex; align-items: center; gap: 10px; color: white; margin: 0;">
                        <i class="fas fa-bullhorn" style="color: var(--accent-color);"></i> <?php echo __('platform_updates'); ?>
                    </h4>
                    <a href="notifications.php" style="font-size: 0.8rem; color: var(--accent-color); font-weight: 600; text-decoration: none; display: flex; align-items: center; gap: 5px;">
                        <?php echo __('view_all_history'); ?> <i class="fas fa-arrow-right" style="font-size: 0.7rem;"></i>
                    </a>
                </div>
                
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px;">
                    <?php if (empty($platform_notifications)): ?>
                        <div style="grid-column: 1 / -1; text-align: center; padding: 30px; opacity: 0.5;">
                            <p style="font-size: 0.85rem; margin: 0;">No new updates at the moment.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($platform_notifications as $n): ?>
                            <div class="notif-card-mini" style="padding: 15px; border-radius: 12px; background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.05); transition: all 0.3s ease; position: relative; overflow: hidden;">
                                <div style="position: absolute; top: 0; left: 0; width: 3px; height: 100%; background: var(--accent-color); opacity: 0.5;"></div>
                                <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 8px;">
                                    <strong style="font-size: 0.9rem; color: white;"><?php echo htmlspecialchars($n['title']); ?></strong>
                                    <small style="font-size: 0.65rem; opacity: 0.4; white-space: nowrap;"><?php echo date('d M', strtotime($n['created_at'])); ?></small>
                                </div>
                                <p style="font-size: 0.75rem; opacity: 0.7; margin: 0; line-height: 1.5; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;"><?php echo htmlspecialchars($n['message']); ?></p>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div> <!-- Close container -->
    <?php include_once '../includes/mobile_nav.php'; ?>
</body>
</html>

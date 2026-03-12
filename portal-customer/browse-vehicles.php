<?php
include_once '../includes/db.php';
include_once '../includes/functions.php';
// Aggressive Cache Busting for real-time fleet status
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'customer') {
    header("Location: ../login.php");
    exit;
}

$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';
$search_query = trim($_GET['search'] ?? '');
$title_status = 'All Vehicles';

// Base Query
$sql = "SELECT v.*, b.name as brand_name FROM vehicles v 
        LEFT JOIN brands b ON v.brand_id = b.id 
        WHERE 1=1";
$params = [];

// Apply Search
if (!empty($search_query)) {
    $sql .= " AND (v.make LIKE ? OR v.model LIKE ? OR b.name LIKE ? OR v.features LIKE ?)";
    $search_param = "%$search_query%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
}

// Apply Filters
if ($status_filter === 'available') {
    $sql .= " AND status = 'available' ";
    $title_status = 'Available Vehicles';
} elseif ($status_filter === 'maintenance') {
    $sql .= " AND status = 'maintenance' ";
    $title_status = 'Vehicles Under Maintenance';
} elseif ($status_filter === 'rented') {
    $sql .= " AND LOWER(TRIM(status)) IN ('rented', 'hired') ";
    $title_status = 'Vehicles Currently Hired';
}

$sql .= " ORDER BY CASE WHEN LOWER(TRIM(status)) = 'available' THEN 0 ELSE 1 END, make ASC, model ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$vehicles = $stmt->fetchAll();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Browse Vehicles | Car Hire</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../public/css/style.css">
    <!-- Theme System -->
    <link rel="stylesheet" href="../public/css/theme.css?v=4.0">
    <script src="../public/js/theme-switcher.js?v=4.0"></script>
    <style>
        .filter-bar {
            background: rgba(30, 30, 35, 0.6);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            padding: 15px 20px;
            border-radius: 12px;
            margin-bottom: 30px;
            display: flex;
            gap: 10px;
            overflow-x: auto;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        .filter-bar::-webkit-scrollbar {
            height: 4px;
        }
        .filter-bar::-webkit-scrollbar-track {
            background: transparent;
        }
        .filter-bar::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.2);
            border-radius: 10px;
        }
        .filter-btn {
            padding: 8px 18px;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 600;
            text-decoration: none;
            color: rgba(255, 255, 255, 0.7);
            background: rgba(255, 255, 255, 0.05);
            white-space: nowrap;
            transition: all 0.2s;
            border: 1px solid rgba(255, 255, 255, 0.05);
        }
        .filter-btn:hover {
            background: rgba(255, 255, 255, 0.1);
            color: white;
            transform: translateY(-2px);
            border-color: rgba(255, 255, 255, 0.2);
        }
        .filter-btn.active {
            background: var(--primary-color);
            color: white;
            box-shadow: 0 4px 10px rgba(37, 99, 235, 0.3);
            border-color: var(--primary-color);
        }
        body { 
            background: transparent !important;
        }

        @media (max-width: 768px) {
            .container {
                padding: 0 8px !important;
            }
            .hub-bar {
                padding: 10px 15px !important;
            }
            .hub-nav {
                display: none !important;
            }
            .dashboard-header {
                flex-direction: column !important;
                text-align: center !important;
                gap: 12px !important;
                margin-bottom: 20px !important;
            }
            .dashboard-header h1 {
                font-size: 1.4rem !important;
                margin-bottom: 4px !important;
            }
            .dashboard-header p {
                font-size: 0.8rem !important;
            }
            .dashboard-header .btn {
                width: auto !important;
                padding: 8px 18px !important;
                font-size: 0.75rem !important;
                margin: 0 auto !important;
                border-radius: 10px !important;
            }
            .filter-bar {
                padding: 12px !important;
                gap: 12px !important;
                margin-bottom: 20px !important;
            }
            .filter-btn {
                padding: 5px 12px !important;
                font-size: 0.7rem !important;
                border-radius: 8px !important;
            }
            form[action="browse-vehicles.php"] {
                gap: 6px !important;
            }
            form[action="browse-vehicles.php"] input {
                padding: 8px 15px !important;
                font-size: 0.85rem !important;
            }
            form[action="browse-vehicles.php"] .search-btn {
                padding: 8px 12px !important;
                font-size: 0.85rem !important;
            }
            .cars-grid {
                grid-template-columns: 1fr 1fr !important;
                gap: 8px !important;
                padding: 0 4px !important;
            }
            .fleet-card {
                border-radius: 12px !important;
            }
            .fleet-img {
                height: 100px !important;
            }
            .fleet-info {
                padding: 10px !important;
            }
            .fleet-info h3 {
                font-size: 0.85rem !important;
                margin-top: 2px !important;
                margin-bottom: 3px !important;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
            }
            .brand-name {
                font-size: 0.55rem !important;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
                display: block;
            }
            .vehicle-meta {
                font-size: 0.6rem !important;
                margin-bottom: 8px !important;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
            }
            .fleet-info .status-badge {
                font-size: 0.55rem !important;
                padding: 3px 6px !important;
            }
            .fleet-info p {
                font-size: 0.65rem !important;
                margin-bottom: 8px !important;
            }
            .fleet-info .btn {
                padding: 6px 10px !important;
                font-size: 0.7rem !important;
                width: 100%;
                text-align: center;
                border-radius: 8px !important;
            }
            .fleet-card-footer {
                flex-direction: column !important;
                align-items: flex-start !important;
                gap: 5px !important;
                margin-top: 8px !important;
                padding-top: 8px !important;
            }
            .fleet-card-footer .price-val {
                font-size: 1rem !important;
            }
        }
    </style>
</head>
<body class="stabilized-car-bg">

    <?php include_once '../includes/mobile_header.php'; ?>

    <nav class="hub-bar">
        <a href="dashboard.php" class="logo">Car Hire</a>
        <div class="hub-nav">
            <a href="dashboard.php"><?php echo __('dashboard'); ?></a>
            <a href="browse-vehicles.php" class="active"><?php echo __('browse_fleet'); ?></a>
            <a href="my-bookings.php"><?php echo __('my_bookings'); ?></a>
            <a href="support.php"><?php echo __('support'); ?></a>
            <a href="profile.php"><?php echo __('profile'); ?></a>
        </div>
        <div class="hub-user">
            <!-- Theme Switcher -->
            <?php include_once '../includes/theme_switcher.php'; ?>
            
            <?php 
                $display_name = $_SESSION['user_name'] ?? 'User';
                $first_name = explode(' ', $display_name)[0];
                $initial = !empty($display_name) ? strtoupper($display_name[0]) : 'U';
            ?>
            <span class="hub-user-name"><?php echo htmlspecialchars($first_name); ?></span>
            <div class="hub-avatar"><?php echo htmlspecialchars($initial); ?></div>
            <a href="../logout.php" style="color: var(--danger); margin-left: 10px; font-size: 0.85rem;"><i class="fas fa-sign-out-alt"></i></a>
        </div>
    </nav>

    <div class="portal-content">
        <div class="container">
            <div class="dashboard-header">
                <div>
                    <h1 style="font-weight: 900; font-size: 2rem; letter-spacing: -1px;"><?php echo $title_status; ?></h1>
                    <p style="color: rgba(255,255,255,0.6);">Find the perfect ride for your next journey.</p>
                </div>
                <a href="dashboard.php" class="btn btn-outline"><i class="fas fa-arrow-left"></i> <?php echo __('dashboard'); ?></a>
            </div>

            <div class="filter-bar">
                <div style="display: flex; gap: 8px; overflow-x: auto; padding-bottom: 5px; -webkit-overflow-scrolling: touch;">
                    <a href="?status=all" class="filter-btn <?php echo $status_filter === 'all' ? 'active' : ''; ?>">All</a>
                    <a href="?status=available" class="filter-btn <?php echo $status_filter === 'available' ? 'active' : ''; ?>">Available</a>
                    <a href="?status=rented" class="filter-btn <?php echo $status_filter === 'rented' ? 'active' : ''; ?>">Hired</a>
                    <a href="?status=maintenance" class="filter-btn <?php echo $status_filter === 'maintenance' ? 'active' : ''; ?>">Service</a>
                </div>
                <form action="browse-vehicles.php" method="GET" style="display: flex; gap: 8px; width: 100%;">
                    <input type="hidden" name="status" value="<?php echo htmlspecialchars($status_filter); ?>">
                    <input type="text" name="search" class="form-control" value="<?php echo htmlspecialchars($search_query); ?>" placeholder="Search car..." style="flex: 1;">
                    <button type="submit" class="btn btn-primary" style="padding: 10px 15px;"><i class="fas fa-search"></i></button>
                </form>
            </div>

            <!-- Multi-car Fleet Booking Banner -->
            <div style="background: linear-gradient(135deg, rgba(37, 99, 235, 0.15), rgba(30, 30, 35, 0.4)); border: 1px dashed rgba(59, 130, 246, 0.4); padding: 20px; border-radius: 16px; margin-bottom: 30px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 15px;">
                <div style="display: flex; align-items: center; gap: 15px;">
                    <div style="background: rgba(37, 99, 235, 0.2); width: 45px; height: 45px; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #60a5fa; font-size: 1.2rem;">
                        <i class="fas fa-car-side"></i><i class="fas fa-plus" style="font-size: 0.6rem; transform: translate(2px, -8px);"></i>
                    </div>
                    <div>
                        <h3 style="margin: 0; font-size: 1rem; color: white;">Need Multiple Vehicles?</h3>
                        <p style="margin: 2px 0 0; font-size: 0.8rem; color: rgba(255,255,255,0.7);">Book a fleet for weddings, corporate events, or group travel.</p>
                    </div>
                </div>
                <a href="event-booking.php" class="btn btn-primary" style="background: var(--accent-vibrant); border: none; padding: 10px 20px; font-size: 0.85rem;">Request Fleet Booking</a>
            </div>

            <?php if (count($vehicles) > 0): ?>
                <div class="cars-grid">
                    <?php foreach ($vehicles as $car): ?>
                        <div class="fleet-card" style="border-radius: 20px; overflow: hidden;">
                            <img src="<?php echo !empty($car['image_url']) ? '../'.$car['image_url'] : 'https://via.placeholder.com/400x250?text=No+Image'; ?>" 
                                 alt="<?php echo $car['make'] . ' ' . $car['model']; ?>" 
                                 class="fleet-img" 
                                 onerror="this.src='../public/images/cars/default.jpg'">
                            
                            <div class="fleet-info" style="padding: 20px;">
                                <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom: 5px;">
                                    <div style="min-width: 0; flex: 1; padding-right: 5px;">
                                        <span class="brand-name" style="color: var(--accent-color); font-weight: 700; font-size: 0.7rem; text-transform: uppercase; display: block; margin-bottom: 2px;">
                                            <?php echo htmlspecialchars($car['brand_name'] ?? 'Premium'); ?>
                                        </span>
                                        <h3 style="margin: 0; font-size: 1.1rem; color: white;"><?php echo $car['make'] . ' ' . $car['model']; ?></h3>
                                    </div>
                                    <?php 
                                        $v_status = trim(strtolower($car['status']));
                                        $badge_class = ($v_status === 'available') ? 'available' : (($v_status === 'maintenance') ? 'maintenance' : 'hired');
                                        $display_status = ($v_status === 'maintenance') ? 'Service' : ($v_status === 'available' ? 'Available' : 'Hired');
                                    ?>
                                    <span class="status-badge status-<?php echo $badge_class; ?>" style="font-size: 0.65rem; padding: 4px 8px; border-radius: 4px; flex-shrink: 0;">
                                        <?php echo $display_status; ?>
                                    </span>
                                </div>
                                
                                <p class="vehicle-meta" style="font-size: 0.85rem; margin-bottom: 20px; color: rgba(255,255,255,0.6);">
                                    <?php echo $car['year']; ?> • <?php echo $car['transmission'] ?? 'Auto'; ?>
                                </p>
                                
                                <div class="fleet-card-footer" style="display: flex; justify-content: space-between; align-items: center; border-top: 1px solid rgba(255,255,255,0.1); padding-top: 15px;">
                                    <div>
                                        <span class="price-val" style="font-weight: 800; color: white; font-size: 1.2rem;">ZMW <?php echo number_format($car['price_per_day'], 0); ?></span>
                                        <span style="font-size: 0.75rem; color: rgba(255,255,255,0.5); font-weight: 600;">/ day</span>
                                    </div>
                                    
                                    <?php if (trim(strtolower($car['status'])) === 'available'): ?>
                                        <a href="vehicle-details.php?id=<?php echo $car['id']; ?>" class="btn btn-primary" style="padding: 10px 20px; font-size: 0.85rem; background: var(--accent-vibrant); border: none;">View Details</a>
                                    <?php else: ?>
                                        <button class="btn btn-outline" disabled style="padding: 8px 20px; font-size: 0.85rem; opacity: 0.5;">
                                            <?php echo (trim(strtolower($car['status'])) === 'maintenance') ? 'Service' : 'Hired'; ?>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-results">
                    <i class="fas fa-car" style="font-size: 3rem; opacity: 0.5; margin-bottom: 15px;"></i>
                    <h2>No vehicles found</h2>
                    <p>There are no vehicles matching this status at the moment.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <?php include_once '../includes/mobile_nav.php'; ?>
</body>
</html>

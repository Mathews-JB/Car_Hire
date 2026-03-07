<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include_once 'includes/db.php';
include_once 'includes/functions.php';

// Aggressive Cache Busting
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// Handle incoming Booking Context (Save to Session)
if (isset($_GET['pickup_date'])) {
    $_SESSION['booking_query'] = [
        'pickup_location' => $_GET['pickup_location'] ?? '',
        'pickup_date' => $_GET['pickup_date'],
        'pickup_time' => $_GET['pickup_time'] ?? '10:00',
        'dropoff_date' => $_GET['dropoff_date'] ?? '',
        'dropoff_time' => $_GET['dropoff_time'] ?? '10:00'
    ];
}

// Retrieve from session if not in GET (for persistence)
$booking_ctx = $_SESSION['booking_query'] ?? [];
$pickup_location = $_GET['pickup_location'] ?? ($booking_ctx['pickup_location'] ?? '');
$pickup_date = $_GET['pickup_date'] ?? ($booking_ctx['pickup_date'] ?? '');
$pickup_time = $_GET['pickup_time'] ?? ($booking_ctx['pickup_time'] ?? '10:00');
$dropoff_date = $_GET['dropoff_date'] ?? ($booking_ctx['dropoff_date'] ?? '');
$dropoff_time = $_GET['dropoff_time'] ?? ($booking_ctx['dropoff_time'] ?? '10:00');
$brand_id = $_GET['brand'] ?? '';
$search_query = trim($_GET['search'] ?? '');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Our Exclusive Fleet | Car Hire</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="public/css/style.css?v=2.7">
    <!-- PWA Manifest -->
    <link rel="manifest" href="manifest.json">
    <meta name="theme-color" content="#2563eb">
    <link rel="apple-touch-icon" href="public/images/icon-192x192.png">
    <style>
        .fleet-header { background: var(--bg-dark); padding: 60px 0; text-align: center; border-bottom: 1px solid rgba(255,255,255,0.05); }
        .brand-chip {
            padding: 10px 25px;
            background: rgba(255,255,255,0.05);
            color: rgba(255,255,255,0.6);
            border-radius: 50px;
            font-size: 0.85rem;
            font-weight: 600;
            white-space: nowrap;
            transition: all 0.3s ease;
            text-decoration: none;
            border: 1px solid rgba(255,255,255,0.1);
        }
        .brand-chip:hover { background: rgba(255,255,255,0.1); color: white; }
        .brand-chip.active {
            background: var(--accent-vibrant);
            color: white;
            border-color: var(--accent-vibrant);
            box-shadow: 0 4px 15px rgba(245, 158, 11, 0.3);
        }
        .fleet-card { padding: 0 !important; overflow: hidden !important; border: 1px solid rgba(0,0,0,0.05); background: white; border-radius: 16px; box-shadow: var(--shadow); transition: all 0.3s ease; }
        .fleet-card:hover { transform: translateY(-5px); box-shadow: 0 15px 30px rgba(0,0,0,0.1); }
        .fleet-img { width: 100%; height: 220px; object-fit: cover; }
        .fleet-info { padding: 25px; }
        .search-compact { margin-top: -40px; position: relative; z-index: 100; }
        body { background: transparent !important; }

        @media (max-width: 768px) {
            .container {
                padding: 0 8px !important;
            }
            .fleet-header {
                padding: 60px 0 30px !important;
            }
            .fleet-header h1 {
                font-size: 1.8rem !important;
                letter-spacing: -1px !important;
                margin-bottom: 5px !important;
            }
            .fleet-header p {
                font-size: 0.8rem !important;
                line-height: 1.4 !important;
                padding: 0 20px;
                opacity: 0.8;
            }
            .search-compact {
                margin-top: -50px !important;
                padding: 0 8px !important;
            }
            .search-compact .auth-card {
                padding: 15px !important;
                border-radius: 16px !important;
            }
            .search-form {
                grid-template-columns: 1fr 1fr !important;
                gap: 10px !important;
            }
            .search-form .form-group:nth-of-type(1) { grid-column: span 2 !important; }
            .search-form .form-group:nth-of-type(2) { grid-column: span 2 !important; }
            .search-form .form-group:nth-of-type(3) { grid-column: span 1 !important; }
            .search-form .form-group:nth-of-type(4) { grid-column: span 1 !important; }
            .search-form .form-group:nth-of-type(5) { grid-column: span 2 !important; }
            .search-form .form-group:nth-of-type(5) label { display: none !important; }
            .search-form input,
            .search-form select,
            .search-form button {
                height: 42px !important;
                font-size: 0.85rem !important;
                border-radius: 8px !important;
            }
            .form-group label {
                font-size: 0.7rem !important;
                margin-bottom: 3px !important;
            }
            .brand-chip {
                padding: 8px 18px !important;
                font-size: 0.75rem !important;
            }
            .features-grid {
                grid-template-columns: 1fr 1fr !important;
                gap: 8px !important;
                padding: 0 4px !important;
            }
            .fleet-card {
                max-width: 100% !important;
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
            .fleet-info p {
                font-size: 0.65rem !important;
                margin-bottom: 8px !important;
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
            .fleet-info .btn {
                padding: 6px 10px !important;
                font-size: 0.7rem !important;
                width: 100%;
                text-align: center;
                border-radius: 8px !important;
            }
            .auth-buttons {
                display: none !important;
            }
            .nav-links {
                display: none !important;
            }
        }
    </style>
    <link rel="stylesheet" href="public/css/notification.css">
</head>
<body style="position: relative; min-height: 100vh;">

    <!-- Floating Trip Context Pill -->
    <?php if(empty($pickup_date)): ?>
        <div class="trip-float-pill warning" style="display: <?php echo isset($_GET['trigger_warning']) ? 'flex' : 'none'; ?>;">
            <div class="trip-pill-icon"><i class="fas fa-exclamation"></i></div>
            <div class="trip-pill-content">
                <span class="trip-pill-title">Action Needed</span>
                <span class="trip-pill-text">Set pickup dates for pricing</span>
            </div>
            <a href="#" onclick="document.querySelector('.search-form').scrollIntoView({behavior: 'smooth'}); return false;" class="trip-pill-action">Set Dates</a>
        </div>
    <?php endif; ?>

    <!-- Success Toast (Temporary) -->
    <?php if(isset($_GET['pickup_date']) && !empty($_GET['pickup_date'])): ?>
        <div id="success-toast" class="trip-float-pill success" style="z-index: 10001;">
            <div class="trip-pill-icon"><i class="fas fa-check"></i></div>
            <div class="trip-pill-content">
                <span class="trip-pill-title">Success</span>
                <span class="trip-pill-text">Date Successfully Set</span>
            </div>
        </div>
        <script>
            setTimeout(function() {
                var toast = document.getElementById('success-toast');
                if(toast) {
                    toast.style.opacity = '0';
                    setTimeout(function(){ toast.remove(); }, 500);
                }
            }, 3000); // Disappear after 3 seconds
        </script>
    <?php endif; ?>

    <!-- Header -->
    <header id="mainHeader" class="header-solid">
        <div class="container" style="display: flex; justify-content: space-between; align-items: center;">
            <a href="index.php" class="logo">Car Hire</a>
            <ul class="nav-links">
                <li><a href="index.php"><?php echo __('welcome'); ?></a></li>
                <li><a href="our-fleet.php" class="active"><?php echo __('search_title'); ?></a></li>
                <li><a href="index.php#about">Features</a></li>
            </ul>
            <div class="auth-buttons" style="display: flex; gap: 10px; align-items: center;">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="portal-customer/dashboard.php" class="btn btn-primary" style="padding: 0.6rem 1.2rem;"><?php echo __('profile'); ?></a>
                <?php else: ?>
                    <a href="login.php" class="btn btn-outline" style="border:none; color: white;"><?php echo __('login'); ?></a>
                    <a href="register.php" class="btn btn-primary" style="padding: 0.6rem 1.5rem;"><?php echo __('register'); ?></a>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <div class="fleet-header" style="background: transparent; padding: 120px 0 60px;">
        <div class="container">
            <h1 style="color: white; font-size: 3.5rem; font-weight: 900; margin-bottom: 10px; letter-spacing: -2px;"><?php echo __('search_title'); ?></h1>
            <p style="color: rgba(255,255,255,0.7); font-size: 1.1rem; max-width: 600px; margin: 0 auto;">Premium vehicles for every terrain and occasion in Zambia. Experience elite mobility.</p>
        </div>
    </div>

    <!-- Compact Search -->
    <div class="container search-compact">
        <div class="auth-card" style="max-width: 800px; margin: 0 auto; border-radius: 24px; padding: 25px; border: 1px solid rgba(255,255,255,0.05);">
            <form action="our-fleet.php" method="GET" class="search-form" style="display: flex; gap: 15px; width: 100%;">
                <input type="hidden" name="brand" value="<?php echo htmlspecialchars($brand_id); ?>">
                <div class="form-group" style="flex: 1; margin-bottom: 0;">
                    <div style="position: relative;">
                        <i class="fas fa-search" style="position: absolute; left: 15px; top: 18px; color: var(--accent-vibrant);"></i>
                        <input type="text" name="search" value="<?php echo htmlspecialchars($search_query); ?>" placeholder="Search Vehicle (Make, Model, or Type)..." style="width: 100%; height: 50px; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); border-radius: 12px; padding: 0 15px 0 45px; color: white; outline: none;">
                    </div>
                </div>
                <button type="submit" class="btn btn-primary" style="height: 50px; padding: 0 30px; border-radius: 12px; background: var(--accent-vibrant); font-weight: 700;">Search</button>
            </form>
        </div>
    </div>

    <!-- Main Content -->
    <main class="container" style="padding-top: 60px; padding-bottom: 100px;">
        
        <!-- Brand Chips -->
        <div style="display: flex; gap: 12px; overflow-x: auto; padding-bottom: 25px; margin-bottom: 50px; scroll-behavior: smooth;">
            <?php
            // Preserve search params in brand links
            $queryStr = http_build_query([
                'pickup_location' => $pickup_location,
                'pickup_date' => $pickup_date,
                'pickup_time' => $pickup_time,
                'dropoff_date' => $dropoff_date,
                'dropoff_time' => $dropoff_time
            ]);
            ?>
            <a href="our-fleet.php?<?php echo $queryStr; ?>" class="brand-chip <?php echo empty($brand_id) ? 'active' : ''; ?>">All Collections</a>
            <?php
            $brands = $pdo->query("SELECT * FROM brands ORDER BY name ASC")->fetchAll();
            foreach ($brands as $brand):
                $isActive = ($brand_id == $brand['id']);
                $brandQuery = http_build_query(array_merge($_GET, ['brand' => $brand['id']]));
            ?>
                <a href="our-fleet.php?<?php echo $brandQuery; ?>" class="brand-chip <?php echo $isActive ? 'active' : ''; ?>">
                    <?php echo htmlspecialchars($brand['name']); ?>
                </a>
            <?php endforeach; ?>
        </div>

        <div class="features-grid">
            <?php
            $query = "SELECT v.*, b.name as brand_name FROM vehicles v 
                       LEFT JOIN brands b ON v.brand_id = b.id 
                       WHERE 1=1";
            $params = [];
            
            if (!empty($brand_id)) {
                $query .= " AND v.brand_id = ?";
                $params[] = $brand_id;
            }

            if (!empty($search_query)) {
                $query .= " AND (v.make LIKE ? OR v.model LIKE ? OR b.name LIKE ? OR v.features LIKE ?)";
                $search_param = "%$search_query%";
                $params[] = $search_param;
                $params[] = $search_param;
                $params[] = $search_param;
                $params[] = $search_param;
            }

            // Simple search logic for location if provided
            // Note: In a real app, you'd check availability against booking dates too
            
            $query .= " ORDER BY CASE WHEN LOWER(TRIM(v.status)) = 'available' THEN 0 ELSE 1 END, v.price_per_day DESC";
            
            $stmt = $pdo->prepare($query);
            $stmt->execute($params);
            $fleet = $stmt->fetchAll();

            // Typo Tolerance Logic (Fuzzy Search Suggestion)
            $suggestion = null;
            if (empty($fleet) && !empty($search_query)) {
                $all_vehicles = $pdo->query("SELECT v.make, v.model, b.name as brand_name FROM vehicles v JOIN brands b ON v.brand_id = b.id")->fetchAll();
                $best_dist = -1;
                $closest_match = '';
                
                foreach ($all_vehicles as $v) {
                    $search_terms = [strtolower($v['make']), strtolower($v['model']), strtolower($v['brand_name'])];
                    foreach ($search_terms as $term) {
                        $dist = levenshtein(strtolower($search_query), $term);
                        if ($best_dist === -1 || $dist < $best_dist) {
                            $best_dist = $dist;
                            $closest_match = ($term === strtolower($v['brand_name'])) ? $v['brand_name'] : ($v['make'] . ' ' . $v['model']);
                        }
                    }
                }
                
                if ($best_dist >= 1 && $best_dist <= 3) {
                    $suggestion = $closest_match;
                }
            }

            if (empty($fleet)): ?>
                <div class="auth-card" style="grid-column: 1/-1; text-align: center; padding: 100px 20px; max-width: 100%;">
                    <i class="fas fa-search" style="font-size: 4rem; color: rgba(255,255,255,0.1); margin-bottom: 20px;"></i>
                    <h2 style="color: white;"><?php echo $suggestion ? "No exact matches for '".htmlspecialchars($search_query)."'" : "No vehicles found matching your criteria"; ?></h2>
                    <?php if ($suggestion): ?>
                        <p style="color: rgba(255,255,255,0.6); margin-top: 10px;">Did you mean: <a href="our-fleet.php?search=<?php echo urlencode($suggestion); ?>" style="color: var(--accent-color); font-weight: 700; text-decoration: underline;"><?php echo htmlspecialchars($suggestion); ?></a>?</p>
                    <?php else: ?>
                        <p style="color: rgba(255,255,255,0.6);">Try adjusting your dates or exploring another brand collection.</p>
                    <?php endif; ?>
                    <a href="our-fleet.php" class="btn btn-primary" style="margin-top: 30px;">View All Vehicles</a>
                </div>
            <?php else:
                foreach ($fleet as $vehicle):
                    $bookUrl = "portal-customer/booking-form.php?vehicle_id=" . $vehicle['id'];
                    if ($pickup_date) $bookUrl .= "&pickup_date=" . urlencode($pickup_date . ($pickup_time ? " $pickup_time" : ""));
                    if ($dropoff_date) $bookUrl .= "&dropoff_date=" . urlencode($dropoff_date . ($dropoff_time ? " $dropoff_time" : ""));
                    if ($pickup_location) $bookUrl .= "&location=" . urlencode($pickup_location);
                    
                    $finalUrl = $bookUrl;
                    if (!isset($_SESSION['user_id'])) {
                        $finalUrl = "login.php?msg=not_logged_in&return_url=" . urlencode($bookUrl);
                    }
            ?>
                <div class="auth-card fleet-card" style="padding: 0; overflow: hidden; max-width: 450px; margin: 0 auto; border-radius: 16px;">
                    <div style="position: relative;">
                        <img src="<?php echo htmlspecialchars($vehicle['image_url']); ?>" class="fleet-img" onerror="this.src='public/images/cars/default.jpg'">
                        <?php 
                        $status = trim(strtolower($vehicle['status']));
                        $badge_bg = ($status === 'available') ? 'rgba(16, 185, 129, 0.9)' : (($status === 'maintenance') ? 'rgba(245, 158, 11, 0.9)' : 'rgba(239, 68, 68, 0.9)');
                        $status_txt = ($status === 'available') ? 'AVAILABLE' : (($status === 'maintenance') ? 'SERVICE' : 'HIRED');
                        ?>
                        <div style="position: absolute; top: 10px; right: 10px; background: <?php echo $badge_bg; ?>; backdrop-filter: blur(5px); padding: 4px 10px; border-radius: 50px; font-weight: 700; font-size: 0.6rem; color: white; box-shadow: 0 4px 10px rgba(0,0,0,0.2);">
                            <?php echo $status_txt; ?>
                        </div>
                    </div>
                    <div class="fleet-info" style="padding: 20px;">
                        <span class="brand-name" style="color: var(--accent-color); font-weight: 700; font-size: 0.7rem; text-transform: uppercase;"><?php echo htmlspecialchars($vehicle['brand_name']); ?></span>
                        <h3 style="margin-top: 5px; margin-bottom: 2px; color: white;"><?php echo htmlspecialchars($vehicle['make'] . ' ' . $vehicle['model']); ?></h3>
                        <p class="vehicle-meta" style="font-size: 0.85rem; color: rgba(255,255,255,0.5);"><?php echo $vehicle['year']; ?> • Auto</p>
                        
                        <div class="fleet-card-footer" style="margin-top: 25px; padding-top: 20px; border-top: 1px solid rgba(255,255,255,0.05); display: flex; justify-content: space-between; align-items: center;">
                            <div>
                                <span class="price-val" style="font-weight: 900; color: white; font-size: 1.4rem;">K<?php echo number_format($vehicle['price_per_day'], 0); ?></span>
                                <span style="font-size: 0.75rem; color: rgba(255,255,255,0.5); font-weight: 600;">/ day</span>
                            </div>
                            <a href="vehicle-details.php?id=<?php echo $vehicle['id']; ?>" class="btn btn-primary" style="padding: 10px 20px; font-size: 0.85rem; font-weight: 700; background: var(--accent-vibrant); border: none;">View Details</a>

                        </div>
                    </div>
                </div>
            <?php endforeach; 
            endif; ?>
        </div>
    </main>

    <!-- Footer -->
    <?php include_once 'includes/footer.php'; ?>

    <?php include_once 'includes/mobile_nav.php'; ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const pickup = document.querySelector('input[name="pickup_date"]');
            const dropoff = document.querySelector('input[name="dropoff_date"]');
            const location = document.querySelector('select[name="pickup_location"]');
            
            function autoSubmit() {
                // Determine if we should submit (e.g. both dates present)
                if(pickup.value && dropoff.value) {
                    pickup.style.borderColor = '#10b981';
                    dropoff.style.borderColor = '#10b981';
                    // Visual feedback then submit
                    setTimeout(() => {
                        document.querySelector('.search-form').submit();
                    }, 300);
                }
            }
            
            if(pickup && dropoff) {
                pickup.addEventListener('change', autoSubmit);
                dropoff.addEventListener('change', autoSubmit);
                if(location) location.addEventListener('change', autoSubmit);
            }
        });
    </script>
    <!-- PWA Service Worker Registration -->
    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('sw.js')
                    .then(reg => console.log('Service Worker registered'))
                    .catch(err => console.log('Service Worker registration failed', err));
            });
        }
    </script>
</body>
</html>

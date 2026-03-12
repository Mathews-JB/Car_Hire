<?php
// Aggressive Cache Busting
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

include_once 'includes/db.php';
include_once 'includes/functions.php';

$id = $_GET['id'] ?? '';

if (empty($id)) {
    header("Location: our-fleet.php");
    exit;
}

// Session & Booking Context
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

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

$booking_ctx = $_SESSION['booking_query'] ?? [];
$pickup_location = $_GET['pickup_location'] ?? ($booking_ctx['pickup_location'] ?? '');
$pickup_date = $_GET['pickup_date'] ?? ($booking_ctx['pickup_date'] ?? '');
$pickup_time = $_GET['pickup_time'] ?? ($booking_ctx['pickup_time'] ?? '10:00');
$dropoff_date = $_GET['dropoff_date'] ?? ($booking_ctx['dropoff_date'] ?? '');
$dropoff_time = $_GET['dropoff_time'] ?? ($booking_ctx['dropoff_time'] ?? '10:00');


// Fetch vehicle details
$stmt = $pdo->prepare("SELECT v.*, b.name as brand_name FROM vehicles v 
                       LEFT JOIN brands b ON v.brand_id = b.id 
                       WHERE v.id = ?");
$stmt->execute([$id]);
$vehicle = $stmt->fetch();

if (!$vehicle) {
    header("Location: our-fleet.php");
    exit;
}

// Fetch vehicle images
$stmt = $pdo->prepare("SELECT * FROM vehicle_images WHERE vehicle_id = ? ORDER BY is_primary DESC, id ASC");
$stmt->execute([$id]);
$vehicle_images = $stmt->fetchAll();

// If no images in new table, fallback to old column
if (empty($vehicle_images)) {
    $vehicle_images[] = ['image_url' => $vehicle['image_url'], 'view_type' => 'exterior'];
    if (!empty($vehicle['interior_image_url'])) {
        $vehicle_images[] = ['image_url' => $vehicle['interior_image_url'], 'view_type' => 'interior'];
    }
}

// ----------------------------------------------------
// AVAILABILITY CHECK
// ----------------------------------------------------
$is_available = true;
$availability_msg = '';

if ($vehicle['status'] !== 'available') {
    $is_available = false;
    $availability_msg = 'Currently Under Maintenance or Unavailable';
} else if (!empty($pickup_date) && !empty($dropoff_date)) {
    // Check specific dates if provided
    $is_available = checkAvailability($pdo, $id, $pickup_date, $dropoff_date);
    if (!$is_available) {
        $availability_msg = 'Booked for these dates ('.date('d M', strtotime($pickup_date)).' - '.date('d M', strtotime($dropoff_date)).')';
    }
} else {
    // If no dates provided, check if it's currently booked RIGHT NOW (today)
    $today = date('Y-m-d');
    $is_available = checkAvailability($pdo, $id, $today, $today);
    if (!$is_available) {
        $availability_msg = 'Currently out on rent. Contact us for future dates.';
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $vehicle['make'] . ' ' . $vehicle['model']; ?> | Car Hire</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" />
    <link rel="stylesheet" href="public/css/style.css?v=2.3">
    <!-- Theme System -->
    <link rel="stylesheet" href="public/css/theme.css?v=4.0">
    <script src="public/js/theme-switcher.js?v=4.0"></script>
    <!-- PWA Manifest -->
    <link rel="manifest" href="manifest.json">
    <meta name="theme-color" content="#2563eb">
    <link rel="apple-touch-icon" href="public/images/icon-192x192.png">
    <style>
        .details-hero {
            padding: 120px 0 60px;
            background: linear-gradient(to bottom, rgba(30, 30, 35, 0.8), rgba(30, 30, 35, 0.4));
            min-height: 400px;
            display: flex;
            align-items: center;
        }
        .details-grid {
            display: grid;
            grid-template-columns: 1.5fr 1fr;
            gap: 40px;
            margin-top: -60px;
            position: relative;
            z-index: 10;
        }
        .swiper {
            width: 100%;
            max-width: 850px; /* Constrain width */
            margin: 0 auto;   /* Center horizontally */
            height: 380px;    /* Reduced height */
            border-radius: 24px;
            overflow: hidden;
            background: #ffffff;
            position: relative;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        }

        .swiper-wrapper {
            display: flex;
            height: 100%;
        }

        .swiper-slide {
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(30, 30, 35, 0.9); /* Matching dark theme background */
            position: relative;
            flex-shrink: 0;
            text-align: center; /* Ensure text centering */
        }

        .swiper-slide img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        .no-image-placeholder {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 15px;
            color: rgba(255,255,255,0.2);
        }
        .no-image-placeholder i {
            font-size: 5rem;
        }




        .view-badge {
            position: absolute;
            top: 20px;
            left: 20px;
            background: rgba(30, 30, 35, 0.8);
            backdrop-filter: blur(5px);
            padding: 5px 15px;
            border-radius: 50px;
            color: white;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            z-index: 10;
            border: 1px solid rgba(255,255,255,0.1);
        }
        .spec-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
            margin: 25px 0;
        }
        .spec-item {
            background: rgba(255,255,255,0.03);
            padding: 15px;
            border-radius: 12px;
            border: 1px solid rgba(255,255,255,0.05);
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .spec-item i {
            color: var(--accent-color);
            font-size: 1.2rem;
            width: 24px;
            text-align: center;
        }
        .spec-label {
            display: block;
            font-size: 0.7rem;
            text-transform: uppercase;
            color: rgba(255,255,255,0.4);
            letter-spacing: 1px;
        }
        .spec-value {
            font-weight: 700;
            color: white;
            font-size: 0.9rem;
        }
        .sticky-book {
            position: sticky;
            top: 100px;
        }
        body { background: transparent !important; }

        @media (max-width: 992px) {
            .details-grid { 
                grid-template-columns: 1fr; 
                margin-top: 0; 
                gap: 20px;
                padding: 0 15px;
            }
            .details-hero { 
                padding: 80px 0 30px;
                min-height: 180px;
            }
            .details-hero h1 {
                font-size: 2rem !important;
            }
            .swiper { 
                max-width: 100%;
                height: 280px;
                border-radius: 16px;
            }
        }

        @media (max-width: 768px) {
            html, body {
                overflow-x: hidden !important;
                width: 100% !important;
                position: relative !important;
            }
            .container {
                padding-left: 15px !important;
                padding-right: 15px !important;
                width: 100% !important;
                max-width: 100% !important;
                box-sizing: border-box !important;
                margin: 0 auto !important;
            }
            .details-hero {
                padding: 60px 0 20px !important;
                min-height: auto !important;
                text-align: center !important;
                display: block !important;
            }
            .details-hero .container > div {
                text-align: center !important;
                width: 100% !important;
            }
            .details-hero h1 {
                font-size: 1.5rem !important;
                margin: 5px auto !important;
                line-height: 1.2 !important;
                letter-spacing: -0.5px !important;
            }
            .details-hero p {
                font-size: 0.8rem !important;
                margin: 2px auto !important;
                max-width: 300px !important;
                opacity: 0.8;
            }
            .details-grid {
                display: flex !important;
                flex-direction: column !important;
                margin-top: 10px !important;
                padding: 0 !important;
                gap: 15px !important;
                width: 100% !important;
                position: static !important;
            }
            .details-grid > div {
                width: 100% !important;
                max-width: 100% !important;
                margin: 0 !important;
                padding: 0 !important;
            }
            /* Fixed Aspect Ratio Container for Carousel */
            .swiper {
                height: 250px !important;
                padding-bottom: 0 !important;
                border-radius: 16px !important;
                width: 100% !important;
                margin: 0 auto !important;
                background: #ffffff;
                position: relative;
            }
            .swiper-wrapper {
                position: relative;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
            }
            .swiper-slide {
                width: 100% !important;
                height: 100% !important;
                display: flex;
                align-items: center;
                justify-content: center;
                background: #ffffff;
            }
            .swiper-slide img {
                width: 100% !important;
                height: 100% !important;
                max-height: none !important;
                object-fit: cover !important;
                margin: 0 !important;
            }
            .auth-card {
                width: 100% !important;
                max-width: 100% !important;
                box-sizing: border-box !important;
                margin: 0 !important;
                padding: 20px !important;
                border-radius: 20px !important;
            }
            .sticky-book {
                position: relative !important;
                top: 0 !important;
                width: 100% !important;
            }
            .sticky-book .auth-card {
                padding: 20px !important;
            }
            .spec-grid {
                grid-template-columns: 1fr 1fr !important;
                gap: 10px !important;
            }
            .spec-item {
                padding: 10px !important;
            }
            .features-list-grid {
                grid-template-columns: 1fr 1fr !important;
                gap: 8px !important;
            }
        }
        
        .swiper-button-next, .swiper-button-prev {
            color: white;
            background: rgba(30, 30, 35, 0.5);
            width: 40px;
            height: 40px;
            border-radius: 50%;
            transition: all 0.3s;
        }
        .swiper-button-next:after, .swiper-button-prev:after {
            font-size: 1.2rem;
        }
        .swiper-button-next:hover, .swiper-button-prev:hover {
            background: var(--accent-color);
        }
        .swiper-pagination-bullet {
            background: white;
            opacity: 0.5;
        }
        .swiper-pagination-bullet-active {
            background: var(--accent-color);
            opacity: 1;
        }

        .video-tour-container {
            margin-top: 30px;
            background: rgba(30, 30, 35, 0.6);
            border-radius: 20px;
            overflow: hidden;
            border: 1px solid rgba(255,255,255,0.05);
            position: relative;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .video-tour-header {
            padding: 20px;
            background: rgba(255,255,255,0.03);
            display: flex;
            align-items: center;
            justify-content: space-between;
            cursor: pointer;
            transition: background 0.3s;
        }
        .video-tour-header:hover {
            background: rgba(255,255,255,0.06);
        }
        .video-tour-header h3 {
            font-size: 1.1rem;
            color: white;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .video-tour-header .chevron {
            font-size: 0.9rem;
            color: rgba(255,255,255,0.4);
            transition: transform 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .video-tour-container.active .chevron {
            transform: rotate(180deg);
        }
        .video-tour-content {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .video-tour-container.active .video-tour-content {
            max-height: 800px; /* Large enough to hold the video aspect ratio */
            border-top: 1px solid rgba(255,255,255,0.05);
        }
        .video-tour-wrapper {
            position: relative;
            width: 100%;
            padding-top: 56.25%; /* 16:9 Aspect Ratio */
        }
        .video-tour-wrapper video {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .video-overlay-play {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 70px;
            height: 70px;
            background: var(--accent-color);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.5rem;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 0 30px rgba(245, 158, 11, 0.4);
            z-index: 5;
        }
        .video-overlay-play:hover {
            transform: translate(-50%, -50%) scale(1.1);
            background: #ffffff;
            color: var(--accent-color);
        }
        .video-controls-bottom {
            position: absolute;
            bottom: 20px;
            right: 20px;
            display: flex;
            gap: 15px;
            z-index: 10;
            opacity: 0;
            transition: all 0.3s ease;
        }
        .video-tour-wrapper:hover .video-controls-bottom {
            opacity: 1;
        }
        .video-control-btn {
            width: 45px;
            height: 45px;
            background: rgba(30, 30, 35, 0.7);
            backdrop-filter: blur(5px);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            cursor: pointer;
            border: 1px solid rgba(255,255,255,0.1);
            transition: all 0.3s ease;
        }
        .video-control-btn:hover {
            background: var(--accent-color);
            transform: scale(1.1);
        }
    </style>
    <link rel="stylesheet" href="public/css/notification.css">
</head>
<body class="stabilized-car-bg">
    <!-- Header -->


    <header id="mainHeader" class="header-solid">
        <div class="container" style="display: flex; justify-content: space-between; align-items: center;">
            <a href="index.php" class="logo">Car Hire</a>
            <ul class="nav-links">
                <li><a href="index.php"><?php echo __('welcome'); ?></a></li>
                <li><a href="our-fleet.php" class="active"><?php echo __('search_title'); ?></a></li>
                <li><a href="index.php#about">Features</a></li>
            </ul>
            <div class="auth-buttons" style="display: flex; gap: 15px; align-items: center;">
                <!-- Theme Switcher -->
                <?php include_once 'includes/theme_switcher.php'; ?>

                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="portal-customer/dashboard.php" class="btn btn-primary" style="padding: 0.6rem 1.2rem;"><?php echo __('profile'); ?></a>
                <?php else: ?>
                    <a href="login.php" class="btn btn-outline" style="border:none; color: white;"><?php echo __('login'); ?></a>
                    <a href="register.php" class="btn btn-primary" style="padding: 0.6rem 1.5rem;"><?php echo __('register'); ?></a>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <!-- Floating Trip Context Pill -->


    <section class="details-hero">
        <div class="container">
            <a href="our-fleet.php" style="color: rgba(255,255,255,0.6); text-decoration: none; display: inline-flex; align-items: center; gap: 10px; margin-bottom: 20px;">
                <i class="fas fa-arrow-left"></i> Back to Fleet
            </a>
            <div style="display: flex; align-items: center; gap: 15px;">
                <span style="color: var(--accent-color); font-weight: 700; font-size: 0.8rem; text-transform: uppercase; letter-spacing: 2px;"><?php echo $vehicle['brand_name']; ?></span>
            </div>
            <h1 style="color: white; font-size: 3rem; font-weight: 900; margin-top: 5px;"><?php echo $vehicle['make'] . ' ' . $vehicle['model']; ?></h1>
        </div>
    </section>

    <main class="container" style="padding-bottom: 100px;">
        <div class="details-grid">
            <div>
                <!-- Swiper Carousel -->
                <div class="swiper" style="position: relative;">
                    <div class="swiper-wrapper">
                        <?php foreach($vehicle_images as $img): 
                            $has_img = !empty($img['image_url']);
                        ?>
                            <div class="swiper-slide">
                                <span class="view-badge"><?php echo $img['view_type']; ?></span>
                                <?php if($has_img): ?>
                                    <img src="<?php echo htmlspecialchars($img['image_url']); ?>" 
                                         alt="Vehicle View" 
                                         style="display: block;"
                                         onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                <?php endif; ?>
                                
                                <div class="no-image-placeholder" style="display: <?php echo $has_img ? 'none' : 'flex'; ?>;">
                                    <i class="fas fa-car-side"></i>
                                    <span style="font-size: 0.9rem; font-weight: 600;">Image Unavailable</span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="swiper-pagination"></div>
                    <div class="swiper-button-next"></div>
                    <div class="swiper-button-prev"></div>
                </div>

                <?php if(!empty($vehicle['video_url'])): ?>
                    <div class="video-tour-container" id="videoTour">
                        <div class="video-tour-header" onclick="toggleVideoDropdown()">
                            <div>
                                <h3><i class="fas fa-play-circle" style="color: var(--accent-color);"></i> Cinema Showcase</h3>
                                <p style="font-size: 0.75rem; color: rgba(255,255,255,0.4); margin: 5px 0 0 32px; text-transform: uppercase; letter-spacing: 1px;">Virtual Tour Available</p>
                            </div>
                            <i class="fas fa-chevron-down chevron"></i>
                        </div>
                        <div class="video-tour-content">
                            <div class="video-tour-wrapper" id="videoWrapper">
                                <video id="carVideo" src="<?php echo htmlspecialchars($vehicle['video_url']); ?>" poster="<?php echo !empty($vehicle['image_url']) ? htmlspecialchars($vehicle['image_url']) : ''; ?>" playsinline muted loop onclick="togglePlay()"></video>
                                <div class="video-overlay-play" id="playBtn" onclick="togglePlay()">
                                    <i class="fas fa-play"></i>
                                </div>
                                <div class="video-controls-bottom">
                                    <div class="video-control-btn" onclick="toggleFullscreen(event)" title="Full Screen">
                                        <i class="fas fa-expand"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <script>
                        function toggleVideoDropdown() {
                            const container = document.getElementById('videoTour');
                            const video = document.getElementById('carVideo');
                            
                            if (!container) return;
                            
                            container.classList.toggle('active');
                            
                            // Force scroll into view if opening
                            if (container.classList.contains('active')) {
                                setTimeout(() => {
                                    container.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                                }, 300);
                            }
                            
                            // Sync video playback
                            if (!container.classList.contains('active') && video) {
                                video.pause();
                            }
                        }

                        function togglePlay() {
                            const video = document.getElementById('carVideo');
                            const btn = document.getElementById('playBtn');
                            if (!video) return;
                            
                            if (video.paused) {
                                video.play().then(() => {
                                    video.muted = false;
                                    if (btn) {
                                        btn.style.opacity = '0';
                                        btn.style.transform = 'translate(-50%, -50%) scale(1.5)';
                                        btn.style.pointerEvents = 'none';
                                    }
                                }).catch(err => {
                                    console.log("Autoplay blocked or video issue:", err);
                                    video.muted = true;
                                    video.play();
                                });
                            } else {
                                video.pause();
                                if (btn) {
                                    btn.style.opacity = '1';
                                    btn.style.transform = 'translate(-50%, -50%) scale(1)';
                                    btn.style.pointerEvents = 'all';
                                }
                            }
                        }

                        // Use a dedicated listener for better sync
                        document.addEventListener('DOMContentLoaded', () => {
                            const vRef = document.getElementById('carVideo');
                            const bRef = document.getElementById('playBtn');
                            
                            if (vRef && bRef) {
                                vRef.addEventListener('play', () => {
                                    bRef.style.opacity = '0';
                                    bRef.style.pointerEvents = 'none';
                                });
                                
                                vRef.addEventListener('pause', () => {
                                    bRef.style.opacity = '1';
                                    bRef.style.pointerEvents = 'all';
                                });
                            }
                        });

                        function toggleFullscreen(e) {
                            if(e) e.stopPropagation();
                            const video = document.getElementById('carVideo');
                            if (!video) return;
                            
                            if (video.requestFullscreen) {
                                video.requestFullscreen();
                            } else if (video.webkitRequestFullscreen) {
                                video.webkitRequestFullscreen();
                            } else if (video.msRequestFullscreen) {
                                video.msRequestFullscreen();
                            }
                        }
                    </script>
                <?php endif; ?>
                
                <div class="auth-card" style="margin-top: 30px; max-width: 100%; padding: 25px; border: 1px solid rgba(255,255,255,0.05);">
                    <h3 style="color: white; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; font-size: 1.1rem;">
                        <i class="fas fa-check-double" style="color: var(--accent-color);"></i>
                        Master Features
                    </h3>
                    <div class="features-list-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 12px;">
                        <?php 
                        $features = explode(',', $vehicle['features']);
                        foreach($features as $f): 
                            if(empty($f)) continue;
                        ?>
                            <div class="feature-item" style="display: flex; align-items: center; gap: 8px; color: rgba(255,255,255,0.6); font-size: 0.85rem;">
                                <i class="fas fa-check" style="color: #10b981; font-size: 0.7rem;"></i>
                                <span><?php echo trim($f); ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>


            <div class="sticky-book">
                <div class="auth-card" style="max-width: 100%; border-radius: 24px; padding: 35px; border: 1px solid var(--accent-vibrant);">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
                        <div>
                            <span style="color: rgba(255,255,255,0.5); font-size: 0.8rem;">Daily Rate</span>
                            <div style="color: white; font-size: 2rem; font-weight: 900;">K<?php echo number_format($vehicle['price_per_day'], 0); ?></div>
                        </div>
                        <?php if($is_available): ?>
                            <span class="status-badge" style="background: #10b981; color: white; padding: 5px 15px; border-radius: 50px; font-weight: 700; font-size: 0.7rem;">AVAILABLE</span>
                        <?php else: ?>
                            <span class="status-badge" style="background: #ef4444; color: white; padding: 5px 15px; border-radius: 50px; font-weight: 700; font-size: 0.7rem;">UNAVAILABLE</span>
                        <?php endif; ?>
                    </div>

                    <div class="form-group" style="margin-bottom: 20px;">
                        <label style="color: rgba(255,255,255,0.7); font-size: 0.8rem; margin-bottom: 8px; display: block;">Select Pickup Location</label>
                        <div style="position: relative;">
                            <i class="fas fa-map-marker-alt" style="position: absolute; left: 15px; top: 15px; color: var(--accent-vibrant);"></i>
                            <select name="pickup_location" id="detail_location" class="premium-select" style="padding-left: 45px; width: 100%; height: 50px; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); border-radius: 12px; color: white;">
                                <option value="Lusaka" <?php echo $pickup_location == 'Lusaka' ? 'selected' : ''; ?>>Lusaka (Central Branch)</option>
                                <option value="Livingstone" <?php echo $pickup_location == 'Livingstone' ? 'selected' : ''; ?>>Livingstone (Airport Hub)</option>
                                <option value="Ndola" <?php echo $pickup_location == 'Ndola' ? 'selected' : ''; ?>>Ndola (Copperbelt Branch)</option>
                            </select>
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 20px;">
                        <div class="form-group">
                            <label style="color: rgba(255,255,255,0.7); font-size: 0.8rem; margin-bottom: 8px; display: block;">Pickup Date</label>
                            <input type="date" id="p_date" name="pickup_date" value="<?php echo htmlspecialchars($pickup_date); ?>" min="<?php echo date('Y-m-d'); ?>" style="width: 100%; height: 45px; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); border-radius: 10px; color: white; padding: 0 10px;">
                        </div>
                        <div class="form-group">
                            <label style="color: rgba(255,255,255,0.7); font-size: 0.8rem; margin-bottom: 8px; display: block;">Pickup Time</label>
                            <input type="time" id="p_time" name="pickup_time" value="<?php echo htmlspecialchars($pickup_time); ?>" style="width: 100%; height: 45px; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); border-radius: 10px; color: white; padding: 0 10px;">
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 25px;">
                        <div class="form-group">
                            <label style="color: rgba(255,255,255,0.7); font-size: 0.8rem; margin-bottom: 8px; display: block;">Return Date</label>
                            <input type="date" id="d_date" name="dropoff_date" value="<?php echo htmlspecialchars($dropoff_date); ?>" min="<?php echo date('Y-m-d'); ?>" style="width: 100%; height: 45px; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); border-radius: 10px; color: white; padding: 0 10px;">
                        </div>
                        <div class="form-group">
                            <label style="color: rgba(255,255,255,0.7); font-size: 0.8rem; margin-bottom: 8px; display: block;">Return Time</label>
                            <input type="time" id="d_time" name="dropoff_time" value="<?php echo htmlspecialchars($dropoff_time); ?>" style="width: 100%; height: 45px; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); border-radius: 10px; color: white; padding: 0 10px;">
                        </div>
                    </div>

                    <div class="spec-grid" style="margin-bottom: 25px; grid-template-columns: 1fr 1fr;">
                        <div class="spec-item" style="padding: 10px; background: rgba(255,255,255,0.02); border-radius: 12px;">
                            <i class="fas fa-users" style="font-size: 0.9rem;"></i>
                            <div>
                                <span class="spec-label" style="font-size: 0.65rem;">Capacity</span>
                                <span class="spec-value" style="font-size: 0.8rem;"><?php echo $vehicle['capacity']; ?> Pers</span>
                            </div>
                        </div>
                        <div class="spec-item" style="padding: 10px; background: rgba(255,255,255,0.02); border-radius: 12px;">
                            <i class="fas fa-cog" style="font-size: 0.9rem;"></i>
                            <div>
                                <span class="spec-label" style="font-size: 0.65rem;">Trans</span>
                                <span class="spec-value" style="font-size: 0.8rem;">Auto</span>
                            </div>
                        </div>
                    </div>

                    <?php 
                    // Initial Booking Parameters
                    $bookParams = [
                        'vehicle_id' => $vehicle['id'],
                        'location' => $pickup_location,
                        'pickup_date' => $pickup_date,
                        'pickup_time' => $pickup_time,
                        'dropoff_date' => $dropoff_date,
                        'dropoff_time' => $dropoff_time
                    ];
                    $bookUrl = "portal-customer/booking-form.php?" . http_build_query($bookParams);
                    $encodedBookUrl = urlencode($bookUrl);
                    ?>

                    <?php if(!$is_available): ?>
                        <div style="background: rgba(239, 68, 68, 0.1); border: 1px dashed rgba(239, 68, 68, 0.4); padding: 15px; border-radius: 12px; margin-bottom: 15px; text-align: center;">
                            <i class="fas fa-exclamation-circle" style="color: #ef4444; margin-bottom: 5px; font-size: 1.2rem;"></i>
                            <p style="color: #fca5a5; font-size: 0.85rem; font-weight: 600; margin: 0;"><?php echo $availability_msg; ?></p>
                        </div>
                        <button disabled class="btn btn-primary" style="width: 100%; padding: 18px; font-size: 1.1rem; font-weight: 800; background: rgba(255,255,255,0.1); color: rgba(255,255,255,0.3); border: none; cursor: not-allowed;">Currently Unavailable</button>
                    <?php else: ?>
                        <?php if(isset($_SESSION['user_id'])): ?>
                            <a href="<?php echo $bookUrl; ?>" id="bookBtn" class="btn btn-primary" style="width: 100%; padding: 18px; font-size: 1.1rem; font-weight: 800; background: var(--accent-vibrant); border: none; box-shadow: 0 10px 20px rgba(245, 158, 11, 0.2);">Rent This Car Now</a>
                        <?php else: ?>
                            <a href="login.php?msg=not_logged_in&return_url=<?php echo $encodedBookUrl; ?>" id="bookBtn" class="btn btn-primary" style="width: 100%; padding: 18px; font-size: 1.1rem; font-weight: 800; background: var(--accent-vibrant); border: none;">Sign In to Rent</a>
                        <?php endif; ?>
                    <?php endif; ?>

                    <div style="text-align: center; margin-top: 20px;">
                        <p style="color: rgba(255,255,255,0.4); font-size: 0.8rem;"><i class="fas fa-shield-alt"></i> 100% Secure Booking Guarantee</p>
                    </div>
                </div>

                <!-- Multi-Car Promo Below Booking Card -->
                <div style="margin-top: 20px; background: rgba(255, 255, 255, 0.03); border: 1px dashed rgba(255, 255, 255, 0.1); border-radius: 16px; padding: 20px; text-align: center;">
                    <i class="fas fa-car-side" style="color: var(--accent-color); font-size: 1.5rem; margin-bottom: 10px; display: block;"></i>
                    <h4 style="color: white; font-size: 0.95rem; margin-bottom: 5px;">Planning an Event?</h4>
                    <p style="color: rgba(255,255,255,0.6); font-size: 0.8rem; margin-bottom: 15px; line-height: 1.4;">Need multiple vehicles for a wedding, corporate event, or group trip?</p>
                    <a href="portal-customer/event-booking.php" style="display: inline-block; padding: 10px 20px; background: transparent; border: 1px solid var(--accent-color); color: var(--accent-color); border-radius: 8px; font-size: 0.8rem; font-weight: 700; text-decoration: none; transition: 0.3s;">Request a Fleet</a>
                </div>
            </div>
        </div>
    </main>



    <?php include_once 'includes/mobile_nav.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>
    <script>
        const swiper = new Swiper('.swiper', {
            loop: true,
            pagination: {
                el: '.swiper-pagination',
                clickable: true,
            },
            navigation: {
                nextEl: '.swiper-button-next',
                prevEl: '.swiper-button-prev',
            },
            autoplay: {
                delay: 5000,
                disableOnInteraction: false,
            },
        });

        // Comprehensive Dynamic Link Update
        document.addEventListener('DOMContentLoaded', function() {
            const inputs = {
                location: document.getElementById('detail_location'),
                pickup_date: document.getElementById('p_date'),
                pickup_time: document.getElementById('p_time'),
                dropoff_date: document.getElementById('d_date'),
                dropoff_time: document.getElementById('d_time')
            };
            const bookBtn = document.getElementById('bookBtn');
            
            if(bookBtn) {
                const updateUrl = () => {
                    try {
                        let currentUrl = bookBtn.getAttribute('href');
                        let isLoginRedirect = currentUrl.includes('return_url=');
                        
                        let baseUrl, targetUrl;
                        if(isLoginRedirect) {
                            let parts = currentUrl.split('return_url=');
                            baseUrl = parts[0] + 'return_url=';
                            targetUrl = decodeURIComponent(parts[1]);
                        } else {
                            baseUrl = '';
                            targetUrl = currentUrl;
                        }

                        // Split path and query to preserve relative paths correctly
                        let urlParts = targetUrl.split('?');
                        let path = urlParts[0];
                        let params = new URLSearchParams(urlParts[1] || '');
                        
                        // Update all params from inputs
                        for(let key in inputs) {
                            if(inputs[key] && inputs[key].value) {
                                let paramKey = key === 'location' ? 'location' : key;
                                params.set(paramKey, inputs[key].value);
                            }
                        }

                        let finalTarget = path + '?' + params.toString();
                        bookBtn.href = isLoginRedirect ? baseUrl + encodeURIComponent(finalTarget) : finalTarget;
                        
                    } catch(e) { console.error("Dynamic Sync Failed", e); }
                };

                // Attach listeners to all inputs
                Object.values(inputs).forEach(input => {
                    if(input) input.addEventListener('change', updateUrl);
                });
            }
        });
    </script>
</body>
</html>

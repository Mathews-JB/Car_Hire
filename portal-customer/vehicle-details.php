<?php
// Aggressive Cache Busting
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

include_once '../includes/db.php';
include_once '../includes/functions.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'customer') {
    header("Location: ../login.php");
    exit;
}

$id = $_GET['id'] ?? '';

if (empty($id)) {
    header("Location: browse-vehicles.php");
    exit;
}

// Fetch vehicle details
$stmt = $pdo->prepare("SELECT v.*, b.name as brand_name FROM vehicles v 
                       LEFT JOIN brands b ON v.brand_id = b.id 
                       WHERE v.id = ?");
$stmt->execute([$id]);
$vehicle = $stmt->fetch();

if (!$vehicle) {
    header("Location: browse-vehicles.php");
    exit;
}

// Fetch vehicle images
$stmt = $pdo->prepare("SELECT * FROM vehicle_images WHERE vehicle_id = ? ORDER BY is_primary DESC, id ASC");
$stmt->execute([$id]);
$vehicle_images = $stmt->fetchAll();

// If no images in new table, fallback
if (empty($vehicle_images)) {
    $vehicle_images[] = ['image_url' => $vehicle['image_url'], 'view_type' => 'exterior'];
    if (!empty($vehicle['interior_image_url'])) {
        $vehicle_images[] = ['image_url' => $vehicle['interior_image_url'], 'view_type' => 'interior'];
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $vehicle['make'] . ' ' . $vehicle['model']; ?> | Customer Portal</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" />
    <link rel="stylesheet" href="../public/css/style.css">
    <style>
        .details-grid {
            display: grid;
            grid-template-columns: 1fr 400px;
            gap: 30px;
            margin-top: 20px;
            max-width: 1400px;
            margin-left: auto;
            margin-right: auto;
        }
        .swiper {
            width: 100%;
            max-width: 800px;
            height: 450px;
            border-radius: 20px;
            overflow: hidden;
            background: #ffffff;
            position: relative;
            box-shadow: 0 15px 35px rgba(0,0,0,0.4);
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
            background: #ffffff;
            flex-shrink: 0;
        }

        .swiper-slide img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
            background: #ffffff;
        }


        .view-badge {
            position: absolute;
            top: 20px;
            left: 20px;
            background: rgba(0,0,0,0.6);
            backdrop-filter: blur(5px);
            padding: 5px 15px;
            border-radius: 50px;
            color: white;
            font-size: 0.7rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            z-index: 10;
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
            color: var(--primary-color);
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

        /* Video Showcase Styles */
        .video-tour-container {
            margin-top: 25px;
            background: rgba(15, 23, 42, 0.6);
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
            max-height: 800px;
            border-top: 1px solid rgba(255,255,255,0.05);
        }
        .video-tour-wrapper {
            position: relative;
            width: 100%;
            padding-top: 56.25%;
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
            width: 60px;
            height: 60px;
            background: var(--accent-vibrant);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.2rem;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 0 30px rgba(245, 158, 11, 0.4);
            z-index: 5;
        }
        .video-controls-bottom {
            position: absolute;
            bottom: 15px;
            right: 15px;
            display: flex;
            gap: 10px;
            z-index: 10;
            opacity: 0;
            transition: all 0.3s ease;
        }
        .video-tour-wrapper:hover .video-controls-bottom {
            opacity: 1;
        }
        .video-control-btn {
            width: 40px;
            height: 40px;
            background: rgba(15, 23, 42, 0.7);
            backdrop-filter: blur(5px);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            cursor: pointer;
            border: 1px solid rgba(255,255,255,0.1);
        }
        @media (max-width: 768px) {
            html, body {
                overflow-x: hidden !important;
                width: 100% !important;
                position: relative !important;
            }
            .container {
                padding: 0 12px !important;
                width: 100% !important;
                max-width: 100% !important;
                box-sizing: border-box !important;
                margin: 0 auto !important;
            }
            .portal-content {
                padding: 10px 0 !important;
                width: 100% !important;
            }
            .dashboard-header {
                text-align: center !important;
                margin-bottom: 15px !important;
            }
            .dashboard-header h1 {
                font-size: 1.5rem !important;
                margin: 5px auto !important;
            }
            .details-grid {
                display: flex !important;
                flex-direction: column !important;
                margin-top: 10px !important;
                padding: 0 !important;
                gap: 20px !important;
                width: 100% !important;
                position: static !important;
            }
            .details-grid > div {
                width: 100% !important;
                max-width: 100% !important;
                margin: 0 !important;
                padding: 0 !important;
            }
            .swiper {
                height: 0 !important;
                padding-bottom: 56.25% !important; /* 16:9 Aspect Ratio */
                border-radius: 12px !important;
                width: 100% !important;
                margin: 0 auto !important;
                background: #ffffff;
            }
            .swiper-wrapper {
                position: absolute;
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
                max-width: none !important;
                object-fit: cover !important;
                margin: 0 !important;
            }
            .auth-card {
                width: 100% !important;
                max-width: 100% !important;
                box-sizing: border-box !important;
                margin: 10px 0 !important;
                padding: 15px !important;
            }
            .features-list-grid {
                grid-template-columns: 1fr 1fr !important;
                gap: 8px !important;
            }
            .sticky-book {
                position: relative !important;
                top: 0 !important;
                width: 100% !important;
            }
            .spec-grid {
                grid-template-columns: 1fr 1fr !important;
                gap: 10px !important;
            }
        }
        
        .swiper-button-next, .swiper-button-prev {
            color: white;
            background: rgba(0,0,0,0.5);
            width: 35px;
            height: 35px;
            border-radius: 50%;
        }
        .swiper-button-next:after, .swiper-button-prev:after {
            font-size: 1rem;
        }
        .swiper-pagination-bullet {
            background: white;
        }
    </style>
</head>
<body>

    <nav class="hub-bar">
        <a href="dashboard.php" class="logo">Car Hire</a>
        <div class="hub-nav">
            <a href="dashboard.php">Dashboard</a>
            <a href="browse-vehicles.php" class="active">Browse Fleet</a>
            <a href="my-bookings.php">My Bookings</a>
            <a href="support.php">Support</a>
            <a href="profile.php">Profile</a>
        </div>
        <div class="hub-user">
            <?php 
                $display_name = $_SESSION['user_name'] ?? 'User';
                $initial = !empty($display_name) ? strtoupper($display_name[0]) : 'U';
            ?>
            <div class="hub-avatar"><?php echo htmlspecialchars($initial); ?></div>
        </div>
    </nav>

    <div class="portal-content">
        <div class="container">
            <div class="dashboard-header">
                <div>
                    <a href="browse-vehicles.php" style="color: rgba(255,255,255,0.5); text-decoration: none; font-size: 0.9rem; display: inline-flex; align-items: center; gap: 8px; margin-bottom: 10px;">
                        <i class="fas fa-arrow-left"></i> Back to Fleet
                    </a>
                    <div style="display: flex; align-items: center; gap: 15px;">
                        <h1 style="font-weight: 900; font-size: 2.2rem; letter-spacing: -1px; margin: 0;"><?php echo $vehicle['brand_name'] . ' ' . $vehicle['make'] . ' ' . $vehicle['model']; ?></h1>
                    </div>
                    <p style="color: rgba(255,255,255,0.6);">Review technical specifications and features.</p>
                </div>
            </div>

            <div class="details-grid">
                <div>
                    <!-- Swiper Carousel -->
                    <div class="swiper">
                        <div class="swiper-wrapper">
                            <?php foreach($vehicle_images as $img): 
                                $path = strpos($img['image_url'], 'http') === 0 ? $img['image_url'] : '../'.$img['image_url'];
                            ?>
                                <div class="swiper-slide">
                                    <span class="view-badge"><?php echo $img['view_type']; ?></span>
                                    <img src="<?php echo htmlspecialchars($path); ?>" alt="Vehicle View">
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
                                    <h3><i class="fas fa-play-circle" style="color: #ff9d00;"></i> Cinema Showcase</h3>
                                    <p style="font-size: 0.7rem; color: rgba(255,255,255,0.4); margin: 5px 0 0 32px; text-transform: uppercase;">Premium Virtual Tour</p>
                                </div>
                                <i class="fas fa-chevron-down chevron"></i>
                            </div>
                            <div class="video-tour-content">
                                <div class="video-tour-wrapper">
                                    <video id="carVideo" src="<?php echo htmlspecialchars($vehicle['video_url']); ?>" playsinline muted loop onclick="togglePlay()"></video>
                                    <div class="video-overlay-play" id="playBtn" onclick="togglePlay()">
                                        <i class="fas fa-play"></i>
                                    </div>
                                    <div class="video-controls-bottom">
                                        <div class="video-control-btn" onclick="toggleFullscreen(event)">
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
                                if (container.classList.contains('active')) {
                                    setTimeout(() => { container.scrollIntoView({ behavior: 'smooth', block: 'nearest' }); }, 300);
                                } else if (video) {
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
                                        if (btn) btn.style.opacity = '0';
                                    }).catch(() => {
                                        video.muted = true;
                                        video.play();
                                    });
                                } else {
                                    video.pause();
                                    if (btn) btn.style.opacity = '1';
                                }
                            }

                            function toggleFullscreen(e) {
                                if(e) e.stopPropagation();
                                const video = document.getElementById('carVideo');
                                if (!video) return;
                                if (video.requestFullscreen) video.requestFullscreen();
                                else if (video.webkitRequestFullscreen) video.webkitRequestFullscreen();
                            }

                            document.addEventListener('DOMContentLoaded', () => {
                                const vRef = document.getElementById('carVideo');
                                const bRef = document.getElementById('playBtn');
                                if (vRef && bRef) {
                                    vRef.addEventListener('play', () => bRef.style.opacity = '0');
                                    vRef.addEventListener('pause', () => bRef.style.opacity = '1');
                                }
                            });
                        </script>
                    <?php endif; ?>
                    
                <div class="auth-card" style="margin-top: 25px; padding: 25px; border: 1px solid rgba(255,255,255,0.05);">
                    <h3 style="color: white; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; font-size: 1.1rem;">
                        <i class="fas fa-check-double" style="color: var(--accent-color);"></i>
                        Master Features
                    </h3>
                    <div class="features-list-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(140px, 1fr)); gap: 12px bridge-missing-class;">
                        <?php 
                        $features = explode(',', $vehicle['features']);
                        foreach($features as $f): 
                            if(empty($f)) continue;
                        ?>
                            <div class="feature-item" style="display: flex; align-items: center; gap: 8px; color: rgba(255,255,255,0.6); font-size: 0.8rem;">
                                <i class="fas fa-check" style="color: #10b981; font-size: 0.7rem;"></i>
                                <span style="white-space: nowrap;"><?php echo trim($f); ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                </div>


                <div class="sticky-book">
                    <div class="auth-card" style="max-width: 100%; border-radius: 20px; padding: 30px; border-top: 4px solid var(--primary-color);">
                        <div style="margin-bottom: 25px;">
                            <span style="color: rgba(255,255,255,0.5); font-size: 0.8rem; font-weight: 600;">RENTAL PRICE</span>
                            <div style="color: white; font-size: 1.8rem; font-weight: 900;">ZMW <?php echo number_format($vehicle['price_per_day'], 2); ?> <span style="font-size: 0.9rem; color: rgba(255,255,255,0.4);">/ day</span></div>
                        </div>

                        <div class="spec-grid">
                            <div class="spec-item">
                                <i class="fas fa-users"></i>
                                <div>
                                    <span class="spec-label">Seats</span>
                                    <span class="spec-value"><?php echo $vehicle['capacity']; ?> Space</span>
                                </div>
                            </div>
                            <div class="spec-item">
                                <i class="fas fa-gas-pump"></i>
                                <div>
                                    <span class="spec-label">Fuel</span>
                                    <span class="spec-value"><?php echo $vehicle['fuel_type']; ?></span>
                                </div>
                            </div>
                            <div class="spec-item">
                                <i class="fas fa-cog"></i>
                                <div>
                                    <span class="spec-label">Gear</span>
                                    <span class="spec-value">Automatic</span>
                                </div>
                            </div>
                            <div class="spec-item">
                                <i class="fas fa-hashtag"></i>
                                <div>
                                    <span class="spec-label">Year</span>
                                    <span class="spec-value"><?php echo $vehicle['year']; ?></span>
                                </div>
                            </div>
                        </div>

                        <!-- Date Selection Form -->
                        <form id="bookingDatesForm" style="margin-top: 25px; padding-top: 25px; border-top: 1px solid rgba(255,255,255,0.05);">
                            <h4 style="color: white; margin-bottom: 20px; font-size: 0.95rem; font-weight: 700; text-transform: uppercase; letter-spacing: 1px;">
                                <i class="fas fa-calendar-alt" style="color: var(--primary-color); margin-right: 8px;"></i>
                                Select Rental Period
                            </h4>
                            
                            <div style="margin-bottom: 15px;">
                                <label style="display: block; color: rgba(255,255,255,0.6); font-size: 0.8rem; margin-bottom: 8px; font-weight: 600;">
                                    <i class="fas fa-map-marker-alt" style="margin-right: 5px;"></i> Pickup Location
                                </label>
                                <select name="location" id="location" required class="premium-select" style="width: 100%; font-size: 0.9rem;">
                                    <option value="">Select Location</option>
                                    <option value="Lusaka">Lusaka (Central)</option>
                                    <option value="Livingstone">Livingstone (Tourism Hub)</option>
                                    <option value="Ndola">Ndola (Copperbelt)</option>
                                </select>
                            </div>

                            <div style="margin-bottom: 20px; display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                                <div>
                                    <label style="display: block; color: rgba(255,255,255,0.6); font-size: 0.8rem; margin-bottom: 8px; font-weight: 600;">
                                        <i class="fas fa-calendar-check" style="margin-right: 5px;"></i> Pickup Date
                                    </label>
                                    <input type="date" name="pickup_date" id="pickup_date" required min="<?php echo date('Y-m-d'); ?>" style="width: 100%; padding: 12px; border-radius: 8px; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); color: white; font-size: 0.9rem;">
                                </div>
                                <div>
                                    <label style="display: block; color: rgba(255,255,255,0.6); font-size: 0.8rem; margin-bottom: 8px; font-weight: 600;">
                                        <i class="fas fa-clock" style="margin-right: 5px;"></i> Pickup Time
                                    </label>
                                    <input type="time" name="pickup_time" id="pickup_time" required value="10:00" style="width: 100%; padding: 12px; border-radius: 8px; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); color: white; font-size: 0.9rem;">
                                </div>
                            </div>

                            <div style="margin-bottom: 25px; display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                                <div>
                                    <label style="display: block; color: rgba(255,255,255,0.6); font-size: 0.8rem; margin-bottom: 8px; font-weight: 600;">
                                        <i class="fas fa-calendar-times" style="margin-right: 5px;"></i> Drop-off Date
                                    </label>
                                    <input type="date" name="dropoff_date" id="dropoff_date" required min="<?php echo date('Y-m-d'); ?>" style="width: 100%; padding: 12px; border-radius: 8px; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); color: white; font-size: 0.9rem;">
                                </div>
                                <div>
                                    <label style="display: block; color: rgba(255,255,255,0.6); font-size: 0.8rem; margin-bottom: 8px; font-weight: 600;">
                                        <i class="fas fa-history" style="margin-right: 5px;"></i> Drop-off Time
                                    </label>
                                    <input type="time" name="dropoff_time" id="dropoff_time" required value="10:00" style="width: 100%; padding: 12px; border-radius: 8px; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); color: white; font-size: 0.9rem;">
                                </div>
                            </div>

                            <div id="pricePreview" style="background: rgba(59, 130, 246, 0.1); border: 1px solid rgba(59, 130, 246, 0.3); padding: 15px; border-radius: 8px; margin-bottom: 20px; display: none;">
                                <div style="display: flex; justify-content: space-between; align-items: center;">
                                    <span style="color: rgba(255,255,255,0.7); font-size: 0.85rem;">Estimated Total</span>
                                    <span id="estimatedPrice" style="color: white; font-weight: 800; font-size: 1.2rem;">ZMW 0.00</span>
                                </div>
                                <div style="color: rgba(255,255,255,0.5); font-size: 0.75rem; margin-top: 5px;">
                                    <span id="daysCount">0</span> days × ZMW <?php echo number_format($vehicle['price_per_day'], 2); ?>/day
                                </div>
                            </div>
                        </form>

                        <?php if(trim(strtolower($vehicle['status'])) === 'available'): ?>
                            <button onclick="proceedToBooking()" class="btn btn-primary" style="width: 100%; padding: 15px; font-weight: 800; font-size: 1.1rem; border-radius: 12px; box-shadow: 0 10px 20px rgba(59, 130, 246, 0.2);">Initialize Booking</button>
                        <?php else: ?>
                            <button class="btn btn-outline" style="width: 100%; padding: 15px; opacity: 0.5; cursor: not-allowed;" disabled>Currently Unavailable (<?php echo ucfirst($vehicle['status']); ?>)</button>
                        <?php endif; ?>

                        <script>
                            const dailyRate = <?php echo $vehicle['price_per_day']; ?>;
                            const vehicleId = <?php echo $vehicle['id']; ?>;

                            // Update price preview when dates/times change
                            ['pickup_date', 'dropoff_date', 'pickup_time', 'dropoff_time'].forEach(id => {
                                document.getElementById(id).addEventListener('change', updatePricePreview);
                            });

                            function updatePricePreview() {
                                const pickupDate = document.getElementById('pickup_date').value;
                                const pickupTime = document.getElementById('pickup_time').value;
                                const dropoffDate = document.getElementById('dropoff_date').value;
                                const dropoffTime = document.getElementById('dropoff_time').value;

                                if (pickupDate && dropoffDate) {
                                    const pickup = new Date(`${pickupDate}T${pickupTime || '00:00'}`);
                                    const dropoff = new Date(`${dropoffDate}T${dropoffTime || '00:00'}`);
                                    
                                    const diffTime = dropoff - pickup;
                                    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));

                                    if (diffDays > 0) {
                                        const total = diffDays * dailyRate;
                                        document.getElementById('daysCount').textContent = diffDays;
                                        document.getElementById('estimatedPrice').textContent = 'ZMW ' + total.toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,');
                                        document.getElementById('pricePreview').style.display = 'block';
                                    } else {
                                        document.getElementById('pricePreview').style.display = 'none';
                                    }
                                }
                            }

                            function proceedToBooking() {
                                const location = document.getElementById('location').value;
                                const pickupDate = document.getElementById('pickup_date').value;
                                const pickupTime = document.getElementById('pickup_time').value;
                                const dropoffDate = document.getElementById('dropoff_date').value;
                                const dropoffTime = document.getElementById('dropoff_time').value;

                                if (!location) { alert('Please select a pickup location'); return; }
                                if (!pickupDate || !pickupTime) { alert('Please select pickup date and time'); return; }
                                if (!dropoffDate || !dropoffTime) { alert('Please select drop-off date and time'); return; }

                                const pickup = new Date(`${pickupDate}T${pickupTime}`);
                                const dropoff = new Date(`${dropoffDate}T${dropoffTime}`);

                                if (dropoff <= pickup) {
                                    alert('Drop-off date/time must be after pickup date/time');
                                    return;
                                }

                                // Format for URL: YYYY-MM-DD HH:MM
                                const pStr = `${pickupDate} ${pickupTime}`;
                                const dStr = `${dropoffDate} ${dropoffTime}`;

                                const url = `booking-form.php?vehicle_id=${vehicleId}&pickup_date=${encodeURIComponent(pStr)}&dropoff_date=${encodeURIComponent(dStr)}&location=${encodeURIComponent(location)}`;
                                window.location.href = url;
                            }
                        </script>

                        <div style="margin-top: 20px; border-top: 1px solid rgba(255,255,255,0.05); padding-top: 20px; text-align: center;">
                            <p style="color: rgba(255,255,255,0.5); font-size: 0.8rem; line-height: 1.4;">
                                <i class="fas fa-check-circle" style="color: #10b981; margin-right: 5px;"></i> Insurance Included<br>
                                <i class="fas fa-check-circle" style="color: #10b981; margin-right: 5px;"></i> Free Cancellation up to 24h
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include_once '../includes/mobile_nav.php'; ?>

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

        // MOBILE FIX: Force proportions on mobile
        function applyMobileFixes() {
            const swiperEl = document.querySelector('.swiper');
            const pricingCard = document.querySelector('.sticky-book .auth-card');
            
            if (window.innerWidth <= 576) {
                // Force carousel height on mobile
                if (swiperEl) {
                    swiperEl.style.height = '160px';
                }
                
                // Force pricing card compact on mobile
                if (pricingCard) {
                    pricingCard.style.padding = '15px';
                    const priceDiv = pricingCard.querySelector('div[style*="font-size: 1.8rem"]');
                    if (priceDiv) priceDiv.style.fontSize = '1.4rem';
                }
            } else {
                // RESET FOR DESKTOP - This prevents the "thin image" bug when resizing
                if (swiperEl) {
                    swiperEl.style.height = ''; 
                }
                if (pricingCard) {
                    pricingCard.style.padding = '';
                    const priceDiv = pricingCard.querySelector('div[style*="font-size: 1.8rem"]');
                    if (priceDiv) priceDiv.style.fontSize = '';
                }
            }
        }
        
        applyMobileFixes();
        window.addEventListener('resize', applyMobileFixes);
        setTimeout(applyMobileFixes, 500); 
    </script>
</body>
</html>

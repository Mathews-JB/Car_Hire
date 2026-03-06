<?php
include_once 'includes/db.php';
include_once 'includes/functions.php';

// Aggressive Cache Busting
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Car Hire | Your Adventure Starts Here</title>
    <meta name="description" content="Rent a car in Zambia with Car Hire. Easy mobile money payments, wide range of vehicles, and 24/7 support.">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="public/css/style.css?v=2.7">
    <!-- PWA Manifest -->
    <link rel="manifest" href="manifest.json">
    <meta name="theme-color" content="#2563eb">
    <link rel="apple-touch-icon" href="public/images/icon-192x192.png">
</head>
<body>

    <!-- App Splash Preloader -->
    <div id="appSplash" class="app-splash">
        <div class="splash-logo-container">
            <img src="public/images/splash_logo.png" class="splash-logo-img" alt="Logo">
        </div>
        <div class="splash-loader-container">
            <div id="splashProgress" class="splash-progress-bar"></div>
        </div>
        <div class="splash-loading-text">Synchronizing Fleet...</div>
    </div>

    <!-- Header -->
    <header id="mainHeader">
        <div class="container" style="display: flex; justify-content: space-between; align-items: center;">
            <a href="index.php" class="logo">Car Hire</a>
            <ul class="nav-links">
                <li><a href="#home"><?php echo __('welcome'); ?></a></li>
                <li><a href="our-fleet.php"><?php echo __('search_title'); ?></a></li>
                <li><a href="#about">Features</a></li>
                <li><a href="#" onclick="openManual(); return false;" style="color: var(--accent-vibrant);"><i class="fas fa-book"></i> User Manual</a></li>
            </ul>
            <div class="auth-buttons" style="display: flex; gap: 15px; align-items: center;">
                <!-- Language Switcher -->
                <div class="lang-switcher" style="position: relative;">
                    <button class="btn btn-outline" style="border: 1px solid rgba(255,255,255,0.2); background: rgba(255,255,255,0.05); color: white; padding: 5px 12px; font-size: 0.8rem; border-radius: 8px; display: flex; align-items: center; gap: 8px;" onclick="document.getElementById('langDropdown').classList.toggle('show')">
                        <i class="fas fa-globe"></i> <span><?php echo strtoupper($current_lang); ?></span> <i class="fas fa-chevron-down" style="font-size: 0.7rem;"></i>
                    </button>
                    <div id="langDropdown" class="dropdown-content-lang" style="display: none; position: absolute; top: 40px; right: 0; background: rgba(30,30,35,0.95); backdrop-filter: blur(10px); border: 1px solid rgba(255,255,255,0.1); border-radius: 12px; width: 150px; z-index: 1000; box-shadow: 0 10px 25px rgba(0,0,0,0.5);">
                        <a href="?lang=en" style="display: block; padding: 10px 15px; color: white; text-decoration: none; font-size: 0.85rem; border-bottom: 1px solid rgba(255,255,255,0.05);">English</a>
                        <a href="?lang=bem" style="display: block; padding: 10px 15px; color: white; text-decoration: none; font-size: 0.85rem; border-bottom: 1px solid rgba(255,255,255,0.05);">Icibemba</a>
                        <a href="?lang=nya" style="display: block; padding: 10px 15px; color: white; text-decoration: none; font-size: 0.85rem; border-bottom: 1px solid rgba(255,255,255,0.05);">Chinyanja</a>
                        <a href="?lang=ton" style="display: block; padding: 10px 15px; color: white; text-decoration: none; font-size: 0.85rem;">Chitonga</a>
                    </div>
                </div>

                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="portal-customer/dashboard.php" class="btn btn-primary" style="padding: 0.6rem 1.2rem;"><?php echo __('profile'); ?></a>
                    <a href="logout.php" class="btn btn-outline" style="border:none; color: white;"><?php echo __('logout'); ?></a>
                <?php else: ?>
                    <a href="login.php" class="btn btn-outline" style="border:none; color: white;"><?php echo __('login'); ?></a>
                    <a href="register.php" class="btn btn-primary" style="padding: 0.6rem 1.5rem;"><?php echo __('register'); ?></a>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <!-- Hero Slider (5 Slides) -->
    <section class="hero-slider" id="home">
        <div class="slide active" style="background-image: url('public/images/hero_mercedes_s.png'); background-position: center bottom;">
            <div class="slide-content">
                <span style="color: var(--accent-color); font-weight: 700; text-transform: uppercase; letter-spacing: 3px;">Executive Collection</span>
                <h1>Drive Your Dream</h1>
                <p>Pure luxury meets Zambian roads. Experience the elite rental privilege.</p>
                <a href="#search" class="btn btn-primary btn-lg" style="margin-top: 20px;">Book Exclusive</a>
            </div>
        </div>
        <div class="slide" style="background-image: url('public/images/cars/defender.jpg');">
            <div class="slide-content">
                <span style="color: var(--accent-color); font-weight: 700; text-transform: uppercase; letter-spacing: 3px;">Adventure Awaits</span>
                <h1>Explore Beyond</h1>
                <p>Rugged capability for the untamed wild. The ultimate safari companion.</p>
                <a href="#search" class="btn btn-primary btn-lg" style="margin-top: 20px;">Start Journey</a>
            </div>
        </div>
        <div class="slide" style="background-image: url('public/images/cars/audi_q7.jpg');">
            <div class="slide-content">
                <span style="color: var(--accent-color); font-weight: 700; text-transform: uppercase; letter-spacing: 3px;">City Sleek</span>
                <h1>Urban Sophistication</h1>
                <p>Navigating the capital in style. Perfect for business and leisure.</p>
                <a href="#search" class="btn btn-primary btn-lg" style="margin-top: 20px;">Rent Now</a>
            </div>
        </div>
        <div class="slide" style="background-image: url('public/images/cars/g63.jpg');">
            <div class="slide-content">
                <span style="color: var(--accent-color); font-weight: 700; text-transform: uppercase; letter-spacing: 3px;">Unmatched Status</span>
                <h1>Powerful Presence</h1>
                <p>Iconic design, legendary performance. Command the road in a G-Wagon.</p>
                <a href="#search" class="btn btn-primary btn-lg" style="margin-top: 20px;">Experience G-Class</a>
            </div>
        </div>
        <div class="slide" style="background-image: url('public/images/cars/prado.jpg');">
            <div class="slide-content">
                <span style="color: var(--accent-color); font-weight: 700; text-transform: uppercase; letter-spacing: 3px;">Reliable Journeys</span>
                <h1>Always With You</h1>
                <p>Your peace of mind is our priority. Every kilometer, every hour.</p>
                <a href="#contact" class="btn btn-primary btn-lg" style="margin-top: 20px;">View Full Fleet</a>
            </div>
        </div>
    </section>

    <!-- Search Section -->
    <section class="container" id="search" style="position: relative; z-index: 50;">
        <div class="search-container data-card" id="mobile-search-adjust">
            <form action="our-fleet.php" method="GET" class="search-form">
                <div class="form-group">
                    <label>Where to pick up?</label>
                    <div style="position: relative;">
                        <i class="fas fa-map-marker-alt" style="position: absolute; left: 15px; top: 15px; color: var(--primary-color);"></i>
                        <select name="pickup_location" class="premium-select" style="padding-left: 45px; width: 100%; height: 50px; border: none;">
                            <option value="">Select Location</option>
                            <option value="Lusaka" <?php echo ($_GET['pickup_location'] ?? '') == 'Lusaka' ? 'selected' : ''; ?>>Lusaka (Central)</option>
                            <option value="Livingstone" <?php echo ($_GET['pickup_location'] ?? '') == 'Livingstone' ? 'selected' : ''; ?>>Livingstone (Tourism Hub)</option>
                            <option value="Ndola" <?php echo ($_GET['pickup_location'] ?? '') == 'Ndola' ? 'selected' : ''; ?>>Ndola (Copperbelt)</option>
                        </select>
                    </div>
                </div>
                <div class="form-group" style="display: grid; grid-template-columns: 1.5fr 1fr; gap: 10px;">
                    <div>
                        <label>Pickup Date</label>
                        <input type="date" name="pickup_date" value="<?php echo $_GET['pickup_date'] ?? ''; ?>" min="<?php echo date('Y-m-d'); ?>" style="width: 100%; background: #f8fafc; border: none; height: 50px; border-radius: 12px; padding: 0 15px;">
                    </div>
                    <div>
                        <label>Time</label>
                        <input type="time" name="pickup_time" value="<?php echo $_GET['pickup_time'] ?? '10:00'; ?>" style="width: 100%; background: #f8fafc; border: none; height: 50px; border-radius: 12px; padding: 0 15px;">
                    </div>
                </div>
                <div class="form-group" style="display: grid; grid-template-columns: 1.5fr 1fr; gap: 10px;">
                    <div>
                        <label>Drop-off Date</label>
                        <input type="date" name="dropoff_date" value="<?php echo $_GET['dropoff_date'] ?? ''; ?>" min="<?php echo date('Y-m-d'); ?>" style="width: 100%; background: #f8fafc; border: none; height: 50px; border-radius: 12px; padding: 0 15px;">
                    </div>
                    <div>
                        <label>Time</label>
                        <input type="time" name="dropoff_time" value="<?php echo $_GET['dropoff_time'] ?? '10:00'; ?>" style="width: 100%; background: #f8fafc; border: none; height: 50px; border-radius: 12px; padding: 0 15px;">
                    </div>
                </div>
                <div class="form-group" style="display: flex; align-items: flex-end;">
                    <button type="submit" class="btn btn-primary" style="height: 50px; width: 100%; border-radius: 12px; background: var(--accent-vibrant); font-weight: 700;">Update Results</button>
                </div>
            </form>
        </div>
    </section>

    <!-- Featured Fleet Section -->
    <section class="features container" id="fleet" style="padding: 40px 0;">
        <div class="section-title">
            <h2 style="font-weight: 900; letter-spacing: -1px;">Explore Our Exclusive Fleet</h2>
            <p>From luxury sedans to rugged 4x4s, find the perfect companion for your Zambian journey.</p>
        </div>

        <div style="text-align: center; margin-bottom: 30px;">
            <div class="features-grid" style="margin-bottom: 30px;">
                <?php
                $stmt = $pdo->query("SELECT v.*, b.name as brand_name FROM vehicles v 
                                  LEFT JOIN brands b ON v.brand_id = b.id 
                                  ORDER BY CASE WHEN LOWER(TRIM(v.status)) = 'available' THEN 0 ELSE 1 END, v.price_per_day DESC LIMIT 3");
                $featured = $stmt->fetchAll();
                
                foreach ($featured as $vehicle):
                ?>
                    <div class="feature-card fleet-card">
                        <img src="<?php echo htmlspecialchars($vehicle['image_url']); ?>" class="fleet-img" onerror="this.src='public/images/cars/default.jpg'">
                        <div class="fleet-info">
                            <h3 style="margin-bottom: 5px;"><?php echo htmlspecialchars($vehicle['make'] . ' ' . $vehicle['model']); ?></h3>
                            <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 10px;">
                                <?php 
                                $status = trim(strtolower($vehicle['status']));
                                $badge_bg = ($status === 'available') ? '#10b981' : (($status === 'maintenance') ? '#f59e0b' : '#ef4444');
                                $status_txt = ($status === 'available') ? 'AVAILABLE' : (($status === 'maintenance') ? 'SERVICE' : 'HIRED');
                                ?>
                                <span style="background: <?php echo $badge_bg; ?>; color: white; font-size: 0.6rem; font-weight: 800; padding: 3px 8px; border-radius: 4px;"><?php echo $status_txt; ?></span>
                                <p style="font-size: 0.85rem; color: var(--secondary-color); margin: 0;"><?php echo $vehicle['year']; ?> • Premium</p>
                            </div>
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 20px;">
                                <span style="font-weight: 800; color: var(--primary-color);">K<?php echo number_format($vehicle['price_per_day'], 0); ?>/day</span>
                                <a href="vehicle-details.php?id=<?php echo $vehicle['id']; ?>" class="btn btn-primary" style="padding: 8px 20px; font-size: 0.75rem;">View Details</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <a href="our-fleet.php" class="btn btn-primary btn-lg" style="padding: 1.2rem 3rem; background: var(--bg-dark); border: none; font-weight: 800; letter-spacing: 1px;">
                VIEW FULL COLLECTIONS <i class="fas fa-arrow-right" style="margin-left: 10px;"></i>
            </a>
        </div>
    </section>

    <!-- Premium Fleet Showcase Marquee -->
    <div class="premium-marquee-wrapper" style="padding-bottom: 30px;">
        <div class="premium-marquee">
            <div class="marquee-track">
                <?php 
                $demo_cars = [
                    ['img' => 'public/images/cars/g63.jpg', 'title' => 'G63 AMG', 'cat' => 'Ultra Luxury'],
                    ['img' => 'public/images/cars/defender.jpg', 'title' => 'Land Rover Defender', 'cat' => 'Off-Road King'],
                    ['img' => 'public/images/cars/prado.jpg', 'title' => 'Toyota Prado', 'cat' => 'Executive SUV'],
                    ['img' => 'public/images/cars/audi_q7.jpg', 'title' => 'Audi Q7', 'cat' => 'Premium Comfort'],
                    ['img' => 'public/images/cars/bmw_x5.jpg', 'title' => 'BMW X5', 'cat' => 'Sport Activity'],
                    ['img' => 'public/images/cars/jeep.jpg', 'title' => 'Jeep Wrangler', 'cat' => 'Adventure Ready'],
                    ['img' => 'public/images/cars/navara.jpg', 'title' => 'Nissan Navara', 'cat' => 'Tough Utility'],
                    ['img' => 'public/images/cars/everest.jpg', 'title' => 'Ford Everest', 'cat' => 'Family 4x4']
                ];
                
                // Duplicate for seamless infinite scroll
                for ($i = 0; $i < 2; $i++):
                    foreach ($demo_cars as $car): ?>
                        <div class="marquee-item" onclick="window.location.href='our-fleet.php'">
                            <img src="<?php echo $car['img']; ?>" alt="<?php echo $car['title']; ?>">
                            <div class="marquee-overlay">
                                <span><?php echo $car['cat']; ?></span>
                                <h4><?php echo $car['title']; ?></h4>
                            </div>
                        </div>
                    <?php endforeach; 
                endfor; ?>
            </div>
        </div>
    </div>

    <!-- Features Section -->
    <section class="features container" id="about">
        <div class="section-title">
            <h2>Why Choose Car Hire?</h2>
            <p>We provide the best car rental experience in Zambia</p>
        </div>
        <div class="features-grid">
            <div class="feature-card">
                <i class="fas fa-mobile-alt"></i>
                <h3>Mobile Money Payments</h3>
                <p>Easy and secure payments via MTN, Airtel, and Zamtel.</p>
            </div>
            <div class="feature-card">
                <i class="fas fa-car"></i>
                <h3>Wide Range of Vehicles</h3>
                <p>From luxury SUVs to economy cars, we have it all.</p>
            </div>
            <div class="feature-card">
                <i class="fas fa-headset"></i>
                <h3>24/7 Customer Support</h3>
                <p>We're always here to help you on your journey.</p>
            </div>
        </div>
    </section>
 
     <!-- Footer -->
     <footer>
        <div class="container">
            <h4 style="text-align: center; color: rgba(255,255,255,0.4); text-transform: uppercase; letter-spacing: 3px; font-size: 0.8rem; margin-bottom: 30px;">Reach Us</h4>
            <div class="footer-contact-row">
                <div class="footer-contact-item">
                    <i class="fas fa-phone"></i>
                    <span>+260 970 000 000</span>
                </div>
                <div class="footer-contact-item">
                    <i class="fas fa-envelope"></i>
                    <span>info@CarHire.zm</span>
                </div>
                <div class="footer-contact-item">
                    <i class="fas fa-map-marker-alt"></i>
                    <span>Lusaka, Zambia</span>
                </div>
            </div>
            
            <div class="footer-copyright">
                <p>&copy; <?php echo date('Y'); ?> Car Hire Zambia. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <style>
        .brand-chip {
            padding: 10px 20px;
            background: #f1f5f9;
            color: #64748b;
            border-radius: 50px;
            font-size: 0.85rem;
            font-weight: 600;
            white-space: nowrap;
            transition: all 0.3s ease;
            text-decoration: none;
        }
        .brand-chip:hover {
            background: #e2e8f0;
            color: var(--primary-color);
        }
        .brand-chip.active {
            background: var(--accent-vibrant);
            color: white;
            box-shadow: 0 4px 12px rgba(245, 158, 11, 0.3);
        }
        .fleet-card { padding: 0 !important; overflow: hidden !important; border: 1px solid rgba(255,255,255,0.05); }
        .fleet-img { width: 100%; height: 220px; object-fit: cover; transition: transform 0.5s ease; }
        .fleet-card:hover .fleet-img { transform: scale(1.05); }
        .fleet-info { padding: 25px; }
        
        @media (max-width: 768px) {
            .container { padding: 0 8px !important; }
            #mobile-search-adjust { margin-top: -30px !important; padding: 20px !important; }
            .fleet-img { height: 220px !important; }
            .hero-slider { height: 65vh !important; }
            .slide-content h1 { font-size: 2.2rem !important; }
            .slide-content p { font-size: 0.9rem !important; }
            .features-grid {
                grid-template-columns: 1fr !important;
                gap: 20px !important;
                padding: 0 10px !important;
            }
            .fleet-card { border-radius: 12px !important; }
            .fleet-info { padding: 12px !important; }
            .fleet-info h3 { font-size: 0.9rem !important; }
        }

        /* --- NEW PROFESSIONAL MANUAL STYLES --- */
        .manual-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(15, 23, 42, 0.8);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            z-index: 10000;
            overflow-y: auto;
            padding: 20px;
            animation: fadeIn 0.4s ease;
        }

        .manual-content {
            max-width: 1000px;
            margin: 40px auto;
            background: rgba(30, 30, 35, 0.85);
            backdrop-filter: blur(30px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 32px;
            overflow: hidden;
            display: grid;
            grid-template-columns: 280px 1fr;
            box-shadow: 0 40px 100px rgba(0, 0, 0, 0.6);
            min-height: 650px;
        }

        .manual-sidebar {
            background: rgba(15, 23, 42, 0.9);
            padding: 40px 20px;
            border-right: 1px solid rgba(255, 255, 255, 0.05);
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .manual-nav-item {
            padding: 12px 18px;
            border-radius: 12px;
            color: rgba(255, 255, 255, 0.5);
            font-size: 0.9rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .manual-nav-item i {
            width: 20px;
            text-align: center;
            font-size: 1rem;
        }

        .manual-nav-item:hover {
            background: rgba(255, 255, 255, 0.05);
            color: white;
        }

        .manual-nav-item.active {
            background: var(--accent-vibrant);
            color: white;
            box-shadow: 0 4px 15px rgba(255, 94, 0, 0.3);
        }

        .manual-main {
            padding: 50px;
            display: flex;
            flex-direction: column;
            position: relative;
        }

        .manual-step-content {
            display: none;
            animation: slideUp 0.5s ease backwards;
        }

        .manual-step-content.active {
            display: block;
        }

        .manual-step-header {
            margin-bottom: 35px;
        }

        .manual-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(255, 94, 0, 0.1);
            padding: 8px 16px;
            border-radius: 100px;
            color: var(--accent-vibrant);
            font-weight: 700;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 15px;
        }

        .manual-step-title {
            font-size: 2.2rem;
            font-weight: 900;
            color: white;
            letter-spacing: -1px;
            line-height: 1.1;
        }

        .manual-step-body {
            color: rgba(255, 255, 255, 0.8);
            line-height: 1.8;
            font-size: 1.05rem;
        }

        .manual-step-body ul {
            margin-top: 20px;
            list-style: none;
        }

        .manual-step-body li {
            margin-bottom: 15px;
            display: flex;
            gap: 15px;
            align-items: flex-start;
        }

        .manual-step-body li::before {
            content: "\f058";
            font-family: "Font Awesome 6 Free";
            font-weight: 900;
            color: var(--accent-vibrant);
            margin-top: 4px;
        }

        .manual-note {
            background: rgba(15, 23, 42, 0.7);
            border-radius: 20px;
            padding: 25px;
            margin-top: 40px;
            border: 1px solid rgba(255, 255, 255, 0.05);
            display: flex;
            gap: 20px;
            align-items: center;
        }

        .manual-note i {
            font-size: 1.8rem;
            color: var(--accent-vibrant);
        }

        .manual-controls {
            margin-top: auto;
            padding-top: 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-top: 1px solid rgba(255, 255, 255, 0.05);
        }

        .manual-close-x {
            position: absolute;
            top: 25px;
            right: 25px;
            width: 45px;
            height: 45px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
            color: white;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .manual-close-x:hover {
            background: var(--danger);
            border-color: var(--danger);
            transform: rotate(90deg);
        }

        /* Responsive */
        @media (max-width: 900px) {
            .manual-content {
                grid-template-columns: 1fr;
                margin: 20px auto;
                border-radius: 20px;
                min-height: auto;
            }
            .manual-sidebar {
                display: none;
            }
            .manual-main {
                padding: 40px 20px 20px;
            }
            .manual-step-title {
                font-size: 1.6rem;
                margin-top: 10px;
            }
            .manual-step-body {
                font-size: 0.95rem;
            }
            .manual-close-x {
                top: 15px;
                right: 15px;
                width: 35px;
                height: 35px;
                z-index: 100;
            }
            .manual-controls {
                flex-wrap: wrap;
                gap: 15px;
                justify-content: center;
                padding-top: 25px;
            }
            .manual-controls .btn {
                flex: 1;
                font-size: 0.85rem;
                padding: 12px 5px;
                min-width: 0 !important;
                white-space: nowrap;
            }
            .step-dots {
                width: 100%;
                justify-content: center;
                order: -1;
                margin-bottom: 5px;
            }
        }

        @keyframes slideUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        /* Floating Manual Button (Mobile Only) */
        .floating-manual-btn {
            position: fixed;
            bottom: 30px;
            right: 20px;
            width: 56px;
            height: 56px;
            background: linear-gradient(135deg, var(--accent-vibrant), #f97316);
            border-radius: 18px;
            display: none;
            align-items: center;
            justify-content: center;
            box-shadow: 0 10px 25px rgba(245, 158, 11, 0.4);
            cursor: pointer;
            z-index: 9999;
            transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        .floating-manual-btn:hover {
            transform: scale(1.1) translateY(-5px);
            box-shadow: 0 15px 35px rgba(245, 158, 11, 0.6);
        }
        .floating-manual-btn i {
            font-size: 1.4rem;
            color: white;
        }
        @media (max-width: 768px) {
            .floating-manual-btn {
                display: flex;
            }
        }

        /* Progress Bar */
        .manual-progress {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: rgba(255, 255, 255, 0.1);
            z-index: 10001;
        }

        .manual-progress-bar {
            height: 100%;
            background: var(--accent-vibrant);
            width: 0%;
            transition: width 0.4s ease;
            box-shadow: 0 0 15px var(--accent-vibrant);
        }
    </style>

    <!-- Floating Manual Button (Mobile Only) -->
    <div class="floating-manual-btn" onclick="openManual()">
        <i class="fas fa-book"></i>
    </div>

    <!-- User Manual Modal -->
    <div class="manual-modal" id="manualModal">
        <div class="manual-progress">
            <div class="manual-progress-bar" id="manualProgress"></div>
        </div>
        
        <div class="manual-content">
            <div class="manual-close-x" onclick="closeManual()">
                <i class="fas fa-times"></i>
            </div>

            <!-- Sidebar Navigation -->
            <div class="manual-sidebar">
                <div class="manual-nav-item active" onclick="goToStep(1)">
                    <i class="fas fa-user-plus"></i> Account
                </div>
                <div class="manual-nav-item" onclick="goToStep(2)">
                    <i class="fas fa-sign-in-alt"></i> Login
                </div>
                <div class="manual-nav-item" onclick="goToStep(3)">
                    <i class="fas fa-user-check"></i> Verification
                </div>
                <div class="manual-nav-item" onclick="goToStep(4)">
                    <i class="fas fa-search"></i> Browsing
                </div>
                <div class="manual-nav-item" onclick="goToStep(5)">
                    <i class="fas fa-calendar-check"></i> Booking
                </div>
                <div class="manual-nav-item" onclick="goToStep(6)">
                    <i class="fas fa-credit-card"></i> Payment
                </div>
                <div class="manual-nav-item" onclick="goToStep(7)">
                    <i class="fas fa-car-side"></i> Pickup
                </div>
                <div class="manual-nav-item" onclick="goToStep(8)">
                    <i class="fas fa-undo"></i> Return
                </div>
                
                <div style="margin-top: auto; padding: 20px; background: rgba(255,255,255,0.05); border-radius: 15px;">
                    <p style="font-size: 0.75rem; color: rgba(255,255,255,0.4); line-height: 1.4;">Need instant help? Our team is live 24/7.</p>
                    <a href="tel:+260970000000" style="color: var(--accent-vibrant); font-size: 0.8rem; font-weight: 700; display: block; margin-top: 5px;">+260 970 000 000</a>
                </div>
            </div>

            <!-- Main Content Area -->
            <div class="manual-main">
                
                <!-- Step 1 -->
                <div class="manual-step-content active" id="step1">
                    <div class="manual-step-header">
                        <span class="manual-badge">Step 1 of 8</span>
                        <h2 class="manual-step-title">Create Your Account</h2>
                    </div>
                    <div class="manual-step-body">
                        <p>Begin your luxury car rental experience by joining our exclusive platform. It only takes a minute.</p>
                        <ul>
                            <li>Click the <strong>"Join Now"</strong> button in the top right corner.</li>
                            <li>Fill in your personal details (Name, Email, Phone).</li>
                            <li>Create a secure password and agree to our terms.</li>
                            <li>Verify your email address via the link we send you.</li>
                        </ul>
                    </div>
                    <div class="manual-note">
                        <i class="fas fa-shield-halved"></i>
                        <div>
                            <strong style="color: white; display: block; margin-bottom: 4px;">Data Security</strong>
                            <p style="font-size: 0.85rem; margin:0;">Your data is encrypted and never shared with third parties.</p>
                        </div>
                    </div>
                </div>

                <!-- Step 2 -->
                <div class="manual-step-content" id="step2">
                    <div class="manual-step-header">
                        <span class="manual-badge">Step 2 of 8</span>
                        <h2 class="manual-step-title">Access Your Portal</h2>
                    </div>
                    <div class="manual-step-body">
                        <p>Log in to manage your bookings, track your documents, and access premium features.</p>
                        <ul>
                            <li>Click <strong>"Sign In"</strong> in the header.</li>
                            <li>Enter your registered email and password.</li>
                            <li>Enjoy your personalized customer dashboard.</li>
                        </ul>
                    </div>
                </div>

                <!-- Step 3 -->
                <div class="manual-step-content" id="step3">
                    <div class="manual-step-header">
                        <span class="manual-badge">Step 3 of 8</span>
                        <h2 class="manual-step-title">Identity Verification</h2>
                    </div>
                    <div class="manual-step-body">
                        <p>For high-end security, we require a one-time identity verification before your first rental.</p>
                        <ul>
                            <li>Upload a high-quality photo of your <strong>National ID or Passport</strong>.</li>
                            <li>Upload your <strong>Driver's License</strong>.</li>
                            <li>Our team reviews documents within <strong>12-24 hours</strong>.</li>
                        </ul>
                    </div>
                    <div class="manual-note">
                        <i class="fas fa-clock"></i>
                        <div>
                            <strong style="color: white; display: block; margin-bottom: 4px;">Swift Approval</strong>
                            <p style="font-size: 0.85rem; margin:0;">We prioritize verification requests to get you on the road faster.</p>
                        </div>
                    </div>
                </div>

                <!-- Step 4 -->
                <div class="manual-step-content" id="step4">
                    <div class="manual-step-header">
                        <span class="manual-badge">Step 4 of 8</span>
                        <h2 class="manual-step-title">Explore the Fleet</h2>
                    </div>
                    <div class="manual-step-body">
                        <p>Browse through our curated collection of luxury sedans, rugged 4x4s, and executive SUVs.</p>
                        <ul>
                            <li>Use advanced filters to find your perfect match.</li>
                            <li>View high-resolution images and detailed specs.</li>
                            <li>Check real-time availability for your selected dates.</li>
                        </ul>
                    </div>
                </div>

                <!-- Step 5 -->
                <div class="manual-step-content" id="step5">
                    <div class="manual-step-header">
                        <span class="manual-badge">Step 5 of 8</span>
                        <h2 class="manual-step-title">Reserve Your Ride</h2>
                    </div>
                    <div class="manual-step-body">
                        <p>Finalize your selection and secure your vehicle with our easy booking process.</p>
                        <ul>
                            <li>Choose your <strong>Pickup and Drop-off</strong> locations.</li>
                            <li>Select your dates and preferred times.</li>
                            <li>Add premium extras like GPS or personal protection.</li>
                            <li>Review your transparent quotation.</li>
                        </ul>
                    </div>
                </div>

                <!-- Step 6 -->
                <div class="manual-step-content" id="step6">
                    <div class="manual-step-header">
                        <span class="manual-badge">Step 6 of 8</span>
                        <h2 class="manual-step-title">Secure Payment</h2>
                    </div>
                    <div class="manual-step-body">
                        <p>Confirm your booking by completing the payment through our secure channels.</p>
                        <ul>
                            <li>Pay via <strong>Mobile Money</strong> (MTN, Airtel, Zamtel).</li>
                            <li>Use <strong>Credit/Debit cards</strong> for instant confirmation.</li>
                            <li>Or choose <strong>Pay at Branch</strong> for traditional settling.</li>
                        </ul>
                    </div>
                </div>

                <!-- Step 7 -->
                <div class="manual-step-content" id="step7">
                    <div class="manual-step-header">
                        <span class="manual-badge">Step 7 of 8</span>
                        <h2 class="manual-step-title">Priority Pickup</h2>
                    </div>
                    <div class="manual-step-body">
                        <p>Experience our VIP collection process at your chosen branch.</p>
                        <ul>
                            <li>Bring your original ID and License.</li>
                            <li>Quick vehicle inspection with our agent.</li>
                            <li>Sign the digital agreement and take the keys.</li>
                        </ul>
                    </div>
                    <div class="manual-note">
                        <i class="fas fa-star" style="color: #fbbf24;"></i>
                        <div>
                            <strong style="color: white; display: block; margin-bottom: 4px;">Wait-free Service</strong>
                            <p style="font-size: 0.85rem; margin:0;">Verified users enjoy an express collection track.</p>
                        </div>
                    </div>
                </div>

                <!-- Step 8 -->
                <div class="manual-step-content" id="step8">
                    <div class="manual-step-header">
                        <span class="manual-badge">Step 8 of 8</span>
                        <h2 class="manual-step-title">Seamless Return</h2>
                    </div>
                    <div class="manual-step-body">
                        <p>Return your vehicle simple and easy at the end of your journey.</p>
                        <ul>
                            <li>Drop off the vehicle at the agreed location.</li>
                            <li>Ensure the fuel is at the same level as pickup.</li>
                            <li>Receive your final inspection report instantly.</li>
                        </ul>
                    </div>
                </div>

                <!-- Navigation Controls -->
                <div class="manual-controls">
                    <button class="btn btn-outline" id="prevStep" onclick="changeStep(-1)" style="min-width: 120px; border-color: rgba(255,255,255,0.1);">
                        <i class="fas fa-arrow-left"></i> Previous
                    </button>
                    <div class="step-dots" style="display: flex; gap: 8px;">
                        <span class="dot active"></span>
                        <span class="dot"></span>
                        <span class="dot"></span>
                        <span class="dot"></span>
                        <span class="dot"></span>
                        <span class="dot"></span>
                        <span class="dot"></span>
                        <span class="dot"></span>
                    </div>
                    <button class="btn btn-primary" id="nextStep" onclick="changeStep(1)" style="background: var(--accent-vibrant); min-width: 120px;">
                        Next Step <i class="fas fa-arrow-right"></i>
                    </button>
                </div>

            </div>
        </div>
    </div>
    
    <style>
        .step-dots .dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.2);
            transition: all 0.3s ease;
        }
        .step-dots .dot.active {
            background: var(--accent-vibrant);
            transform: scale(1.3);
            box-shadow: 0 0 10px rgba(255, 94, 0, 0.5);
        }
    </style>

    <script>
        let currentStep = 1;
        const totalSteps = 8;

        function openManual() {
            document.getElementById('manualModal').style.display = 'block';
            document.body.style.overflow = 'hidden';
            updateProgressBar();
        }
        
        function closeManual() {
            document.getElementById('manualModal').style.display = 'none';
            document.body.style.overflow = 'auto';
        }

        function goToStep(step) {
            // Hide current step
            document.querySelector('.manual-step-content.active').classList.remove('active');
            document.querySelector('.manual-nav-item.active').classList.remove('active');
            document.querySelector('.step-dots .dot.active').classList.remove('active');

            // Show new step
            currentStep = step;
            document.getElementById('step' + step).classList.add('active');
            document.querySelectorAll('.manual-nav-item')[step - 1].classList.add('active');
            document.querySelectorAll('.step-dots .dot')[step - 1].classList.add('active');

            // Update controls
            document.getElementById('prevStep').style.visibility = (step === 1) ? 'hidden' : 'visible';
            document.getElementById('nextStep').innerHTML = (step === totalSteps) ? 'Finish' : 'Next Step <i class="fas fa-arrow-right"></i>';
            
            updateProgressBar();
        }

        function changeStep(delta) {
            let next = currentStep + delta;
            if (next >= 1 && next <= totalSteps) {
                goToStep(next);
            } else if (next > totalSteps) {
                closeManual();
            }
        }

        function updateProgressBar() {
            const progress = (currentStep / totalSteps) * 100;
            document.getElementById('manualProgress').style.width = progress + '%';
        }
        
        // Close modal when clicking outside
        document.getElementById('manualModal')?.addEventListener('click', function(e) {
            if (e.target === this) {
                closeManual();
            }
        });
        
        // Close modal with Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeManual();
            }
        });

        // Initialize first step visibility
        document.getElementById('prevStep').style.visibility = 'hidden';
    </script>
    
    <script src="public/js/main.js"></script>
    <?php include_once 'includes/mobile_nav.php'; ?>
    <script>
        // Language Dropdown Toggle
        window.onclick = function(event) {
            if (!event.target.closest('.lang-switcher')) {
                var dropdowns = document.getElementsByClassName("dropdown-content-lang");
                for (var i = 0; i < dropdowns.length; i++) {
                    var openDropdown = dropdowns[i];
                    if (openDropdown.classList.contains('show')) {
                        openDropdown.classList.remove('show');
                        openDropdown.style.display = 'none';
                    }
                }
            }
        }

        // Overriding the inline onclick for better control
        document.querySelector('.lang-switcher button').onclick = function(e) {
            e.stopPropagation();
            const dd = document.getElementById('langDropdown');
            const isVisible = dd.style.display === 'block';
            dd.style.display = isVisible ? 'none' : 'block';
            dd.classList.toggle('show');
        }
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
    <script>
        // High-end App Splash Handler
        window.addEventListener('load', function() {
            const splash = document.getElementById('appSplash');
            const progress = document.getElementById('splashProgress');
            
            // Initializing delay
            setTimeout(() => {
                if(progress) progress.style.width = '100%';
                setTimeout(() => {
                    document.body.classList.add('splash-loaded');
                    // Optional: remove from DOM after fade
                    setTimeout(() => splash.style.display = 'none', 1000);
                }, 600);
            }, 400);
        });
    </script>
</body>
</html>

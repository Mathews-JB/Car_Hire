<?php
include_once '../includes/db.php';
include_once '../includes/functions.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'customer') {
    header("Location: ../login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$id = isset($_GET['id']) ? $_GET['id'] : '';

if (empty($id)) {
    header("Location: dashboard.php");
    exit;
}

// Fetch booking details with vehicle metadata
$stmt = $pdo->prepare("SELECT b.*, v.make, v.model, v.image_url, v.plate_number, v.price_per_day, v.features, v.interior_image_url
                       FROM bookings b 
                       JOIN vehicles v ON b.vehicle_id = v.id 
                       WHERE b.id = ? AND b.user_id = ?");
$stmt->execute([$id, $user_id]);
$booking = $stmt->fetch();

if (!$booking) {
    header("Location: dashboard.php");
    exit;
}

// Fetch Contract Info (if any)
$stmt = $pdo->prepare("SELECT * FROM contracts WHERE booking_id = ?");
$stmt->execute([$id]);
$contract = $stmt->fetch();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking #<?php echo $id; ?> | Car Hire</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../public/css/style.css?v=2.1">
    <style>
        .details-container { max-width: 900px; margin: 0 auto; padding: 40px 20px; }
        .booking-hero { 
            position: relative; 
            height: 300px; 
            border-radius: 24px; 
            overflow: hidden; 
            margin-bottom: 30px;
            background-size: cover;
            background-position: center;
        }
        .booking-hero::before {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(to top, rgba(15, 23, 42, 0.9), transparent);
        }
        .hero-info {
            position: absolute;
            bottom: 30px;
            left: 30px;
            right: 30px;
            color: white;
        }
        .details-grid { display: grid; grid-template-columns: 1.5fr 1fr; gap: 30px; }
        .glass-card { 
            background: rgba(15, 23, 42, 0.4); 
            backdrop-filter: blur(20px); 
            border: 1px solid rgba(255, 255, 255, 0.1); 
            border-radius: 24px; 
            padding: 30px; 
            margin-bottom: 30px; 
            color: white;
        }
        .info-label { color: rgba(125, 180, 59, 0.7); font-size: 0.7rem; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 5px; display: block; font-weight: 700; }
        .info-value { font-size: 1.1rem; font-weight: 600; }
        @media (max-width: 768px) {
            .details-grid { grid-template-columns: 1fr; }
            .booking-hero { height: 200px; }
        }
        body { 
            background: url('../public/images/cars/camry.jpg') center/cover no-repeat fixed !important;
        }
    </style>
</head>
<body>

    <nav class="hub-bar">
        <a href="dashboard.php" class="logo">Car Hire</a>
        <div class="hub-nav">
            <a href="dashboard.php" class="active">Dashboard</a>
            <a href="browse-vehicles.php">Browse Fleet</a>
            <a href="my-bookings.php">My Bookings</a>
            <a href="support.php">Support</a>
            <a href="profile.php">Profile</a>
        </div>
    </nav>

    <div class="portal-content">
        <div class="details-container">
            <div style="margin-bottom: 25px;">
                <a href="dashboard.php" style="color: rgba(255,255,255,0.6); text-decoration: none; font-size: 0.9rem;">
                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                </a>
            </div>

            <div class="booking-hero" style="background-image: url('../<?php echo $booking['image_url']; ?>');">
                <div class="hero-info">
                    <span class="status-badge status-<?php echo $booking['status']; ?>" style="margin-bottom: 15px; display: inline-block;">
                        <?php echo strtoupper($booking['status']); ?>
                    </span>
                    <h1 style="margin: 0; font-size: 2.2rem;"><?php echo $booking['make'] . ' ' . $booking['model']; ?></h1>
                    <p style="margin: 5px 0 0; opacity: 0.8;">Booking Reference: #<?php echo $id; ?></p>
                </div>
            </div>

            <div class="details-grid">
                <div class="main-details">
                    <div class="glass-card">
                        <h3 style="margin-bottom: 20px;"><i class="fas fa-info-circle"></i> Booking Information</h3>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                            <div>
                                <span class="info-label">Pickup Location</span>
                                <span class="info-value"><?php echo $booking['pickup_location']; ?></span>
                            </div>
                            <div>
                                <span class="info-label">Drop-off Location</span>
                                <span class="info-value"><?php echo $booking['dropoff_location']; ?></span>
                            </div>
                            <div>
                                <span class="info-label">Pickup Date</span>
                                <span class="info-value"><?php echo date('d M Y, h:i A', strtotime($booking['pickup_date'])); ?></span>
                            </div>
                            <div>
                                <span class="info-label">Drop-off Date</span>
                                <span class="info-value"><?php echo date('d M Y, h:i A', strtotime($booking['dropoff_date'])); ?></span>
                            </div>
                        </div>
                    </div>

                    <div class="glass-card">
                        <h3 style="margin-bottom: 20px;"><i class="fas fa-car-side"></i> Vehicle & Add-ons</h3>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                            <div>
                                <span class="info-label">Daily Rate</span>
                                <span class="info-value">ZMW <?php echo number_format($booking['price_per_day']); ?></span>
                            </div>
                            <div>
                                <span class="info-label">Add-ons</span>
                                <span class="info-value" style="font-size: 0.9rem; opacity: 0.8;"><?php echo $booking['addons'] ?? 'None'; ?></span>
                            </div>
                        </div>
                    </div>

                    <?php if (!empty($booking['features']) || !empty($booking['interior_image_url'])): ?>
                        <div class="glass-card">
                            <h3 style="margin-bottom: 20px;"><i class="fas fa-list-check"></i> Vehicle Specifications</h3>
                            
                            <?php if (!empty($booking['interior_image_url'])): ?>
                                <div style="margin-bottom: 20px;">
                                    <img src="../<?php echo $booking['interior_image_url']; ?>" alt="Interior" style="width: 100%; max-height: 300px; object-fit: cover; border-radius: 16px;">
                                </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($booking['features'])): ?>
                                <div>
                                    <span class="info-label" style="margin-bottom: 10px; display: block;">Features</span>
                                    <div style="display: flex; flex-wrap: wrap; gap: 8px;">
                                        <?php 
                                        $features = explode(',', $booking['features']);
                                        foreach ($features as $feature): 
                                            $feature = trim($feature);
                                            if (!empty($feature)):
                                        ?>
                                            <span style="background: rgba(255,255,255,0.1); padding: 6px 12px; border-radius: 8px; font-size: 0.85rem; display: inline-flex; align-items: center; gap: 5px;">
                                                <i class="fas fa-check" style="color: var(--accent-color); font-size: 0.7rem;"></i>
                                                <?php echo htmlspecialchars($feature); ?>
                                            </span>
                                        <?php 
                                            endif;
                                        endforeach; 
                                        ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="sidebar-details">
                    <div class="glass-card" style="border-top: 4px solid var(--primary-color);">
                        <span class="info-label">Total Amount</span>
                        <h2 style="font-size: 2rem; margin-bottom: 20px;">ZMW <?php echo number_format($booking['total_price']); ?></h2>
                        
                        <?php if ($booking['status'] === 'pending'): ?>
                            <a href="payment.php?booking_id=<?php echo $id; ?>" class="btn btn-primary" style="width: 100%; text-align: center; margin-bottom: 10px;">Pay Now</a>
                        <?php elseif ($booking['status'] === 'confirmed'): ?>
                            <a href="track-vehicle.php" class="btn btn-primary" style="width: 100%; text-align: center; margin-bottom: 10px; background: #3b82f6;">
                                <i class="fas fa-map-marker-alt"></i> Track Live
                            </a>
                        <?php endif; ?>

                        <a href="receipt.php?booking_id=<?php echo $id; ?>&print=true" class="btn btn-outline" style="width: 100%; text-align: center; border-color: rgba(255,255,255,0.2);">
                            <i class="fas fa-file-download"></i> Download Receipt
                        </a>
                    </div>

                    <?php if ($contract && $contract['is_signed']): ?>
                        <div class="glass-card" style="background: rgba(16, 185, 129, 0.1); border: 1px solid rgba(16, 185, 129, 0.2);">
                            <h4 style="color: #10b981; margin-bottom: 10px;"><i class="fas fa-file-contract"></i> Rental Agreement</h4>
                            <p style="font-size: 0.85rem; opacity: 0.8; margin-bottom: 15px;">Your signed contract is securely stored.</p>
                            <a href="../<?php echo $contract['contract_pdf_path']; ?>" target="_blank" class="btn btn-outline" style="width:100%; border-color: #10b981; color: #10b981; font-size: 0.8rem; text-align:center;">
                                View Agreement
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <?php include_once '../includes/mobile_nav.php'; ?>
</body>
</html>

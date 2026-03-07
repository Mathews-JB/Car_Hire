<?php
include_once '../includes/db.php';
include_once '../includes/functions.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

$booking_id = isset($_GET['booking_id']) ? $_GET['booking_id'] : '';

if (empty($booking_id)) {
    header("Location: dashboard.php");
    exit;
}

// Fetch booking details
$stmt = $pdo->prepare("SELECT b.*, v.make, v.model, v.image_url 
                       FROM bookings b 
                       JOIN vehicles v ON b.vehicle_id = v.id 
                       WHERE b.id = ? AND b.user_id = ?");
$stmt->execute([$booking_id, $_SESSION['user_id']]);
$booking = $stmt->fetch();

if (!$booking) {
    header("Location: dashboard.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reservation Confirmed | Car Hire</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../public/css/style.css">
    <style>
        body { 
            background: transparent !important;
        }
    </style>
</head>
<body class="stabilized-car-bg">

    <header class="header-solid">
        <div class="container nav">
            <a href="dashboard.php" class="logo">Car Hire</a>
            <ul class="nav-links">
                <li><a href="dashboard.php">Portal Home</a></li>
                <li><a href="my-bookings.php">My Bookings</a></li>
            </ul>
        </div>
    </header>

    <div class="portal-content">
        <div class="container" style="text-align: center; padding: 40px 0;">
            <i class="fas fa-check-circle" style="font-size: 5rem; color: #28a745; margin-bottom: 20px;"></i>
            <h1>Reservation Confirmed!</h1>
            <p style="font-size: 1.2rem; margin-bottom: 40px;">Your booking ID is <strong>#<?php echo $booking_id; ?></strong>. We've sent a confirmation to your email.</p>

            <div class="feature-card" style="max-width: 600px; margin: 0 auto; text-align: left;">
                <h3>Reservation Details</h3>
                <p><strong>Vehicle:</strong> <?php echo $booking['make'] . ' ' . $booking['model']; ?></p>
                <p><strong>Pickup:</strong> <?php echo $booking['pickup_date']; ?> (<?php echo $booking['pickup_location']; ?>)</p>
                <p><strong>Drop-off:</strong> <?php echo $booking['dropoff_date']; ?> (<?php echo $booking['dropoff_location']; ?>)</p>
                <p><strong>Total Price:</strong> ZMW <?php echo number_format($booking['total_price'], 2); ?></p>
                
                <div style="margin-top: 30px;">
                    <a href="payment.php?booking_id=<?php echo $booking_id; ?>" class="btn btn-primary">Pay Now via Mobile Money</a>
                    <a href="my-bookings.php" class="btn btn-outline">View My Bookings</a>
                </div>
            </div>
        </div>
    </div>

    <style>
        @media (max-width: 768px) {
            .nav-links { display: none !important; }
            .header-solid { padding: 15px !important; }
            .container { padding-left: 20px; padding-right: 20px; }
            .feature-card { padding: 20px !important; }
            h1 { font-size: 1.8rem !important; }
            .btn { width: 100%; display: block; margin-bottom: 10px; text-align: center; }
        }
    </style>

    <?php include_once '../includes/mobile_nav.php'; ?>
</body>
</html>

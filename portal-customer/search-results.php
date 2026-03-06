<?php
include_once '../includes/db.php';
include_once '../includes/functions.php';

// Get search parameters
$location = isset($_GET['pickup_location']) ? $_GET['pickup_location'] : '';
$pickup_date = isset($_GET['pickup_date']) ? $_GET['pickup_date'] : '';
$dropoff_date = isset($_GET['dropoff_date']) ? $_GET['dropoff_date'] : '';

// Validation: Basic redirect if parameters are missing
if (empty($location) || empty($pickup_date) || empty($dropoff_date)) {
    header("Location: ../index.php#search");
    exit;
}

// Search Logic: Get vehicles that are 'available' and not booked for these dates
$sql = "SELECT * FROM vehicles WHERE status = 'available' AND id NOT IN (
            SELECT vehicle_id FROM bookings 
            WHERE status NOT IN ('cancelled')
            AND (
                (pickup_date <= :pickup_date AND dropoff_date >= :pickup_date) OR
                (pickup_date <= :dropoff_date AND dropoff_date >= :dropoff_date) OR
                (:pickup_date <= pickup_date AND :dropoff_date >= dropoff_date)
            )
        )";

$stmt = $pdo->prepare($sql);
$stmt->execute([
    'pickup_date' => $pickup_date . ' 00:00:00',
    'dropoff_date' => $dropoff_date . ' 23:59:59'
]);
$vehicles = $stmt->fetchAll();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search Results | Car Hire</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../public/css/style.css">
    <style>
        body { 
            background: url('../public/images/cars/camry.jpg') center/cover no-repeat fixed !important;
        }
    </style>
</head>
<body>

    <!-- Header -->
    <header class="header-solid">
        <div class="container nav">
            <a href="../index.php" class="logo">Car Hire</a>
            <ul class="nav-links">
                <li><a href="../index.php">Home</a></li>
<?php if (isset($_SESSION['user_id'])): ?>
                    <li><a href="my-bookings.php">My Bookings</a></li>
                    <li><a href="profile.php">Profile</a></li>
                    <li style="margin-left: 20px;"><a href="../logout.php" class="btn btn-outline" style="color: var(--danger); border-color: var(--danger); padding: 5px 15px;">Logout</a></li>
<?php else: ?>
                    <li><a href="../login.php">Login</a></li>
                    <li><a href="../register.php" class="btn btn-primary" style="padding: 8px 18px;">Register</a></li>
<?php endif; ?>
            </ul>
        </div>
    </header>

    <div class="portal-content">

    <!-- Results Header -->
    <div class="results-header">
        <div class="container">
            <h1>Available Vehicles in <?php echo htmlspecialchars($location); ?></h1>
            <p style="color: var(--secondary-color);">From <strong><?php echo $pickup_date; ?></strong> to <strong><?php echo $dropoff_date; ?></strong></p>
        </div>
    </div>

    <!-- Main Content -->
    <main class="container">
        <?php if (count($vehicles) > 0): ?>
            <div class="cars-grid">
                <?php foreach ($vehicles as $car): ?>
                    <div class="car-card">
                        <img src="<?php echo !empty($car['image_url']) ? '../'.$car['image_url'] : 'https://via.placeholder.com/400x250?text=No+Image'; ?>" alt="<?php echo $car['make'] . ' ' . $car['model']; ?>" class="car-image">
                        <div class="car-content">
                            <h3 class="car-title"><?php echo $car['make'] . ' ' . $car['model']; ?></h3>
                            <div class="car-meta">
                                <span><i class="fas fa-users"></i> <?php echo $car['capacity']; ?> Seats</span>
                                <span><i class="fas fa-calendar-alt"></i> <?php echo $car['year']; ?></span>
                                <span><i class="fas fa-cog"></i> <?php echo $car['transmission'] ?? 'Auto'; ?></span>
                            </div>
                            
                            <?php if (!empty($car['features'])): ?>
                                <div class="feature-badges">
                                    <?php 
                                    $features = explode(',', $car['features']);
                                    foreach (array_slice($features, 0, 3) as $feature): // Show top 3
                                    ?>
                                        <span class="feature-badge"><?php echo trim($feature); ?></span>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>

                            <div class="car-price">
                                ZMW <?php echo number_format($car['price_per_day'], 2); ?> <span>/ day</span>
                            </div>
                            <a href="./booking-form.php?vehicle_id=<?php echo $car['id']; ?>&pickup_date=<?php echo $pickup_date; ?>&dropoff_date=<?php echo $dropoff_date; ?>&location=<?php echo urlencode($location); ?>" class="btn btn-primary btn-block" style="width: 100%; text-align: center;">Book This Car</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-results">
                <i class="fas fa-car-side"></i>
                <h2>No vehicles available for these dates</h2>
                <p>Try changing your dates or location for better results.</p>
                <a href="browse-vehicles.php" class="btn btn-primary" style="margin-top: 20px;">Search Again</a>
            </div>
        <?php endif; ?>
    </main>
</div>

    <!-- Footer -->
    <footer>
        <div class="container footer-content">
            <div class="footer-section">
                <h3>Car Hire</h3>
                <p>The leading car rental service in Zambia. Experience quality and reliability.</p>
            </div>
        </div>
    </footer>

    <?php include_once '../includes/mobile_nav.php'; ?>
</body>
</html>

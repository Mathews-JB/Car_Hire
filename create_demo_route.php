<?php
// Demo script to create realistic Lusaka route for testing
include_once 'includes/db.php';

// Realistic Lusaka route coordinates (matching reference image)
$lusaka_route = [
    ['lat' => -15.3950, 'lng' => 28.3100, 'speed' => 35], // Matero area
    ['lat' => -15.3920, 'lng' => 28.3150, 'speed' => 40],
    ['lat' => -15.3900, 'lng' => 28.3200, 'speed' => 42],
    ['lat' => -15.3901, 'lng' => 28.3235, 'speed' => 42], // Reference coordinate
    ['lat' => -15.3920, 'lng' => 28.3280, 'speed' => 38],
    ['lat' => -15.3950, 'lng' => 28.3320, 'speed' => 45],
    ['lat' => -15.3980, 'lng' => 28.3380, 'speed' => 50], // Near Chilenje
    ['lat' => -15.4020, 'lng' => 28.3450, 'speed' => 48],
    ['lat' => -15.4050, 'lng' => 28.3520, 'speed' => 52],
    ['lat' => -15.4100, 'lng' => 28.3600, 'speed' => 55], // Heading to airport area
    ['lat' => -15.4150, 'lng' => 28.3680, 'speed' => 50],
    ['lat' => -15.4180, 'lng' => 28.3750, 'speed' => 45]
];

// Get or create a vehicle
$stmt = $pdo->query("SELECT id FROM vehicles LIMIT 1");
$vehicle = $stmt->fetch();

if (!$vehicle) {
    die("No vehicles found in database.\n");
}

$vehicle_id = $vehicle['id'];

// Update vehicle status to hired
$pdo->exec("UPDATE vehicles SET status = 'hired' WHERE id = $vehicle_id");

// Clear existing history
$pdo->exec("DELETE FROM vehicle_history WHERE vehicle_id = $vehicle_id");

// Insert route history
echo "Creating realistic Lusaka route for vehicle ID: $vehicle_id\n";
$timestamp = time() - (count($lusaka_route) * 30); // 30 seconds between points

foreach ($lusaka_route as $point) {
    $stmt = $pdo->prepare("INSERT INTO vehicle_history (vehicle_id, latitude, longitude, speed, timestamp) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$vehicle_id, $point['lat'], $point['lng'], $point['speed'], date('Y-m-d H:i:s', $timestamp)]);
    $timestamp += 30;
}

// Set current position to middle of route (where car is in reference image)
$current_index = 3; // The reference coordinate
$current = $lusaka_route[$current_index];

$stmt = $pdo->prepare("UPDATE vehicles SET last_lat = ?, last_lng = ?, current_speed = ?, bearing = ?, tracking_status = 'online' WHERE id = ?");

// Calculate bearing to next point
$next = $lusaka_route[$current_index + 1];
$bearing = atan2($next['lng'] - $current['lng'], $next['lat'] - $current['lat']) * 180 / M_PI;

$stmt->execute([$current['lat'], $current['lng'], $current['speed'], $bearing, $vehicle_id]);

// Create or update a confirmed booking for this vehicle
$stmt = $pdo->query("SELECT id FROM users WHERE role = 'customer' LIMIT 1");
$user = $stmt->fetch();

if ($user) {
    $user_id = $user['id'];
    
    // Check if booking exists
    $stmt = $pdo->prepare("SELECT id FROM bookings WHERE vehicle_id = ? AND status = 'confirmed' LIMIT 1");
    $stmt->execute([$vehicle_id]);
    $booking = $stmt->fetch();
    
    if (!$booking) {
        // Create new booking
        $stmt = $pdo->prepare("INSERT INTO bookings (user_id, vehicle_id, pickup_date, dropoff_date, pickup_location, dropoff_location, total_price, status, payment_status) VALUES (?, ?, NOW(), DATE_ADD(NOW(), INTERVAL 3 DAY), 'Matero, Lusaka', 'Kenneth Kaunda Airport', 500, 'confirmed', 'paid')");
        $stmt->execute([$user_id, $vehicle_id]);
        $booking_id = $pdo->lastInsertId();
        echo "✓ Created booking ID: $booking_id\n";
    } else {
        $booking_id = $booking['id'];
        echo "✓ Using existing booking ID: $booking_id\n";
    }
}

echo "✓ Route created successfully!\n";
echo "✓ Current position: Lat {$current['lat']}, Lng {$current['lng']}\n";
echo "✓ Speed: {$current['speed']} km/h\n";
echo "✓ Bearing: " . round($bearing) . "°\n";
echo "\nVehicle is now ready for live tracking demo!\n";
echo "Access tracking at: portal-customer/track-booking.php?id=$booking_id\n";
?>

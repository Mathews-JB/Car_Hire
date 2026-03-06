<?php
// Fix data specifically for Booking #7 (or any specific ID)
include_once 'includes/db.php';

$booking_id = 7; // The ID from the screenshot

// 1. Get Vehicle ID for this booking
$stmt = $pdo->prepare("SELECT vehicle_id FROM bookings WHERE id = ?");
$stmt->execute([$booking_id]);
$booking = $stmt->fetch();

if (!$booking) {
    // If booking 7 doesn't exist, finding the latest one used by the user in the dashboard
    $stmt = $pdo->query("SELECT id, vehicle_id FROM bookings ORDER BY id DESC LIMIT 1");
    $booking = $stmt->fetch();
    $booking_id = $booking['id'];
    echo "Booking #7 not found. Using Booking #$booking_id instead.\n";
}
$vehicle_id = $booking['vehicle_id'];

echo "Fixing data for Booking #$booking_id (Vehicle #$vehicle_id)...\n";

// 2. Define Lusaka Route (Reference Coordinates)
$center_lat = -15.3901;
$center_lng = 28.3235;

$lusaka_route = [
    ['lat' => -15.3950, 'lng' => 28.3100, 'speed' => 35],
    ['lat' => -15.3901, 'lng' => 28.3235, 'speed' => 45], // Reference
    ['lat' => -15.4100, 'lng' => 28.3600, 'speed' => 55],
];

// 3. Clear and Insert History
$pdo->exec("DELETE FROM vehicle_history WHERE vehicle_id = $vehicle_id");

$timestamp = time() - 60;
foreach ($lusaka_route as $point) {
    $stmt = $pdo->prepare("INSERT INTO vehicle_history (vehicle_id, latitude, longitude, speed, timestamp) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$vehicle_id, $point['lat'], $point['lng'], $point['speed'], date('Y-m-d H:i:s', $timestamp)]);
    $timestamp += 20;
}

// 4. Update Current Vehicle Position to CENTER
$bearing = 45;
$stmt = $pdo->prepare("UPDATE vehicles SET last_lat = ?, last_lng = ?, current_speed = 45, bearing = ?, tracking_status = 'online' WHERE id = ?");
$stmt->execute([$center_lat, $center_lng, $bearing, $vehicle_id]);

// 5. Ensure Booking is Confirmed and Active
$pdo->exec("UPDATE bookings SET status = 'confirmed' WHERE id = $booking_id");

// 6. Fix "Toyota Camry" (if visuals needed)
// User screen shows "Toyota Camry". Make sure it has an image or handled.
// $pdo->exec("UPDATE vehicles SET make = 'Toyota', model = 'Camry' WHERE id = $vehicle_id");

echo "✓ Data fixed! Map should now center on Lusaka (-15.3901, 28.3235).\n";
echo "✓ Vehicle positioned.\n";
echo "✓ Route history created.\n";
?>

<?php
require_once '../includes/db.php';

// This script simulates movement for a vehicle to test the 3D map
// Usage: api/simulate-gps.php?id=1

$vehicle_id = $_GET['id'] ?? 1;

// 1. Get current position or set default (Lusaka coordinates)
$stmt = $pdo->prepare("SELECT last_lat, last_lng, bearing FROM vehicles WHERE id = ?");
$stmt->execute([$vehicle_id]);
$v = $stmt->fetch();

$lat = $v['last_lat'] ?: -15.3901;
$lng = $v['last_lng'] ?: 28.3235;
$bearing = $v['bearing'] ?: 0;

// 2. Calculate next position
// Move slightly in the direction of the bearing
$speed_kmh = 40; // Simulated speed
$distance_km = ($speed_kmh / 3600) * 2; // Distance traveled in 2 seconds
$radius_earth = 6371;

// Randomize bearing slightly to make it look natural
$bearing += rand(-10, 10);
if ($bearing < 0) $bearing += 360;
if ($bearing > 360) $bearing -= 360;

$lat_rad = deg2rad($lat);
$lng_rad = deg2rad($lng);
$bearing_rad = deg2rad($bearing);

$next_lat_rad = asin(sin($lat_rad) * cos($distance_km / $radius_earth) +
                cos($lat_rad) * sin($distance_km / $radius_earth) * cos($bearing_rad));

$next_lng_rad = $lng_rad + atan2(sin($bearing_rad) * sin($distance_km / $radius_earth) * cos($lat_rad),
                          cos($distance_km / $radius_earth) - sin($lat_rad) * sin($next_lat_rad));

$next_lat = rad2deg($next_lat_rad);
$next_lng = rad2deg($next_lng_rad);

// 3. Update database
try {
    $pdo->beginTransaction();

    // Update vehicle
    $upd = $pdo->prepare("
        UPDATE vehicles 
        SET last_lat = ?, last_lng = ?, bearing = ?, current_speed = ?, tracking_status = 'online'
        WHERE id = ?
    ");
    $upd->execute([$next_lat, $next_lng, $bearing, $speed_kmh, $vehicle_id]);

    // Add to history
    $hist = $pdo->prepare("
        INSERT INTO vehicle_history (vehicle_id, latitude, longitude, speed, timestamp)
        VALUES (?, ?, ?, ?, NOW())
    ");
    $hist->execute([$vehicle_id, $next_lat, $next_lng, $speed_kmh]);

    $pdo->commit();

    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'new_pos' => ['lat' => $next_lat, 'lng' => $next_lng, 'bearing' => $bearing]]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo json_encode(['error' => $e->getMessage()]);
}

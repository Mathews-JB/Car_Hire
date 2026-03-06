<?php
// Enhanced simulation - moves vehicles along realistic Lusaka routes
include_once 'includes/db.php';

// Define realistic Lusaka route (circular for continuous demo)
$lusaka_route = [
    ['lat' => -15.3950, 'lng' => 28.3100, 'speed' => 35],
    ['lat' => -15.3920, 'lng' => 28.3150, 'speed' => 40],
    ['lat' => -15.3900, 'lng' => 28.3200, 'speed' => 42],
    ['lat' => -15.3901, 'lng' => 28.3235, 'speed' => 42],
    ['lat' => -15.3920, 'lng' => 28.3280, 'speed' => 38],
    ['lat' => -15.3950, 'lng' => 28.3320, 'speed' => 45],
    ['lat' => -15.3980, 'lng' => 28.3380, 'speed' => 50],
    ['lat' => -15.4020, 'lng' => 28.3450, 'speed' => 48],
    ['lat' => -15.4050, 'lng' => 28.3520, 'speed' => 52],
    ['lat' => -15.4100, 'lng' => 28.3600, 'speed' => 55],
    ['lat' => -15.4150, 'lng' => 28.3680, 'speed' => 50],
    ['lat' => -15.4180, 'lng' => 28.3750, 'speed' => 45],
    // Return path
    ['lat' => -15.4150, 'lng' => 28.3700, 'speed' => 40],
    ['lat' => -15.4100, 'lng' => 28.3650, 'speed' => 38],
    ['lat' => -15.4050, 'lng' => 28.3550, 'speed' => 42],
    ['lat' => -15.4000, 'lng' => 28.3450, 'speed' => 45],
    ['lat' => -15.3970, 'lng' => 28.3350, 'speed' => 40],
    ['lat' => -15.3950, 'lng' => 28.3250, 'speed' => 38],
    ['lat' => -15.3940, 'lng' => 28.3180, 'speed' => 35]
];

// Get hired vehicles
$stmt = $pdo->query("SELECT id, last_lat, last_lng FROM vehicles WHERE status = 'hired'");
$vehicles = $stmt->fetchAll();

if (empty($vehicles)) {
    echo "No hired vehicles to simulate.\n";
    exit;
}

echo "Simulating " . count($vehicles) . " vehicle(s)...\n";

foreach ($vehicles as $vehicle) {
    $vehicle_id = $vehicle['id'];
    
    // Find current position in route or start at beginning
    $current_lat = $vehicle['last_lat'];
    $current_lng = $vehicle['last_lng'];
    
    // Find nearest point in route
    $min_dist = PHP_FLOAT_MAX;
    $nearest_index = 0;
    
    foreach ($lusaka_route as $index => $point) {
        $dist = sqrt(pow($point['lat'] - $current_lat, 2) + pow($point['lng'] - $current_lng, 2));
        if ($dist < $min_dist) {
            $min_dist = $dist;
            $nearest_index = $index;
        }
    }
    
    // Move to next point
    $next_index = ($nearest_index + 1) % count($lusaka_route);
    $next_point = $lusaka_route[$next_index];
    
    // Calculate bearing
    $bearing = atan2(
        $next_point['lng'] - $current_lng,
        $next_point['lat'] - $current_lat
    ) * 180 / M_PI;
    
    // Update vehicle position
    $stmt = $pdo->prepare("
        UPDATE vehicles 
        SET last_lat = ?, 
            last_lng = ?, 
            current_speed = ?, 
            bearing = ?,
            tracking_status = 'online'
        WHERE id = ?
    ");
    
    $stmt->execute([
        $next_point['lat'],
        $next_point['lng'],
        $next_point['speed'],
        $bearing,
        $vehicle_id
    ]);
    
    // Log to history
    $stmt = $pdo->prepare("
        INSERT INTO vehicle_history (vehicle_id, latitude, longitude, speed, timestamp)
        VALUES (?, ?, ?, ?, NOW())
    ");
    
    $stmt->execute([
        $vehicle_id,
        $next_point['lat'],
        $next_point['lng'],
        $next_point['speed']
    ]);
    
    echo "✓ Vehicle $vehicle_id: {$next_point['lat']}, {$next_point['lng']} @ {$next_point['speed']} km/h\n";
}

echo "\nSimulation tick complete!\n";
?>

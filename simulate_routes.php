<?php
include_once 'includes/db.php';

/**
 * Uber-Style Route Simulator
 * This script ticks the position of all hired vehicles to simulate real movements.
 */

try {
    $stmt = $pdo->query("SELECT id, last_lat, last_lng, bearing, status FROM vehicles WHERE status = 'hired' OR status = 'available'");
    $vehicles = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($vehicles as $v) {
        $id = $v['id'];
        
        // If it's first run or data is missing, initialize to a known Lusaka point
        if (empty($v['last_lat']) || $v['last_lat'] == 0) {
            $base_lat = -15.4167;
            $base_lng = 28.2833;
            $lat = $base_lat + (mt_rand(-500, 500) / 10000);
            $lng = $base_lng + (mt_rand(-500, 500) / 10000);
            $bearing = mt_rand(0, 359);
        } else {
            $lat = (float)$v['last_lat'];
            $lng = (float)$v['last_lng'];
            $bearing = (int)$v['bearing'];
        }

        // 1. Calculate New Position (Simulate driving)
        // If 'available', it stays mostly put. If 'hired', it moves.
        if ($v['status'] == 'hired') {
            $speed = mt_rand(40, 80); // km/h
            $move_factor = ($speed / 3600) * 0.01; // Scale for 1-2 second ticks
            
            // Randomly adjust bearing slightly to simulate curves
            $bearing += mt_rand(-10, 10);
            $bearing = ($bearing + 360) % 360;

            $rad = deg2rad($bearing);
            $lat += cos($rad) * $move_factor;
            $lng += sin($rad) * $move_factor;
            $tracking_status = 'online';
        } else {
            $speed = 0;
            $tracking_status = 'stopped';
        }

        // 2. Update Vehicle Record
        $update = $pdo->prepare("
            UPDATE vehicles 
            SET last_lat = ?, last_lng = ?, current_speed = ?, bearing = ?, tracking_status = ? 
            WHERE id = ?
        ");
        $update->execute([$lat, $lng, $speed, $bearing, $tracking_status, $id]);

        // 3. Log to History (Throttle logging to avoid table bloat, e.g., every 5th tick)
        // For simulation, we'll just log every tick for now to show the path immediately.
        $log = $pdo->prepare("
            INSERT INTO vehicle_history (vehicle_id, latitude, longitude, speed, bearing) 
            VALUES (?, ?, ?, ?, ?)
        ");
        $log->execute([$id, $lat, $lng, $speed, $bearing]);
    }

    echo "Simulation Ticked: " . count($vehicles) . " vehicles updated.\n";

} catch (PDOException $e) {
    echo "Simulation Error: " . $e->getMessage();
}
?>

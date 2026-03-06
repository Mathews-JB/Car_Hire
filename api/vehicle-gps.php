<?php
header('Content-Type: application/json');
include_once '../includes/db.php';

$booking_id = $_GET['booking_id'] ?? null;

if (!$booking_id) {
    echo json_encode(['error' => 'Missing booking ID']);
    exit;
}

try {
    // 1. Fetch vehicle associated with booking and its latest telemetry
    $stmt = $pdo->prepare("
        SELECT v.id, v.make, v.model, v.last_lat as lat, v.last_lng as lng, 
               v.current_speed as speed, v.bearing, v.tracking_status as status
        FROM bookings b
        JOIN vehicles v ON b.vehicle_id = v.id
        WHERE b.id = ?
    ");
    $stmt->execute([$booking_id]);
    $vehicle = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$vehicle) {
        echo json_encode(['error' => 'Vehicle not found or booking inactive']);
        exit;
    }

    // 2. Fetch recent path history (last 50 points)
    $hist_stmt = $pdo->prepare("
        SELECT latitude as lat, longitude as lng 
        FROM vehicle_history 
        WHERE vehicle_id = ? 
        ORDER BY timestamp DESC 
        LIMIT 50
    ");
    $hist_stmt->execute([$vehicle['id']]);
    $history = $hist_stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'data' => [
            'vehicle' => $vehicle,
            'history' => array_reverse($history) // Return in chronological order for polyline
        ]
    ]);

} catch (PDOException $e) {
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>

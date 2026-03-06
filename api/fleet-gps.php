<?php
header('Content-Type: application/json');
include_once '../includes/db.php';

// Fetch all vehicles that should be tracked (hired, available, etc.)
try {
    $stmt = $pdo->query("
        SELECT id, make, model, plate_number, last_lat as lat, last_lng as lng, 
               current_speed as speed, bearing, tracking_status as status, status as fleet_status
        FROM vehicles
        WHERE last_lat IS NOT NULL AND status IN ('hired', 'available', 'maintenance', 'booked')
    ");
    $vehicles = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'data' => $vehicles
    ]);

} catch (PDOException $e) {
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>

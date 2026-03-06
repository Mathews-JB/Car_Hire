<?php
include_once 'includes/db.php';
include_once 'includes/functions.php';

/**
 * auto-refresh-fleet.php
 * This script updates the status of vehicles whose rental period has ended.
 * It should be run periodically (e.g., every hour via Cron).
 */

echo "Starting Auto-Availability Refresh...\n";

try {
    $now = date('Y-m-d H:i:s');
    
    // 1. Find bookings that ended before 'now' and were 'confirmed'
    $stmt = $pdo->prepare("SELECT b.*, v.id as vid FROM bookings b 
                           JOIN vehicles v ON b.vehicle_id = v.id 
                           WHERE b.dropoff_date < ? AND b.status = 'confirmed'");
    $stmt->execute([$now]);
    $ended_bookings = $stmt->fetchAll();

    $count = 0;
    foreach ($ended_bookings as $b) {
        $pdo->beginTransaction();
        
        // Update vehicle status back to 'available'
        $pdo->prepare("UPDATE vehicles SET status = 'available' WHERE id = ?")->execute([$b['vid']]);
        
        // Mark booking as 'completed'
        $pdo->prepare("UPDATE bookings SET status = 'completed' WHERE id = ?")->execute([$b['id']]);
        
        // Create a system notification for the user
        $pdo->prepare("INSERT INTO notifications (title, message, type) VALUES (?, ?, ?)")
            ->execute([
                "Rental Completed", 
                "Your rental for the vehicle (Ref: #{$b['id']}) has ended. Thank you for choosing Car Hire!",
                "system"
            ]);

        $pdo->commit();
        $count++;
    }

    // 2. Clear 'booked' status for vehicles whose pickup time passed without confirmation
    // (Optional logic: if status is 'booked' but pickup_date < now - 2 hours, release it)
    $grace_period = date('Y-m-d H:i:s', strtotime('-2 hours'));
    $stmt = $pdo->prepare("SELECT b.*, v.id as vid FROM bookings b 
                           JOIN vehicles v ON b.vehicle_id = v.id 
                           WHERE b.pickup_date < ? AND b.status = 'pending'");
    $stmt->execute([$grace_period]);
    $expired_pending = $stmt->fetchAll();

    foreach ($expired_pending as $ep) {
        $pdo->beginTransaction();
        $pdo->prepare("UPDATE vehicles SET status = 'available' WHERE id = ?")->execute([$ep['vid']]);
        $pdo->prepare("UPDATE bookings SET status = 'cancelled' WHERE id = ?")->execute([$ep['id']]);
        $pdo->commit();
    }

    echo "Successfully processed $count completed rentals.\n";
    echo "Auto-Refresh Complete.\n";

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo "Error during refresh: " . $e->getMessage() . "\n";
}
?>

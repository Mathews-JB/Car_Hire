<?php
include_once 'includes/db.php';
try {
    $pdo->exec("ALTER TABLE support_messages ADD COLUMN IF NOT EXISTS booking_id INT DEFAULT NULL");
    echo "Support Messages linked to Bookings.";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}

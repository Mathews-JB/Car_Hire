<?php
include 'includes/db.php';
$pdo->exec("UPDATE bookings SET status = 'confirmed'");
echo "Confirmed all bookings.";
?>

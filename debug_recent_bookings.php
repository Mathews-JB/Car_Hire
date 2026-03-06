<?php
require 'includes/db.php';
$stmt = $pdo->query("SELECT b.id, v.make, v.model, b.status, b.created_at 
                     FROM bookings b 
                     JOIN vehicles v ON b.vehicle_id = v.id 
                     ORDER BY b.id DESC LIMIT 20");
echo "<pre>";
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
echo "</pre>";

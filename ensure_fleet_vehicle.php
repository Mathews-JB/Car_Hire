<?php
include_once 'includes/db.php';
try {
    $stmt = $pdo->prepare("SELECT id FROM vehicles WHERE model = 'Multi-Car Fleet'");
    $stmt->execute();
    if (!$stmt->fetch()) {
        $pdo->exec("INSERT INTO vehicles (make, model, year, capacity, price_per_day, status, image_url, features) 
                    VALUES ('Zambia', 'Multi-Car Fleet', 2026, 50, 0.00, 'available', 'public/images/cars/fleet-logo.png', 'Special booking for events and multi-car requests.')");
        echo "Event Fleet Vehicle Created.";
    } else {
        echo "Event Fleet Vehicle Exists.";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}

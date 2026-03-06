<?php
include_once 'includes/db.php';

// Lusaka Coordinates Base: -15.4167, 28.2833
$locations = [
    ['lat' => -15.405, 'lng' => 28.275], // Rhodes Park
    ['lat' => -15.420, 'lng' => 28.290], // Ridgeway
    ['lat' => -15.395, 'lng' => 28.310], // Kabulonga
    ['lat' => -15.440, 'lng' => 28.300], // Woodlands
    ['lat' => -15.425, 'lng' => 28.265], // Lusaka Central
    ['lat' => -15.380, 'lng' => 28.330], // Leopard's Hill
    ['lat' => -15.410, 'lng' => 28.240], // Garden City area
    ['lat' => -15.360, 'lng' => 28.280], // Roma
    ['lat' => -15.455, 'lng' => 28.275], // Libala
    ['lat' => -15.400, 'lng' => 28.350], // Salama Park
];

try {
    $stmt = $pdo->query("SELECT id FROM vehicles");
    $vehicles = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $i = 0;
    foreach ($vehicles as $vehicle) {
        $loc = $locations[$i % count($locations)];
        // Add tiny random jitter to make them unique
        $lat = $loc['lat'] + (mt_rand(-100, 100) / 100000);
        $lng = $loc['lng'] + (mt_rand(-100, 100) / 100000);
        
        $update = $pdo->prepare("UPDATE vehicles SET latitude = ?, longitude = ? WHERE id = ?");
        $update->execute([$lat, $lng, $vehicle['id']]);
        $i++;
    }
    echo "Updated " . count($vehicles) . " vehicles with actual Lusaka coordinates.\n";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
